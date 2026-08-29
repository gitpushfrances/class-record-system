<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Program;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;

class SectionController extends Controller
{
    public function show(Department $department, Program $program, Section $section)
    {
        abort_if($program->department_id !== $department->id, 404);
        abort_if($section->program_id !== $program->id, 404);

        $section->load('program.department');

        $currentTerm = $section->terms()->active()->with(['adviser', 'subjects'])->first();

        $teachers = collect();
        if ($currentTerm) {
            $teacherIds = $currentTerm->subjects->pluck('pivot.teacher_id')->unique();
            $teachers = User::whereIn('id', $teacherIds)->get();
        }

        return view('admin.sections.show', compact('department', 'program', 'section', 'currentTerm', 'teachers'));
    }

    public function students(Department $department, Program $program, Section $section)
    {
        abort_if($program->department_id !== $department->id, 404);
        abort_if($section->program_id !== $program->id, 404);

        $currentTerm = $section->terms()->active()->first();

        $students = collect();
        if ($currentTerm) {
            $students = Student::whereIn('id', function ($q) use ($currentTerm) {
                $q->select('student_id')
                    ->from('enrollments')
                    ->where('section_term_id', $currentTerm->id)
                    ->where('status', 'enrolled');
            })->orderBy('last_name')->get();
        }

        return view('admin.sections.students', compact('department', 'program', 'section', 'students'));
    }
}
