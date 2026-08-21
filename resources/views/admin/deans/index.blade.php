<x-sidebar-layout>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold" style="color:#f0dfc0;">Accounts</h1>
        <p class="mt-1 text-sm" style="color:rgba(200,169,126,0.6);">Manage Deans, Program Heads, and Teachers.</p>
    </div>
    <a href="{{ route('admin.users.create') }}"
       class="inline-block px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
        + Create Account
    </a>
</div>

<div class="flex gap-2 mb-4">
    <a href="{{ route('admin.deans.index') }}"
       class="px-3 py-1.5 text-xs font-medium rounded-full {{ !$activeRoleFilter ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600' }}">
        All
    </a>
    @foreach($managedRoles as $role)
        <a href="{{ route('admin.deans.index', ['role' => $role]) }}"
           class="px-3 py-1.5 text-xs font-medium rounded-full {{ $activeRoleFilter === $role ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600' }}">
            {{ ucwords(str_replace('_', ' ', $role)) }}
        </a>
    @endforeach
    <a href="{{ route('admin.deans.index', ['role' => 'pending_review']) }}"
       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border {{ $activeRoleFilter === 'pending_review' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-amber-700 border-amber-300 hover:bg-amber-50' }}">
        Pending Requests
        @if($pendingCount > 0)
            <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full {{ $activeRoleFilter === 'pending_review' ? 'bg-white/25 text-white' : 'bg-amber-500 text-white' }}">{{ $pendingCount }}</span>
        @endif
    </a>
</div>

<div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
    <div class="p-6">
        <table class="min-w-full divide-y divide-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($deans as $dean)
                    <tr>
                        <td class="px-6 py-4">{{ $dean->name }}</td>
                        <td class="px-6 py-4">{{ $dean->email }}</td>
                        <td class="px-6 py-4">
                            @if($dean->role)
                                <span class="px-2 py-1 text-xs text-indigo-700 rounded bg-indigo-50">
                                    {{ ucwords(str_replace('_', ' ', $dean->role)) }}
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs text-gray-500 bg-gray-100 rounded">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $dean->department->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded {{ $dean->status === 'active' ? 'bg-green-100 text-green-800' : ($dean->status === 'pending_review' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                {{ $dean->status === 'pending_review' ? 'Pending Review' : ucfirst($dean->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $dean->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 space-x-2">
                            @if($dean->status === 'pending_review')
                                <form action="{{ route('admin.deans.approve-request', $dean) }}" method="POST" class="inline-flex items-center gap-1">
                                    @csrf
                                    <select name="role" required class="px-2 py-1 text-xs border border-gray-300 rounded">
                                        <option value="">Assign role…</option>
                                        @foreach($managedRoles as $role)
                                            <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                                </form>
                                <form action="{{ route('admin.deans.reject-request', $dean) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Reject this registration request?')">Reject</button>
                                </form>
                            @else
                                <a href="{{ route('admin.deans.edit', $dean) }}" class="text-blue-600 hover:text-blue-900">Edit</a>

                                @if($dean->status === 'active')
                                    <form action="{{ route('admin.deans.deactivate', $dean) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Deactivate this account?')">Deactivate</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.deans.activate', $dean) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-green-600 hover:text-green-900">Activate</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No accounts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $deans->links() }}
        </div>
    </div>
</div>

</x-sidebar-layout>
