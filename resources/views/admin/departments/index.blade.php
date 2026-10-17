<x-sidebar-layout>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Departments</h2>
        <button type="button" onclick="document.getElementById('addDepartment').showModal()" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; font-size:13px; font-weight:600; border-radius:8px; cursor:pointer; color:#fff; background:#0a5b05; border:none;"><i class="fa-solid fa-plus"></i> Add Department</button>
    </div>

    @include('admin.partials.code-name-dialog', ['id' => 'addDepartment', 'title' => 'Add Department', 'action' => route('admin.departments.store'), 'method' => 'POST', 'code' => '', 'name' => '', 'submit' => 'Create'])

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($departments as $department)
            <a href="{{ route('admin.departments.show', $department) }}"
               class="block overflow-hidden transition bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="font-semibold text-gray-800">{{ $department->name }}</div>
                    <div class="text-xs text-gray-500">{{ $department->code }}</div>
                </div>
                <div class="px-5 py-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Dean</span>
                        <span class="font-medium text-gray-700">{{ $department->dean?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Programs</span>
                        <span class="font-medium text-gray-700">{{ $department->programs_count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Faculty</span>
                        <span class="font-medium text-gray-700">{{ $department->teacher_count }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Students</span>
                        <span class="font-medium text-gray-700">{{ $department->student_count }}</span>
                    </div>
                </div>
            </a>
        @empty
            <div class="py-12 text-center text-gray-500 bg-white rounded-lg shadow-sm col-span-full">
                No departments found.
            </div>
        @endforelse
    </div>

</x-sidebar-layout>
