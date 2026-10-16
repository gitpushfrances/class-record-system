<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\SectionTerm;
use App\Models\Student;

/**
 * Section membership (regular enrollment), shared by the Program Head and the Dean.
 * Dropping keeps the record (grades, attendance, "Dr" on the ROG); enrolling the
 * same student again restores it.
 */
class SectionEnrollment
{
    /** Returns an error message, or null when the student was enrolled. */
    public static function enroll(SectionTerm $term, Student $student): ?string
    {
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

    public static function drop(Enrollment $enrollment): void
    {
        $enrollment->update(['status' => Enrollment::STATUS_DROPPED]);
    }
}
