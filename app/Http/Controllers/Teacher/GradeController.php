<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\FinalGrade;
use App\Models\GradeConfiguration;
use App\Models\GradeItem;
use App\Models\Section;
use App\Models\StudentGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    public function config(Section $section)
    {
        $this->authorizeSection($section);
        $config = $section->gradeConfiguration;
        return view('teacher.grades.config', compact('section', 'config'));
    }

    public function storeConfig(Request $request, Section $section)
    {
        $this->authorizeSection($section);

        $data = $request->validate([
            'quiz_weight'       => 'required|numeric|min:0|max:100',
            'exam_weight'       => 'required|numeric|min:0|max:100',
            'project_weight'    => 'required|numeric|min:0|max:100',
            'assessment_weight' => 'required|numeric|min:0|max:100',
            'attendance_weight' => 'required|numeric|min:0|max:100',
        ]);

        $total = array_sum($data);

        if (abs($total - 100) > 0.01) {
            return back()
                ->withErrors(['weights' => "Weights must sum to 100%. Current total: {$total}%"])
                ->withInput();
        }

        GradeConfiguration::updateOrCreate(
            ['section_id' => $section->id],
            $data + ['status' => 'active']
        );

        return redirect()->route('teacher.classes.show', $section)
            ->with('success', 'Grade configuration saved.');
    }

    public function items(Section $section)
    {
        $this->authorizeSection($section);
        $this->requireConfig($section);

        $gradeItems = $section->gradeItems()
            ->orderBy('component_type')
            ->orderBy('created_at')
            ->get()
            ->groupBy('component_type');

        $config = $section->gradeConfiguration;

        return view('teacher.grades.items', compact('section', 'gradeItems', 'config'));
    }

    public function storeItem(Request $request, Section $section)
    {
        $this->authorizeSection($section);
        $this->requireConfig($section);

        $data = $request->validate([
            'component_type' => 'required|in:quiz,exam,project,assessment',
            'name'           => 'required|string|max:100',
            'max_score'      => 'required|numeric|min:1',
            'date_given'     => 'nullable|date',
            'description'    => 'nullable|string|max:500',
        ]);

        $section->gradeItems()->create($data + ['created_by' => auth()->id()]);

        return back()->with('success', 'Grade item added.');
    }

    public function destroyItem(Section $section, GradeItem $gradeItem)
    {
        $this->authorizeSection($section);
        abort_if($gradeItem->section_id !== $section->id, 403);
        abort_if($gradeItem->is_locked, 403, 'Cannot delete a locked grade item.');

        $gradeItem->delete();

        return back()->with('success', 'Grade item removed.');
    }

    public function scores(Section $section, GradeItem $gradeItem)
    {
        $this->authorizeSection($section);
        abort_if($gradeItem->section_id !== $section->id, 403);

        $currentTerm = $section->terms()->where('status', 'active')->first();
        $enrollments = $currentTerm
            ? $currentTerm->enrollments()
                ->with([
                    'student',
                    'studentGrades' => fn($q) => $q->where('grade_item_id', $gradeItem->id),
                ])
                ->get()
            : collect();

        return view('teacher.grades.scores', compact('section', 'gradeItem', 'enrollments'));
    }

    public function storeScores(Request $request, Section $section, GradeItem $gradeItem)
    {
        $this->authorizeSection($section);
        abort_if($gradeItem->section_id !== $section->id, 403);
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

        return redirect()->route('teacher.grades.items', $section)->with('success', 'Scores saved.');
    }

    public function finalGrades(Section $section)
    {
        $this->authorizeSection($section);
        $this->requireConfig($section);

        $currentTerm = $section->terms()->where('status', 'active')->first();

        $section->load([
            'gradeConfiguration',
            'gradeItems',
        ]);

        $enrollments = collect();
        if ($currentTerm) {
            $currentTerm->load([
                'enrollments.student',
                'enrollments.finalGrade',
                'enrollments.studentGrades' => fn($q) => $q->with('gradeItem'),
                'enrollments.attendanceRecords',
            ]);
            $enrollments = $currentTerm->enrollments;
        }

        $config     = $section->gradeConfiguration;
        $liveGrades = [];

        foreach ($enrollments as $enrollment) {
            $scores          = $this->calculateComponentScores($enrollment, $config);
            $finalPercentage = round(array_sum($scores), 2);
            $numerical       = FinalGrade::convertToNumericalGrade($finalPercentage);

            $liveGrades[$enrollment->id] = [
                'quiz_score'       => $scores['quiz'],
                'exam_score'       => $scores['exam'],
                'project_score'    => $scores['project'],
                'assessment_score' => $scores['assessment'],
                'attendance_score' => $scores['attendance'],
                'final_grade'      => $finalPercentage,
                'numerical_grade'  => $numerical,
                'letter_grade'     => number_format($numerical, 2),
                'remarks'          => $numerical <= 3.00 ? 'passed' : 'failed',
            ];
        }

        return view('teacher.grades.final', compact('section', 'enrollments', 'liveGrades'));
    }

    public function computeGrades(Section $section)
    {
        $this->authorizeSection($section);

        $currentTerm = $section->terms()->where('status', 'active')->first();
        $section->load(['gradeConfiguration']);
        $config = $section->gradeConfiguration;

        if (!$config) {
            return back()->with('error', 'No grade configuration found.');
        }

        $enrollments = collect();
        if ($currentTerm) {
            $currentTerm->load([
                'enrollments.studentGrades' => fn($q) => $q->with('gradeItem'),
                'enrollments.attendanceRecords',
            ]);
            $enrollments = $currentTerm->enrollments;
        }

        $errors = [];

        foreach ($enrollments as $enrollment) {
            try {
                $scores          = $this->calculateComponentScores($enrollment, $config);
                $finalPercentage = round(array_sum($scores), 2);
                $numerical       = FinalGrade::convertToNumericalGrade($finalPercentage);

                FinalGrade::updateOrCreate(
                    ['enrollment_id' => $enrollment->id],
                    [
                        'quiz_score'       => $scores['quiz'],
                        'exam_score'       => $scores['exam'],
                        'project_score'    => $scores['project'],
                        'assessment_score' => $scores['assessment'],
                        'attendance_score' => $scores['attendance'],
                        'final_grade'      => $finalPercentage,
                        'numerical_grade'  => $numerical,
                        'letter_grade'     => number_format($numerical, 2),
                        'remarks'          => $numerical <= 3.00 ? 'passed' : 'failed',
                        'computed_by'      => auth()->id(),
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

    public function lockGrades(Section $section)
    {
        $this->authorizeSection($section);

        $currentTerm = $section->terms()->where('status', 'active')->first();

        if (!$currentTerm) {
            return back()->with('error', 'No active term found.');
        }

        $currentTerm->enrollments()
            ->with('finalGrade')
            ->get()
            ->each(function ($enrollment) {
                if ($enrollment->finalGrade && !$enrollment->finalGrade->is_locked) {
                    $enrollment->finalGrade->update([
                        'is_locked' => true,
                        'locked_at' => now(),
                    ]);
                }
            });

        return back()->with('success', 'All final grades locked.');
    }

    private function calculateComponentScores($enrollment, $config): array
    {
        $scores = [
            'quiz'       => 0,
            'exam'       => 0,
            'project'    => 0,
            'assessment' => 0,
            'attendance' => 0,
        ];

        $activeWeight = 0;

        foreach (['quiz', 'exam', 'project', 'assessment'] as $type) {
            $weight = (float) $config->{$type . '_weight'};
            if ($weight === 0.0) continue;

            $items = $enrollment->studentGrades->filter(
                fn($g) => $g->gradeItem !== null && $g->gradeItem->component_type === $type
            );

            if ($items->isNotEmpty()) {
                $earned   = $items->sum(fn($g) => (float) $g->score);
                $possible = $items->sum(fn($g) => (float) $g->gradeItem->max_score);

                $scores[$type] = $possible > 0
                    ? round(($earned / $possible) * $weight, 2)
                    : 0;

                $activeWeight += $weight;
            }
        }

        $attendanceWeight = (float) $config->attendance_weight;
        if ($attendanceWeight > 0) {
            $total   = $enrollment->attendanceRecords->count();
            $present = $enrollment->attendanceRecords->whereIn('status', ['present', 'late'])->count();

            if ($total > 0) {
                $scores['attendance'] = round(($present / $total) * $attendanceWeight, 2);
                $activeWeight += $attendanceWeight;
            }
        }

        if ($activeWeight > 0 && $activeWeight < 100) {
            $factor = 100 / $activeWeight;
            foreach ($scores as $key => $val) {
                $scores[$key] = round($val * $factor, 2);
            }
        }

        return $scores;
    }

    private function authorizeSection(Section $section): void
    {
        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(
            !$currentTerm || $currentTerm->adviser_id !== auth()->id(),
            403,
            'You are not assigned to this section.'
        );
    }

    private function requireConfig(Section $section): void
    {
        if (!$section->gradeConfiguration) {
            redirect()->route('teacher.grades.config', $section)
                ->with('warning', 'Set up grade configuration first.')
                ->send();
            exit;
        }
    }
}
