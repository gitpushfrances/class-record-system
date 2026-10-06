<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Roles manageable from the SuperAdmin Accounts screen.
     */
    protected array $managedRoles = ['dean', 'program_head', 'teacher'];

    public function index(Request $request)
    {
        $filter = $request->get('role');

        if ($filter === 'pending_review') {
            $accounts = User::where('status', 'pending_review')
                ->orderBy('created_at', 'desc')
                ->paginate(20)
                ->withQueryString();
        } else {
            $query = User::where(fn ($q) => $q->whereIn('role', $this->managedRoles)->orWhere('status', 'pending_review'))
                ->with('department')
                ->orderBy('created_at', 'desc');

            if ($filter && in_array($filter, $this->managedRoles, true)) {
                $query->where('role', $filter);
            }

            $accounts = $query->paginate(20)->withQueryString();
        }

        $pendingCount = User::where('status', 'pending_review')->count();

        return view('admin.deans.index', [
            'deans' => $accounts,
            'managedRoles' => $this->managedRoles,
            'activeRoleFilter' => $filter,
            'pendingCount' => $pendingCount,
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'programs' => Program::where('status', 'approved')->orderBy('code')->get(['id', 'code', 'name', 'department_id']),
        ]);
    }

    public function approveRequest(Request $request, User $dean)
    {
        abort_if($dean->status !== 'pending_review', 403);

        $validated = $request->validate([
            'role'          => 'required|in:' . implode(',', $this->managedRoles),
            'department_id' => 'required|exists:departments,id,status,active',
            'program_id'    => 'nullable|exists:programs,id,status,approved',
        ]);

        if ($error = $this->assignmentError($validated, $dean->id)) {
            return back()->withInput()->withErrors($error);
        }

        DB::transaction(function () use ($dean, $validated, $request) {
            $dean->update([
                'role'          => $validated['role'],
                'department_id' => $validated['department_id'],
                'program_id'    => in_array($validated['role'], ['program_head', 'teacher'], true) ? ($validated['program_id'] ?? null) : null,
                'status'        => 'active',
                'approved_by'   => $request->user()->id,
                'approved_at'   => now(),
            ]);

            $dean->syncRoles([$validated['role']]);
        });

        $label = ucwords(str_replace('_', ' ', $validated['role']));

        return redirect()->route('admin.deans.index')->with('success', "{$dean->name} was approved as {$label}.");
    }

    /**
     * Role-specific assignment rules for approving a sign-up request.
     * Returns a [field => message] array on failure, or null when valid.
     */
    protected function assignmentError(array $data, int $userId): ?array
    {
        if ($data['role'] === 'dean') {
            $taken = User::where('role', 'dean')
                ->where('department_id', $data['department_id'])
                ->where('id', '!=', $userId)
                ->exists();

            return $taken
                ? ['department_id' => 'This department is already assigned to another Dean.']
                : null;
        }

        if ($data['role'] === 'teacher') {
            if (empty($data['program_id'])) {
                return null;
            }
            $program = Program::find($data['program_id']);
            return (!$program || (int) $program->department_id !== (int) ($data['department_id'] ?? 0))
                ? ['program_id' => 'Selected program does not belong to the selected department.']
                : null;
        }

        if ($data['role'] === 'program_head') {
            if (empty($data['program_id'])) {
                return ['program_id' => 'A program is required for a Program Head.'];
            }

            $program = Program::find($data['program_id']);

            if (!$program || (int) $program->department_id !== (int) $data['department_id']) {
                return ['program_id' => 'Selected program does not belong to the selected department.'];
            }

            $taken = User::where('role', 'program_head')
                ->where('program_id', $data['program_id'])
                ->where('id', '!=', $userId)
                ->exists();

            return $taken
                ? ['program_id' => 'This program is already assigned to another Program Head.']
                : null;
        }

        return null;
    }

    public function rejectRequest(User $dean)
    {
        abort_if($dean->status !== 'pending_review', 403);

        $dean->update(['status' => 'rejected']);

        return redirect()->route('admin.deans.index')->with('success', 'Request rejected.');
    }

    public function create()
    {
        return view('admin.deans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'dean',
            'status'   => 'active',
        ]);

        return redirect()->route('admin.deans.index')->with('success', 'Dean account created successfully.');
    }

    /**
     * Unified create form for any managed role.
     */
    public function createAccount()
    {
        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $programs = Program::where('status', 'approved')->orderBy('code')->get(['id', 'code', 'name', 'department_id']);

        return view('admin.users.create', [
            'managedRoles' => $this->managedRoles,
            'departments'  => $departments,
            'programs'     => $programs,
        ]);
    }

    /**
     * Unified store for any managed role.
     * department_id is optional for every role — accounts can be created Unassigned and
     * given a department later via Edit. Only one Dean may hold a given department; assigning
     * a department to a Dean here automatically unassigns any Dean currently holding it.
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:8|confirmed',
            'role'          => 'required|in:' . implode(',', $this->managedRoles),
            'department_id' => 'nullable|exists:departments,id',
            'program_id'    => 'nullable|exists:programs,id',
        ]);

        // Only one Dean per department - reject if already taken.
        if ($validated['role'] === 'dean' && !empty($validated['department_id'])) {
            $alreadyTaken = User::where('role', 'dean')
                ->where('department_id', $validated['department_id'])
                ->exists();

            if ($alreadyTaken) {
                return back()->withInput()->withErrors([
                    'department_id' => 'This department is already assigned to another Dean.',
                ]);
            }
        }

        // Program Head: program must belong to the selected department, and only one
        // Program Head may hold a given program.
        if ($validated['role'] === 'program_head' && !empty($validated['program_id'])) {
            $program = Program::find($validated['program_id']);

            if ($program && (int) $program->department_id !== (int) $validated['department_id']) {
                return back()->withInput()->withErrors([
                    'program_id' => 'Selected program does not belong to the selected department.',
                ]);
            }

            $alreadyTaken = User::where('role', 'program_head')
                ->where('program_id', $validated['program_id'])
                ->exists();

            if ($alreadyTaken) {
                return back()->withInput()->withErrors([
                    'program_id' => 'This program is already assigned to another Program Head.',
                ]);
            }
        }

        $user = User::create([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'password'      => Hash::make($validated['password']),
            'role'          => $validated['role'],
            'status'        => 'active',
            'department_id' => $validated['department_id'] ?? null,
            'program_id'    => $validated['program_id'] ?? null,
        ]);

        if ($validated['role'] === 'program_head' && method_exists($user, 'assignRole')) {
            $user->assignRole('program_head');
        }

        $label = ucwords(str_replace('_', ' ', $validated['role']));
        $note  = $validated['role'] === 'program_head'
            ? ' A Dean must assign their program before they can access their dashboard.'
            : '';

        return redirect()->route('admin.deans.index')->with('success', "{$label} account created successfully.{$note}");
    }

    public function edit(User $dean)
    {
        abort_if(!in_array($dean->role, $this->managedRoles, true), 403);

        $departments = Department::where('status', 'active')->orderBy('name')->get();
        $programs = Program::where('status', 'approved')->orderBy('code')->get(['id', 'code', 'name', 'department_id']);

        return view('admin.deans.edit', [
            'user' => $dean,
            'departments' => $departments,
            'programs' => $programs,
            'managedRoles' => $this->managedRoles,
        ]);
    }

    public function update(Request $request, User $dean)
    {
        abort_if(!in_array($dean->role, $this->managedRoles, true), 403);

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $dean->id,
            'password'      => 'nullable|string|min:8|confirmed',
            'role'          => 'required|in:' . implode(',', $this->managedRoles),
            'department_id' => 'nullable|exists:departments,id',
            'program_id'    => 'nullable|exists:programs,id',
        ]);

        // Only one Dean per department - reject if already taken by someone else.
        if ($validated['role'] === 'dean' && !empty($validated['department_id'])) {
            $alreadyTaken = User::where('role', 'dean')
                ->where('department_id', $validated['department_id'])
                ->where('id', '!=', $dean->id)
                ->exists();

            if ($alreadyTaken) {
                return back()->withInput()->withErrors([
                    'department_id' => 'This department is already assigned to another Dean.',
                ]);
            }
        }

        // Program Head: program must belong to the selected department, and only one
        // Program Head may hold a given program.
        if ($validated['role'] === 'program_head' && !empty($validated['program_id'])) {
            $program = Program::find($validated['program_id']);

            if ($program && (int) $program->department_id !== (int) $validated['department_id']) {
                return back()->withInput()->withErrors([
                    'program_id' => 'Selected program does not belong to the selected department.',
                ]);
            }

            $alreadyTaken = User::where('role', 'program_head')
                ->where('program_id', $validated['program_id'])
                ->where('id', '!=', $dean->id)
                ->exists();

            if ($alreadyTaken) {
                return back()->withInput()->withErrors([
                    'program_id' => 'This program is already assigned to another Program Head.',
                ]);
            }
        }

        if ($validated['role'] === 'teacher' && ($error = $this->assignmentError($validated, $dean->id))) {
            return back()->withInput()->withErrors($error);
        }

        $dean->update([
            'name'          => $validated['name'],
            'email'         => $validated['email'],
            'role'          => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'program_id'    => in_array($validated['role'], ['program_head', 'teacher'], true) ? ($validated['program_id'] ?? null) : null,
            ...(isset($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        if (method_exists($dean, 'syncRoles')) {
            $dean->syncRoles([$validated['role']]);
        }

        return redirect()->route('admin.deans.index')->with('success', 'Account updated successfully.');
    }

    public function deactivate(User $dean)
    {
        abort_if(!in_array($dean->role, $this->managedRoles, true), 403);

        $dean->update(['status' => 'inactive']);

        return redirect()->route('admin.deans.index')->with('success', 'Account deactivated.');
    }

    public function activate(User $dean)
    {
        abort_if(!in_array($dean->role, $this->managedRoles, true), 403);

        $dean->update(['status' => 'active']);

        return redirect()->route('admin.deans.index')->with('success', 'Account activated.');
    }

}
