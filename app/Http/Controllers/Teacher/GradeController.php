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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    public function config(Section $section, Subject $subject)
    {
        $this->authorizeSectionSubject($section, $subject);
        $config = $section->gradeConfigurationFor($subject->id);
        return view('teacher.grades.config', compact('section', 'subject', 'config'));
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
            ['section_id' => $section->id, 'subject_id' => $subject->id],
            ['config_json' => $components, 'status' => 'active']
        );

        return redirect()->route('teacher.classes.record', [$section, $subject])
            ->with('success', 'Grade configuration saved.');
    }

    public function items(Section $section, Subject $subject)
    {
        $this->authorizeSectionSubject($section, $subject);
        $this->requireConfig($section, $subject);

        $allItems = $section->gradeItemsFor($subject->id)
            ->orderBy('period')
            ->orderBy('component_type')
            ->orderBy('created_at')
            ->get();

        $gradeItems = $allItems->groupBy('period');
        $config     = $section->gradeConfigurationFor($subject->id);
        $components = collect($config->getComponents())->keyBy('key');

        return view('teacher.grades.items', compact('section', 'subject', 'gradeItems', 'config', 'components'));
    }

    public function storeItem(Request $request, Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertNotLocked($subject, $currentTerm);
        $this->requireConfig($section, $subject);

        $config    = $section->gradeConfigurationFor($subject->id);
        $validKeys = collect($config->getComponents())->pluck('key')->toArray();

        $data = $request->validate([
            'component_type' => ['required', 'string', 'max:50', 'in:' . implode(',', $validKeys)],
            'period'         => 'required|in:midterm,final',
            'name'           => 'required|string|max:100',
            'max_score'      => 'required|numeric|min:1',
            'date_given'     => 'nullable|date',
            'description'    => 'nullable|string|max:500',
        ]);

        $weight = $config->getWeight($data['component_type']);
        if ($weight === 0.0) {
            return back()->withErrors(['component_type' => 'This component has 0% weight. Update grade configuration to enable it.'])->withInput();
        }

        $section->gradeItems()->create($data + [
            'subject_id' => $subject->id,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Grade item added.');
    }

    public function destroyItem(Section $section, Subject $subject, GradeItem $gradeItem)
    {
        $this->authorizeSectionSubject($section, $subject);
        abort_if($gradeItem->section_id !== $section->id || $gradeItem->subject_id !== $subject->id, 403);
        abort_if($gradeItem->is_locked, 403, 'Cannot delete a locked grade item.');

        $gradeItem->delete();

        return back()->with('success', 'Grade item removed.');
    }

    public function scores(Section $section, Subject $subject, GradeItem $gradeItem)
    {
        $this->authorizeSectionSubject($section, $subject);
        abort_if($gradeItem->section_id !== $section->id || $gradeItem->subject_id !== $subject->id, 403);

        $currentTerm = $section->terms()->where('status', 'active')->first();
        $enrollments = $currentTerm
            ? $currentTerm->enrollments()
                ->with([
                    'student',
                    'studentGrades' => fn($q) => $q->where('grade_item_id', $gradeItem->id),
                ])
                ->get()
            : collect();

        return view('teacher.grades.scores', compact('section', 'subject', 'gradeItem', 'enrollments'));
    }

    public function storeScores(Request $request, Section $section, Subject $subject, GradeItem $gradeItem)
    {
        $this->authorizeSectionSubject($section, $subject);
        abort_if($gradeItem->section_id !== $section->id || $gradeItem->subject_id !== $subject->id, 403);
        abort_if($gradeItem->is_locked, 403, 'Grade item is locked.');

        $request->validate([
            'scores'                 => 'required|array',
            'scores.*.enrollment_id' => 'required|exists:enrollments,id',
            'scores.*.score'         => "required|numeric|min:0|max:{$gradeItem->max_score}",
        ]);

        DB::transaction(function () use ($request, $gradeItem) {
            foreach ($request->scores as $entry) {
                $existing = StudentGrade::where('enrollment_id', $entry['enrollment_id'])
                    ->where('grade_item_id', $gradeItem->id)
                    ->first();

                if ($existing) {
                    if ((float) $existing->score !== (float) $entry['score']) {
                        $existing->changeLogs()->create([
                            'old_score'  => $existing->score,
                            'new_score'  => $entry['score'],
                            'changed_by' => auth()->id(),
                        ]);
                        $existing->update(['score' => $entry['score']]);
                    }
                } else {
                    StudentGrade::create([
                        'enrollment_id' => $entry['enrollment_id'],
                        'grade_item_id' => $gradeItem->id,
                        'score'         => $entry['score'],
                        'recorded_by'   => auth()->id(),
                    ]);
                }
            }
        });

        return redirect()->route('teacher.grades.items', [$section, $subject])->with('success', 'Scores saved.');
    }

    public function finalGrades(Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->requireConfig($section, $subject);

        $config = $section->gradeConfigurationFor($subject->id);

        $enrollments = collect();
        if ($currentTerm) {
            $currentTerm->load([
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
            $midScores = $this->calculatePeriodScores($enrollment, $config, 'midterm', $cutoffDate);
            $finScores = $this->calculatePeriodScores($enrollment, $config, 'final', $cutoffDate);
            $midPct    = round(array_sum($midScores), 2);
            $finPct    = round(array_sum($finScores), 2);
            $midNum    = FinalGrade::convertToNumericalGrade($midPct);
            $finNum    = FinalGrade::convertToNumericalGrade($finPct);
            $avgNum    = round(($midNum + $finNum) / 2 * 4) / 4;

            $liveGrades[$enrollment->id] = [
                'midterm_percentage' => $midPct,
                'midterm_numerical'  => $midNum,
                'final_percentage'   => $finPct,
                'final_numerical'    => $finNum,
                'average_numerical'  => $avgNum,
                'final_grade'        => $midPct,
                'numerical_grade'    => $avgNum,
                'letter_grade'       => number_format($avgNum, 1),
                'remarks'            => $avgNum <= 3.00 ? 'passed' : 'failed',
            ];
        }

        return view('teacher.grades.final', compact('section', 'subject', 'enrollments', 'liveGrades', 'currentTerm'));
    }



    public function computeGrades(Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);

        $config = $section->gradeConfigurationFor($subject->id);
        if (!$config) {
            return back()->with('error', 'No grade configuration found.');
        }

        $enrollments = collect();
        if ($currentTerm) {
            $currentTerm->load([
                'enrollments.studentGrades' => fn($q) => $q
                    ->whereHas('gradeItem', fn($q2) => $q2->where('subject_id', $subject->id))
                    ->with('gradeItem'),
                'enrollments.attendanceRecords' => fn($q) => $q->where('subject_id', $subject->id),
            ]);
            $enrollments = $currentTerm->enrollments;
        }

        $cutoffDate = AcademicPeriod::getActive()?->midterm_cutoff_date;
        $errors = [];

        foreach ($enrollments as $enrollment) {
            try {
                $midScores  = $this->calculatePeriodScores($enrollment, $config, 'midterm', $cutoffDate);
                $finScores  = $this->calculatePeriodScores($enrollment, $config, 'final', $cutoffDate);
                $midPct     = round(array_sum($midScores), 2);
                $finPct     = round(array_sum($finScores), 2);
                $midNum     = FinalGrade::convertToNumericalGrade($midPct);
                $finNum     = FinalGrade::convertToNumericalGrade($finPct);
                $avgNum     = round(($midNum + $finNum) / 2 * 4) / 4;
                $allScores  = $this->calculateComponentScores($enrollment, $config, $cutoffDate);
                $finalPct   = round(array_sum($allScores), 2);

                FinalGrade::updateOrCreate(
                    ['enrollment_id' => $enrollment->id, 'subject_id' => $subject->id],
                    [
                        'midterm_percentage' => $midPct,
                        'midterm_numerical'  => $midNum,
                        'final_percentage'   => $finPct,
                        'final_numerical'    => $finNum,
                        'average_numerical'  => $avgNum,
                        'final_grade'        => $finalPct,
                        'numerical_grade'    => $avgNum,
                        'letter_grade'       => number_format($avgNum, 1),
                        'remarks'            => $avgNum <= 3.00 ? 'passed' : 'failed',
                        'computed_by'        => auth()->id(),
                    ]
                );
            } catch (\Exception $e) {
                $errors[] = "Enrollment {$enrollment->id}: " . $e->getMessage();
            }
        }

        if (!empty($errors)) {
            return back()->with('error', 'Some grades failed: ' . implode(' | ', $errors));
        }

        return back()->with('success', 'Final grades computed and saved successfully.');
    }

    public function submitForVerification(Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);
        $this->assertNotLocked($subject, $currentTerm);

        $config = $section->gradeConfigurationFor($subject->id);
        if (!$config) {
            return back()->with('error', 'No grade configuration found.');
        }

        $currentTerm->load([
            'enrollments.studentGrades' => fn($q) => $q
                ->whereHas('gradeItem', fn($q2) => $q2->where('subject_id', $subject->id))
                ->with('gradeItem'),
            'enrollments.attendanceRecords' => fn($q) => $q->where('subject_id', $subject->id),
        ]);

        $cutoffDate = AcademicPeriod::getActive()?->midterm_cutoff_date;

        foreach ($currentTerm->enrollments as $enrollment) {
            $midScores = $this->calculatePeriodScores($enrollment, $config, 'midterm', $cutoffDate);
            $finScores = $this->calculatePeriodScores($enrollment, $config, 'final', $cutoffDate);
            $midPct    = round(array_sum($midScores), 2);
            $finPct    = round(array_sum($finScores), 2);
            $midNum    = FinalGrade::convertToNumericalGrade($midPct);
            $finNum    = FinalGrade::convertToNumericalGrade($finPct);
            $avgNum    = round(($midNum + $finNum) / 2 * 4) / 4;
            $allScores = $this->calculateComponentScores($enrollment, $config, $cutoffDate);
            $finalPct  = round(array_sum($allScores), 2);

            FinalGrade::updateOrCreate(
                ['enrollment_id' => $enrollment->id, 'subject_id' => $subject->id],
                [
                    'midterm_percentage' => $midPct,
                    'midterm_numerical'  => $midNum,
                    'final_percentage'   => $finPct,
                    'final_numerical'    => $finNum,
                    'average_numerical'  => $avgNum,
                    'final_grade'        => $finalPct,
                    'numerical_grade'    => $avgNum,
                    'letter_grade'       => number_format($avgNum, 1),
                    'remarks'            => $avgNum <= 3.00 ? 'passed' : 'failed',
                    'computed_by'        => auth()->id(),
                    'is_locked'          => true,
                    'locked_at'          => now(),
                ]
            );
        }

        $section->gradeItemsFor($subject->id)->update(['is_locked' => true]);

        GradeVerification::updateOrCreate(
            ['section_term_id' => $currentTerm->id, 'subject_id' => $subject->id],
            [
                'status'           => 'pending',
                'verified_by'      => null,
                'verified_at'      => null,
                'rejection_reason' => null,
            ]
        );

        return redirect()->route('teacher.grades.final', [$section, $subject])
            ->with('success', 'Final grades submitted for verification.');
    }

    private function isAttendanceComponent(string $key): bool
    {
        return in_array($key, ['attendance', 'attendance_f'], true);
    }

    private function calculateAttendanceRate($enrollment, string $period, $cutoffDate): ?float
    {
        if (!$cutoffDate) {
            return null;
        }

        $records = $enrollment->attendanceRecords->filter(
            fn($r) => $period === 'midterm' ? $r->date->lte($cutoffDate) : $r->date->gt($cutoffDate)
        );

        if ($records->isEmpty()) {
            return null;
        }

        $creditSum = $records->sum(function ($r) {
            return match ($r->status) {
                'present', 'excused' => 1.0,
                'late'                => 0.5,
                default               => 0.0,
            };
        });

        return round(($creditSum / $records->count()) * 100, 2);
    }

    private function calculateComponentScores($enrollment, $config, $cutoffDate = null): array
    {
        $components   = $config->getComponents();
        $scores       = [];
        $activeWeight = 0;

        foreach ($components as $comp) {
            $key    = $comp['key'];
            $weight = (float) $comp['weight'];
            $scores[$key] = 0;
            if ($weight === 0.0) continue;

            if ($this->isAttendanceComponent($key)) {
                $period = $comp['period'] ?? 'midterm';
                $rate   = $this->calculateAttendanceRate($enrollment, $period, $cutoffDate);
                if ($rate !== null) {
                    $scores[$key]  = round(($rate / 100) * $weight, 2);
                    $activeWeight += $weight;
                }
                continue;
            }

            $items = $enrollment->studentGrades->filter(
                fn($g) => $g->gradeItem !== null && $g->gradeItem->component_type === $key
            );

            if ($items->isNotEmpty()) {
                $earned   = $items->sum(fn($g) => (float) $g->score);
                $possible = $items->sum(fn($g) => (float) $g->gradeItem->max_score);
                $scores[$key]  = $possible > 0 ? round(($earned / $possible) * $weight, 2) : 0;
                $activeWeight += $weight;
            }
        }

        if ($activeWeight > 0 && $activeWeight < 100) {
            $factor = 100 / $activeWeight;
            foreach ($scores as $k => $v) {
                $scores[$k] = round($v * $factor, 2);
            }
        }

        return $scores;
    }

    private function calculatePeriodScores($enrollment, $config, string $period, $cutoffDate = null): array
    {
        $components   = $config->getComponentsByPeriod($period);
        $scores       = [];
        $activeWeight = 0;

        foreach ($components as $comp) {
            $key    = $comp['key'];
            $weight = (float) $comp['weight'];
            $scores[$key] = 0;
            if ($weight === 0.0) continue;

            if ($this->isAttendanceComponent($key)) {
                $rate = $this->calculateAttendanceRate($enrollment, $period, $cutoffDate);
                if ($rate !== null) {
                    $scores[$key]  = round(($rate / 100) * $weight, 2);
                    $activeWeight += $weight;
                }
                continue;
            }

            $items = $enrollment->studentGrades->filter(
                fn($g) => $g->gradeItem !== null
                    && $g->gradeItem->component_type === $key
                    && $g->gradeItem->period === $period
            );

            if ($items->isNotEmpty()) {
                $earned   = $items->sum(fn($g) => (float) $g->score);
                $possible = $items->sum(fn($g) => (float) $g->gradeItem->max_score);
                $scores[$key]  = $possible > 0 ? round(($earned / $possible) * $weight, 2) : 0;
                $activeWeight += $weight;
            }
        }

        if ($activeWeight > 0 && $activeWeight < 100) {
            $factor = 100 / $activeWeight;
            foreach ($scores as $k => $v) {
                $scores[$k] = round($v * $factor, 2);
            }
        }

        return $scores;
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
        $locked = $currentTerm->verifications()
            ->where('subject_id', $subject->id)
            ->whereIn('status', ['pending', 'verified'])
            ->exists();

        abort_if($locked, 403, 'Grades for this subject are locked pending or after verification.');
    }

    private function requireConfig(Section $section, Subject $subject): void
    {
        if (!$section->gradeConfigurationFor($subject->id)) {
            redirect()->route('teacher.grades.config', [$section, $subject])
                ->with('warning', 'Set up grade configuration first.')
                ->send();
            exit;
        }
    }
}
