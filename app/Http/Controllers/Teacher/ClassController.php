<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Exports\ClassRecordExport;
use App\Models\FinalGrade;
use App\Models\Section;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ClassController extends Controller
{
    public function show(Section $section)
    {
        abort_if($section->teacher_id !== auth()->id(), 403);

        $section->load([
            'subject',
            'gradeItems',
            'enrollments.student',
            'enrollments.finalGrade',
        ]);

        $hasConfig = $section->gradeConfiguration !== null;

        return view('teacher.classes.show', compact('section', 'hasConfig'));
    }

    public function record(Section $section)
    {
        abort_if($section->teacher_id !== auth()->id(), 403);

        if (!$section->gradeConfiguration) {
            return redirect()->route('teacher.grades.config', $section)
                ->with('warning', 'Set up grade configuration first.');
        }

        $section->load([
            'gradeConfiguration',
            'gradeItems',
            'enrollments.student',
            'enrollments.finalGrade',
            'enrollments.studentGrades' => fn($q) => $q->with('gradeItem'),
            'enrollments.attendanceRecords',
        ]);

        $config = $section->gradeConfiguration;

        $gradeItems = $section->gradeItems
            ->sortBy('created_at')
            ->groupBy('component_type');

        $enrollments = $section->enrollments;

        // Live grade calculation
        $liveGrades = [];
        foreach ($enrollments as $enrollment) {
            $scores = $this->calculateComponentScores($enrollment, $config);
            $finalPercentage = round(array_sum($scores), 2);
            $numerical = FinalGrade::convertToNumericalGrade($finalPercentage);

            $liveGrades[$enrollment->id] = [
                'quiz_score'       => $scores['quiz'],
                'exam_score'       => $scores['exam'],
                'project_score'    => $scores['project'],
                'assessment_score' => $scores['assessment'],
                'attendance_score' => $scores['attendance'],
                'final_grade'      => $finalPercentage,
                'numerical_grade'  => $numerical,
                'letter_grade'     => number_format($numerical, 2),
                'remarks'          => $finalPercentage >= 75 ? 'passed' : 'failed',
            ];
        }

        return view('teacher.classes.record', compact('section', 'config', 'gradeItems', 'enrollments', 'liveGrades'));
    }

    public function export(Section $section)
    {
        abort_if($section->teacher_id !== auth()->id(), 403);

        if (!$section->gradeConfiguration) {
            return redirect()->route('teacher.grades.config', $section)
                ->with('warning', 'Set up grade configuration before exporting.');
        }

        $section->load([
            'subject',
            'teacher',
            'gradeConfiguration',
            'gradeItems',
            'enrollments.student',
            'enrollments.studentGrades.gradeItem',
            'enrollments.attendanceRecords',
            'enrollments.finalGrade',
        ]);

        $config      = $section->gradeConfiguration;
        $gradeItems  = $section->gradeItems->sortBy('created_at')->groupBy('component_type');
        $enrollments = $section->enrollments;

        $liveGrades = [];
        foreach ($enrollments as $enrollment) {
            $scores     = $this->calculateComponentScores($enrollment, $config);
            $finalPct   = round(array_sum($scores), 2);
            $numerical  = FinalGrade::convertToNumericalGrade($finalPct);

            $liveGrades[$enrollment->id] = [
                'quiz_score'       => $scores['quiz'],
                'exam_score'       => $scores['exam'],
                'project_score'    => $scores['project'],
                'assessment_score' => $scores['assessment'],
                'attendance_score' => $scores['attendance'],
                'final_grade'      => $finalPct,
                'numerical_grade'  => $numerical,
                'remarks'          => $finalPct >= 75 ? 'passed' : 'failed',
            ];
        }

        $filename = implode('_', [
            $section->subject->code,
            $section->section_name,
            str_replace(' ', '-', $section->semester),
            $section->academic_year,
        ]) . '.xlsx';

        return Excel::download(new ClassRecordExport($section, $liveGrades), $filename);
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
            }
        }

        $attendanceWeight = (float) $config->attendance_weight;
        if ($attendanceWeight > 0) {
            $total   = $enrollment->attendanceRecords->count();
            $present = $enrollment->attendanceRecords->whereIn('status', ['present', 'late'])->count();

            $scores['attendance'] = $total > 0
                ? round(($present / $total) * $attendanceWeight, 2)
                : 0;
        }

        return $scores;
    }
}
