<x-sidebar-layout>

    <div class="mb-4 text-sm text-gray-500">
        <a href="{{ route('admin.departments.index') }}" class="hover:text-gray-700">Departments</a>
        <span class="mx-1">/</span>
        <span class="text-gray-800 font-medium">{{ $department->name }}</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">{{ $department->name }}</h2>
            <div class="text-xs text-gray-500">{{ $department->code }} &middot; Dean: {{ $department->dean?->name ?? '—' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($programs as $program)
            <a href="{{ route('admin.programs.show', [$department, $program]) }}"
               class="block overflow-hidden transition bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="font-semibold text-gray-800">{{ $program->name }}</div>
                    <div class="text-xs text-gray-500">{{ $program->code }}</div>
                </div>
                <div class="px-5 py-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Program Head</span>
                        <span class="font-medium text-gray-700">{{ $program->programHead?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Sections</span>
                        <span class="font-medium text-gray-700">{{ $program->sections_count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Faculty</span>
                        <span class="font-medium text-gray-700">{{ $program->teacher_count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Students</span>
                        <span class="font-medium text-gray-700">{{ $program->student_count }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="py-12 text-center text-gray-500 bg-white rounded-lg shadow-sm col-span-full">
                No programs found for this department.
            </div>
        @endforelse
    </div>

</x-sidebar-layout>
