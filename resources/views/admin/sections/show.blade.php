<x-sidebar-layout>

    <div class="mb-4 text-sm text-gray-500">
        <a href="{{ route('admin.departments.index') }}" class="hover:text-gray-700">Departments</a>
        <span class="mx-1">/</span>
        <a href="{{ route('admin.departments.show', $department) }}" class="hover:text-gray-700">{{ $department->name }}</a>
        <span class="mx-1">/</span>
        <a href="{{ route('admin.programs.show', [$department, $program]) }}" class="hover:text-gray-700">{{ $program->name }}</a>
        <span class="mx-1">/</span>
        <span class="text-gray-800 font-medium">{{ $program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800">
            {{ $program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
        </h2>
        <a href="{{ route('admin.sections.students', [$department, $program, $section]) }}"
           class="px-4 py-2 text-sm font-medium text-white rounded hover:opacity-90" style="background-color: #c8a97e;">
            View Students
        </a>
    </div>

    <div class="p-6 mb-6 bg-white rounded-lg shadow">
        @if($currentTerm)
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Term</span><span class="font-medium">{{ $currentTerm->semester }}, {{ $currentTerm->academic_year }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Adviser</span><span class="font-medium">{{ $currentTerm->adviser?->name ?? '—' }}</span></div>
            </div>
        @else
            <div class="py-2 text-sm text-center text-gray-400">No active term this semester.</div>
        @endif
    </div>

    <div class="p-6 bg-white rounded-lg shadow">
        <h3 class="mb-4 text-sm font-semibold text-gray-700">Subjects & Teachers</h3>
        <div class="overflow-hidden border border-gray-200 rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 uppercase">Subject</th>
                        <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 uppercase">Teacher</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($currentTerm?->subjects ?? [] as $subject)
                        <tr>
                            <td class="px-3 py-2">{{ $subject->code }} — {{ $subject->name }}</td>
                            <td class="px-3 py-2">{{ $teachers->firstWhere('id', $subject->pivot->teacher_id)?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-3 py-3 text-xs text-center text-gray-400">No subjects assigned yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-sidebar-layout>
