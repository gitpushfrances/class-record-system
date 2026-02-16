<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherApprovalController extends Controller
{
    public function index()
    {
        $pendingTeachers = User::where('role', 'teacher')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.teachers.pending', compact('pendingTeachers'));
    }

    public function approve(User $user)
    {
        $user->update([
            'status' => 'active',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Teacher approved successfully.');
    }

    public function reject(User $user)
    {
        $user->delete();
        return redirect()->back()->with('success', 'Teacher registration rejected.');
    }
}
