<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\Promotion;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Batch year-level update: move students of a section's last closed term into
 * a target section's term for the active academic period. Same section = next
 * semester; a higher-year section = promotion. The student's year level becomes
 * the target section's year level. Nobody is moved unless ticked.
 */
class BatchPromotion
{
    /** The section's most recent closed term (the term students are promoted from). */
    public static function sourceTerm(Section $section): ?SectionTerm
    {
        return $section->terms()->where('status', 'completed')->orderByDesc('id')->first();
    }

    /** Active-period terms of the program's active sections that can receive students. */
    public static function targetTerms(Section $section): Collection
    {
        $period = AcademicPeriod::getActive();
        if (!$period) {
            return collect();
        }

        return SectionTerm::with('section')
            ->where('status', 'active')
            ->where('academic_year', $period->school_year)
            ->where('semester', $period->semester)
            ->whereHas('section', fn ($q) => $q->where('program_id', $section->program_id)->where('status', 'active'))
            ->get()
            ->sortBy(fn ($t) => sprintf('%d-%s', $t->section->year_number, $t->section->section_letter))
            ->values();
    }

    /**
     * Students of the source term who already sit in a regular class list of the active period: they have been moved,
     * so they are not offered again. A student who could not be moved is not in a class list yet and stays.
     */
    public static function movedStudentIds(SectionTerm $source): Collection
    {
        $period = AcademicPeriod::getActive();
        if (!$period) {
            return collect();
        }

        return Enrollment::whereIn('student_id', $source->enrollments()->pluck('student_id'))
            ->where('type', Enrollment::TYPE_REGULAR)
            ->where('status', Enrollment::STATUS_ENROLLED)
            ->whereHas('sectionTerm', fn ($q) => $q
                ->where('status', 'active')
                ->where('academic_year', $period->school_year)
                ->where('semester', $period->semester))
            ->pluck('student_id')
            ->unique()
            ->values();
    }

    /** Members of the source term still waiting to be moved. */
    public static function remainingCount(SectionTerm $source): int
    {
        return $source->enrollments()->whereNotIn('student_id', self::movedStudentIds($source))->count();
    }

    /** Source-term members not moved yet, with their INC / failed subjects (subject codes) for the review list. */
    public static function candidates(SectionTerm $source): Collection
    {
        $moved   = self::movedStudentIds($source);
        $members = Enrollment::sortRoster($source->enrollments()->with('student')->get())
            ->reject(fn ($e) => $moved->contains($e->student_id))
            ->values();
        $flags   = FinalGrade::with('subject')
            ->whereIn('enrollment_id', $members->pluck('id'))
            ->whereIn('remarks', ['failed', 'incomplete'])
            ->get()
            ->groupBy('enrollment_id');

        return $members->map(fn ($e) => [
            'enrollment' => $e,
            'failed'     => ($flags[$e->id] ?? collect())->where('remarks', 'failed')->map(fn ($g) => $g->subject?->code)->filter()->values(),
            'incomplete' => ($flags[$e->id] ?? collect())->where('remarks', 'incomplete')->map(fn ($g) => $g->subject?->code)->filter()->values(),
        ]);
    }

    /** @return array{promoted: int, skipped: string[]} */
    public static function promote(SectionTerm $source, SectionTerm $target, array $studentIds): array
    {
        return DB::transaction(function () use ($source, $target, $studentIds) {
            $yearLevel = $target->section->year_level;
            $students  = Student::whereIn('id', $source->enrollments()->whereIn('student_id', $studentIds)->pluck('student_id'))->get();
            $promoted  = [];
            $skipped   = [];

            foreach ($students as $student) {
                if (SectionEnrollment::enroll($target, $student) !== null) {
                    $skipped[] = $student;
                    continue;
                }
                $student->update(['year_level' => $yearLevel]);
                $promoted[] = $student->id;
            }

            Promotion::create([
                'source_section_term_id' => $source->id,
                'target_section_term_id' => $target->id,
                'year_level'             => $yearLevel,
                'student_ids'            => $promoted,
                'skipped_ids'            => array_map(fn ($s) => $s->id, $skipped),
                'promoted_by'            => auth()->id(),
            ]);

            return ['promoted' => count($promoted), 'skipped' => array_map(fn ($s) => $s->full_name, $skipped)];
        });
    }
}
