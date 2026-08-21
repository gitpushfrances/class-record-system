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
        $programId = auth()->user()->program_id;
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
            ->where('department_id', auth()->user()->department_id)
            ->orderBy('name')
            ->get();

        $allSubjects = Subject::where('status', 'approved')
            ->where('program_id', $programId)
            ->orderBy('name')
            ->get();
        $activePeriod = AcademicPeriod::getActive();

        return view('program-head.sections.index', compact('sections', 'teachers', 'allSubjects', 'activePeriod'));
    }

    public function create()
    {
        abort_if(!auth()->user()->program_id, 403, 'No program assigned to your account.');

        return view('program-head.sections.create');
    }

    public function store(Request $request)
    {
        $programId = auth()->user()->program_id;
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
            $section->program_id !== auth()->user()->program_id,
            403,
            'This section does not belong to your program.'
        );

        $section->load(['program', 'terms.adviser', 'terms.enrollments.student', 'terms.subjects.teachers']);
        $currentTerm = $section->terms->where('status', 'active')->first();

        $availableSubjects = Subject::where('status', 'approved')
            ->where('program_id', $section->program_id)
            ->when($currentTerm, fn($q) => $q->whereNotIn('id', $currentTerm->subjects->pluck('id')))
            ->orderBy('name')
            ->get();

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->where('department_id', auth()->user()->department_id)
            ->orderBy('name')
            ->get();

        return view('program-head.sections.show', compact('section', 'currentTerm', 'availableSubjects', 'teachers'));
    }

    public function edit(Section $section)
    {
        abort_if(
            $section->program_id !== auth()->user()->program_id,
            403,
            'This section does not belong to your program.'
        );

        return view('program-head.sections.edit', compact('section'));
    }

    public function update(Request $request, Section $section)
    {
        abort_if(
            $section->program_id !== auth()->user()->program_id,
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
            $section->program_id !== auth()->user()->program_id,
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
            $section->program_id !== auth()->user()->program_id,
            403,
            'This section does not belong to your program.'
        );

        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(!$currentTerm, 422, 'This section has no active term.');

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
        ]);

        $subject = Subject::findOrFail($request->subject_id);
        abort_if(
            $subject->program_id !== $section->program_id,
            403,
            'This subject does not belong to your program.'
        );

        $currentTerm->subjects()->syncWithoutDetaching([
            $request->subject_id => ['teacher_id' => $request->teacher_id],
        ]);

        return redirect()->route('program-head.sections.show', $section)->with('success', 'Subject added and teacher assigned.');
    }

    public function changeSubjectTeacher(Request $request, Section $section)
    {
        abort_if(
            $section->program_id !== auth()->user()->program_id,
            403,
            'This section does not belong to your program.'
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

        return redirect()->route('program-head.sections.show', $section)->with('success', 'Teacher updated.');
    }

    public function changeAdviser(Request $request, Section $section)
    {
        abort_if(
            $section->program_id !== auth()->user()->program_id,
            403,
            'This section does not belong to your program.'
        );

        $activePeriod = AcademicPeriod::getActive();
        abort_if(!$activePeriod, 422, 'No active academic period set. Contact your Administrator.');

        $request->validate([
            'adviser_id' => 'required|exists:users,id',
        ]);

        $currentTerm = $section->terms()->where('status', 'active')->first();

        $alreadyAdvising = SectionTerm::where('adviser_id', $request->adviser_id)
            ->where('status', 'active')
            ->when($currentTerm, fn($q) => $q->where('id', '!=', $currentTerm->id))
            ->first();

        if ($alreadyAdvising) {
            $conflictingSection = Section::find($alreadyAdvising->section_id);
            $conflictingLabel   = $conflictingSection?->full_name ?? 'another section';

            return back()
                ->withErrors(['adviser_id' => "This teacher already advises {$conflictingLabel}. A teacher can only advise one section at a time."])
                ->withInput();
        }

        if ($currentTerm) {
            $currentTerm->update([
                'academic_year' => $activePeriod->school_year,
                'semester'      => $activePeriod->semester,
                'adviser_id'    => $request->adviser_id,
            ]);
        } else {
            SectionTerm::create([
                'section_id'    => $section->id,
                'academic_year' => $activePeriod->school_year,
                'semester'      => $activePeriod->semester,
                'adviser_id'    => $request->adviser_id,
                'status'        => 'active',
            ]);
        }

        return redirect()->route('program-head.sections.index')->with('success', 'Term saved successfully.');
    }
}
