<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Exports\ClassRecordExport;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ClassController extends Controller
{
    public function show(Section $section)
    {
        $this->authorizeSection($section);

        $currentTerm = $section->terms()->where('status', 'active')->first();

        $section->load([
            'program.department',
            'gradeItems',
        ]);

        if ($currentTerm) {
            $currentTerm->load([
                'enrollments.student',
                'enrollments.finalGrade',
            ]);
        }

        $hasConfig = $section->gradeConfiguration !== null;

        $enrolledIds = $currentTerm
            ? $currentTerm->enrollments->pluck('student_id')->toArray()
            : [];

        $availableStudents = Student::where('status', 'active')
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('last_name')
            ->get();

        return view('teacher.classes.show', compact('section', 'currentTerm', 'hasConfig', 'availableStudents'));
    }

    public function record(Section $section)
    {
        $this->authorizeSection($section);

        if (!$section->gradeConfiguration) {
            return redirect()->route('teacher.grades.config', $section)
                ->with('warning', 'Set up grade configuration first.');
        }

        $currentTerm = $section->terms()->where('status', 'active')->first();

        $section->load([
            'program.department',
            'gradeConfiguration',
            'gradeItems',
        ]);

        if ($currentTerm) {
            $currentTerm->load([
                'enrollments.student',
                'enrollments.finalGrade',
                'enrollments.studentGrades' => fn($q) => $q->with('gradeItem'),
                'enrollments.attendanceRecords',
            ]);
        }

        $config     = $section->gradeConfiguration;
        $gradeItems = $section->gradeItems->sortBy('created_at')->groupBy('component_type');
        $enrollments = $currentTerm ? $currentTerm->enrollments : collect();

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

        return view('teacher.classes.record', compact('section', 'currentTerm', 'config', 'gradeItems', 'enrollments', 'liveGrades'));
    }

    public function export(Section $section)
    {
        $this->authorizeSection($section);

        if (!$section->gradeConfiguration) {
            return redirect()->route('teacher.grades.config', $section)
                ->with('warning', 'Set up grade configuration before exporting.');
        }

        $currentTerm = $section->terms()->where('status', 'active')->first();

        $section->load([
            'program.department',
            'gradeConfiguration',
            'gradeItems',
        ]);

        if ($currentTerm) {
            $currentTerm->load([
                'enrollments.student',
                'enrollments.studentGrades.gradeItem',
                'enrollments.attendanceRecords',
                'enrollments.finalGrade',
            ]);
        }

        $config      = $section->gradeConfiguration;
        $gradeItems  = $section->gradeItems->sortBy('created_at')->groupBy('component_type');
        $enrollments = $currentTerm ? $currentTerm->enrollments : collect();

        $liveGrades = [];
        foreach ($enrollments as $enrollment) {
            $scores   = $this->calculateComponentScores($enrollment, $config);
            $finalPct = round(array_sum($scores), 2);
            $numerical = FinalGrade::convertToNumericalGrade($finalPct);

            $liveGrades[$enrollment->id] = [
                'quiz_score'       => $scores['quiz'],
                'exam_score'       => $scores['exam'],
                'project_score'    => $scores['project'],
                'assessment_score' => $scores['assessment'],
                'attendance_score' => $scores['attendance'],
                'final_grade'      => $finalPct,
                'numerical_grade'  => $numerical,
                'remarks'          => $numerical <= 3.00 ? 'passed' : 'failed',
            ];
        }

        $sectionLabel = $section->program->code . '_' . $section->year_number . '-' . $section->section_letter;
        $termLabel    = $currentTerm
            ? str_replace(' ', '-', $currentTerm->semester) . '_' . $currentTerm->academic_year
            : 'no-term';
        $filename = $sectionLabel . '_' . $termLabel . '.xlsx';

        return Excel::download(new ClassRecordExport($section, $liveGrades), $filename);
    }

    public function enrollStudent(Request $request, Section $section)
    {
        $this->authorizeSection($section);

        $currentTerm = $section->terms()->where('status', 'active')->first();

        abort_if(!$currentTerm, 403, 'No active term for this section.');

        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $alreadyEnrolled = Enrollment::where('section_term_id', $currentTerm->id)
            ->where('student_id', $request->student_id)
            ->exists();

        if ($alreadyEnrolled) {
            return back()->with('error', 'Student is already enrolled in this class.');
        }

        Enrollment::create([
            'student_id'      => $request->student_id,
            'section_term_id' => $currentTerm->id,
            'status'          => 'enrolled',
            'enrolled_at'     => now(),
        ]);

        return back()->with('success', 'Student enrolled successfully.');
    }

    public function unenrollStudent(Section $section, Enrollment $enrollment)
    {
        $this->authorizeSection($section);
        $enrollment->delete();
        return back()->with('success', 'Student removed from class.');
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

    private function calculateComponentScores($enrollment, $config): array
    {
        $components = $config->getComponents();
        $scores     = [];
        $gradeMap   = $enrollment->studentGrades->keyBy(fn($g) => $g->gradeItem?->id);

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
                $scores[$key] = $possible > 0 ? round(($earned / $possible) * $weight, 2) : 0;
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
}
