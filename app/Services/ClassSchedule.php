<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Meeting days and times of a subject in a section term (section_subject_teachers).
 * Every subject has a class (lecture) schedule; a subject with a laboratory also has
 * a laboratory schedule, which may fall on the same day at another time.
 * Columns: schedule_days / schedule_start / schedule_end and lab_days / lab_start / lab_end.
 */
class ClassSchedule
{
    public const DAYS = ['M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 'Th' => 'Thursday', 'F' => 'Friday', 'S' => 'Saturday', 'Su' => 'Sunday'];

    public const ATTRIBUTES = [
        'schedule_days' => 'class days', 'schedule_start' => 'start time', 'schedule_end' => 'end time',
        'lab_days' => 'laboratory days', 'lab_start' => 'laboratory start time', 'lab_end' => 'laboratory end time',
    ];

    /** Optional as a whole; once any part of a schedule is entered, its days, start and end are all required. */
    public static function rules(string $prefix = 'schedule'): array
    {
        [$d, $s, $e] = ["{$prefix}_days", "{$prefix}_start", "{$prefix}_end"];

        return [
            $d        => ['nullable', 'array', "required_with:$s,$e"],
            "$d.*"    => ['in:' . implode(',', array_keys(self::DAYS))],
            $s        => ['nullable', 'date_format:H:i', "required_with:$d,$e"],
            $e        => ['nullable', 'date_format:H:i', "required_with:$d,$s", "after:$s"],
        ];
    }

    /** Class (lecture) schedule and laboratory schedule rules together. */
    public static function allRules(): array
    {
        return self::rules() + self::rules('lab');
    }

    /** Pivot columns of one schedule from a validated request; all null when it was not entered. */
    public static function fromRequest(Request $request, string $prefix = 'schedule'): array
    {
        $days  = array_values(array_intersect(array_keys(self::DAYS), (array) $request->input("{$prefix}_days", [])));
        $empty = ["{$prefix}_days" => null, "{$prefix}_start" => null, "{$prefix}_end" => null];

        if (!$days || !$request->filled("{$prefix}_start") || !$request->filled("{$prefix}_end")) {
            return $empty;
        }

        return [
            "{$prefix}_days"  => implode(',', $days),
            "{$prefix}_start" => $request->input("{$prefix}_start"),
            "{$prefix}_end"   => $request->input("{$prefix}_end"),
        ];
    }

    /** Both schedules' pivot columns; the laboratory columns stay null for a subject without a laboratory. */
    public static function columnsFromRequest(Request $request, bool $hasLaboratory): array
    {
        return self::fromRequest($request)
            + ($hasLaboratory ? self::fromRequest($request, 'lab') : ['lab_days' => null, 'lab_start' => null, 'lab_end' => null]);
    }

    /**
     * A message when the given columns clash: the lecture and laboratory of the same subject,
     * or either of them with any schedule of another subject in the same section term. Null when clear.
     */
    public static function conflict(int $sectionTermId, int $subjectId, array $columns): ?string
    {
        $own = self::slots((object) $columns);

        if (count($own) === 2 && self::overlaps($own[0], $own[1])) {
            return 'Schedule conflict: the lecture and laboratory times overlap.';
        }

        $others = DB::table('section_subject_teachers as sst')
            ->join('subjects', 'subjects.id', '=', 'sst.subject_id')
            ->where('sst.section_term_id', $sectionTermId)
            ->where('sst.subject_id', '!=', $subjectId)
            ->get(['subjects.code', 'subjects.name', 'subjects.has_laboratory', 'sst.schedule_days', 'sst.schedule_start', 'sst.schedule_end', 'sst.lab_days', 'sst.lab_start', 'sst.lab_end']);

        foreach ($own as $mine) {
            foreach ($others as $other) {
                foreach (self::slots($other, (bool) $other->has_laboratory) as $theirs) {
                    if (self::overlaps($mine, $theirs)) {
                        $what = $theirs['type'] === 'lab' ? ' (laboratory)' : '';

                        return "Schedule conflict: {$other->code} — {$other->name}{$what} already meets "
                            . self::label($theirs['days'], $theirs['start'], $theirs['end']) . ' in this section.';
                    }
                }
            }
        }

        return null;
    }

    /** "10:30-12:00PM (MW)", the format printed on the Report of Grades. Empty when not set. */
    public static function label(?string $days, ?string $start, ?string $end): string
    {
        if (!$days || !$start || !$end) {
            return '';
        }

        $from = Carbon::createFromFormat('H:i', substr($start, 0, 5));
        $to   = Carbon::createFromFormat('H:i', substr($end, 0, 5));

        return $from->format('g:i') . '-' . $to->format('g:iA') . ' (' . str_replace(',', '', $days) . ')';
    }

    /**
     * Both schedules of an assignment (a pivot or row with the schedule and lab columns):
     * "10:30-12:00PM (TTh)", or "Lec 10:30-12:00PM (TTh); Lab 1:00-4:00PM (F)" when a laboratory schedule is set.
     */
    public static function summary(object $row, bool $hasLaboratory): string
    {
        $lecture = self::label($row->schedule_days ?? null, $row->schedule_start ?? null, $row->schedule_end ?? null);
        $lab     = $hasLaboratory ? self::label($row->lab_days ?? null, $row->lab_start ?? null, $row->lab_end ?? null) : '';

        if ($lab === '') {
            return $lecture;
        }

        return ($lecture !== '' ? "Lec {$lecture}; " : '') . "Lab {$lab}";
    }

    /** Values for partials/schedule-fields: the saved schedule, or the old input for a new row. */
    public static function formValues($pivot, string $prefix = 'schedule'): array
    {
        if ($pivot === null) {
            return ['days' => (array) old("{$prefix}_days", []), 'start' => old("{$prefix}_start", ''), 'end' => old("{$prefix}_end", ''), 'prefix' => $prefix];
        }

        $days = $pivot->{"{$prefix}_days"};

        return [
            'days'   => $days ? explode(',', $days) : [],
            'start'  => substr((string) $pivot->{"{$prefix}_start"}, 0, 5),
            'end'    => substr((string) $pivot->{"{$prefix}_end"}, 0, 5),
            'prefix' => $prefix,
        ];
    }

    /** @return array<int, array{type: string, days: string, start: string, end: string}> */
    private static function slots(object $row, bool $withLab = true): array
    {
        $slots = [];

        foreach ($withLab ? ['schedule' => 'lecture', 'lab' => 'lab'] : ['schedule' => 'lecture'] as $prefix => $type) {
            $days = $row->{"{$prefix}_days"} ?? null;
            if ($days && ($row->{"{$prefix}_start"} ?? null) && ($row->{"{$prefix}_end"} ?? null)) {
                $slots[] = [
                    'type'  => $type,
                    'days'  => $days,
                    'start' => substr($row->{"{$prefix}_start"}, 0, 5),
                    'end'   => substr($row->{"{$prefix}_end"}, 0, 5),
                ];
            }
        }

        return $slots;
    }

    private static function overlaps(array $a, array $b): bool
    {
        return array_intersect(explode(',', $a['days']), explode(',', $b['days']))
            && $a['start'] < $b['end'] && $a['end'] > $b['start'];
    }
}
