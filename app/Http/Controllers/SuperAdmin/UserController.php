<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $deans = User::where('role', 'dean')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.deans.index', compact('deans'));
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

    public function edit(User $dean)
    {
        abort_if(!$dean->isDean(), 403);
        return view('admin.deans.edit', ['user' => $dean]);
    }

    public function update(Request $request, User $dean)
    {
        abort_if(!$dean->isDean(), 403);

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

        return redirect()->route('admin.deans.index')->with('success', 'Dean account updated successfully.');
    }

    public function deactivate(User $dean)
    {
        abort_if(!$dean->isDean(), 403);

        $dean->update(['status' => 'inactive']);

        return redirect()->route('admin.deans.index')->with('success', 'Dean account deactivated.');
    }

    public function activate(User $dean)
    {
        abort_if(!$dean->isDean(), 403);

        $dean->update(['status' => 'active']);

        return redirect()->route('admin.deans.index')->with('success', 'Dean account activated.');
    }
}
