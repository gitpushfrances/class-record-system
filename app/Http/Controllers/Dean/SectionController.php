<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\User;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $departmentId = \App\Services\Scope::departmentId();

        $sections = Section::with([
                'program.department',
                'terms' => fn($q) => $q->where('status', 'active')->with(['adviser', 'enrollments', 'subjects']),
            ])
            ->whereHas('program', fn($q) => $q->where('department_id', $departmentId))
            ->where('status', 'active')
            ->orderBy('year_level')
            ->orderBy('section_letter')
            ->get()
            ->groupBy(fn($section) => $section->program->code)
            ->sortKeys();

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->get();
        $activePeriod = AcademicPeriod::getActive();

        return view('dean.sections.index', compact('sections', 'teachers', 'activePeriod'));
    }

    public function show(Section $section)
    {
        abort_if(
            $section->program->department_id !== \App\Services\Scope::departmentId(),
            403,
            'This section does not belong to your department.'
        );

        $section->load(['program.department', 'terms.adviser', 'terms.enrollments.student', 'terms.subjects.teachers']);
        $currentTerm = $section->terms->where('status', 'active')->first();

        $availableSubjects = $currentTerm ? \App\Services\SubjectOffering::forTerm($currentTerm) : collect();

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->where('department_id', \App\Services\Scope::departmentId())
            ->orderBy('name')
            ->get();

        return view('dean.sections.show', compact('section', 'currentTerm', 'availableSubjects', 'teachers'));
    }

    public function attachSubject(Request $request, Section $section)
    {
        abort_if(
            $section->program->department_id !== \App\Services\Scope::departmentId(),
            403,
            'This section does not belong to your department.'
        );

        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(!$currentTerm, 422, 'This section has no active term.');

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            ...\App\Services\ClassSchedule::rules(),
        ], [], \App\Services\ClassSchedule::ATTRIBUTES);

        if ($refusal = \App\Services\SubjectOffering::refusal($currentTerm, \App\Models\Subject::findOrFail($request->subject_id))) {
            return back()->withErrors(['subject_id' => $refusal])->withInput();
        }

        $slot = \App\Services\ClassSchedule::fromRequest($request);
        if ($conflict = \App\Services\ClassSchedule::conflict($currentTerm->id, (int) $request->subject_id, $slot)) {
            return back()->withErrors(['schedule_days' => $conflict])->withInput();
        }

        $currentTerm->subjects()->syncWithoutDetaching([
            $request->subject_id => ['teacher_id' => $request->teacher_id] + $slot,
        ]);

        return redirect()->route('dean.sections.show', $section)->with('success', 'Subject added and teacher assigned.');
    }

    public function changeSubjectTeacher(Request $request, Section $section)
    {
        abort_if(
            $section->program->department_id !== \App\Services\Scope::departmentId(),
            403,
            'This section does not belong to your department.'
        );

        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(!$currentTerm, 422, 'This section has no active term.');

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
        ]);

        $currentTerm->subjects()->updateExistingPivot($request->subject_id, [
            'teacher_id' => $request->teacher_id,
        ]);

        return redirect()->route('dean.sections.show', $section)->with('success', 'Teacher updated.');
    }

    public function changeAdviser(Request $request, Section $section)
    {
        abort_if(
            $section->program->department_id !== \App\Services\Scope::departmentId(),
            403,
            'This section does not belong to your department.'
        );

        $activePeriod = AcademicPeriod::getActive();
        abort_if(!$activePeriod, 422, 'No active academic period set. Contact your Administrator.');

        $request->validate([
            'adviser_id' => 'required|exists:users,id',
        ]);

        $currentTerm = $section->terms()->where('status', 'active')->first();

        // A teacher can advise multiple sections. Warn first so it's a
        // deliberate choice — skip the warning once the Dean has confirmed.
        if (!$request->boolean('confirmed')) {
            $existingAdvisories = SectionTerm::where('adviser_id', $request->adviser_id)
                ->where('status', 'active')
                ->when($currentTerm, fn($q) => $q->where('id', '!=', $currentTerm->id))
                ->with('section')
                ->get();

            if ($existingAdvisories->isNotEmpty()) {
                $teacher = User::find($request->adviser_id);

                return back()->with('adviser_conflict', [
                    'section_id'   => $section->id,
                    'adviser_id'   => $request->adviser_id,
                    'teacher_name' => $teacher->name,
                    'sections'     => $existingAdvisories->map(function ($term) {
                        $label = $term->section?->full_name ?? 'Unknown section';
                        return "{$label} ({$term->semester}, {$term->academic_year})";
                    })->values()->all(),
                ]);
            }
        }

        if (!$request->boolean('close_confirmed') && ($unfinished = \App\Services\TermRollover::unfinished($section, $activePeriod))) {
            return back()->with('term_close_warning', [
                'section_id' => $section->id,
                'adviser_id' => $request->adviser_id,
                'subjects'   => $unfinished,
            ]);
        }

        [$term, $closed] = \App\Services\TermRollover::setTerm($section, (int) $request->adviser_id, $activePeriod);

        return redirect()->route('dean.sections.index')->with('success', \App\Services\TermRollover::message($term, $closed));
    }
}
