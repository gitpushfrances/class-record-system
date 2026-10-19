<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeConfiguration;
use App\Models\SectionTerm;
use App\Models\Subject;

/**
 * Everything the Class Record shows for one subject in one term: the component matrix,
 * the roster, each student's live result, component grades and attendance totals.
 * Used by the current Class Record and by Past Classes (read-only), so both compute alike.
 */
class ClassRecordData
{
    public static function build(SectionTerm $term, Subject $subject, GradeConfiguration $config, $cutoffDate, ?string $gender = null, ?string $order = null): array
    {
        $term->loadRoster($subject->id, [
            'enrollments.student',
            'enrollments.finalGrade' => fn ($q) => $q->where('subject_id', $subject->id),
            'enrollments.studentGrades' => fn ($q) => $q
                ->whereHas('gradeItem', fn ($q2) => $q2->where('subject_id', $subject->id))
                ->with('gradeItem'),
            'enrollments.attendanceRecords' => fn ($q) => $q->where('subject_id', $subject->id),
        ]);

        $matrix      = $config->buildComponentMatrix($term->gradeItemsFor($subject->id)->get()->groupBy('component_type'), true);
        $enrollments = Enrollment::sortRoster($term->enrollments, $gender, $order);
        $streaks     = AttendanceRecord::streaksFor($subject->id, $enrollments->pluck('id'));

        $liveGrades = $componentGrades = $attendanceDisplay = [];

        foreach ($enrollments as $enrollment) {
            $liveGrades[$enrollment->id]      = GradeCalculator::result($enrollment, $config, $cutoffDate);
            $componentGrades[$enrollment->id] = self::componentGrades($enrollment, $config, $cutoffDate);

            foreach ($matrix as $comp) {
                if ($comp['type'] !== 'attendance') {
                    continue;
                }
                $records = $enrollment->attendanceRecords->filter(
                    fn ($r) => $cutoffDate
                        ? ($comp['period'] === 'midterm' ? $r->date->lte($cutoffDate) : $r->date->gt($cutoffDate))
                        : false
                );
                $attendanceDisplay[$enrollment->id][$comp['key']] = [
                    'present' => $records->where('status', 'present')->count(),
                    'total'   => $records->count(),
                ];
            }
        }

        return compact('matrix', 'enrollments', 'liveGrades', 'attendanceDisplay', 'componentGrades', 'streaks');
    }

    /** Midterm cutoff of the term's own academic period (a closed term is not the active period). */
    public static function cutoffFor(SectionTerm $term)
    {
        return AcademicPeriod::where('school_year', $term->academic_year)
            ->where('semester', $term->semester)
            ->first()?->midterm_cutoff_date;
    }

    /**
     * Per-component grade equivalent (display only). Transmutes each component's own raw
     * percentage independently; the composite still runs on weighted percentages in
     * GradeCalculator. Lets the "Grade" column show 1.0–5.0 instead of weighted points.
     */
    private static function componentGrades($enrollment, GradeConfiguration $config, $cutoffDate): array
    {
        $method = $config->computation_method ?? 'standard';
        $grades = [];

        foreach ($config->getComponents() as $comp) {
            $key          = $comp['key'];
            $grades[$key] = null;

            if (GradeCalculator::isAttendance($key)) {
                $rate = GradeCalculator::attendanceRate($enrollment, $comp['period'] ?? 'midterm', $cutoffDate);
                if ($rate !== null) {
                    $grades[$key] = FinalGrade::convertToNumericalGrade($rate, $method);
                }
                continue;
            }

            $items = $enrollment->studentGrades->filter(
                fn ($g) => $g->gradeItem !== null && $g->gradeItem->component_type === $key
            );

            if ($items->isNotEmpty()) {
                $earned   = $items->sum(fn ($g) => (float) $g->score);
                $possible = $items->sum(fn ($g) => (float) $g->gradeItem->max_score);
                if ($possible > 0) {
                    $grades[$key] = FinalGrade::convertToNumericalGrade(round(($earned / $possible) * 100, 2), $method);
                }
            }
        }

        return $grades;
    }
}
