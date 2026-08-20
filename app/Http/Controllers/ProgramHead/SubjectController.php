<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $programId = auth()->user()->program_id;
        abort_if(!$programId, 403, 'No program assigned to your account.');

        $subjects = Subject::where('program_id', $programId)
            ->where('requested_by', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('program-head.subjects.index', compact('subjects'));
    }

    public function create()
    {
        abort_if(!auth()->user()->program_id, 403, 'No program assigned to your account.');

        return view('program-head.subjects.create');
    }

    public function store(Request $request)
    {
        $programId = auth()->user()->program_id;
        abort_if(!$programId, 403, 'No program assigned to your account.');

        $validated = $request->validate([
            'code'        => 'required|unique:subjects,code|max:20',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'units'       => 'required|integer|min:1|max:10',
        ]);

        $validated['program_id']   = $programId;
        $validated['requested_by'] = auth()->id();
        $validated['status']       = 'pending';

        Subject::create($validated);

        return redirect()->route('program-head.subjects.index')
            ->with('success', 'Subject request submitted. Awaiting Dean approval.');
    }

    public function edit(Subject $subject)
    {
        abort_if($subject->requested_by !== auth()->id(), 403);
        abort_if($subject->status !== 'pending', 403, 'Only pending subjects can be edited.');

        return view('program-head.subjects.edit', compact('subject'));
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
        ]);

        $subject->update($validated);

        return redirect()->route('program-head.subjects.index')
            ->with('success', 'Subject request updated.');
    }

    public function destroy(Subject $subject)
    {
        abort_if($subject->requested_by !== auth()->id(), 403);
        abort_if($subject->status !== 'pending', 403, 'Only pending subjects can be deleted.');

        $subject->delete();

        return redirect()->route('program-head.subjects.index')
            ->with('success', 'Subject request cancelled.');
    }
}
