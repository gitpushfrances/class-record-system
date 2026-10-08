<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Exports\ClassRecordExport;
use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\Subject;
use App\Services\GradeCalculator;
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
            ->where('program_id', $section->program_id)
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('last_name')
            ->get();

        return view('teacher.classes.show', compact('section', 'currentTerm', 'subjectsData', 'availableStudents'));
    }

    public function record(Section $section, Subject $subject, Request $request)
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
        $enrollments = $currentTerm ? \App\Models\Enrollment::sortRoster($currentTerm->enrollments, $request->gender, $request->order) : collect();
        $cutoffDate       = AcademicPeriod::getActive()?->midterm_cutoff_date;

        $liveGrades        = [];
        $attendanceDisplay = [];
        $componentGrades   = [];

        foreach ($enrollments as $enrollment) {
            $liveGrades[$enrollment->id] = GradeCalculator::summary($enrollment, $config, $cutoffDate);

            $componentGrades[$enrollment->id] = $this->calculateComponentGrades($enrollment, $config, $cutoffDate);

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
            'enrollments', 'liveGrades', 'attendanceDisplay', 'componentGrades'
        ));
    }

    public function export(Section $section, Subject $subject, Request $request)
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
        $enrollments = $currentTerm ? \App\Models\Enrollment::sortRoster($currentTerm->enrollments, $request->gender, $request->order) : collect();
        $cutoffDate       = AcademicPeriod::getActive()?->midterm_cutoff_date;

        $liveGrades = [];
        foreach ($enrollments as $enrollment) {
            $liveGrades[$enrollment->id] = GradeCalculator::summary($enrollment, $config, $cutoffDate);
        }

        $sectionLabel = $section->program->code . '_' . $section->year_number . '-' . $section->section_letter;
        $termLabel    = $currentTerm
            ? str_replace(' ', '-', $currentTerm->semester) . '_' . $currentTerm->academic_year
            : 'no-term';
        $filename = $sectionLabel . '_' . $subject->code . '_' . $termLabel . '.xlsx';

        return Excel::download(new ClassRecordExport($section, $currentTerm, $subject, $matrix, $enrollments, $liveGrades, $request->gender), $filename);
    }

    public function enrollStudent(Request $request, Section $section)
    {
        $this->authorizeSection($section);

        $currentTerm = $section->terms()->where('status', 'active')->first();

        abort_if(!$currentTerm, 403, 'No active term for this section.');

        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($request->student_id);

        abort_if(
            $student->program_id !== $section->program_id,
            403,
            'This student does not belong to this section\'s program.'
        );

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

        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(!$currentTerm || (int) $enrollment->section_term_id !== (int) $currentTerm->id, 404);

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

    /**
     * Per-component grade equivalent (display only). Transmutes each
     * component's own raw percentage independently — does NOT feed into
     * the final composite calculation, which still runs on weighted
     * percentages via GradeCalculator. Exists only so the
     * "Grade" column can show 1.00–5.00 instead of weighted points.
     */
    private function calculateComponentGrades($enrollment, $config, $cutoffDate = null): array
    {
        $components = $config->getComponents();
        $grades = [];

        foreach ($components as $comp) {
            $key = $comp['key'];
            $grades[$key] = null;

            if (GradeCalculator::isAttendance($key)) {
                $period = $comp['period'] ?? 'midterm';
                $rate = GradeCalculator::attendanceRate($enrollment, $period, $cutoffDate);
                if ($rate !== null) {
                    $grades[$key] = FinalGrade::convertToNumericalGrade($rate, $config->computation_method ?? 'standard');
                }
                continue;
            }

            $items = $enrollment->studentGrades->filter(
                fn($g) => $g->gradeItem !== null && $g->gradeItem->component_type === $key
            );

            if ($items->isNotEmpty()) {
                $earned   = $items->sum(fn($g) => (float) $g->score);
                $possible = $items->sum(fn($g) => (float) $g->gradeItem->max_score);
                if ($possible > 0) {
                    $pct = round(($earned / $possible) * 100, 2);
                    $grades[$key] = FinalGrade::convertToNumericalGrade($pct, $config->computation_method ?? 'standard');
                }
            }
        }

        return $grades;
    }

}
