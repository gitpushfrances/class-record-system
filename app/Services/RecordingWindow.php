<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\SectionTerm;
use Carbon\Carbon;

/**
 * The dates a teacher may record for a class, from the Administrator's academic period of the term.
 * Both ends are included:
 *   attendance (any date)  semester start  to  finals cutoff
 *   midterm records        semester start  to  midterm cutoff
 *   finals records         the day after the midterm cutoff  to  finals cutoff
 * The midterm/finals split is the one GradeCalculator and AttendanceSheet already use
 * (a date on or before the midterm cutoff is midterm, a later one is finals).
 *
 * Recording is closed, never open, when the term has no period or the period lacks the semester
 * start, the midterm cutoff or the finals cutoff: the teacher is told to contact the Administrator.
 */
class RecordingWindow
{
    public const SEMESTER = 'semester';
    public const MIDTERM  = 'midterm';
    public const FINAL    = 'final';

    /** The school's clock. The server runs on UTC, which is still "yesterday" in Manila until 8 AM. */
    public const TIMEZONE = 'Asia/Manila';

    public const NOT_CONFIGURED = 'No valid academic period is configured for this date. Please contact the administrator.';

    private const LABELS = [
        self::MIDTERM  => 'the midterm period',
        self::FINAL    => 'the finals period',
        self::SEMESTER => 'this semester',
    ];

    /** Today's date in the school's time zone, as Y-m-d. */
    public static function today(): string
    {
        return Carbon::now(self::TIMEZONE)->toDateString();
    }

    /** The term's academic period, only when it has all three dates. */
    public static function period(?SectionTerm $term): ?AcademicPeriod
    {
        $period = $term ? AcademicPeriod::forTerm($term->academic_year, $term->semester) : null;

        return $period && $period->start_date && $period->midterm_cutoff_date && $period->finals_cutoff_date ? $period : null;
    }

    /** @return array{semester: array{0: ?string, 1: ?string}, midterm: array{0: ?string, 1: ?string}, final: array{0: ?string, 1: ?string}} [min, max] as Y-m-d, null when recording is closed */
    public static function bounds(?SectionTerm $term): array
    {
        $period = self::period($term);

        if (!$period) {
            return [self::SEMESTER => [null, null], self::MIDTERM => [null, null], self::FINAL => [null, null]];
        }

        $start = $period->start_date->toDateString();
        $mid   = $period->midterm_cutoff_date->toDateString();
        $final = $period->finals_cutoff_date->toDateString();

        return [
            self::SEMESTER => [$start, $final],
            self::MIDTERM  => [$start, $mid],
            self::FINAL    => [$period->midterm_cutoff_date->copy()->addDay()->toDateString(), $final],
        ];
    }

    /** Why recording is closed for this term, or null when it is open. */
    public static function problem(?SectionTerm $term): ?string
    {
        return self::period($term) ? null : self::NOT_CONFIGURED;
    }

    /** The message for a date that may not be recorded, or null. $period is "midterm", "final" or null (any date of the semester). */
    public static function refusal(?SectionTerm $term, ?string $date, ?string $period = null): ?string
    {
        if ($problem = self::problem($term)) {
            return $problem;
        }

        if (!filled($date)) {
            return 'Enter the date of this record.';
        }

        $key         = in_array($period, [self::MIDTERM, self::FINAL], true) ? $period : self::SEMESTER;
        [$min, $max] = self::bounds($term)[$key];
        $day         = Carbon::parse($date)->toDateString();

        if ($day < $min || $day > $max) {
            return Carbon::parse($date)->format('M j, Y') . ' is outside ' . self::LABELS[$key] . '. Records for ' . self::LABELS[$key] . ' must be dated ' . self::describe($min, $max) . '.';
        }

        return null;
    }

    /** One line for the teacher: the dates that can be recorded (or why none can), for a semester, midterm or final key. */
    public static function allowed(?SectionTerm $term, string $key = self::SEMESTER): string
    {
        if ($problem = self::problem($term)) {
            return $problem;
        }

        [$min, $max] = self::bounds($term)[$key];

        return 'Allowed dates: ' . self::describe($min, $max) . '.';
    }

    /** The same line for every key, ready for a script. */
    public static function hints(?SectionTerm $term): array
    {
        return [
            self::SEMESTER => self::allowed($term, self::SEMESTER),
            self::MIDTERM  => self::allowed($term, self::MIDTERM),
            self::FINAL    => self::allowed($term, self::FINAL),
        ];
    }

    /** A date kept inside [min, max]: the default for a date field. */
    public static function clamp(string $date, array $range): string
    {
        [$min, $max] = $range;

        if ($max && $date > $max) {
            return $max;
        }

        return $min && $date < $min ? $min : $date;
    }

    private static function describe(string $min, string $max): string
    {
        return 'from ' . Carbon::parse($min)->format('M j, Y') . ' to ' . Carbon::parse($max)->format('M j, Y');
    }
}
