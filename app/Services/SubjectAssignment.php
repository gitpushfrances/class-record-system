<?php

namespace App\Services;

use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Assigns a subject with its teacher and schedule to a section term, and updates it.
 * The one place for these rules, used by the Program Head (section page) and the Dean
 * (section page and Assignments). A subject is assigned once per term; a subject with a
 * laboratory keeps its laboratory schedule on the same assignment.
 */
class SubjectAssignment
{
    public static function rules(): array
    {
        return [
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
        ] + ClassSchedule::allRules();
    }

    /** Returns [field => message] when refused, or null when the subject was added. */
    public static function attach(SectionTerm $term, Request $request): ?array
    {
        $subject = Subject::findOrFail($request->input('subject_id'));

        if ($refusal = SubjectOffering::refusal($term, $subject)) {
            return ['subject_id' => $refusal];
        }

        if ($term->subjects()->where('subjects.id', $subject->id)->exists()) {
            return ['subject_id' => "{$subject->code} is already assigned to this section. Use Edit to change its teacher or schedule."];
        }

        return self::save($term, $subject, $request, fn (array $columns) => $term->subjects()->attach($subject->id, $columns));
    }

    /** Returns [field => message] when refused, or null when the assignment was updated. */
    public static function update(SectionTerm $term, Request $request): ?array
    {
        $subject = $term->subjects()->where('subjects.id', $request->input('subject_id'))->first();
        abort_if(!$subject, 404, 'This subject is not assigned to this section.');

        return self::save($term, $subject, $request, fn (array $columns) => $term->subjects()->updateExistingPivot($subject->id, $columns));
    }

    private static function save(SectionTerm $term, Subject $subject, Request $request, callable $write): ?array
    {
        $teacher = User::whereKey($request->input('teacher_id'))->where('role', 'teacher')->where('status', 'active')->first();
        if (!$teacher) {
            return ['teacher_id' => 'Select an active teacher.'];
        }

        $departmentId = $term->section->program->department_id;
        if (!FacultyBorrowing::canAssign($teacher, $departmentId ? (int) $departmentId : null, $term->academic_year, $term->semester)) {
            return ['teacher_id' => "{$teacher->name} belongs to another department. Request them under Borrowed Faculty and wait for approval."];
        }

        $columns = ClassSchedule::columnsFromRequest($request, (bool) $subject->has_laboratory);

        if ($conflict = ClassSchedule::conflict($term->id, $subject->id, $columns)) {
            return ['schedule_days' => $conflict];
        }

        $write(['teacher_id' => (int) $request->input('teacher_id')] + $columns);

        return null;
    }
}
