<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\SectionTerm;
use App\Models\Student;

/**
 * Section membership (regular enrollment), shared by the Program Head and the Dean.
 * Dropping keeps the record (grades, attendance, "Dr" on the ROG); enrolling the
 * same student again restores it.
 *
 * The class list is locked while any subject of the term is submitted (pending)
 * or verified: a change would alter a finished record. The Program Head rejects
 * the submission first (a verified one is unverified, then rejected).
 */
class SectionEnrollment
{
    /** Returns an error message, or null when the student was enrolled. */
    public static function enroll(SectionTerm $term, Student $student): ?string
    {
        if ($locked = self::lockMessage($term)) {
            return $locked;
        }

        if ((int) $student->program_id !== (int) $term->section->program_id) {
            return "{$student->full_name} does not belong to this section's program.";
        }

        $existing = Enrollment::where('section_term_id', $term->id)->where('student_id', $student->id)->first();

        if ($existing && $existing->type === Enrollment::TYPE_REGULAR && $existing->status !== Enrollment::STATUS_DROPPED) {
            return "{$student->full_name} is already enrolled in this section for this term.";
        }

        Enrollment::updateOrCreate(
            ['student_id' => $student->id, 'section_term_id' => $term->id],
            ['type' => Enrollment::TYPE_REGULAR, 'status' => Enrollment::STATUS_ENROLLED, 'enrolled_at' => $existing?->enrolled_at ?? now()]
        );

        return null;
    }

    /** Returns an error message, or null when the student was dropped. */
    public static function drop(Enrollment $enrollment): ?string
    {
        if ($locked = self::lockMessage($enrollment->sectionTerm)) {
            return $locked;
        }

        $enrollment->update(['status' => Enrollment::STATUS_DROPPED]);

        return null;
    }

    /** Codes of the subjects whose grades are submitted (pending) or verified in this term. */
    public static function lockedSubjects(SectionTerm $term): array
    {
        return $term->verifications()->whereIn('status', ['pending', 'verified'])->with('subject')->get()
            ->map(fn ($v) => $v->subject?->code)->filter()->sort()->values()->all();
    }

    private static function lockMessage(SectionTerm $term): ?string
    {
        $codes = self::lockedSubjects($term);

        return $codes
            ? 'The class list is locked: grades are already submitted for ' . implode(', ', $codes)
              . '. Reject those submissions first (unverify a verified one, then reject it), then add or drop the student.'
            : null;
    }
}
