<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SectionTerm;

class DashboardController extends Controller
{
    public function index()
    {
        $advisoryTerms = $this->advisoryQuery()->get();
        $teachingTerms = $this->teachingQuery()->get();

        return view('teacher.dashboard', compact('advisoryTerms', 'teachingTerms'));
    }

    public function advisory()
    {
        $advisoryTerms = $this->advisoryQuery()->get();

        return view('teacher.advisory', compact('advisoryTerms'));
    }

    public function teaching()
    {
        $teachingTerms = $this->teachingQuery()->get();

        return view('teacher.teaching', compact('teachingTerms'));
    }

    private function advisoryQuery()
    {
        $teacher = auth()->user();

        return SectionTerm::where('adviser_id', $teacher->id)
            ->where('status', 'active')
            ->whereHas('section')
            ->with([
                'section.program.department',
                'enrollments',
            ]);
    }

    private function teachingQuery()
    {
        $teacher = auth()->user();

        return SectionTerm::where('status', 'active')
            ->whereHas('section')
            ->whereHas('subjects', fn($q) => $q->where('section_subject_teachers.teacher_id', $teacher->id))
            ->with([
                'section.program.department',
                'enrollments',
                'subjects' => fn($q) => $q->where('section_subject_teachers.teacher_id', $teacher->id),
            ]);
    }
}
