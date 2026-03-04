<x-sidebar-layout>

    
            @if (session('success'))
                <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($pendingTeachers->isEmpty())
                        <p class="text-gray-500">No pending teacher approvals.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Email</th>
                                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Registered</th>
                                    <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($pendingTeachers as $teacher)
                                    <tr>
                                        <td class="px-6 py-4">{{ $teacher->name }}</td>
                                        <td class="px-6 py-4">{{ $teacher->email }}</td>
                                        <td class="px-6 py-4">{{ $teacher->created_at->diffForHumans() }}</td>
                                        <td class="px-6 py-4">
                                            <form action="{{ route('admin.teachers.approve', $teacher) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-4 py-2 text-white bg-green-500 rounded hover:bg-green-700">
                                                    Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.teachers.reject', $teacher) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-4 py-2 text-white bg-red-500 rounded hover:bg-red-700" onclick="return confirm('Are you sure?')">
                                                    Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
</x-sidebar-layout>
