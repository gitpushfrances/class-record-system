<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('dean')
            ->withCount('programs')
            ->get()
            ->map(function ($department) {
                $department->teacher_count = $department->activeFacultyIds()->count();
                $department->student_count = $department->activeStudentCount();

                return $department;
            });

        return view('admin.departments.index', compact('departments'));
    }

    public function show(Department $department)
    {
        $department->load('dean');

        $programs = $department->programs()
            ->with('programHead')
            ->withCount('sections')
            ->get()
            ->map(function ($program) {
                $program->student_count = $program->activeStudentCount();
                $program->teacher_count = $program->activeFacultyIds()->count();

                return $program;
            });

        return view('admin.departments.show', compact('department', 'programs'));
    }
}
