<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::orderBy('student_number')->paginate(20);
        return view('dean.students.index', compact('students'));
    }

    public function create()
    {
        return view('dean.students.create');
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
            'program'        => 'nullable|string|max:255',
            'email'          => 'nullable|email|unique:students,email',
        ]);

        $validated['status'] = 'active'; // explicit — don't rely on DB default

        Student::create($validated);

        return redirect()->route('dean.students.index')->with('success', 'Student added successfully.');
    }

    public function edit(Student $student)
    {
        return view('dean.students.edit', compact('student'));
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
            'program'        => 'nullable|string|max:255',
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
