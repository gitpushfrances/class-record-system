<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Services\ReportOfGrades;

class ReportOfGradesController extends Controller
{
    public function teacher(Section $section, Subject $subject)
    {
        $term = $this->teacherTerm($section, $subject);

        if (!ReportOfGrades::isSubmitted($term, $subject)) {
            return redirect()->route('teacher.grades.final', [$section, $subject])
                ->with('warning', 'Submit the grades for verification first. The Report of Grades uses the submitted grades.');
        }

        return view('rog.show', ReportOfGrades::build($term, $subject) + [
            'backUrl' => route('teacher.grades.final', [$section, $subject]),
        ]);
    }

    public function programHead(SectionTerm $sectionTerm, Subject $subject)
    {
        $sectionTerm->loadMissing('section');
        abort_if((int) $sectionTerm->section->program_id !== (int) \App\Services\Scope::programId(), 403);

        return $this->readOnly($sectionTerm, $subject, route('program-head.dashboard'));
    }

    public function dean(SectionTerm $sectionTerm, Subject $subject)
    {
        $sectionTerm->loadMissing('section.program');
        abort_if((int) $sectionTerm->section->program?->department_id !== (int) \App\Services\Scope::departmentId(), 403);

        return $this->readOnly($sectionTerm, $subject, route('dean.sections.show', $sectionTerm->section));
    }

    private function readOnly(SectionTerm $term, Subject $subject, string $backUrl)
    {
        abort_unless(ReportOfGrades::isSubmitted($term, $subject), 404, 'The teacher has not submitted grades for this subject yet.');

        return view('rog.show', ReportOfGrades::build($term, $subject) + ['backUrl' => $backUrl]);
    }

    private function teacherTerm(Section $section, Subject $subject): SectionTerm
    {
        $term = $section->terms()->where('status', 'active')->first();
        abort_if(!$term, 403, 'No active term for this section.');

        abort_unless(
            $term->subjects()->wherePivot('teacher_id', auth()->id())->where('subjects.id', $subject->id)->exists(),
            403,
            'You are not assigned to teach this subject in this section.'
        );

        return $term;
    }
}
