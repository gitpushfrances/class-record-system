<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = auth()->user();

        // Get all sections assigned to this teacher
        $classes = Section::where('teacher_id', $teacher->id)
            ->with(['subject', 'enrollments'])
            ->get();

        return view('teacher.dashboard', compact('classes'));
    }
}
