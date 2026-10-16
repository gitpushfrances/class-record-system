<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Exports\ClassRecordExport;
use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\Section;
use App\Models\SectionTerm;
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

        return view('teacher.classes.show', compact('section', 'currentTerm', 'subjectsData'));
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
            $currentTerm->loadRoster($subject->id, [
                'enrollments.student',
                'enrollments.finalGrade' => fn($q) => $q->where('subject_id', $subject->id),
                'enrollments.studentGrades' => fn($q) => $q
                    ->whereHas('gradeItem', fn($q2) => $q2->where('subject_id', $subject->id))
                    ->with('gradeItem'),
                'enrollments.attendanceRecords' => fn($q) => $q->where('subject_id', $subject->id),
            ]);
        }

        $gradeItemsByType = $section->gradeItemsFor($subject->id)->get()->groupBy('component_type');
        $matrix           = $config->buildComponentMatrix($gradeItemsByType, true);
        $enrollments = $currentTerm ? \App\Models\Enrollment::sortRoster($currentTerm->enrollments, $request->gender, $request->order) : collect();
        $cutoffDate       = AcademicPeriod::getActive()?->midterm_cutoff_date;

        $streaks = \App\Models\AttendanceRecord::streaksFor($subject->id, $enrollments->pluck('id'));
        $locked  = $currentTerm->verifications()->where('subject_id', $subject->id)->whereIn('status', ['pending', 'verified'])->exists();

        $liveGrades        = [];
        $attendanceDisplay = [];
        $componentGrades   = [];

        foreach ($enrollments as $enrollment) {
            $liveGrades[$enrollment->id] = GradeCalculator::result($enrollment, $config, $cutoffDate);

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
                    'present' => $records->where('status', 'present')->count(),
                    'total'   => $records->count(),
                ];
            }
        }

        return view('teacher.classes.record', compact(
            'section', 'subject', 'currentTerm', 'config', 'matrix',
            'enrollments', 'liveGrades', 'attendanceDisplay', 'componentGrades', 'streaks', 'locked'
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
            $currentTerm->loadRoster($subject->id, [
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
            $liveGrades[$enrollment->id] = GradeCalculator::result($enrollment, $config, $cutoffDate);
        }

        $sectionLabel = $section->program->code . '_' . $section->year_number . '-' . $section->section_letter;
        $termLabel    = $currentTerm
            ? str_replace(' ', '-', $currentTerm->semester) . '_' . $currentTerm->academic_year
            : 'no-term';
        $filename = $sectionLabel . '_' . $subject->code . '_' . $termLabel . '.xlsx';

        return Excel::download(new ClassRecordExport($section, $currentTerm, $subject, $matrix, $enrollments, $liveGrades, $request->gender), $filename);
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
