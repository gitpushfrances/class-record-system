<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherApprovalController extends Controller
{
    public function index()
    {
        $pendingTeachers = User::where('role', 'teacher')
            ->where('status', 'pending')
            ->where('department_id', auth()->user()->department_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dean.teachers.pending', compact('pendingTeachers'));
    }

    public function approve(User $user)
    {
        abort_if($user->role !== 'teacher', 404);
        abort_if($user->department_id !== auth()->user()->department_id, 403, 'This teacher does not belong to your department.');

        $user->update([
            'status' => 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Teacher approved successfully.');
    }

    public function reject(User $user)
    {
        abort_if($user->role !== 'teacher', 404);
        abort_if($user->department_id !== auth()->user()->department_id, 403, 'This teacher does not belong to your department.');

        $user->update(['status' => 'rejected']);
        return redirect()->back()->with('success', 'Teacher registration rejected.');
    }
}
