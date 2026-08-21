<x-sidebar-layout>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.deans.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <x-input-label for="name" :value="__('Full Name')" />
                            <x-text-input id="name" name="name" type="text" class="block w-full mt-1" :value="old('name', $user->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="block w-full mt-1" :value="old('email', $user->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                        <div class="mb-4">
                            <x-input-label for="department_id" :value="__('Department')" />
                            <select id="department_id" name="department_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                <option value="">Unassigned</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }} ({{ $department->code }})</option>
                                @endforeach
                            </select>
                            @if($user->role === 'dean')
                                <p class="mt-1 text-xs text-gray-500">Only one Dean can be assigned per department — assigning here will unassign any existing Dean from that department.</p>
                            @endif
                            <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                        </div>
                        @if($user->role === 'program_head')
                            <div class="mb-4">
                                <x-input-label for="program_id" :value="__('Program')" />
                                <select id="program_id" name="program_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                    <option value="">Select department first</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Only one Program Head can be assigned per program.</p>
                                <x-input-error :messages="$errors->get('program_id')" class="mt-2" />
                            </div>
                        @endif

                        <div class="mb-4">
                            <x-input-label for="password" :value="__('New Password (leave blank to keep current)')" />
                            <x-text-input id="password" name="password" type="password" class="block w-full mt-1" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="block w-full mt-1" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Update Dean</x-primary-button>
                            <a href="{{ route('admin.deans.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                        </div>
                    </form>

@if($user->role === 'program_head')
<script>
    const programsData = @json($programs);
    const currentProgramId = "{{ old('program_id', $user->program_id) }}";

    function populateProgramOptions() {
        const deptId = document.getElementById('department_id').value;
        const progSelect = document.getElementById('program_id');

        progSelect.innerHTML = '';

        if (!deptId) {
            progSelect.innerHTML = '<option value="">Select department first</option>';
            return;
        }

        const matches = programsData.filter(p => String(p.department_id) === String(deptId));

        progSelect.innerHTML = '<option value="">Unassigned</option>';

        if (matches.length === 0) {
            progSelect.innerHTML = '<option value="">No programs in this department</option>';
            return;
        }

        matches.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.code} - ${p.name}`;
            if (currentProgramId && String(currentProgramId) === String(p.id)) opt.selected = true;
            progSelect.appendChild(opt);
        });
    }

    document.getElementById('department_id').addEventListener('change', populateProgramOptions);
    document.addEventListener('DOMContentLoaded', populateProgramOptions);
</script>
@endif

</x-sidebar-layout>
