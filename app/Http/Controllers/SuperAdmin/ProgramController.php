<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\User;

class ProgramController extends Controller
{
    public function show(Department $department, Program $program)
    {
        abort_if($program->department_id !== $department->id, 404);

        $program->load('programHead', 'department');

        $sections = $program->sections()
            ->with(['terms' => function ($q) {
                $q->active()->with('adviser');
            }])
            ->get()
            ->map(function ($section) {
                $currentTerm = $section->terms->first();

                $section->current_adviser = $currentTerm?->adviser;

                $section->student_count = $currentTerm
                    ? Enrollment::where('status', 'enrolled')
                        ->where('section_term_id', $currentTerm->id)
                        ->count()
                    : 0;

                return $section;
            });

        return view('admin.programs.show', compact('department', 'program', 'sections'));
    }
}
