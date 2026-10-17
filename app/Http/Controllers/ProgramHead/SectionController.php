<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicPeriod;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $programId = \App\Services\Scope::programId();
        abort_if(!$programId, 403, 'No program assigned to your account.');

        $sections = Section::with([
                'program',
                'terms' => fn($q) => $q->where('status', 'active')->with(['adviser', 'enrollments', 'subjects']),
            ])
            ->where('program_id', $programId)
            ->where('status', 'active')
            ->orderBy('year_level')
            ->orderBy('section_letter')
            ->get()
            ->groupBy('year_level');

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->where('department_id', \App\Services\Scope::departmentId())
            ->orderBy('name')
            ->get();

        $activePeriod = AcademicPeriod::getActive();

        return view('program-head.sections.index', compact('sections', 'teachers', 'activePeriod'));
    }

    public function create()
    {
        abort_if(!\App\Services\Scope::programId(), 403, 'No program assigned to your account.');

        return view('program-head.sections.create');
    }

    public function store(Request $request)
    {
        $programId = \App\Services\Scope::programId();
        abort_if(!$programId, 403, 'No program assigned to your account.');

        $validated = $request->validate([
            'year_number'    => 'required|in:1,2,3,4,5',
            'section_letter' => 'required|string|max:50',
            'year_level'     => 'required|in:1st Year,2nd Year,3rd Year,4th Year,5th Year',
        ]);

        $validated['program_id'] = $programId;

        $exists = Section::where('program_id', $programId)
            ->where('year_number', $validated['year_number'])
            ->where('section_letter', $validated['section_letter'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['section_letter' => 'This section already exists for your program and year.'])->withInput();
        }

        Section::create($validated);

        return redirect()->route('program-head.sections.index')->with('success', 'Section created successfully.');
    }

    public function show(Section $section)
    {
        abort_if(
            $section->program_id !== \App\Services\Scope::programId(),
            403,
            'This section does not belong to your program.'
        );

        $section->load(['program', 'terms.adviser', 'terms.enrollments.student', 'terms.subjects.teachers']);
        $currentTerm = $section->terms->where('status', 'active')->first();

        $availableSubjects = $currentTerm ? \App\Services\SubjectOffering::forTerm($currentTerm) : collect();

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->where('department_id', \App\Services\Scope::departmentId())
            ->orderBy('name')
            ->get();

        $availableStudents = $currentTerm
            ? \App\Models\Student::where('program_id', $section->program_id)
                ->where('status', 'active')
                ->whereNotIn('id', $currentTerm->enrollments->pluck('student_id'))
                ->orderBy('last_name')->orderBy('first_name')
                ->get()
            : collect();

        $droppedEnrollments = $currentTerm
            ? \App\Models\Enrollment::with('student')
                ->where('section_term_id', $currentTerm->id)
                ->where('type', \App\Models\Enrollment::TYPE_REGULAR)
                ->where('status', \App\Models\Enrollment::STATUS_DROPPED)
                ->get()
            : collect();

        return view('program-head.sections.show', compact('section', 'currentTerm', 'availableSubjects', 'teachers', 'availableStudents', 'droppedEnrollments'));
    }

    public function edit(Section $section)
    {
        abort_if(
            $section->program_id !== \App\Services\Scope::programId(),
            403,
            'This section does not belong to your program.'
        );

        return view('program-head.sections.edit', compact('section'));
    }

    public function update(Request $request, Section $section)
    {
        abort_if(
            $section->program_id !== \App\Services\Scope::programId(),
            403,
            'This section does not belong to your program.'
        );

        $validated = $request->validate([
            'year_number'    => 'required|in:1,2,3,4,5',
            'section_letter' => 'required|string|max:50',
            'year_level'     => 'required|in:1st Year,2nd Year,3rd Year,4th Year,5th Year',
            'status'         => 'required|in:active,inactive',
        ]);

        $exists = Section::where('program_id', $section->program_id)
            ->where('year_number', $validated['year_number'])
            ->where('section_letter', $validated['section_letter'])
            ->where('id', '!=', $section->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['section_letter' => 'This section already exists for your program and year.'])->withInput();
        }

        $section->update($validated);

        return redirect()->route('program-head.sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        abort_if(
            $section->program_id !== \App\Services\Scope::programId(),
            403,
            'This section does not belong to your program.'
        );

        $activeEnrollments = $section->terms()
            ->where('status', 'active')
            ->withCount('enrollments')
            ->get()
            ->sum('enrollments_count');

        if ($activeEnrollments > 0) {
            return redirect()->route('program-head.sections.index')
                ->with('error', 'Cannot delete section with active enrolled students.');
        }

        $section->delete();
        return redirect()->route('program-head.sections.index')->with('success', 'Section deleted.');
    }

    public function attachSubject(Request $request, Section $section)
    {
        abort_if(
            $section->program_id !== \App\Services\Scope::programId(),
            403,
            'This section does not belong to your program.'
        );

        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(!$currentTerm, 422, 'This section has no active term.');

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            ...\App\Services\ClassSchedule::rules(),
        ], [], \App\Services\ClassSchedule::ATTRIBUTES);

        if ($refusal = \App\Services\SubjectOffering::refusal($currentTerm, Subject::findOrFail($request->subject_id))) {
            return back()->withErrors(['subject_id' => $refusal])->withInput();
        }

        $slot = \App\Services\ClassSchedule::fromRequest($request);
        if ($conflict = \App\Services\ClassSchedule::conflict($currentTerm->id, (int) $request->subject_id, $slot)) {
            return back()->withErrors(['schedule_days' => $conflict])->withInput();
        }

        $currentTerm->subjects()->syncWithoutDetaching([
            $request->subject_id => ['teacher_id' => $request->teacher_id] + $slot,
        ]);

        return redirect()->route('program-head.sections.show', $section)->with('success', 'Subject added and teacher assigned.');
    }

    public function changeSubjectTeacher(Request $request, Section $section)
    {
        abort_if(
            $section->program_id !== \App\Services\Scope::programId(),
            403,
            'This section does not belong to your program.'
        );

        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(!$currentTerm, 422, 'This section has no active term.');

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            ...\App\Services\ClassSchedule::rules(),
        ], [], \App\Services\ClassSchedule::ATTRIBUTES);

        $slot = \App\Services\ClassSchedule::fromRequest($request);
        if ($conflict = \App\Services\ClassSchedule::conflict($currentTerm->id, (int) $request->subject_id, $slot)) {
            return back()->withErrors(['schedule_days' => $conflict])->withInput();
        }

        $currentTerm->subjects()->updateExistingPivot($request->subject_id, ['teacher_id' => $request->teacher_id] + $slot);

        return redirect()->route('program-head.sections.show', $section)->with('success', 'Teacher and schedule updated.');
    }

    public function enrollStudent(Request $request, Section $section)
    {
        $term = $this->activeTermOfMyProgram($section);

        $request->validate(['student_id' => 'required|exists:students,id']);
        $student = \App\Models\Student::findOrFail($request->student_id);

        if ($error = \App\Services\SectionEnrollment::enroll($term, $student)) {
            return back()->with('error', $error);
        }

        return back()->with('success', "{$student->full_name} enrolled.");
    }

    public function dropStudent(Section $section, \App\Models\Enrollment $enrollment)
    {
        $term = $this->activeTermOfMyProgram($section);
        abort_unless($term->enrollments()->whereKey($enrollment->id)->exists(), 404);

        if ($error = \App\Services\SectionEnrollment::drop($enrollment)) {
            return back()->with('error', $error);
        }

        return back()->with('success', "{$enrollment->student->full_name} dropped from the section. Grades and attendance are kept.");
    }

    private function activeTermOfMyProgram(Section $section): SectionTerm
    {
        abort_if($section->program_id !== \App\Services\Scope::programId(), 403, 'This section does not belong to your program.');

        $term = $section->terms()->where('status', 'active')->first();
        abort_if(!$term, 422, 'This section has no active term.');

        return $term;
    }

    public function changeAdviser(Request $request, Section $section)
    {
        abort_if(
            $section->program_id !== \App\Services\Scope::programId(),
            403,
            'This section does not belong to your program.'
        );

        $activePeriod = AcademicPeriod::getActive();
        abort_if(!$activePeriod, 422, 'No active academic period set. Contact your Administrator.');

        $request->validate([
            'adviser_id' => 'required|exists:users,id',
        ]);

        $currentTerm = $section->terms()->where('status', 'active')->first();

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

        return redirect()->route('program-head.sections.index')->with('success', \App\Services\TermRollover::message($term, $closed));
    }
}
