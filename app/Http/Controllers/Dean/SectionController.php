<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::with([
                'program.department',
                'terms' => fn($q) => $q->where('status', 'active')->with(['adviser', 'enrollments', 'subjects']),
            ])
            ->where('status', 'active')
            ->orderBy('year_level')
            ->orderBy('section_letter')
            ->get()
            ->groupBy('year_level');

        $teachers = User::where('role', 'teacher')->where('status', 'active')->orderBy('name')->get();
        $allSubjects = \App\Models\Subject::where('status', 'approved')->orderBy('name')->get();

        return view('dean.sections.index', compact('sections', 'teachers', 'allSubjects'));
    }

    public function create()
    {
        $programs = Program::where('status', 'approved')->orderBy('code')->get();
        return view('dean.sections.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id'     => 'required|exists:programs,id',
            'year_number'    => 'required|in:1,2,3,4,5',
            'section_letter' => 'required|string|max:50',
            'year_level'     => 'required|in:1st Year,2nd Year,3rd Year,4th Year,5th Year',
        ]);

        $exists = Section::where('program_id', $validated['program_id'])
            ->where('year_number', $validated['year_number'])
            ->where('section_letter', $validated['section_letter'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['section_letter' => 'This section already exists for this program and year.'])->withInput();
        }

        Section::create($validated);

        return redirect()->route('dean.sections.index')->with('success', 'Section created successfully.');
    }

    public function show(Section $section)
    {
        $section->load(['program.department', 'terms.adviser', 'terms.enrollments.student', 'terms.subjects.teachers']);
        $currentTerm = $section->terms->where('status', 'active')->first();

        $availableSubjects = \App\Models\Subject::where('status', 'approved')
            ->when($currentTerm, fn($q) => $q->whereNotIn('id', $currentTerm->subjects->pluck('id')))
            ->orderBy('name')
            ->get();

        $teachers = User::where('role', 'teacher')->where('status', 'active')->orderBy('name')->get();

        return view('dean.sections.show', compact('section', 'currentTerm', 'availableSubjects', 'teachers'));
    }

    public function edit(Section $section)
    {
        $programs = Program::where('status', 'approved')->orderBy('code')->get();
        return view('dean.sections.edit', compact('section', 'programs'));
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'program_id'     => 'required|exists:programs,id',
            'year_number'    => 'required|in:1,2,3,4,5',
            'section_letter' => 'required|string|max:50',
            'year_level'     => 'required|in:1st Year,2nd Year,3rd Year,4th Year,5th Year',
            'status'         => 'required|in:active,inactive',
        ]);

        $exists = Section::where('program_id', $validated['program_id'])
            ->where('year_number', $validated['year_number'])
            ->where('section_letter', $validated['section_letter'])
            ->where('id', '!=', $section->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['section_letter' => 'This section already exists for this program and year.'])->withInput();
        }

        $section->update($validated);

        return redirect()->route('dean.sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        $activeEnrollments = $section->terms()
            ->where('status', 'active')
            ->withCount('enrollments')
            ->get()
            ->sum('enrollments_count');

        if ($activeEnrollments > 0) {
            return redirect()->route('dean.sections.index')
                ->with('error', 'Cannot delete section with active enrolled students.');
        }

        $section->delete();
        return redirect()->route('dean.sections.index')->with('success', 'Section deleted.');
    }

    public function attachSubject(Request $request, Section $section)
    {
        $currentTerm = $section->terms()->where('status', 'active')->first();
        abort_if(!$currentTerm, 422, 'This section has no active term.');

        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
        ]);

        $currentTerm->subjects()->syncWithoutDetaching([
            $request->subject_id => ['teacher_id' => $request->teacher_id],
        ]);

        return redirect()->route('dean.sections.show', $section)->with('success', 'Subject added and teacher assigned.');
    }

    public function changeSubjectTeacher(Request $request, Section $section)
    {
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
        $request->validate([
            'adviser_id'    => 'required|exists:users,id',
            'academic_year' => 'required|string|max:20',
            'semester'      => 'required|in:1st Semester,2nd Semester,Summer',
        ]);

        $currentTerm = $section->terms()->where('status', 'active')->first();

        if ($currentTerm) {
            $currentTerm->update([
                'academic_year' => $request->academic_year,
                'semester'      => $request->semester,
                'adviser_id'    => $request->adviser_id,
            ]);
        } else {
            SectionTerm::create([
                'section_id'    => $section->id,
                'academic_year' => $request->academic_year,
                'semester'      => $request->semester,
                'adviser_id'    => $request->adviser_id,
                'status'        => 'active',
            ]);
        }

        return redirect()->route('dean.sections.index')->with('success', 'Term saved successfully.');
    }
}
