<?php

namespace App\Services;

use App\Models\SectionTerm;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Which subjects may be added to a section's term. One rule for every
 * assignment screen (Program Head section page and modal, Dean section page,
 * Dean Assignments):
 *  - approved subjects of the section's own program only;
 *  - 1st / 2nd Semester terms: subjects of that semester; Summer terms: any
 *    subject (back subjects and retakes); subjects with no semester set yet
 *    stay assignable until the Program Head sets one.
 */
class SubjectOffering
{
    /** Assignable subjects not yet in the term: same semester and the section's year level first, unset ones last. */
    public static function forTerm(SectionTerm $term): Collection
    {
        $section  = $term->section;
        $assigned = $term->subjects()->pluck('subjects.id');

        return Subject::where('status', 'approved')
            ->where('program_id', $section->program_id)
            ->whereNotIn('id', $assigned)
            ->when($term->semester !== 'Summer', fn ($q) => $q->where(fn ($q2) => $q2->where('semester', $term->semester)->orWhereNull('semester')))
            ->get()
            ->sortBy(fn ($s) => sprintf(
                '%d-%d-%s',
                $s->semester === null ? 2 : ($s->semester === $term->semester ? 0 : 1),
                $s->year_level === $section->year_level ? 0 : 1,
                $s->code
            ))
            ->values();
    }

    /** Why the subject cannot be added to the term, or null when it can. */
    public static function refusal(SectionTerm $term, Subject $subject): ?string
    {
        $section = $term->section;

        if ($subject->status !== 'approved') {
            return "{$subject->code} is not approved yet.";
        }

        if ((int) $subject->program_id !== (int) $section->program_id) {
            return "{$subject->code} does not belong to the {$section->program->code} program.";
        }

        if ($term->semester !== 'Summer' && $subject->semester !== null && $subject->semester !== $term->semester) {
            return "{$subject->code} is a {$subject->semester} subject; this section's term is {$term->semester}.";
        }

        return null;
    }
}
