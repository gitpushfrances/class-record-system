<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\SectionTerm;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    public function index()
    {
        $departmentId = auth()->user()->department_id;

        $assignments = DB::table('section_subject_teachers')
            ->join('section_terms', 'section_terms.id', '=', 'section_subject_teachers.section_term_id')
            ->join('sections', 'sections.id', '=', 'section_terms.section_id')
            ->join('subjects', 'subjects.id', '=', 'section_subject_teachers.subject_id')
            ->join('users', 'users.id', '=', 'section_subject_teachers.teacher_id')
            ->join('programs', 'programs.id', '=', 'sections.program_id')
            ->select(
                'section_subject_teachers.id',
                'programs.code as program_code',
                'sections.year_number',
                'sections.section_letter',
                'section_terms.academic_year',
                'section_terms.semester',
                'subjects.code as subject_code',
                'subjects.name as subject_name',
                'users.name as teacher_name',
                'section_subject_teachers.section_term_id',
                'section_subject_teachers.subject_id',
                'section_subject_teachers.teacher_id'
            )
            ->where('section_terms.status', 'active')
            ->where('programs.department_id', $departmentId)
            ->orderBy('programs.code')
            ->orderBy('sections.year_number')
            ->orderBy('sections.section_letter')
            ->get();

        // Only sections with an active term can receive assignments
        $sections = Section::with(['program', 'terms' => fn($q) => $q->where('status', 'active')])
            ->where('status', 'active')
            ->whereHas('program', fn($q) => $q->where('department_id', $departmentId))
            ->whereHas('terms', fn($q) => $q->where('status', 'active'))
            ->orderBy('year_level')
            ->get();

        $subjects = Subject::where('status', 'approved')->orderBy('code')->get();
        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->where('department_id', $departmentId)
            ->orderBy('name')
            ->get();

        return view('dean.assignments.index', compact('assignments', 'sections', 'subjects', 'teachers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
        ]);

        $section = Section::with('program')->findOrFail($validated['section_id']);
        abort_if(
            $section->program->department_id !== auth()->user()->department_id,
            403,
            'This section does not belong to your department.'
        );

        $currentTerm = SectionTerm::where('section_id', $validated['section_id'])
            ->where('status', 'active')
            ->first();

        if (!$currentTerm) {
            return redirect()->route('dean.assignments.index')
                ->with('error', 'This section has no active term. Set one up on the Sections page first.');
        }

        $exists = DB::table('section_subject_teachers')
            ->where('section_term_id', $currentTerm->id)
            ->where('subject_id', $validated['subject_id'])
            ->exists();

        if ($exists) {
            return redirect()->route('dean.assignments.index')
                ->with('error', 'A teacher is already assigned to that subject for this section\'s current term. Remove it first to reassign.');
        }

        DB::table('section_subject_teachers')->insert([
            'section_term_id' => $currentTerm->id,
            'subject_id' => $validated['subject_id'],
            'teacher_id' => $validated['teacher_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dean.assignments.index')
            ->with('success', 'Teacher assigned successfully.');
    }

    public function destroy($id)
    {
        $assignment = DB::table('section_subject_teachers')
            ->join('section_terms', 'section_terms.id', '=', 'section_subject_teachers.section_term_id')
            ->join('sections', 'sections.id', '=', 'section_terms.section_id')
            ->join('programs', 'programs.id', '=', 'sections.program_id')
            ->where('section_subject_teachers.id', $id)
            ->select('programs.department_id')
            ->first();

        abort_if(!$assignment, 404);
        abort_if(
            $assignment->department_id !== auth()->user()->department_id,
            403,
            'This assignment does not belong to your department.'
        );

        DB::table('section_subject_teachers')->where('id', $id)->delete();
        return redirect()->route('dean.assignments.index')
            ->with('success', 'Assignment removed.');
    }
}
