<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'pending_teachers' => User::where('role', 'teacher')->where('status', 'pending')->count(),
            'total_teachers' => User::where('role', 'teacher')->where('status', 'active')->count(),
            'total_sections' => Section::count(),
            'total_students' => Student::count(),
        ];

        $pendingTeachers = User::where('role', 'teacher')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        return view('dean.dashboard', compact('stats', 'pendingTeachers'));
    }
}
