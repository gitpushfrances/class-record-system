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
        $departmentId = \App\Services\Scope::departmentId();

        $sectionTerms = SectionTerm::with(['section.program.department', 'adviser', 'enrollments'])
            ->whereHas('section.program', fn($q) => $q->where('department_id', $departmentId))
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'desc')
            ->paginate(20);

        return view('dean.enrollments.index', compact('sectionTerms'));
    }

    public function show(SectionTerm $sectionTerm)
    {
        abort_if(
            $sectionTerm->section->program->department_id !== \App\Services\Scope::departmentId(),
            403,
            'This section does not belong to your department.'
        );

        $sectionTerm->load(['section.program', 'enrollments.student']);

        return view('dean.enrollments.show', [
            'section'            => $sectionTerm->section,
            'currentTerm'        => $sectionTerm,
            'availableStudents'  => \App\Services\SectionEnrollment::availableStudents($sectionTerm),
            'removableIds'       => \App\Services\SectionEnrollment::removableIds($sectionTerm, $sectionTerm->enrollments),
            'droppedEnrollments' => Enrollment::with('student')
                ->where('section_term_id', $sectionTerm->id)
                ->where('type', Enrollment::TYPE_REGULAR)
                ->where('status', Enrollment::STATUS_DROPPED)
                ->get(),
        ]);
    }

    public function store(Request $request, SectionTerm $sectionTerm)
    {
        abort_if(
            $sectionTerm->section->program->department_id !== \App\Services\Scope::departmentId(),
            403,
            'This section does not belong to your department.'
        );

        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($request->student_id);

        if ($error = \App\Services\SectionEnrollment::enroll($sectionTerm, $student)) {
            return back()->with('error', $error);
        }

        return back()->with('success', "{$student->full_name} enrolled.");
    }

    public function destroy(SectionTerm $sectionTerm, Enrollment $enrollment)
    {
        abort_if(
            $sectionTerm->section->program->department_id !== \App\Services\Scope::departmentId(),
            403,
            'This section does not belong to your department.'
        );
        abort_if($enrollment->section_term_id !== $sectionTerm->id, 404);

        if ($error = \App\Services\SectionEnrollment::drop($enrollment)) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Student dropped from the section. Grades and attendance are kept.');
    }

    public function remove(SectionTerm $sectionTerm, Enrollment $enrollment)
    {
        abort_if(
            $sectionTerm->section->program->department_id !== \App\Services\Scope::departmentId(),
            403,
            'This section does not belong to your department.'
        );
        abort_unless($sectionTerm->enrollments()->whereKey($enrollment->id)->exists(), 404);

        $name = $enrollment->student->full_name;
        if ($error = \App\Services\SectionEnrollment::remove($enrollment)) {
            return back()->with('error', $error);
        }

        return back()->with('success', "{$name} removed from the section.");
    }
}
