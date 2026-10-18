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

    /** Deactivate or activate. An inactive department is hidden when creating accounts; its records stay. */
    public function toggleStatus(Department $department)
    {
        $department->update(['status' => $department->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', $department->status === 'active'
            ? "{$department->code} activated."
            : "{$department->code} deactivated. Its records are kept.");
    }

    /** Delete a department added by mistake: only while it has no programs and no accounts. */
    public function destroy(Department $department)
    {
        if ($department->programs()->exists() || \App\Models\User::where('department_id', $department->id)->exists()) {
            return back()->with('error', "{$department->code} already has programs or accounts. Deactivate it instead: its records are kept.");
        }

        // For good: a soft-deleted row would keep the code taken (unique rule), so it could not be added again.
        $code = $department->code;
        $department->forceDelete();

        return redirect()->route('admin.departments.index')->with('success', "{$code} deleted.");
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
