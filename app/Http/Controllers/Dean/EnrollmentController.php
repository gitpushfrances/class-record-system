<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Student;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index()
    {
        $sectionTerms = SectionTerm::with(['section.program.department', 'adviser', 'enrollments'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'desc')
            ->paginate(20);

        return view('dean.enrollments.index', compact('sectionTerms'));
    }

    public function show(SectionTerm $sectionTerm)
    {
        $sectionTerm->load(['section.program', 'enrollments.student']);
        $enrolledStudentIds = $sectionTerm->enrollments->pluck('student_id');
        $availableStudents = Student::whereNotIn('id', $enrolledStudentIds)
            ->orderBy('student_number')
            ->get();

        return view('dean.enrollments.show', [
            'section'            => $sectionTerm->section,
            'currentTerm'        => $sectionTerm,
            'availableStudents'  => $availableStudents,
        ]);
    }

    public function store(Request $request, SectionTerm $sectionTerm)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $already = Enrollment::where('section_term_id', $sectionTerm->id)
            ->where('student_id', $request->student_id)
            ->exists();

        if ($already) {
            return back()->with('error', 'Student is already enrolled in this section for this term.');
        }

        Enrollment::create([
            'student_id'      => $request->student_id,
            'section_term_id' => $sectionTerm->id,
            'status'          => 'enrolled',
            'enrolled_at'     => now(),
        ]);

        return back()->with('success', 'Student enrolled successfully.');
    }

    public function destroy(SectionTerm $sectionTerm, Enrollment $enrollment)
    {
        $enrollment->delete();
        return back()->with('success', 'Student removed from enrollment.');
    }
}
