<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Program;
use App\Services\Scope;
use Illuminate\Http\Request;

/** Admin "all access": work in any department as its Dean or in any program as its Program Head. */
class ViewAsController extends Controller
{
    public function index()
    {
        $departments = Department::with(['programs' => fn ($q) => $q->where('status', 'approved')->orderBy('code')])
            ->orderBy('name')
            ->get();

        return view('admin.view-as.index', compact('departments'));
    }

    public function start(Request $request)
    {
        $request->validate([
            'role'          => 'required|in:dean,program_head',
            'department_id' => 'required_if:role,dean|nullable|exists:departments,id',
            'program_id'    => 'required_if:role,program_head|nullable|exists:programs,id',
        ]);

        if ($request->role === 'dean') {
            Scope::viewAsDean(Department::findOrFail($request->department_id));

            return redirect()->route('dean.dashboard');
        }

        $program = Program::where('status', 'approved')->findOrFail($request->program_id);
        Scope::viewAsProgramHead($program);

        return redirect()->route('program-head.dashboard');
    }

    public function stop()
    {
        Scope::stop();

        return redirect()->route('admin.view-as.index')->with('success', 'Back to Admin.');
    }
}
