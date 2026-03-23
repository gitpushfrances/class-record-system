<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::where('requested_by', auth()->id())
            ->with('teacher')
            ->orderByDesc('created_at')
            ->paginate(20);

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('dean.subjects.index', compact('subjects', 'teachers'));
    }

    public function create()
    {
        return view('dean.subjects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|unique:subjects,code|max:20',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'units'       => 'required|integer|min:1|max:10',
            'department'  => 'required|string|max:255',
        ]);

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

        return view('dean.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        abort_if($subject->requested_by !== auth()->id(), 403);

        // Handle teacher assignment separately (allowed for approved subjects too)
        if ($request->has('assign_teacher')) {
            $request->validate([
                'teacher_id' => 'nullable|exists:users,id',
            ]);
            $subject->update(['teacher_id' => $request->teacher_id ?: null]);
            return redirect()->route('dean.subjects.index')
                ->with('success', 'Teacher assigned successfully.');
        }

        abort_if($subject->status !== 'pending', 403, 'Only pending subjects can be edited.');

        $validated = $request->validate([
            'code'        => 'required|unique:subjects,code,' . $subject->id . '|max:20',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'units'       => 'required|integer|min:1|max:10',
            'department'  => 'required|string|max:255',
        ]);

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
}
