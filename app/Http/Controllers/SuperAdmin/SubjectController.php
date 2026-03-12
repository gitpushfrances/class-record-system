<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $pending  = Subject::with('requester')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $approved = Subject::with('requester', 'approver')
            ->where('status', 'approved')
            ->orderByDesc('approved_at')
            ->paginate(20);

        return view('admin.subjects.index', compact('pending', 'approved'));
    }

    public function approve(Subject $subject)
    {
        abort_if($subject->status !== 'pending', 422, 'Subject is not pending.');

        $subject->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejected_reason' => null,
        ]);

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject [{$subject->code}] approved.");
    }

    public function reject(Request $request, Subject $subject)
    {
        abort_if($subject->status !== 'pending', 422, 'Subject is not pending.');

        $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        $subject->update([
            'status'          => 'rejected',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'rejected_reason' => $request->rejected_reason,
        ]);

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject [{$subject->code}] rejected.");
    }
}
