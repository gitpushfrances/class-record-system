<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Services\ClassRecordData;
use App\Services\ReportOfGrades;
use Illuminate\Http\Request;

/**
 * Past Classes: the teacher's subjects in closed terms (Set Term in a new period closes a term).
 * Read-only: the Class Record as it was and the Report of Grades once submitted. Nothing is
 * copied or moved; a closed term keeps its own items, scores, attendance and final grades.
 */
class PastClassController extends Controller
{
    public function index()
    {
        $teacherId = auth()->id();

        $terms = SectionTerm::where('status', 'completed')
            ->whereHas('section')
            ->whereHas('subjects', fn ($q) => $q->where('section_subject_teachers.teacher_id', $teacherId))
            ->with([
                'section.program',
                'verifications',
                'subjects' => fn ($q) => $q->where('section_subject_teachers.teacher_id', $teacherId)->orderBy('subjects.code'),
            ])
            ->orderByDesc('academic_year')
            ->orderByDesc('semester')
            ->get()
            ->groupBy(fn ($term) => $term->semester . ' · ' . $term->academic_year);

        return view('teacher.past-classes.index', compact('terms'));
    }

    public function show(Request $request, SectionTerm $sectionTerm, Subject $subject)
    {
        $this->authorizePastClass($sectionTerm, $subject);

        $section = $sectionTerm->section;
        $config  = $sectionTerm->gradeConfigurationFor($subject->id);
        $rogUrl  = ReportOfGrades::isSubmitted($sectionTerm, $subject)
            ? route('teacher.past-classes.rog', [$sectionTerm, $subject])
            : null;

        if (!$config) {
            return view('teacher.past-classes.no-record', compact('sectionTerm', 'section', 'subject', 'rogUrl'));
        }

        $data = ClassRecordData::build($sectionTerm, $subject, $config, ClassRecordData::cutoffFor($sectionTerm), $request->gender, $request->order);

        return view('teacher.classes.record', $data + [
            'section'     => $section,
            'subject'     => $subject,
            'currentTerm' => $sectionTerm,
            'config'      => $config,
            'locked'      => true,
            'readOnly'    => true,
            'rogUrl'      => $rogUrl,
        ]);
    }

    public function rog(SectionTerm $sectionTerm, Subject $subject)
    {
        $this->authorizePastClass($sectionTerm, $subject);
        abort_unless(ReportOfGrades::isSubmitted($sectionTerm, $subject), 404, 'Grades for this subject were not submitted.');

        return view('rog.show', ReportOfGrades::build($sectionTerm, $subject) + [
            'backUrl' => route('teacher.past-classes.show', [$sectionTerm, $subject]),
        ]);
    }

    /** A closed term in which the signed-in teacher taught this subject. */
    private function authorizePastClass(SectionTerm $sectionTerm, Subject $subject): void
    {
        abort_unless($sectionTerm->status === 'completed', 404);

        abort_unless(
            $sectionTerm->subjects()->wherePivot('teacher_id', auth()->id())->where('subjects.id', $subject->id)->exists(),
            403,
            'You did not teach this subject in this section.'
        );
    }
}
