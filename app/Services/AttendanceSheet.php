<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\SectionTerm;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * The attendance of one subject in one term as a sheet: one column per recorded session
 * (date, and Lecture / Laboratory for a subject with a laboratory), one row per student
 * with present / absent per session, totals, the absence streak and the rate.
 * A session dated on or before the midterm cutoff belongs to Midterm, a later one to Finals
 * (the same rule as GradeCalculator::attendanceRate).
 */
class AttendanceSheet
{
    /** @return array{sessions: Collection, rows: Collection} */
    public static function build(SectionTerm $term, Subject $subject, $cutoffDate): array
    {
        $enrollments = Enrollment::sortRoster(
            $term->rosterFor($subject->id)
                ->with(['student', 'attendanceRecords' => fn ($q) => $q->where('subject_id', $subject->id)])
                ->get()
        );

        $sessions = $enrollments->flatMap(fn ($enrollment) => $enrollment->attendanceRecords)
            ->map(fn ($record) => ['key' => self::key($record), 'date' => $record->date, 'type' => $record->session_type])
            ->unique('key')
            ->sortBy(fn ($session) => $session['date']->toDateString() . ($session['type'] === 'laboratory' ? '1' : '0'))
            ->map(fn ($session) => $session + ['period' => self::period($session['date'], $cutoffDate)])
            ->values();

        $rows = $enrollments->map(function ($enrollment) {
            $records = $enrollment->attendanceRecords;
            $total   = $records->count();
            $present = $records->where('status', 'present')->count();

            return [
                'student' => $enrollment->student,
                'marks'   => $records->mapWithKeys(fn ($record) => [self::key($record) => $record->status])->all(),
                'total'   => $total,
                'present' => $present,
                'absent'  => $records->where('status', 'absent')->count(),
                'streak'  => AttendanceRecord::consecutiveAbsences($records),
                'percent' => $total > 0 ? round(($present / $total) * 100, 2) : 0,
            ];
        });

        return compact('sessions', 'rows');
    }

    private static function key(AttendanceRecord $record): string
    {
        return $record->date->toDateString() . '|' . $record->session_type;
    }

    /** "midterm" or "final"; null when the academic period has no midterm cutoff yet. */
    private static function period($date, $cutoffDate): ?string
    {
        if (!$cutoffDate) {
            return null;
        }

        return $date->lte($cutoffDate) ? 'midterm' : 'final';
    }
}
