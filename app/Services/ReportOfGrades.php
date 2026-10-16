<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\SectionTerm;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;

/** Data for the Report of Grades (form ESSU-ACAD-712.b), built from the submitted final grades. */
class ReportOfGrades
{
    public const ROWS_PER_PAGE = 25;

    private const SEMESTERS = ['1st Semester' => 'First Semester', '2nd Semester' => 'Second Semester', 'Summer' => 'Summer'];
    private const ROMAN     = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V'];

    /** The ROG exists once the teacher has submitted the subject's grades (pending or verified). */
    public static function isSubmitted(SectionTerm $term, Subject $subject): bool
    {
        return $term->verifications()
            ->where('subject_id', $subject->id)
            ->whereIn('status', ['pending', 'verified'])
            ->exists();
    }

    public static function build(SectionTerm $term, Subject $subject): array
    {
        $term->loadMissing('section.program.department.dean', 'section.program.programHead');
        $section = $term->section;
        $program = $section->program;
        $pivot    = $term->subjects()->where('subjects.id', $subject->id)->firstOrFail()->pivot;
        $schedule = ClassSchedule::label($pivot->schedule_days, $pivot->schedule_start, $pivot->schedule_end);

        $active      = $term->rosterFor($subject->id)->with('student')->get();
        $dropped     = $term->droppedFrom($subject->id)->with('student')->get();
        $droppedIds  = $dropped->pluck('id')->all();
        $enrollments = Enrollment::sortRoster($active->concat($dropped));
        $grades      = FinalGrade::where('subject_id', $subject->id)
            ->whereIn('enrollment_id', $enrollments->pluck('id'))
            ->get()
            ->keyBy('enrollment_id');

        $rows = $enrollments->map(fn ($e) => self::row($e, $grades->get($e->id), in_array($e->id, $droppedIds, true)));

        $groups = [
            'MALE'   => $rows->where('gender', 'male'),
            'FEMALE' => $rows->where('gender', 'female'),
        ];
        $unset = $rows->whereNotIn('gender', ['male', 'female']);
        if ($unset->isNotEmpty()) {
            $groups['GENDER NOT SET'] = $unset;
        }

        $pages = [];
        foreach ($groups as $label => $groupRows) {
            $numbered = $groupRows->values()->map(fn ($r, $i) => $r + ['no' => $i + 1]);
            foreach ($numbered->isEmpty() ? [collect()] : $numbered->chunk(self::ROWS_PER_PAGE) as $chunk) {
                $pages[] = ['label' => $label, 'rows' => $chunk->values()];
            }
        }

        $settings = Setting::rog();
        $teacher  = User::find($pivot->teacher_id);

        return [
            'pages'       => $pages,
            'settings'    => $settings,
            'logoLeft'    => Setting::rogLogoUrl('left'),
            'logoRight'   => Setting::rogLogoUrl('right'),
            'termLine'    => (self::SEMESTERS[$term->semester] ?? $term->semester) . ', School Year ' . $term->academic_year,
            'schedule'    => $schedule,
            'course'      => [
                'Course No.'          => $subject->code,
                'Descriptive Title'   => $subject->name,
                'Course/Year/Section' => trim(($program?->code ?? '') . ' ' . (self::ROMAN[(string) $section->year_number] ?? $section->year_number) . '-' . $section->section_letter),
                'Schedule of Classes' => $schedule,
                'No. of Units'        => rtrim(rtrim(number_format((float) $subject->units, 1, '.', ''), '0'), '.'),
            ],
            'signatories' => [
                ['caption' => 'Certified True and Correct:', 'name' => self::signName($teacher), 'title' => $teacher?->academic_rank ?? ''],
                ['caption' => 'Checked:', 'name' => self::signName($program?->programHead), 'title' => 'Program Head'],
                ['caption' => 'Noted:', 'name' => self::signName($program?->department?->dean), 'title' => 'College Dean'],
                ['caption' => 'Received:', 'name' => $settings['rog_registrar_name'], 'title' => 'Registrar'],
            ],
        ];
    }

    private static function row(Enrollment $enrollment, ?FinalGrade $grade, bool $dropped = false): array
    {
        $student = $enrollment->student;

        if ($dropped) {
            return [
                'gender'  => $student?->gender,
                'name'    => $student?->full_name ?? '',
                'number'  => $student?->student_number ?? '',
                'midterm' => '',
                'finals'  => '',
                'average' => '',
                'remarks' => 'Dr',
            ];
        }
        $inc     = $grade?->remarks === 'incomplete';
        $cell    = fn ($value) => $grade === null ? '' : ($value !== null ? GradeCalculator::formatGrade($value) : ($inc ? 'INC' : ''));

        return [
            'gender'  => $student?->gender,
            'name'    => $student?->full_name ?? '',
            'number'  => $student?->student_number ?? '',
            'midterm' => $cell($grade?->midterm_numerical),
            'finals'  => $cell($grade?->final_numerical),
            'average' => $cell($grade?->average_numerical),
            'remarks' => match ($grade?->remarks) {
                'passed'     => 'Passed',
                'failed'     => 'Failed',
                'incomplete' => 'INC',
                default      => '',
            },
        ];
    }

    /** "ROGER R. ABULENCIA, MIT, PCpE" */
    private static function signName(?User $user): string
    {
        if (!$user) {
            return '';
        }

        $name = mb_strtoupper($user->display_name);

        return filled($user->credentials) ? $name . ', ' . $user->credentials : $name;
    }
}
