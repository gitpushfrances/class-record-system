<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Roles manageable from the SuperAdmin Accounts screen.
     */
    protected array $managedRoles = ['dean', 'program_head', 'teacher'];

    public function index(Request $request)
    {
        $query = User::whereIn('role', $this->managedRoles)
            ->orderBy('created_at', 'desc');

        if ($request->filled('role') && in_array($request->role, $this->managedRoles, true)) {
            $query->where('role', $request->role);
        }

        $accounts = $query->paginate(20)->withQueryString();

        return view('admin.deans.index', [
            'deans' => $accounts,
            'managedRoles' => $this->managedRoles,
            'activeRoleFilter' => $request->get('role'),
        ]);
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
        return view('admin.users.create', ['managedRoles' => $this->managedRoles]);
    }

    /**
     * Unified store for any managed role.
     */
    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:' . implode(',', $this->managedRoles),
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
            'status'   => 'active',
        ]);

        if ($validated['role'] === 'program_head' && method_exists($user, 'assignRole')) {
            $user->assignRole('program_head');
        }

        $label = ucwords(str_replace('_', ' ', $validated['role']));

        return redirect()->route('admin.deans.index')->with('success', "{$label} account created successfully.");
    }

    public function edit(User $dean)
    {
        abort_if(!in_array($dean->role, $this->managedRoles, true), 403);
        return view('admin.deans.edit', ['user' => $dean]);
    }

    public function update(Request $request, User $dean)
    {
        abort_if(!in_array($dean->role, $this->managedRoles, true), 403);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $dean->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $dean->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            ...(isset($validated['password']) ? ['password' => Hash::make($validated['password'])] : []),
        ]);

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

    public function createTeacher()
    {
        return view('admin.users.create-teacher');
    }

    public function storeTeacher(Request $request)
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
            'role'     => 'teacher',
            'status'   => 'active',
        ]);

        return redirect()->route('admin.deans.index')->with('success', 'Teacher account created successfully.');
    }

    public function createProgramHead()
    {
        return view('admin.users.create-program-head');
    }

    public function storeProgramHead(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'program_head',
            'status'   => 'active',
        ]);
        $user->assignRole('program_head');

        return redirect()->route('admin.deans.index')
            ->with('success', 'Program Head account created successfully.');
    }
}
