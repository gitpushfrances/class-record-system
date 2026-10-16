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

    public function store(\Illuminate\Http\Request $request)
    {
        $data = $this->validated($request);

        $department = Department::create($data + ['status' => 'active']);

        return redirect()->route('admin.departments.show', $department)->with('success', "{$department->code} created. Add its programs below.");
    }

    public function update(\Illuminate\Http\Request $request, Department $department)
    {
        $department->update($this->validated($request, $department->id));

        return back()->with('success', "{$department->code} updated.");
    }

    private function validated(\Illuminate\Http\Request $request, ?int $ignoreId = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->code)), 'name' => trim((string) $request->name)]);

        return $request->validate([
            'code' => ['required', 'max:20', 'regex:/^[A-Z0-9-]+$/', \Illuminate\Validation\Rule::unique('departments', 'code')->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('departments', 'name')->ignore($ignoreId)],
        ], [
            'code.regex'  => 'The code may only contain letters, numbers and dashes.',
            'code.unique' => 'Another department already uses this code.',
            'name.unique' => 'Another department already uses this name.',
        ]);
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
