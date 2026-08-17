<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Exports\ClassRecordExport;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\Subject;
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
                'subjects',
            ]);
        }

        // Per-subject grading state for this section, used to render one
        // action row per subject instead of a single section-wide gate.
        $subjectsData = collect();
        if ($currentTerm) {
            foreach ($currentTerm->subjects as $subject) {
                $subjectsData->push([
                    'subject'   => $subject,
                    'isMine'    => (int) $subject->pivot->teacher_id === (int) auth()->id(),
                    'hasConfig' => $section->gradeConfigurationFor($subject->id) !== null,
                    'itemCount' => $section->gradeItemsFor($subject->id)->count(),
                ]);
            }
        }

        $enrolledIds = $currentTerm
            ? $currentTerm->enrollments->pluck('student_id')->toArray()
            : [];

        $availableStudents = Student::where('status', 'active')
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('last_name')
            ->get();

        return view('teacher.classes.show', compact('section', 'currentTerm', 'subjectsData', 'availableStudents'));
    }

    public function record(Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);

        $config = $section->gradeConfigurationFor($subject->id);
        if (!$config) {
            return redirect()->route('teacher.grades.config', [$section, $subject])
                ->with('warning', 'Set up grade configuration first.');
        }

        if ($currentTerm) {
            $currentTerm->load([
                'enrollments.student',
                'enrollments.finalGrade' => fn($q) => $q->where('subject_id', $subject->id),
                'enrollments.studentGrades' => fn($q) => $q
                    ->whereHas('gradeItem', fn($q2) => $q2->where('subject_id', $subject->id))
                    ->with('gradeItem'),
                'enrollments.attendanceRecords' => fn($q) => $q->where('subject_id', $subject->id),
            ]);
        }

        $gradeItemsByType = $section->gradeItemsFor($subject->id)->get()->groupBy('component_type');
        $matrix           = $config->buildComponentMatrix($gradeItemsByType);
        $enrollments      = $currentTerm ? $currentTerm->enrollments : collect();
        $cutoffDate       = $currentTerm?->midterm_cutoff_date;

        $liveGrades        = [];
        $attendanceDisplay = [];

        foreach ($enrollments as $enrollment) {
            $scores          = $this->calculateComponentScores($enrollment, $config, $cutoffDate);
            $finalPercentage = round(array_sum($scores), 2);
            $numerical       = FinalGrade::convertToNumericalGrade($finalPercentage);

            $liveGrades[$enrollment->id] = [
                'scores'          => $scores,
                'final_grade'     => $finalPercentage,
                'numerical_grade' => $numerical,
                'letter_grade'    => number_format($numerical, 2),
                'remarks'         => $numerical <= 3.00 ? 'passed' : 'failed',
            ];

            foreach ($matrix as $comp) {
                if ($comp['type'] !== 'attendance') continue;
                $period  = $comp['period'];
                $records = $enrollment->attendanceRecords->filter(
                    fn($r) => $cutoffDate
                        ? ($period === 'midterm' ? $r->date->lte($cutoffDate) : $r->date->gt($cutoffDate))
                        : false
                );
                $attendanceDisplay[$enrollment->id][$comp['key']] = [
                    'present' => $records->whereIn('status', ['present', 'late'])->count(),
                    'total'   => $records->count(),
                ];
            }
        }

        return view('teacher.classes.record', compact(
            'section', 'subject', 'currentTerm', 'config', 'matrix',
            'enrollments', 'liveGrades', 'attendanceDisplay'
        ));
    }

    public function export(Section $section, Subject $subject)
    {
        $currentTerm = $this->authorizeSectionSubject($section, $subject);

        $config = $section->gradeConfigurationFor($subject->id);
        if (!$config) {
            return redirect()->route('teacher.grades.config', [$section, $subject])
                ->with('warning', 'Set up grade configuration before exporting.');
        }

        if ($currentTerm) {
            $currentTerm->load([
                'enrollments.student',
                'enrollments.studentGrades' => fn($q) => $q
                    ->whereHas('gradeItem', fn($q2) => $q2->where('subject_id', $subject->id))
                    ->with('gradeItem'),
                'enrollments.attendanceRecords' => fn($q) => $q->where('subject_id', $subject->id),
                'enrollments.finalGrade' => fn($q) => $q->where('subject_id', $subject->id),
            ]);
        }

        $gradeItemsByType = $section->gradeItemsFor($subject->id)->get()->groupBy('component_type');
        $matrix           = $config->buildComponentMatrix($gradeItemsByType);
        $enrollments      = $currentTerm ? $currentTerm->enrollments : collect();
        $cutoffDate       = $currentTerm?->midterm_cutoff_date;

        $liveGrades = [];
        foreach ($enrollments as $enrollment) {
            $scores    = $this->calculateComponentScores($enrollment, $config, $cutoffDate);
            $finalPct  = round(array_sum($scores), 2);
            $numerical = FinalGrade::convertToNumericalGrade($finalPct);

            $liveGrades[$enrollment->id] = [
                'scores'          => $scores,
                'final_grade'     => $finalPct,
                'numerical_grade' => $numerical,
                'remarks'         => $numerical <= 3.00 ? 'passed' : 'failed',
            ];
        }

        $sectionLabel = $section->program->code . '_' . $section->year_number . '-' . $section->section_letter;
        $termLabel    = $currentTerm
            ? str_replace(' ', '-', $currentTerm->semester) . '_' . $currentTerm->academic_year
            : 'no-term';
        $filename = $sectionLabel . '_' . $subject->code . '_' . $termLabel . '.xlsx';

        return Excel::download(new ClassRecordExport($section, $currentTerm, $subject, $matrix, $enrollments, $liveGrades), $filename);
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

        $isAdviser = $currentTerm && $currentTerm->adviser_id === auth()->id();
        $isSubjectTeacher = $currentTerm && $currentTerm->subjects()
            ->where('section_subject_teachers.teacher_id', auth()->id())
            ->exists();

        abort_if(
            !$currentTerm || (!$isAdviser && !$isSubjectTeacher),
            403,
            'You are not assigned to this section.'
        );
    }

    /**
     * Stricter than authorizeSection() — used by record()/export(), which are
     * subject-specific gradebooks. Only the teacher assigned to THIS subject
     * may open it, not just any adviser or any subject-teacher of the section.
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
