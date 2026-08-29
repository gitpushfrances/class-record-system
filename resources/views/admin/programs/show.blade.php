<x-sidebar-layout>

    <div class="mb-4 text-sm text-gray-500">
        <a href="{{ route('admin.departments.index') }}" class="hover:text-gray-700">Departments</a>
        <span class="mx-1">/</span>
        <a href="{{ route('admin.departments.show', $department) }}" class="hover:text-gray-700">{{ $department->name }}</a>
        <span class="mx-1">/</span>
        <span class="text-gray-800 font-medium">{{ $program->name }}</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">{{ $program->name }}</h2>
            <div class="text-xs text-gray-500">{{ $program->code }} &middot; Program Head: {{ $program->programHead?->name ?? '—' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($sections as $section)
            <a href="{{ route('admin.sections.show', [$department, $program, $section]) }}"
               class="block overflow-hidden transition bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="font-semibold text-gray-800">{{ $program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</div>
                    <div class="text-xs text-gray-500">{{ $section->year_level }}</div>
                </div>
                <div class="px-5 py-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Adviser</span>
                        <span class="font-medium text-gray-700">{{ $section->current_adviser?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Students</span>
                        <span class="font-medium text-gray-700">{{ $section->student_count }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="py-12 text-center text-gray-500 bg-white rounded-lg shadow-sm col-span-full">
                No sections found for this program.
            </div>
        @endforelse
    </div>

</x-sidebar-layout>
