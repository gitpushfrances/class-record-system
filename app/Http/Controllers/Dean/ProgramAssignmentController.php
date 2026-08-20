<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;

class ProgramAssignmentController extends Controller
{
    public function index()
    {
        $departmentId = auth()->user()->department_id;

        $programHeads = User::where('role', 'program_head')
            ->where('department_id', $departmentId)
            ->with('program')
            ->orderBy('name')
            ->get();

        $programs = Program::where('department_id', $departmentId)
            ->where('status', 'approved')
            ->orderBy('code')
            ->get();

        return view('dean.program-heads.index', compact('programHeads', 'programs'));
    }

    public function assign(Request $request, User $programHead)
    {
        abort_if($programHead->role !== 'program_head', 404);
        abort_if($programHead->department_id !== auth()->user()->department_id, 403);

        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
        ]);

        $program = Program::findOrFail($validated['program_id']);
        abort_if($program->department_id !== auth()->user()->department_id, 403, 'That program does not belong to your department.');

        $programHead->update(['program_id' => $program->id]);

        return redirect()->route('dean.program-heads.index')->with('success', "{$programHead->name} assigned to {$program->code}.");
    }

    public function remove(User $programHead)
    {
        abort_if($programHead->role !== 'program_head', 404);
        abort_if($programHead->department_id !== auth()->user()->department_id, 403);

        $programHead->update(['program_id' => null]);

        return redirect()->route('dean.program-heads.index')->with('success', "{$programHead->name} unassigned from their program.");
    }
}
