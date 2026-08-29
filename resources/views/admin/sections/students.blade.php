<x-sidebar-layout>

    <div class="mb-4 text-sm text-gray-500">
        <a href="{{ route('admin.departments.index') }}" class="hover:text-gray-700">Departments</a>
        <span class="mx-1">/</span>
        <a href="{{ route('admin.departments.show', $department) }}" class="hover:text-gray-700">{{ $department->name }}</a>
        <span class="mx-1">/</span>
        <a href="{{ route('admin.programs.show', [$department, $program]) }}" class="hover:text-gray-700">{{ $program->name }}</a>
        <span class="mx-1">/</span>
        <a href="{{ route('admin.sections.show', [$department, $program, $section]) }}" class="hover:text-gray-700">{{ $program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</a>
        <span class="mx-1">/</span>
        <span class="text-gray-800 font-medium">Students</span>
    </div>

    <h2 class="mb-6 text-lg font-semibold text-gray-800">
        Students — {{ $program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
    </h2>

    <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Student No.</th>
                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Year</th>
                    <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Section</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($students as $student)
                    <tr>
                        <td class="px-4 py-3">{{ $student->student_number }}</td>
                        <td class="px-4 py-3">{{ $student->full_name }}</td>
                        <td class="px-4 py-3">{{ $student->year_level }}</td>
                        <td class="px-4 py-3">{{ $program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-sm text-center text-gray-400">No students enrolled this term.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-sidebar-layout>
