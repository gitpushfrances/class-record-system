<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentNumberGenerator;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $programId = auth()->user()->program_id;
        abort_if(!$programId, 403, 'No program assigned to your account.');

        $sort      = in_array($request->get('sort'), ['last_name', 'student_number', 'created_at']) ? $request->get('sort') : 'last_name';
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';

        $sections = \App\Models\Section::where('program_id', $programId)->orderBy('year_level')->orderBy('section_letter')->get();

        $students = Student::where('program_id', $programId)
            ->when($request->filled('year_level'), fn($q) => $q->where('year_level', $request->get('year_level')))
            ->when($request->get('section_id') === 'unassigned', fn($q) => $q->whereDoesntHave('enrollments.sectionTerm'))
            ->when($request->filled('section_id') && $request->get('section_id') !== 'unassigned', fn($q) => $q->whereHas('enrollments.sectionTerm', fn($q2) => $q2->where('section_id', $request->get('section_id'))))
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->appends($request->query());

        return view('program-head.students.index', compact('students', 'sections', 'sort', 'direction'));
    }

    public function create()
    {
        abort_if(!auth()->user()->program_id, 403, 'No program assigned to your account.');

        return view('program-head.students.create');
    }

    public function store(Request $request, StudentNumberGenerator $generator)
    {
        $programId = auth()->user()->program_id;
        abort_if(!$programId, 403, 'No program assigned to your account.');

        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'middle_name'  => 'nullable|string|max:255',
            'year_level'   => 'required|in:1st Year,2nd Year,3rd Year,4th Year,5th Year',
            'student_type' => 'required|in:regular,irregular',
            'email'        => 'nullable|email|unique:students,email',
        ]);

        $validated['program_id'] = $programId;
        $validated['status']     = 'active';

        try {
            $validated['student_number'] = $generator->generate();
        } catch (\App\Exceptions\NoActiveAcademicPeriodException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        Student::create($validated);

        return redirect()->route('program-head.students.index')->with('success', 'Student added successfully.');
    }

    public function edit(Student $student)
    {
        abort_if(
            $student->program_id !== auth()->user()->program_id,
            403,
            'This student does not belong to your program.'
        );

        return view('program-head.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        abort_if(
            $student->program_id !== auth()->user()->program_id,
            403,
            'This student does not belong to your program.'
        );

        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'middle_name'  => 'nullable|string|max:255',
            'year_level'   => 'required|in:1st Year,2nd Year,3rd Year,4th Year,5th Year',
            'student_type' => 'required|in:regular,irregular',
            'email'        => 'nullable|email|unique:students,email,' . $student->id,
        ]);

        $student->update($validated);

        return redirect()->route('program-head.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        abort_if(
            $student->program_id !== auth()->user()->program_id,
            403,
            'This student does not belong to your program.'
        );

        \App\Models\Enrollment::where('student_id', $student->id)->delete();
        $student->delete();

        return redirect()->route('program-head.students.index')->with('success', 'Student removed successfully.');
    }
}
