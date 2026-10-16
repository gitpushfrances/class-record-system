<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\User;

class ProgramController extends Controller
{
    public function store(\Illuminate\Http\Request $request, Department $department)
    {
        $program = $department->programs()->create($this->validated($request) + [
            'status'       => 'approved',
            'requested_by' => auth()->id(),
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);

        return back()->with('success', "{$program->code} added to {$department->code}.");
    }

    public function update(\Illuminate\Http\Request $request, Department $department, Program $program)
    {
        abort_if($program->department_id !== $department->id, 404);

        $program->update($this->validated($request, $program->id));

        return back()->with('success', "{$program->code} updated.");
    }

    private function validated(\Illuminate\Http\Request $request, ?int $ignoreId = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->code)), 'name' => trim((string) $request->name)]);

        return $request->validate([
            'code' => ['required', 'max:20', 'regex:/^[A-Z0-9-]+$/', \Illuminate\Validation\Rule::unique('programs', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'code.regex'  => 'The code may only contain letters, numbers and dashes.',
            'code.unique' => 'Another program already uses this code.',
        ]);
    }

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
