<x-sidebar-layout>
<div style="margin-bottom:12px;"><x-back-button :href="route('admin.departments.index')" label="Back to Departments" /></div>

    <div class="mb-4 text-sm text-gray-500">
        <a href="{{ route('admin.departments.index') }}" class="hover:text-gray-700">Departments</a>
        <span class="mx-1">/</span>
        <span class="text-gray-800 font-medium">{{ $department->name }}</span>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">{{ $department->name }}</h2>
            <div class="text-xs text-gray-500">{{ $department->code }} @if($department->status === 'inactive')<span style="margin-left:6px; padding:1px 8px; font-size:11px; font-weight:600; color:#6b7280; background:#f3f4f6; border-radius:9999px;">Inactive</span>@endif &middot; Dean: {{ $department->dean?->name ?? '—' }}</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <button type="button" onclick="document.getElementById('editDepartment').showModal()" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; font-size:13px; font-weight:600; border-radius:8px; cursor:pointer; color:#374151; background:#fff; border:1px solid #d1d5db;"><i class="fa-solid fa-pen"></i> Edit</button>
            @include('admin.partials.status-actions', ['active' => $department->status === 'active', 'statusAction' => route('admin.departments.status', $department), 'deleteAction' => route('admin.departments.destroy', $department), 'label' => $department->code])
            <button type="button" onclick="document.getElementById('addProgram').showModal()" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; font-size:13px; font-weight:600; border-radius:8px; cursor:pointer; color:#fff; background:#0a5b05; border:none;"><i class="fa-solid fa-plus"></i> Add Program</button>
        </div>
    </div>

    @include('admin.partials.code-name-dialog', ['id' => 'editDepartment', 'title' => 'Edit Department', 'action' => route('admin.departments.update', $department), 'method' => 'PUT', 'code' => $department->code, 'name' => $department->name, 'submit' => 'Save'])
    @include('admin.partials.code-name-dialog', ['id' => 'addProgram', 'title' => 'Add Program to ' . $department->code, 'action' => route('admin.programs.store', $department), 'method' => 'POST', 'code' => '', 'name' => '', 'submit' => 'Add', 'codeHint' => 'BSIT', 'nameHint' => 'Bachelor of Science in Information Technology'])

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($programs as $program)
            <a href="{{ route('admin.programs.show', [$department, $program]) }}"
               class="block overflow-hidden transition bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="font-semibold text-gray-800">{{ $program->name }}</div>
                    <div class="text-xs text-gray-500">{{ $program->code }}@if($program->status === 'inactive')<span style="margin-left:6px; padding:1px 8px; font-size:11px; font-weight:600; color:#6b7280; background:#f3f4f6; border-radius:9999px;">Inactive</span>@endif</div>
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
