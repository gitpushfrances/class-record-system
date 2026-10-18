<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\StudentGrade;
use Illuminate\Support\Collection;

/**
 * Section membership (regular enrollment), shared by the Program Head and the Dean.
 * Dropping keeps the record (grades, attendance, "Dr" on the ROG); enrolling the
 * same student again restores it.
 *
 * The class list is locked while any subject of the term is submitted (pending)
 * or verified: a change would alter a finished record. The Program Head rejects
 * the submission first (a verified one is unverified, then rejected).
 *
 * A student belongs to one section per semester (regular enrollment); irregular
 * students are added per subject by the subject teacher. Remove deletes an
 * enrollment made by mistake, and only while it has no scores, attendance or
 * final grades; otherwise the student is dropped.
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

        if ($other = self::otherSection($term, $student)) {
            return "{$student->full_name} is already enrolled in {$other->section->full_name} for {$other->semester} {$other->academic_year}. "
                . 'A student belongs to one section per semester; drop them there first.';
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

    /** Returns an error message, or null when the enrollment was deleted. For a student enrolled by mistake. */
    public static function remove(Enrollment $enrollment): ?string
    {
        if ($locked = self::lockMessage($enrollment->sectionTerm)) {
            return $locked;
        }

        if (!in_array($enrollment->id, self::removableIds($enrollment->sectionTerm, collect([$enrollment])), true)) {
            return "{$enrollment->student->full_name} already has scores, attendance or a final grade in this section. Use Drop instead: the records are kept.";
        }

        $enrollment->delete();

        return null;
    }

    /** Ids of the given enrollments that may be removed: no scores, attendance or final grades (deleted ones included) and no submitted subject. */
    public static function removableIds(SectionTerm $term, Collection $enrollments): array
    {
        if (self::lockedSubjects($term) || $enrollments->isEmpty()) {
            return [];
        }

        $ids  = $enrollments->pluck('id')->map(fn ($id) => (int) $id);
        $used = StudentGrade::withTrashed()->whereIn('enrollment_id', $ids)->pluck('enrollment_id')
            ->merge(AttendanceRecord::withTrashed()->whereIn('enrollment_id', $ids)->pluck('enrollment_id'))
            ->merge(FinalGrade::withTrashed()->whereIn('enrollment_id', $ids)->pluck('enrollment_id'))
            ->map(fn ($id) => (int) $id);

        return $ids->diff($used)->values()->all();
    }

    /** Active students of the section's program who have no section yet this semester (and are not in this one, dropped or not). */
    public static function availableStudents(SectionTerm $term): Collection
    {
        $taken = Enrollment::where('type', Enrollment::TYPE_REGULAR)
            ->where(fn ($q) => $q->where('section_term_id', $term->id)
                ->orWhere(fn ($other) => $other->where('status', '!=', Enrollment::STATUS_DROPPED)
                    ->whereHas('sectionTerm', fn ($t) => $t->where('status', 'active')
                        ->where('academic_year', $term->academic_year)
                        ->where('semester', $term->semester))))
            ->pluck('student_id');

        return Student::where('program_id', $term->section->program_id)
            ->where('status', 'active')
            ->whereNotIn('id', $taken)
            ->orderBy('last_name')->orderBy('first_name')
            ->get();
    }

    /** The student's other active section term in the same semester, if any. */
    private static function otherSection(SectionTerm $term, Student $student): ?SectionTerm
    {
        return SectionTerm::with('section.program')
            ->where('id', '!=', $term->id)
            ->where('status', 'active')
            ->where('academic_year', $term->academic_year)
            ->where('semester', $term->semester)
            ->whereHas('enrollments', fn ($q) => $q->where('student_id', $student->id))
            ->first();
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
