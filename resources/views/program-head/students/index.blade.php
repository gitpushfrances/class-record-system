<x-sidebar-layout>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Student Master List</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your program's student records.</p>
        </div>
        <a href="{{ route('program-head.students.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold transition rounded-lg"
           style="background:linear-gradient(135deg,#9a7a50,#c8a97e); color:#1c1814;">
            <i class="text-xs fa-solid fa-plus"></i> Add Student
        </a>
    </div>

    <form method="GET" class="flex items-center gap-3 mb-4">
        <select name="year_level" onchange="this.form.submit()" class="px-3 py-2 text-sm border rounded-lg" style="border-color:#d1d5db;">
            <option value="">All Years</option>
            @foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $level)
                <option value="{{ $level }}" {{ request('year_level') == $level ? 'selected' : '' }}>{{ $level }}</option>
            @endforeach
        </select>
        <select name="section_id" onchange="this.form.submit()" class="px-3 py-2 text-sm border rounded-lg" style="border-color:#d1d5db;">
            <option value="">All Sections</option>
            <option value="unassigned" {{ request('section_id') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
            @foreach($sections as $section)
                <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>{{ $section->year_number }}-{{ $section->section_letter }}</option>
            @endforeach
        </select>
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
    </form>

    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead style="background:#f9fafb;">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'student_number', 'direction' => $sort === 'student_number' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-800">
                            Student No.
                            @if($sort === 'student_number')<i class="fa-solid fa-sort-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>@endif
                        </a>
                    </th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'last_name', 'direction' => $sort === 'last_name' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-800">
                            Name
                            @if($sort === 'last_name')<i class="fa-solid fa-sort-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>@endif
                        </a>
                    </th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Year Level</th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => $sort === 'created_at' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-800">
                            Date Added
                            @if($sort === 'created_at')<i class="fa-solid fa-sort-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>@endif
                        </a>
                    </th>
                    <th class="px-6 py-3 text-xs font-semibold text-center text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($students as $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-mono text-sm text-gray-700">{{ $student->student_number }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}</div>
                            @if($student->email)
                                <div class="text-sm text-gray-500">{{ $student->email }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->year_level }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($student->student_type === 'regular') bg-green-100 text-green-700
                                @else bg-amber-100 text-amber-700 @endif">
                                {{ ucfirst($student->student_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('program-head.students.edit', $student) }}" title="Edit"
                                   class="flex items-center justify-center w-8 h-8 transition rounded-lg hover:opacity-80"
                                   style="color:#8a6a3d; background:rgba(200,169,126,0.18); border:1px solid rgba(200,169,126,0.35);">
                                    <i class="text-xs fa-solid fa-pen"></i>
                                </a>
                                <button type="button" title="Remove"
                                    onclick="confirmDelete({{ $student->id }}, '{{ addslashes($student->full_name) }}')"
                                    class="flex items-center justify-center w-8 h-8 transition rounded-lg hover:opacity-80"
                                    style="color:#dc2626; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3);">
                                    <i class="text-xs fa-solid fa-trash"></i>
                                </button>
                                <form id="delete-form-{{ $student->id }}"
                                    action="{{ route('program-head.students.destroy', $student) }}"
                                    method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-sm text-center text-gray-400">No students found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $students->links() }}
    </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Remove Student',
        html: `You are about to remove:<br><strong>${name}</strong><br><br><span style="color:#dc2626; font-size: 0.8rem;">This will remove them from all enrolled classes permanently.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Remove',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + id).submit();
        }
    });
}
</script>
</x-sidebar-layout>
