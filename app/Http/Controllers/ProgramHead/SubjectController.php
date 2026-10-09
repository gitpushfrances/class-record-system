<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            'code'        => 'required|max:20',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'units'       => 'required|numeric|decimal:0,1|min:0.5|max:10',
        ]);

        if ($redirect = $this->guardDuplicateCode($request, $validated, $programId)) {
            return $redirect;
        }

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
            'code'        => 'required|max:20',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'units'       => 'required|numeric|decimal:0,1|min:0.5|max:10',
        ]);

        if ($redirect = $this->guardDuplicateCode($request, $validated, (int) $subject->program_id, $subject->id)) {
            return $redirect;
        }

        $subject->update($validated);

        return redirect()->route('program-head.subjects.index')
            ->with('success', 'Subject request updated.');
    }

    /**
     * Subject codes may repeat, but never silently. Looks only at pending/approved
     * subjects inside the same department, so no other department's data is exposed.
     * Exact duplicates (same code, name and program) are blocked; other matches
     * need an explicit confirmation (confirm_duplicate=1).
     */
    private function guardDuplicateCode(Request $request, array $validated, int $programId, ?int $ignoreId = null): ?RedirectResponse
    {
        $departmentId = Program::whereKey($programId)->value('department_id');

        $matches = Subject::with('program.department')
            ->where('code', $validated['code'])
            ->whereIn('status', ['pending', 'approved'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereHas('program', fn ($q) => $q->where('department_id', $departmentId))
            ->get();

        if ($matches->isEmpty()) {
            return null;
        }

        $exact = $matches->contains(fn ($s) => (int) $s->program_id === $programId
            && mb_strtolower($s->name) === mb_strtolower($validated['name']));

        if ($exact) {
            throw ValidationException::withMessages([
                'code' => 'This subject already exists in your program.',
            ]);
        }

        if ($request->boolean('confirm_duplicate')) {
            return null;
        }

        return back()->withInput()->with('duplicate_subjects', $matches->map(fn ($s) => [
            'code'       => $s->code,
            'name'       => $s->name,
            'program'    => $s->program ? $s->program->code . ' — ' . $s->program->name : '—',
            'department' => $s->program?->department?->name ?? '—',
            'status'     => ucfirst($s->status),
        ])->values()->all());
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
