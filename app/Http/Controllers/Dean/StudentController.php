<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Program;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $departmentId = auth()->user()->department_id;

        $sort      = in_array($request->get('sort'), ['last_name', 'student_number', 'created_at']) ? $request->get('sort') : 'last_name';
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';

        $programs = Program::where('department_id', $departmentId)
            ->where('status', 'approved')
            ->orderBy('code')
            ->get();

        $students = Student::with('program')
            ->whereHas('program', fn($q) => $q->where('department_id', $departmentId))
            ->when($request->filled('program_id'), fn($q) => $q->where('program_id', $request->get('program_id')))
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->appends($request->query());

        return view('dean.students.index', compact('students', 'programs', 'sort', 'direction'));
    }

    public function create()
    {
        $programs = Program::where('status', 'approved')->orderBy('code')->get();
        return view('dean.students.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_number' => 'required|unique:students,student_number',
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'middle_name'    => 'nullable|string|max:255',
            'year_level'     => 'required|in:1st Year,2nd Year,3rd Year,4th Year,5th Year',
            'student_type'   => 'required|in:regular,irregular',
            'program_id'     => 'required|exists:programs,id',
            'email'          => 'nullable|email|unique:students,email',
        ]);

        $validated['status'] = 'active'; // explicit — don't rely on DB default

        Student::create($validated);

        return redirect()->route('dean.students.index')->with('success', 'Student added successfully.');
    }

    public function edit(Student $student)
    {
        $programs = Program::where('status', 'approved')->orderBy('code')->get();
        return view('dean.students.edit', compact('student', 'programs'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'student_number' => 'required|unique:students,student_number,' . $student->id,
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'middle_name'    => 'nullable|string|max:255',
            'year_level'     => 'required|in:1st Year,2nd Year,3rd Year,4th Year,5th Year',
            'student_type'   => 'required|in:regular,irregular',
            'program_id'     => 'required|exists:programs,id',
            'email'          => 'nullable|email|unique:students,email,' . $student->id,
        ]);

        $student->update($validated);

        return redirect()->route('dean.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        // Remove all enrollments for this student first
        \App\Models\Enrollment::where('student_id', $student->id)->delete();
        $student->delete();
        return redirect()->route('dean.students.index')->with('success', 'Student removed successfully.');
    }
}
