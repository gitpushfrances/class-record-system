<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $departmentId = auth()->user()->department_id;

        // Subjects this Dean personally requested (awaiting Admin approval)
        $subjects = Subject::where('requested_by', auth()->id())
            ->with(['sectionTerms.section.program'])
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'mine');

        // Subjects requested by Program Heads in this Dean's department, awaiting Dean approval
        $pendingFromProgramHeads = Subject::with(['program', 'requester'])
            ->whereHas('program', fn($q) => $q->where('department_id', $departmentId))
            ->where('status', 'pending')
            ->whereHas('requester', fn($q) => $q->where('role', 'program_head'))
            ->orderBy('created_at')
            ->get();

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('dean.subjects.index', compact('subjects', 'pendingFromProgramHeads', 'teachers'));
    }

    public function create()
    {
        $programs = Program::where('status', 'approved')
            ->where('department_id', auth()->user()->department_id)
            ->orderBy('code')
            ->get();

        return view('dean.subjects.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|unique:subjects,code|max:20',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'units'       => 'required|integer|min:1|max:10',
            'program_id'  => 'required|exists:programs,id',
        ]);

        $program = Program::findOrFail($validated['program_id']);
        abort_if(
            $program->department_id !== auth()->user()->department_id,
            403,
            'This program does not belong to your department.'
        );

        $validated['requested_by'] = auth()->id();
        $validated['status']       = 'pending';

        Subject::create($validated);

        return redirect()->route('dean.subjects.index')
            ->with('success', 'Subject request submitted. Awaiting Admin approval.');
    }

    public function edit(Subject $subject)
    {
        abort_if($subject->requested_by !== auth()->id(), 403);
        abort_if($subject->status !== 'pending', 403, 'Only pending subjects can be edited.');

        $programs = Program::where('status', 'approved')
            ->where('department_id', auth()->user()->department_id)
            ->orderBy('code')
            ->get();

        return view('dean.subjects.edit', compact('subject', 'programs'));
    }

    public function update(Request $request, Subject $subject)
    {
        abort_if($subject->requested_by !== auth()->id(), 403);
        abort_if($subject->status !== 'pending', 403, 'Only pending subjects can be edited.');

        $validated = $request->validate([
            'code'        => 'required|unique:subjects,code,' . $subject->id . '|max:20',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'units'       => 'required|integer|min:1|max:10',
            'program_id'  => 'required|exists:programs,id',
        ]);

        $program = Program::findOrFail($validated['program_id']);
        abort_if(
            $program->department_id !== auth()->user()->department_id,
            403,
            'This program does not belong to your department.'
        );

        $subject->update($validated);

        return redirect()->route('dean.subjects.index')
            ->with('success', 'Subject request updated.');
    }

    public function destroy(Subject $subject)
    {
        abort_if($subject->requested_by !== auth()->id(), 403);
        abort_if($subject->status !== 'pending', 403, 'Only pending subjects can be deleted.');

        $subject->delete();

        return redirect()->route('dean.subjects.index')
            ->with('success', 'Subject request cancelled.');
    }

    public function approve(Subject $subject)
    {
        abort_if($subject->status !== 'pending', 422, 'Subject is not pending.');
        abort_if(
            !$subject->program || $subject->program->department_id !== auth()->user()->department_id,
            403,
            'This subject does not belong to your department.'
        );

        $subject->update([
            'status'          => 'approved',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'rejected_reason' => null,
        ]);

        return redirect()->route('dean.subjects.index')
            ->with('success', "Subject [{$subject->code}] approved.");
    }

    public function reject(Request $request, Subject $subject)
    {
        abort_if($subject->status !== 'pending', 422, 'Subject is not pending.');
        abort_if(
            !$subject->program || $subject->program->department_id !== auth()->user()->department_id,
            403,
            'This subject does not belong to your department.'
        );

        $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        $subject->update([
            'status'          => 'rejected',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'rejected_reason' => $request->rejected_reason,
        ]);

        return redirect()->route('dean.subjects.index')
            ->with('success', "Subject [{$subject->code}] rejected.");
    }
}
