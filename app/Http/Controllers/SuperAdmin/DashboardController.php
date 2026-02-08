<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Section;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_deans' => User::where('role', 'dean')->count(),
            'total_teachers' => User::where('role', 'teacher')->where('status', 'active')->count(),
            'pending_approvals' => User::where('role', 'teacher')->where('status', 'pending')->count(),
            'total_students' => Student::count(),
            'total_subjects' => Subject::count(),
            'total_sections' => Section::count(),
        ];

        return view('super-admin.dashboard', compact('stats'));
    }
}
