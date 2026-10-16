<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\SectionTerm;

class DashboardController extends Controller
{
    public function index()
    {
        $teachingTerms = $this->teachingQuery()->get();

        $classes = $teachingTerms
            ->flatMap(function ($term) {
                $isMaleOrFemale = fn ($e) => in_array($e->student?->gender, ['male', 'female'], true);

                return $term->subjects->map(function ($subject) use ($term, $isMaleOrFemale) {
                    $students = $term->rosterFor($subject->id)->with('student')->get();

                    return [
                        'term'    => $term,
                        'subject' => $subject,
                        'roster'  => [
                            'male'   => Enrollment::sortRoster($students, 'male'),
                            'female' => Enrollment::sortRoster($students, 'female'),
                            'other'  => Enrollment::sortRoster($students->reject($isMaleOrFemale)),
                        ],
                    ];
                });
            })
            ->sortBy(fn ($c) => [
                $c['term']->section->program->code,
                (int) $c['term']->section->year_number,
                strtolower($c['term']->section->section_letter),
                $c['subject']->code,
            ])
            ->values();

        return view('teacher.dashboard', compact('classes'));
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
