<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use App\Models\FacultyBorrowRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Borrowed faculty. A Dean asks the Dean of another department for one of its teachers for the
 * semester. When the lending Dean approves, the borrowing department can assign that teacher to
 * its subjects in that semester only (not as adviser). The loan ends with the semester; nothing is
 * moved or copied, so the teacher keeps their own classes and records.
 * Every method that changes a request returns an error message, or null when it worked.
 */
class FacultyBorrowing
{
    /** Active teachers a department can assign this semester: its own, plus approved loans (those carry "borrowed_from"). */
    public static function teachersFor(?int $departmentId): Collection
    {
        $own = User::where('role', 'teacher')->where('status', 'active')->where('department_id', $departmentId)->get();
        $period = AcademicPeriod::getActive();

        if (!$departmentId || !$period) {
            return $own->sortBy(fn (User $teacher) => mb_strtolower($teacher->name))->values();
        }

        $borrowed = FacultyBorrowRequest::with(['teacher', 'lendingDepartment'])
            ->where('borrowing_department_id', $departmentId)
            ->approved()
            ->forTerm($period->school_year, $period->semester)
            ->get()
            ->map(function (FacultyBorrowRequest $loan) {
                $teacher = $loan->teacher;
                if (!$teacher || $teacher->role !== 'teacher' || $teacher->status !== 'active') {
                    return null;
                }

                return $teacher->setAttribute('borrowed_from', $loan->lendingDepartment?->code ?? 'another department');
            })
            ->filter();

        return $own->concat($borrowed)->unique('id')->sortBy(fn (User $teacher) => mb_strtolower($teacher->name))->values();
    }

    /** True when the teacher belongs to the department or has an approved loan to it for that semester. */
    public static function canAssign(User $teacher, ?int $departmentId, string $academicYear, string $semester): bool
    {
        if (!$departmentId) {
            return false;
        }

        return (int) $teacher->department_id === $departmentId
            || FacultyBorrowRequest::approved()
                ->where('borrowing_department_id', $departmentId)
                ->where('teacher_id', $teacher->id)
                ->forTerm($academicYear, $semester)
                ->exists();
    }

    /** Requests waiting for the signed-in Dean's decision (sidebar badge). */
    public static function pendingIncomingCount(): int
    {
        $departmentId = Scope::departmentId();

        return $departmentId ? FacultyBorrowRequest::pending()->where('lending_department_id', $departmentId)->count() : 0;
    }

    public static function request(User $teacher, int $borrowingDepartmentId, int $requestedBy, ?string $message): ?string
    {
        $period = AcademicPeriod::getActive();
        if (!$period) {
            return 'No active academic period is set. Contact your Administrator.';
        }
        if ($teacher->role !== 'teacher' || $teacher->status !== 'active' || !$teacher->department_id) {
            return 'Select an active teacher of another department.';
        }
        if ((int) $teacher->department_id === $borrowingDepartmentId) {
            return "{$teacher->name} already belongs to your department.";
        }

        $open = FacultyBorrowRequest::whereIn('status', [FacultyBorrowRequest::PENDING, FacultyBorrowRequest::APPROVED])
            ->where('teacher_id', $teacher->id)
            ->where('borrowing_department_id', $borrowingDepartmentId)
            ->forTerm($period->school_year, $period->semester)
            ->exists();
        if ($open) {
            return "A request for {$teacher->name} is already open for {$period->semester}, {$period->school_year}.";
        }

        FacultyBorrowRequest::create([
            'borrowing_department_id' => $borrowingDepartmentId,
            'lending_department_id'   => $teacher->department_id,
            'teacher_id'              => $teacher->id,
            'academic_year'           => $period->school_year,
            'semester'                => $period->semester,
            'status'                  => FacultyBorrowRequest::PENDING,
            'message'                 => filled($message) ? trim($message) : null,
            'requested_by'            => $requestedBy,
        ]);

        return null;
    }

    public static function approve(FacultyBorrowRequest $request, User $decider): ?string
    {
        if ($error = self::notPending($request)) {
            return $error;
        }

        $teacher = $request->teacher;
        if (!$teacher || $teacher->status !== 'active' || (int) $teacher->department_id !== (int) $request->lending_department_id) {
            return 'This teacher is no longer an active member of your department. Decline the request instead.';
        }

        return self::decide($request, FacultyBorrowRequest::APPROVED, $decider, null);
    }

    public static function decline(FacultyBorrowRequest $request, User $decider, ?string $note): ?string
    {
        return self::notPending($request) ?? self::decide($request, FacultyBorrowRequest::DECLINED, $decider, $note);
    }

    /** The borrowing Dean withdraws a request that has not been answered yet. */
    public static function cancel(FacultyBorrowRequest $request): ?string
    {
        if ($error = self::notPending($request)) {
            return $error;
        }

        $request->update(['status' => FacultyBorrowRequest::CANCELLED]);

        return null;
    }

    /** The lending Dean takes back an approved loan, as long as the teacher has no class from it yet. */
    public static function revoke(FacultyBorrowRequest $request, User $decider): ?string
    {
        if ($request->status !== FacultyBorrowRequest::APPROVED) {
            return 'Only an approved loan can be revoked.';
        }

        if ($count = self::assignmentCount($request)) {
            return "{$request->teacher->name} is already assigned to {$count} " . \Illuminate\Support\Str::plural('subject', $count)
                . ' of the borrowing department. Ask its Dean to remove those assignments first.';
        }

        return self::decide($request, FacultyBorrowRequest::REVOKED, $decider, null);
    }

    /** Subjects the borrowed teacher teaches in the borrowing department during the loan's semester. */
    public static function assignmentCount(FacultyBorrowRequest $request): int
    {
        return DB::table('section_subject_teachers')
            ->join('section_terms', 'section_terms.id', '=', 'section_subject_teachers.section_term_id')
            ->join('sections', 'sections.id', '=', 'section_terms.section_id')
            ->join('programs', 'programs.id', '=', 'sections.program_id')
            ->where('section_subject_teachers.teacher_id', $request->teacher_id)
            ->where('programs.department_id', $request->borrowing_department_id)
            ->where('section_terms.academic_year', $request->academic_year)
            ->where('section_terms.semester', $request->semester)
            ->count();
    }

    private static function notPending(FacultyBorrowRequest $request): ?string
    {
        return $request->status === FacultyBorrowRequest::PENDING ? null : 'This request was already answered.';
    }

    private static function decide(FacultyBorrowRequest $request, string $status, User $decider, ?string $note): ?string
    {
        $request->update([
            'status'        => $status,
            'decision_note' => filled($note) ? trim($note) : null,
            'decided_by'    => $decider->id,
            'decided_at'    => now(),
        ]);

        return null;
    }
}
