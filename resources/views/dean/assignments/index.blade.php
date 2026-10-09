<x-sidebar-layout>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Teacher Assignments</h1>
    <p class="mt-1 text-sm text-gray-500">Assign teachers to subjects per section.</p>
</div>

@if(session('success'))
    <div class="px-4 py-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="px-4 py-3 mb-4 text-sm text-red-600 border border-red-200 rounded-lg bg-red-50">{{ session('error') }}</div>
@endif

{{-- Add Assignment Form --}}
<div class="p-5 mb-6 bg-white border border-gray-200 shadow-sm rounded-xl">
    <h2 class="mb-4 font-semibold text-gray-700">New Assignment</h2>
    <form method="POST" action="{{ route('dean.assignments.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
        @csrf
        <div>
            <label class="block mb-1 text-xs font-medium text-gray-600">Section</label>
            <select name="section_id" required
                    class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="">Select Section</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block mb-1 text-xs font-medium text-gray-600">Subject</label>
            <select name="subject_id" required
                    class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="">Select Subject</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}">{{ $subject->code }} — {{ $subject->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block mb-1 text-xs font-medium text-gray-600">Teacher</label>
            <select name="teacher_id" required
                    class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400">
                <option value="">Select Teacher</option>
                @foreach($teachers as $teacher)
                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit"
                    class="w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                Assign
            </button>
        </div>
    </form>
</div>

{{-- Assignments Table --}}
<div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-700">Current Assignments</h2>
    </div>
    @if($assignments->isEmpty())
        <div class="px-6 py-10 text-sm text-center text-gray-400">No assignments yet.</div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">Section</th>
                        <th class="px-6 py-3 text-left">Subject</th>
                        <th class="px-6 py-3 text-left">Teacher</th>
                        <th class="px-6 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($assignments as $a)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-800">
                                {{ $a->program_code }} {{ $a->year_number }}-{{ $a->section_letter }}
                                <span class="block text-xs text-gray-500">{{ $a->semester }}, {{ $a->academic_year }}</span>
                            </td>
                            <td class="px-6 py-3 text-gray-800">{{ $a->subject_code }} — {{ $a->subject_name }}</td>
                            <td class="px-6 py-3 text-gray-800">{{ $a->teacher_name }}</td>
                            <td class="px-6 py-3">
                                <form method="POST" action="{{ route('dean.assignments.destroy', $a->id) }}" class="remove-assignment-form">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" class="assignment-label" value="{{ $a->subject_code }} — {{ $a->teacher_name }} ({{ $a->program_code }} {{ $a->year_number }}-{{ $a->section_letter }})">
                                    <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelectorAll('.remove-assignment-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const label = form.querySelector('.assignment-label').value;
            if (typeof Swal === 'undefined') {
                if (confirm('Remove this assignment?\n' + label)) form.submit();
                return;
            }
            Swal.fire({
                title: 'Remove this assignment?',
                text: label,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, remove it',
                cancelButtonText: 'Cancel',
            }).then(result => { if (result.isConfirmed) form.submit(); });
        });
    });
</script>

</x-sidebar-layout>
