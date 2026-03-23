@extends('layouts.app')

@section('title', 'My Subject Requests')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Subject Requests</h1>
            <p class="text-sm mt-0.5" style="color:#6b7280;">Subjects you've submitted for Admin approval.</p>
        </div>
        <a href="{{ route('dean.subjects.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold transition rounded-xl"
           style="background:#1c1814; color:#f0dfc0;">
            <i class="text-xs fas fa-plus"></i> Request Subject
        </a>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-sm text-green-700 border border-green-200 rounded-xl bg-green-50">
            {{ session('success') }}
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-hidden border rounded-2xl" style="border-color:#e5e7eb;">
        <table class="w-full text-sm">
            <thead style="background:#f9fafb;">
                <tr>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Code</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Name</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Department</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Units</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Assigned Teacher</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Status</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Submitted</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subjects as $subject)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ $subject->code }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $subject->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $subject->department }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $subject->units }}</td>
                    <td class="px-4 py-3">
                        @if($subject->status === 'approved')
                            <button onclick="openAssignModal({{ $subject->id }}, '{{ addslashes($subject->name) }}', {{ $subject->teacher_id ?? 'null' }})"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium transition border rounded-lg hover:bg-gray-50"
                                    style="border-color:#d1d5db; color:#374151;">
                                @if($subject->teacher)
                                    <i class="text-green-500 fa-solid fa-user-check"></i>
                                    {{ $subject->teacher->name }}
                                @else
                                    <i class="text-gray-400 fa-solid fa-user-plus"></i>
                                    Assign Teacher
                                @endif
                            </button>
                        @else
                            <span class="text-xs italic text-gray-400">— pending approval</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($subject->status === 'pending')
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#fef3c7; color:#92400e;">⏳ Pending</span>
                        @elseif($subject->status === 'approved')
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#d1fae5; color:#065f46;"><i class="fa-solid fa-circle-check"></i> Approved</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#fee2e2; color:#991b1b;"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $subject->created_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        @if($subject->status === 'pending')
                        <div class="flex items-center gap-2">
                            <a href="{{ route('dean.subjects.edit', $subject) }}"
                               class="px-3 py-1 text-xs font-medium transition border rounded-lg"
                               style="border-color:#d1d5db; color:#374151;">Edit</a>
                            <form method="POST" action="{{ route('dean.subjects.destroy', $subject) }}"
                                  class="inline delete-form">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 text-xs font-medium transition border rounded-lg"
                                        style="border-color:#fecaca; color:#dc2626;">Cancel</button>
                            </form>
                        </div>
                        @elseif($subject->status === 'rejected')
                        <span class="text-xs italic text-gray-400" title="{{ $subject->rejected_reason }}">
                            {{ Str::limit($subject->rejected_reason, 40) }}
                        </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-sm text-center text-gray-400">
                        No subject requests yet. <a href="{{ route('dean.subjects.create') }}" style="color:#c8a97e;">Request one.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $subjects->links() }}</div>
</div>

{{-- Assign Teacher Modal --}}
<div id="assignModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black bg-opacity-40 backdrop-blur-sm">
    <div class="w-full max-w-sm p-6 bg-white shadow-xl rounded-2xl">
        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-indigo-50">
            <i class="text-indigo-600 fa-solid fa-chalkboard-user"></i>
        </div>
        <h3 class="mb-1 text-lg font-semibold text-center text-gray-800">Assign Teacher</h3>
        <p id="assignSubjectName" class="mb-4 text-sm font-medium text-center text-gray-500"></p>

        <form id="assignForm" method="POST">
            @csrf @method('PATCH')
            <input type="hidden" name="assign_teacher" value="1">

            <select name="teacher_id" id="teacherSelect"
                    class="w-full px-3 py-2 mb-4 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="">— No teacher assigned —</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </select>

            <div class="flex gap-3">
                <button type="button" onclick="closeAssignModal()"
                        class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90"
                        style="background:#1c1814;">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Cancel this request?',
            text: 'This subject request will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, cancel it',
            cancelButtonText: 'Keep it',
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
});

function openAssignModal(subjectId, subjectName, currentTeacherId) {
    document.getElementById('assignSubjectName').textContent = subjectName;
    document.getElementById('assignForm').action = '/dean/subjects/' + subjectId;
    const select = document.getElementById('teacherSelect');
    select.value = currentTeacherId ?? '';
    document.getElementById('assignModal').classList.replace('hidden', 'flex');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.replace('flex', 'hidden');
}
</script>
@endsection
