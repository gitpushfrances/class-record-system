<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Meeting days and time range of a subject in a section term (section_subject_teachers). */
class ClassSchedule
{
    public const DAYS = ['M' => 'Monday', 'T' => 'Tuesday', 'W' => 'Wednesday', 'Th' => 'Thursday', 'F' => 'Friday', 'S' => 'Saturday', 'Su' => 'Sunday'];

    public const ATTRIBUTES = ['schedule_days' => 'class days', 'schedule_start' => 'start time', 'schedule_end' => 'end time'];

    /** Optional as a whole; once any part is entered, days, start and end are all required. */
    public static function rules(): array
    {
        return [
            'schedule_days'   => ['nullable', 'array', 'required_with:schedule_start,schedule_end'],
            'schedule_days.*' => ['in:' . implode(',', array_keys(self::DAYS))],
            'schedule_start'  => ['nullable', 'date_format:H:i', 'required_with:schedule_days,schedule_end'],
            'schedule_end'    => ['nullable', 'date_format:H:i', 'required_with:schedule_days,schedule_start', 'after:schedule_start'],
        ];
    }

    /** Pivot columns from a validated request; all null when no schedule was entered. */
    public static function fromRequest(Request $request): array
    {
        $days = array_values(array_intersect(array_keys(self::DAYS), (array) $request->input('schedule_days', [])));

        if (!$days || !$request->filled('schedule_start') || !$request->filled('schedule_end')) {
            return ['schedule_days' => null, 'schedule_start' => null, 'schedule_end' => null];
        }

        return [
            'schedule_days'  => implode(',', $days),
            'schedule_start' => $request->input('schedule_start'),
            'schedule_end'   => $request->input('schedule_end'),
        ];
    }

    /** The first other subject in the same section term that meets on a shared day at an overlapping time, as a message. */
    public static function conflict(int $sectionTermId, int $subjectId, array $slot): ?string
    {
        if (!$slot['schedule_days']) {
            return null;
        }

        $days   = explode(',', $slot['schedule_days']);
        $others = DB::table('section_subject_teachers as sst')
            ->join('subjects', 'subjects.id', '=', 'sst.subject_id')
            ->where('sst.section_term_id', $sectionTermId)
            ->where('sst.subject_id', '!=', $subjectId)
            ->whereNotNull('sst.schedule_days')
            ->where('sst.schedule_start', '<', $slot['schedule_end'])
            ->where('sst.schedule_end', '>', $slot['schedule_start'])
            ->get(['subjects.code', 'subjects.name', 'sst.schedule_days', 'sst.schedule_start', 'sst.schedule_end']);

        foreach ($others as $other) {
            if (array_intersect($days, explode(',', $other->schedule_days))) {
                return "Schedule conflict: {$other->code} — {$other->name} already meets "
                    . self::label($other->schedule_days, $other->schedule_start, $other->schedule_end)
                    . ' in this section.';
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

    /** Values for partials/schedule-fields: the saved schedule, or the old input for a new row. */
    public static function formValues($pivot): array
    {
        if ($pivot === null) {
            return ['days' => (array) old('schedule_days', []), 'start' => old('schedule_start', ''), 'end' => old('schedule_end', '')];
        }

        return [
            'days'  => $pivot->schedule_days ? explode(',', $pivot->schedule_days) : [],
            'start' => substr((string) $pivot->schedule_start, 0, 5),
            'end'   => substr((string) $pivot->schedule_end, 0, 5),
        ];
    }
}
