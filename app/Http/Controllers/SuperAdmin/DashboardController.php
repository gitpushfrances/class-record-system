<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Section;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'deans' => User::where('role', 'dean')->count(),
            'teachers' => User::where('role', 'teacher')->where('status', 'active')->count(),
            'pending_teachers' => User::where('role', 'teacher')->where('status', 'pending')->count(),
            'students' => Student::count(),
            'subjects' => Subject::count(),
            'sections' => Section::count(),
        ];

        $pendingTeachers = User::where('role', 'teacher')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'pendingTeachers'));
    }
}
