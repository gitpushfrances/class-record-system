<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $departmentId = auth()->user()->department_id;

        // Every subject belonging to a program in this Dean's department
        $subjects = Subject::with(['program', 'requester', 'sectionTerms'])
            ->whereHas('program', fn($q) => $q->where('department_id', $departmentId))
            ->orderByDesc('created_at')
            ->paginate(20);

        // Subjects requested by Program Heads in this Dean's department, awaiting Dean approval
        $pendingFromProgramHeads = Subject::with(['program', 'requester'])
            ->whereHas('program', fn($q) => $q->where('department_id', $departmentId))
            ->where('status', 'pending')
            ->whereHas('requester', fn($q) => $q->where('role', 'program_head'))
            ->orderBy('created_at')
            ->get();

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->get();

        return view('dean.subjects.index', compact('subjects', 'pendingFromProgramHeads', 'teachers'));
    }

    public function approve(Subject $subject)
    {
        abort_if($subject->status !== 'pending', 422, 'Subject is not pending.');
        abort_if(
            !$subject->program || $subject->program->department_id !== auth()->user()->department_id,
            403,
            'This subject does not belong to your department.'
        );

        $subject->update([
            'status'          => 'approved',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'rejected_reason' => null,
        ]);

        return redirect()->route('dean.subjects.index')
            ->with('success', "Subject [{$subject->code}] approved.");
    }

    public function reject(Request $request, Subject $subject)
    {
        abort_if($subject->status !== 'pending', 422, 'Subject is not pending.');
        abort_if(
            !$subject->program || $subject->program->department_id !== auth()->user()->department_id,
            403,
            'This subject does not belong to your department.'
        );

        $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        $subject->update([
            'status'          => 'rejected',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'rejected_reason' => $request->rejected_reason,
        ]);

        return redirect()->route('dean.subjects.index')
            ->with('success', "Subject [{$subject->code}] rejected.");
    }
}
