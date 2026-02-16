<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Student;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index()
    {
        $sections = Section::with(['subject', 'teacher', 'enrollments'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'desc')
            ->paginate(20);

        return view('dean.enrollments.index', compact('sections'));
    }

    public function show(Section $section)
    {
        $section->load(['subject', 'teacher', 'enrollments.student']);
        $enrolledStudentIds = $section->enrollments->pluck('student_id');
        $availableStudents = Student::whereNotIn('id', $enrolledStudentIds)
            ->orderBy('student_number')
            ->get();

        return view('dean.enrollments.show', compact('section', 'availableStudents'));
    }

    public function store(Request $request, Section $section)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        foreach ($validated['student_ids'] as $studentId) {
            Enrollment::firstOrCreate([
                'student_id' => $studentId,
                'section_id' => $section->id,
            ]);
        }

        return redirect()->route('dean.enrollments.show', $section)
            ->with('success', 'Students enrolled successfully.');
    }

    public function destroy(Section $section, Enrollment $enrollment)
    {
        $enrollment->delete();

        return redirect()->route('dean.enrollments.show', $section)
            ->with('success', 'Student removed from section.');
    }
}
