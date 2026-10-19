<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\FinalGrade;
use App\Models\GradeConfiguration;
use App\Models\GradeItem;
use App\Models\GradeVerification;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\StudentGrade;
use App\Models\Subject;
use App\Services\GradeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    public function config(Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $config = $currentTerm->gradeConfigurationFor($subject->id);
        $locked = $this->isLocked($subject, $currentTerm);
        return view('teacher.grades.config', compact('section', 'subject', 'config', 'locked'));
    }

    public function storeConfig(Request $request, Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertNotLocked($subject, $currentTerm);

        $request->validate([
            'components'          => 'required|array|min:1',
            'components.*.key'    => 'required|string|max:50',
            'components.*.label'  => 'required|string|max:100',
            'components.*.weight' => 'required|numeric|min:0|max:100',
            'components.*.period' => 'required|in:midterm,final',
            'computation_method'  => 'required|in:standard,formula_a,formula_b',
        ]);

        $components = array_values($request->input('components'));

        $midtermTotal = array_sum(array_column(
            array_filter($components, fn($c) => $c['period'] === 'midterm'), 'weight'
        ));
        $finalTotal = array_sum(array_column(
            array_filter($components, fn($c) => $c['period'] === 'final'), 'weight'
        ));

        if (abs($midtermTotal - 100) > 0.01) {
            return back()
                ->withErrors(['weights' => "Midterm weights must sum to 100%. Current: {$midtermTotal}%"])
                ->withInput();
        }
        if (abs($finalTotal - 100) > 0.01) {
            return back()
                ->withErrors(['weights' => "Final weights must sum to 100%. Current: {$finalTotal}%"])
                ->withInput();
        }

        GradeConfiguration::updateOrCreate(
            ['section_term_id' => $currentTerm->id, 'subject_id' => $subject->id],
            ['section_id' => $section->id, 'config_json' => $components, 'computation_method' => $request->input('computation_method'), 'status' => 'active']
        );

        return redirect(\App\Services\TeacherNav::route('teacher.classes.record', [$section, $subject]))
            ->with('success', 'Grade configuration saved.');
    }

    public function items(Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->requireConfig($section, $currentTerm, $subject);

        $allItems = $currentTerm->gradeItemsFor($subject->id)
            ->orderBy('period')
            ->orderBy('component_type')
            ->orderBy('created_at')
            ->get();

        $gradeItems = $allItems->groupBy('period');
        $config     = $currentTerm->gradeConfigurationFor($subject->id);
        $components = collect($config->getComponents())->keyBy('key');

        return view('teacher.grades.items', compact('section', 'subject', 'gradeItems', 'config', 'components'));
    }

    public function storeItem(Request $request, Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertNotLocked($subject, $currentTerm);
        $this->requireConfig($section, $currentTerm, $subject);

        $config    = $currentTerm->gradeConfigurationFor($subject->id);
        $validKeys = collect($config->getComponents())->pluck('key')->toArray();

        $data = $request->validate([
            'component_type' => ['required', 'string', 'max:50', 'in:' . implode(',', $validKeys)],
            'name'           => 'nullable|string|max:100',
            'max_score'      => 'required|numeric|min:1',
            'date_given'     => 'nullable|date',
            'description'    => 'nullable|string|max:500',
        ]);

        $weight = $config->getWeight($data['component_type']);
        if ($weight === 0.0) {
            return back()->withErrors(['component_type' => 'This component has 0% weight. Update grade configuration to enable it.'])->withInput();
        }

        $component      = collect($config->getComponents())->firstWhere('key', $data['component_type']);
        $data['period'] = $component['period'];

        $existing = $currentTerm->gradeItemsFor($subject->id)->where('component_type', $component['key'])->get();
        $autoName = GradeItem::autoName($existing, $component['key'], $component['label'] ?? '', $component['period']);
        if ($autoName === null) {
            return back()->with('warning', 'This period already has an exam. Enter its scores in the class record.');
        }
        $data['name'] = filled($data['name'] ?? null) ? $data['name'] : $autoName;

        GradeItem::create($data + [
            'section_id'      => $section->id,
            'section_term_id' => $currentTerm->id,
            'subject_id'      => $subject->id,
            'created_by'      => auth()->id(),
        ]);

        return back()->with('success', 'Grade item added.');
    }

    public function destroyItem(Section $section, Subject $subject, GradeItem $gradeItem)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertItemOfTerm($gradeItem, $currentTerm, $subject);
        abort_if($gradeItem->is_locked, 403, 'Cannot delete a locked grade item.');

        $gradeItem->delete();

        return back()->with('success', 'Grade item removed.');
    }

    public function scores(Section $section, Subject $subject, GradeItem $gradeItem)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertItemOfTerm($gradeItem, $currentTerm, $subject);

        $enrollments = $currentTerm->rosterFor($subject->id)
            ->with([
                'student',
                'studentGrades' => fn($q) => $q->where('grade_item_id', $gradeItem->id),
            ])
            ->get();

        $enrollments = \App\Models\Enrollment::sortRoster($enrollments);
        $gradeMethod = $currentTerm->gradeConfigurationFor($subject->id)?->computation_method ?? 'standard';
        return view('teacher.grades.scores', compact('section', 'subject', 'gradeItem', 'enrollments', 'gradeMethod'));
    }

    public function storeScores(Request $request, Section $section, Subject $subject, GradeItem $gradeItem)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertItemOfTerm($gradeItem, $currentTerm, $subject);
        abort_if($gradeItem->is_locked, 403, 'Grade item is locked.');

        $request->validate([
            'scores'                 => 'required|array',
            'scores.*.enrollment_id' => 'required|exists:enrollments,id',
            'scores.*.score'         => "required|numeric|min:0|max:{$gradeItem->max_score}",
        ]);

        $rosterIds = $currentTerm->rosterFor($subject->id)->pluck('id');
        abort_if(collect($request->scores)->pluck('enrollment_id')->diff($rosterIds)->isNotEmpty(), 403);

        DB::transaction(function () use ($request, $gradeItem) {
            foreach ($request->scores as $entry) {
                $this->saveScore((int) $entry['enrollment_id'], $gradeItem, $entry['score']);
            }
        });

        return redirect(\App\Services\TeacherNav::route('teacher.grades.items', [$section, $subject]))->with('success', 'Scores saved.');
    }

    public function storeScore(Request $request, Section $section, Subject $subject, GradeItem $gradeItem)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertNotLocked($subject, $currentTerm);
        $this->assertItemOfTerm($gradeItem, $currentTerm, $subject);
        abort_if($gradeItem->is_locked, 403, 'Grade item is locked.');

        $data = $request->validate([
            'enrollment_id' => 'required|integer',
            'score'         => "nullable|numeric|min:0|max:{$gradeItem->max_score}",
        ]);

        abort_unless($currentTerm->rosterFor($subject->id)->whereKey($data['enrollment_id'])->exists(), 403);

        $this->saveScore((int) $data['enrollment_id'], $gradeItem, $data['score'] ?? null);

        return response()->json(['ok' => true]);
    }

    private function saveScore(int $enrollmentId, GradeItem $gradeItem, $score): void
    {
        $existing = StudentGrade::withTrashed()
            ->where('enrollment_id', $enrollmentId)
            ->where('grade_item_id', $gradeItem->id)
            ->first();

        if ($score === null) {
            if ($existing && !$existing->trashed()) {
                $existing->changeLogs()->create([
                    'old_score'  => $existing->score,
                    'new_score'  => null,
                    'changed_by' => auth()->id(),
                ]);
                $existing->delete();
            }
            return;
        }

        if (!$existing) {
            StudentGrade::create([
                'enrollment_id' => $enrollmentId,
                'grade_item_id' => $gradeItem->id,
                'score'         => $score,
                'recorded_by'   => auth()->id(),
            ]);
            return;
        }

        if ($existing->trashed()) {
            $existing->restore();
            $existing->update(['score' => $score, 'recorded_by' => auth()->id()]);
            return;
        }

        if ((float) $existing->score !== (float) $score) {
            $existing->changeLogs()->create([
                'old_score'  => $existing->score,
                'new_score'  => $score,
                'changed_by' => auth()->id(),
            ]);
            $existing->update(['score' => $score]);
        }
    }

    public function finalGrades(Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->requireConfig($section, $currentTerm, $subject);

        $config = $currentTerm->gradeConfigurationFor($subject->id);

        $enrollments = collect();
        if ($currentTerm) {
            $currentTerm->loadRoster($subject->id, [
                'enrollments.student',
                'enrollments.finalGrade' => fn($q) => $q->where('subject_id', $subject->id),
                'enrollments.studentGrades' => fn($q) => $q
                    ->whereHas('gradeItem', fn($q2) => $q2->where('subject_id', $subject->id))
                    ->with('gradeItem'),
                'enrollments.attendanceRecords' => fn($q) => $q->where('subject_id', $subject->id),
            ]);
            $enrollments = $currentTerm->enrollments;
        }

        $cutoffDate  = AcademicPeriod::getActive()?->midterm_cutoff_date;
        $liveGrades  = [];

        foreach ($enrollments as $enrollment) {
            $liveGrades[$enrollment->id] = GradeCalculator::result($enrollment, $config, $cutoffDate);
        }

        $incReview = [];
        foreach ($enrollments as $enrollment) {
            $missing = GradeCalculator::incompletePeriods($enrollment, $config, $cutoffDate);
            if ($missing) {
                $incReview[$enrollment->id] = $missing;
            }
        }

        $enrollments = \App\Models\Enrollment::sortRoster($enrollments);
        return view('teacher.grades.final', compact('section', 'subject', 'config', 'enrollments', 'liveGrades', 'currentTerm', 'incReview'));
    }

    public function submitForVerification(Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertNotLocked($subject, $currentTerm);

        $config = $currentTerm->gradeConfigurationFor($subject->id);
        if (!$config) {
            return back()->with('error', 'No grade configuration found.');
        }

        $currentTerm->loadRoster($subject->id, [
            'enrollments.studentGrades' => fn($q) => $q
                ->whereHas('gradeItem', fn($q2) => $q2->where('subject_id', $subject->id))
                ->with('gradeItem'),
            'enrollments.attendanceRecords' => fn($q) => $q->where('subject_id', $subject->id),
        ]);

        $cutoffDate = AcademicPeriod::getActive()?->midterm_cutoff_date;

        $results = [];

        foreach ($currentTerm->enrollments as $enrollment) {
            $results[$enrollment->id] = GradeCalculator::result($enrollment, $config, $cutoffDate);
        }

        DB::transaction(function () use ($results, $subject) {
            foreach ($results as $enrollmentId => $r) {
                FinalGrade::updateOrCreate(
                    ['enrollment_id' => $enrollmentId, 'subject_id' => $subject->id],
                    [
                        'midterm_percentage' => $r['midterm_percentage'] ?? 0,
                        'midterm_numerical'  => $r['midterm_numerical'],
                        'final_percentage'   => $r['final_percentage'] ?? 0,
                        'final_numerical'    => $r['final_numerical'],
                        'average_numerical'  => $r['average_numerical'],
                        'final_grade'        => $r['final_grade'] ?? 0,
                        'numerical_grade'    => $r['numerical_grade'],
                        'letter_grade'       => $r['letter_grade'],
                        'remarks'            => $r['remarks'],
                        'computed_by'        => auth()->id(),
                        'is_locked'          => true,
                        'locked_at'          => now(),
                    ]
                );
            }
        });

        $currentTerm->gradeItemsFor($subject->id)->update(['is_locked' => true]);

        GradeVerification::updateOrCreate(
            ['section_term_id' => $currentTerm->id, 'subject_id' => $subject->id],
            [
                'status'           => 'pending',
                'verified_by'      => null,
                'verified_at'      => null,
                'rejection_reason' => null,
            ]
        );

        return redirect(\App\Services\TeacherNav::route('teacher.grades.final', [$section, $subject]))
            ->with('success', 'Final grades submitted for verification.');
    }

    /**
     * Only the teacher assigned to THIS subject in this section's active term
     * may access its grade screens. Being adviser alone is not sufficient —
     * advisory is roster-only per current design.
     */
    private function authorizeSectionSubject(Section $section, Subject $subject): SectionTerm
    {
        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(!$currentTerm, 403, 'No active term for this section.');

        $isSubjectTeacher = $currentTerm->subjects()
            ->wherePivot('teacher_id', auth()->id())
            ->where('subjects.id', $subject->id)
            ->exists();

        abort_if(!$isSubjectTeacher, 403, 'You are not assigned to teach this subject in this section.');

        return $currentTerm;
    }

    /**
     * Blocks writes once a subject's grades have been submitted (pending) or
     * verified. Rejected submissions are excluded so the teacher can correct
     * and resubmit.
     */
    private function assertNotLocked(Subject $subject, SectionTerm $currentTerm): void
    {
        abort_if($this->isLocked($subject, $currentTerm), 403, 'Grades for this subject are locked pending or after verification.');
    }

    private function isLocked(Subject $subject, SectionTerm $currentTerm): bool
    {
        return $currentTerm->verifications()
            ->where('subject_id', $subject->id)
            ->whereIn('status', ['pending', 'verified'])
            ->exists();
    }

    /** A grade item can be used only in the term and subject it was created for. */
    private function assertItemOfTerm(GradeItem $gradeItem, SectionTerm $currentTerm, Subject $subject): void
    {
        abort_if((int) $gradeItem->section_term_id !== (int) $currentTerm->id || (int) $gradeItem->subject_id !== (int) $subject->id, 403);
    }

    private function requireConfig(Section $section, SectionTerm $currentTerm, Subject $subject): void
    {
        if (!$currentTerm->gradeConfigurationFor($subject->id)) {
            redirect(\App\Services\TeacherNav::route('teacher.grades.config', [$section, $subject]))
                ->with('warning', 'Set up grade configuration first.')
                ->send();
            exit;
        }
    }
}
