<x-sidebar-layout>

    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Student Master List</h2>
        <a href="{{ route('dean.students.create') }}" class="px-4 py-2 text-sm font-medium text-white rounded hover:opacity-90" style="background-color: #c8a97e;">+ Add Student</a>
    </div>

    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
        <div class="p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Student No.</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Year Level</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Program</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($students as $student)
                        <tr>
                            <td class="px-6 py-4 font-mono text-sm">{{ $student->student_number }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}</div>
                                @if($student->email)
                                    <div class="text-sm text-gray-500">{{ $student->email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $student->year_level }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded
                                    @if($student->student_type === 'regular') bg-green-100 text-green-800
                                    @else bg-yellow-100 text-yellow-800 @endif">
                                    {{ ucfirst($student->student_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $student->program ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('dean.students.edit', $student) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <button type="button"
                                    onclick="confirmDelete({{ $student->id }}, '{{ addslashes($student->full_name) }}')"
                                    class="ml-2 text-red-600 hover:text-red-900">Remove</button>
                                <form id="delete-form-{{ $student->id }}"
                                    action="{{ route('dean.students.destroy', $student) }}"
                                    method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </div>
    </div>



{{-- Delete Confirmation Modal --}}
<div id="deleteModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black bg-opacity-40">
    <div class="w-full max-w-sm p-6 bg-white shadow-lg rounded-xl">
        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
            <i class="text-xl text-red-600 fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="mb-1 text-lg font-semibold text-center text-gray-800">Remove Student</h3>
        <p class="mb-1 text-sm text-center text-gray-500">You are about to remove:</p>
        <p id="deleteStudentName" class="mb-4 text-sm font-semibold text-center text-gray-800"></p>
        <p class="mb-6 text-xs text-center text-red-500">This will remove them from all enrolled classes permanently.</p>
        <div class="flex gap-3">
            <button onclick="closeDeleteModal()"
                class="flex-1 px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancel
            </button>
            <button id="confirmDeleteBtn"
                class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                Yes, Remove
            </button>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteStudentName').textContent = name;
    document.getElementById('confirmDeleteBtn').onclick = function () {
        document.getElementById('delete-form-' + id).submit();
    };
    document.getElementById('deleteModal').classList.replace('hidden', 'flex');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.replace('flex', 'hidden');
}
</script>
</x-sidebar-layout>
