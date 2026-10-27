<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\FacultyBorrowRequest;
use App\Models\User;
use App\Services\FacultyBorrowing;
use App\Services\Scope;
use Illuminate\Http\Request;

class FacultyBorrowController extends Controller
{
    public function index()
    {
        $departmentId = $this->departmentId();

        $incoming = FacultyBorrowRequest::with(['teacher', 'borrowingDepartment', 'requester'])
            ->where('lending_department_id', $departmentId)
            ->latest('id')
            ->get();

        $mine = FacultyBorrowRequest::with(['teacher', 'lendingDepartment'])
            ->where('borrowing_department_id', $departmentId)
            ->latest('id')
            ->get();

        $teachers = User::with('department')
            ->where('role', 'teacher')
            ->where('status', 'active')
            ->whereNotNull('department_id')
            ->where('department_id', '!=', $departmentId)
            ->orderBy('name')
            ->get()
            ->groupBy(fn (User $teacher) => $teacher->department?->name ?? 'Other');

        return view('dean.borrowing.index', [
            'incoming'    => $incoming,
            'mine'        => $mine,
            'teachers'    => $teachers->sortKeys(),
            'period'      => AcademicPeriod::getActive(),
            'assignments' => $incoming->where('status', FacultyBorrowRequest::APPROVED)
                ->mapWithKeys(fn (FacultyBorrowRequest $loan) => [$loan->id => FacultyBorrowing::assignmentCount($loan)]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'message'    => 'nullable|string|max:500',
        ]);

        $teacher = User::with('department')->findOrFail($validated['teacher_id']);

        if ($error = FacultyBorrowing::request($teacher, $this->departmentId(), $request->user()->id, $validated['message'] ?? null)) {
            return back()->withInput()->with('error', $error);
        }

        return back()->with('success', "Request sent to the Dean of {$teacher->department->name}.");
    }

    public function cancel(FacultyBorrowRequest $borrowRequest)
    {
        abort_if((int) $borrowRequest->borrowing_department_id !== $this->departmentId(), 403, 'This request is not from your department.');

        return $this->respond(FacultyBorrowing::cancel($borrowRequest), 'Request cancelled.');
    }

    public function approve(Request $request, FacultyBorrowRequest $borrowRequest)
    {
        $this->authorizeLender($borrowRequest);

        return $this->respond(FacultyBorrowing::approve($borrowRequest, $request->user()), "{$borrowRequest->teacher->name} can now be assigned by {$borrowRequest->borrowingDepartment->code} this semester.");
    }

    public function decline(Request $request, FacultyBorrowRequest $borrowRequest)
    {
        $this->authorizeLender($borrowRequest);
        $validated = $request->validate(['decision_note' => 'nullable|string|max:500']);

        return $this->respond(FacultyBorrowing::decline($borrowRequest, $request->user(), $validated['decision_note'] ?? null), 'Request declined.');
    }

    public function revoke(Request $request, FacultyBorrowRequest $borrowRequest)
    {
        $this->authorizeLender($borrowRequest);

        return $this->respond(FacultyBorrowing::revoke($borrowRequest, $request->user()), 'Loan revoked.');
    }

    private function departmentId(): int
    {
        $departmentId = Scope::departmentId();
        abort_if(!$departmentId, 403, 'No department assigned to your account.');

        return $departmentId;
    }

    private function authorizeLender(FacultyBorrowRequest $borrowRequest): void
    {
        abort_if((int) $borrowRequest->lending_department_id !== $this->departmentId(), 403, 'This request is not for your department.');
    }

    private function respond(?string $error, string $success)
    {
        return $error ? back()->with('error', $error) : back()->with('success', $success);
    }
}
