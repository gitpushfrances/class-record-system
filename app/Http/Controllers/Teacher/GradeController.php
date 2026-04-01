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

        $request->validate([
            'components'          => 'required|array|min:1',
            'components.*.key'    => 'required|string|max:50',
            'components.*.label'  => 'required|string|max:100',
            'components.*.weight' => 'required|numeric|min:0|max:100',
            'components.*.period' => 'required|in:midterm,final',
        ]);

        $components = $request->input('components');

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
            ['section_id' => $section->id],
            ['config_json' => $components, 'status' => 'active']
        );

        return redirect()->route('teacher.classes.show', $section)
            ->with('success', 'Grade configuration saved.');
    }

    public function items(Section $section)
    {
        $this->authorizeSection($section);
        $this->requireConfig($section);

        $allItems = $section->gradeItems()
            ->orderBy('period')
            ->orderBy('component_type')
            ->orderBy('created_at')
            ->get();

        $gradeItems = $allItems->groupBy('period');
        $config     = $section->gradeConfiguration;
        $components = collect($config->getComponents())->keyBy('key');

        return view('teacher.grades.items', compact('section', 'gradeItems', 'config', 'components'));
    }

    public function storeItem(Request $request, Section $section)
    {
        $this->authorizeSection($section);
        $this->requireConfig($section);

        $config     = $section->gradeConfiguration;
        $validKeys  = collect($config->getComponents())->pluck('key')->toArray();

        $data = $request->validate([
            'component_type' => ['required', 'string', 'max:50', 'in:' . implode(',', $validKeys)],
            'period'         => 'required|in:midterm,final',
            'name'           => 'required|string|max:100',
            'max_score'      => 'required|numeric|min:1',
            'date_given'     => 'nullable|date',
            'description'    => 'nullable|string|max:500',
        ]);

        // Block if weight is 0
        $weight = $config->getWeight($data['component_type']);
        if ($weight === 0.0) {
            return back()->withErrors(['component_type' => 'This component has 0% weight. Update grade configuration to enable it.'])->withInput();
        }

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
            $midScores = $this->calculatePeriodScores($enrollment, $config, 'midterm');
            $finScores = $this->calculatePeriodScores($enrollment, $config, 'final');
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
                'letter_grade'       => number_format($avgNum, 2),
                'remarks'            => $avgNum <= 3.00 ? 'passed' : 'failed',
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
                $midScores  = $this->calculatePeriodScores($enrollment, $config, 'midterm');
                $finScores  = $this->calculatePeriodScores($enrollment, $config, 'final');
                $midPct     = round(array_sum($midScores), 2);
                $finPct     = round(array_sum($finScores), 2);
                $midNum     = FinalGrade::convertToNumericalGrade($midPct);
                $finNum     = FinalGrade::convertToNumericalGrade($finPct);
                $avgNum     = round(($midNum + $finNum) / 2 * 4) / 4;
                $allScores  = $this->calculateComponentScores($enrollment, $config);
                $finalPct   = round(array_sum($allScores), 2);

                FinalGrade::updateOrCreate(
                    ['enrollment_id' => $enrollment->id],
                    [
                        'midterm_percentage' => $midPct,
                        'midterm_numerical'  => $midNum,
                        'final_percentage'   => $finPct,
                        'final_numerical'    => $finNum,
                        'average_numerical'  => $avgNum,
                        'final_grade'        => $finalPct,
                        'numerical_grade'    => $avgNum,
                        'letter_grade'       => number_format($avgNum, 2),
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

    public function lockGrades(Section $section)
    {
        $this->authorizeSection($section);

        $currentTerm = $section->terms()->where('status', 'active')->first();

        if (!$currentTerm) {
            return back()->with('error', 'No active term found.');
        }

        if ($currentTerm->verification()->exists()) {
            return back()->with('error', 'Grades are verified by Program Head and cannot be modified.');
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
        $components   = $config->getComponents();
        $scores       = [];
        $activeWeight = 0;

        foreach ($components as $comp) {
            $key    = $comp['key'];
            $weight = (float) $comp['weight'];
            $scores[$key] = 0;
            if ($weight === 0.0) continue;

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

    private function calculatePeriodScores($enrollment, $config, string $period): array
    {
        $components   = $config->getComponentsByPeriod($period);
        $scores       = [];
        $activeWeight = 0;

        foreach ($components as $comp) {
            $key    = $comp['key'];
            $weight = (float) $comp['weight'];
            $scores[$key] = 0;
            if ($weight === 0.0) continue;

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
