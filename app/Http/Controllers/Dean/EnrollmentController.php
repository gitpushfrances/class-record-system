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

    public function show(Section $section)
    {
        $currentTerm = $section->terms()->where('status', 'active')->with('enrollments.student')->first();
        $enrolledStudentIds = $currentTerm ? $currentTerm->enrollments->pluck('student_id') : collect();
        $availableStudents = Student::whereNotIn('id', $enrolledStudentIds)
            ->orderBy('student_number')
            ->get();

        return view('dean.enrollments.show', compact('section', 'currentTerm', 'availableStudents'));
    }

    public function store(Request $request, Section $section)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $term = $section->terms()->where('status', 'active')->first();

        if (!$term) {
            return back()->with('error', 'This section has no active term. Set one up on the Sections page first.');
        }

        $already = Enrollment::where('section_term_id', $term->id)
            ->where('student_id', $request->student_id)
            ->exists();

        if ($already) {
            return back()->with('error', 'Student is already enrolled in this section for this term.');
        }

        Enrollment::create([
            'student_id'      => $request->student_id,
            'section_term_id' => $term->id,
            'status'          => 'enrolled',
            'enrolled_at'     => now(),
        ]);

        return back()->with('success', 'Student enrolled successfully.');
    }

    public function destroy(Section $section, Enrollment $enrollment)
    {
        $enrollment->delete();
        return back()->with('success', 'Student removed from enrollment.');
    }
}
