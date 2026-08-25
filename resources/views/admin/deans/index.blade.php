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
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($dean->status === 'pending_review')
                                    <form action="{{ route('admin.deans.approve-request', $dean) }}" method="POST" class="inline-flex items-center gap-1">
                                        @csrf
                                        <select name="role" required class="px-2 py-1 text-xs border border-gray-300 rounded">
                                            <option value="">Assign role…</option>
                                            @foreach($managedRoles as $role)
                                                <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" title="Approve" class="p-1.5 text-green-600 rounded hover:bg-green-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.deans.reject-request', $dean) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="button" title="Reject" class="p-1.5 text-red-600 rounded hover:bg-red-50" onclick="confirmAction(this.closest('form'), 'Reject this registration request?', 'This cannot be undone.', 'warning')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.deans.edit', $dean) }}" title="Edit" class="p-1.5 text-blue-600 rounded hover:bg-blue-50 inline-flex">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </a>

                                    @if($dean->status === 'active')
                                        <form action="{{ route('admin.deans.deactivate', $dean) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" title="Deactivate" class="p-1.5 text-red-600 rounded hover:bg-red-50" onclick="confirmAction(this.closest('form'), 'Deactivate this account?', 'This account will lose access until reactivated.', 'warning')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.deans.activate', $dean) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Activate" class="p-1.5 text-green-600 rounded hover:bg-green-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
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

<script>
    function confirmAction(form, title, text, icon = 'warning') {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, continue',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    }
</script>

</x-sidebar-layout>
