<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::with(['subject', 'teacher'])
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'desc')
            ->paginate(20);

        return view('dean.sections.index', compact('sections'));
    }

    public function create()
    {
        $subjects = Subject::where('status', 'active')->orderBy('code')->get();
        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('dean.sections.create', compact('subjects', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'section_name' => 'required|string|max:50',
            'year_level' => 'required|in:1,2,3,4',
            'semester' => 'required|in:1,2',
            'academic_year' => 'required|string|max:20',
            'schedule' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,completed',
        ]);

        Section::create($validated);

        return redirect()->route('dean.sections.index')->with('success', 'Section created successfully.');
    }

    public function edit(Section $section)
    {
        $subjects = Subject::where('status', 'active')->orderBy('code')->get();
        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('dean.sections.edit', compact('section', 'subjects', 'teachers'));
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'section_name' => 'required|string|max:50',
            'year_level' => 'required|in:1,2,3,4',
            'semester' => 'required|in:1,2',
            'academic_year' => 'required|string|max:20',
            'schedule' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,completed',
        ]);

        $section->update($validated);

        return redirect()->route('dean.sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        $section->delete();
        return redirect()->route('dean.sections.index')->with('success', 'Section deleted successfully.');
    }
}
