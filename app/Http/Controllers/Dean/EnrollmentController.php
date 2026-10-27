<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\SectionTerm;
use App\Models\Enrollment;

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
            'droppedEnrollments' => Enrollment::with('student')
                ->where('section_term_id', $sectionTerm->id)
                ->where('type', Enrollment::TYPE_REGULAR)
                ->where('status', Enrollment::STATUS_DROPPED)
                ->get(),
        ]);
    }
}
