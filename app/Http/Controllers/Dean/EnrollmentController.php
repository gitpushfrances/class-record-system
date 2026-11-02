<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\SectionTerm;
use App\Models\Enrollment;

class EnrollmentController extends Controller
{
    public function index()
    {
        $departmentId = \App\Services\Scope::departmentId();
        $period       = AcademicPeriod::getActive();

        $terms = fn () => SectionTerm::with(['section.program.department', 'adviser', 'enrollments'])
            ->whereHas('section.program', fn ($q) => $q->where('department_id', $departmentId));

        // The semester that is active now, listed by program, year and section.
        $current = $period
            ? $terms()
                ->where('academic_year', $period->school_year)
                ->where('semester', $period->semester)
                ->get()
                ->sortBy(fn (SectionTerm $term) => sprintf('%s-%02d-%s', $term->section->program->code, $term->section->year_number, $term->section->section_letter))
                ->values()
            : collect();

        // Every other semester stays available, but apart from the current one.
        $past = $terms()
            ->when($period, fn ($q) => $q->where(fn ($q2) => $q2->where('academic_year', '!=', $period->school_year)->orWhere('semester', '!=', $period->semester)))
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'desc')
            ->paginate(20, ['*'], 'past');

        return view('dean.enrollments.index', compact('current', 'past', 'period'));
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
