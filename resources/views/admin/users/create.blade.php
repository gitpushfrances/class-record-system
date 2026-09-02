<x-sidebar-layout>

<div class="mb-6">
    <a href="{{ route('admin.deans.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to Accounts</a>
    <h1 class="mt-1 text-2xl font-bold" style="color:#f0dfc0;">Create Account</h1>
</div>

@if($errors->any())
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;">
        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
@endif

<div class="max-w-lg rounded-xl p-6" style="background:#211a12;border:1px solid rgba(200,169,126,0.15);">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Role</label>
                <select name="role" id="roleSelect"
                        class="w-full px-3 py-2 text-sm rounded-lg"
                        style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
                    @foreach($managedRoles as $role)
                        <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $role)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Department</label>
                <select name="department_id" id="departmentSelect"
                        class="w-full px-3 py-2 text-sm rounded-lg"
                        style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
                    <option value="">Unassigned</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }} ({{ $department->code }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs" style="color:rgba(200,169,126,0.5);">Optional. Only one Dean can be assigned per department.</p>
            </div>
            <div id="programField" style="display:none;">
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Program</label>
                <select name="program_id" id="programSelect"
                        class="w-full px-3 py-2 text-sm rounded-lg"
                        style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
                    <option value="">Select department first</option>
                </select>
                <p class="mt-1 text-xs" style="color:rgba(200,169,126,0.5);">Only one Program Head can be assigned per program.</p>
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Password</label>
                <input type="password" name="password"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
            </div>
            <div>
                <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Confirm Password</label>
                <input type="password" name="password_confirmation"
                       class="w-full px-3 py-2 text-sm rounded-lg"
                       style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
            </div>
            <button type="submit" class="w-full py-2.5 rounded-lg text-sm font-semibold"
                    style="background:linear-gradient(135deg,#9a7a50,#c8a97e);color:#1c1814;">
                Create Account
            </button>
        </div>
    </form>
</div>

<script>
    const programsData = @json($programs);

    function toggleDepartmentField() {
        const role = document.getElementById('roleSelect').value;
        const showProgram = (role === 'program_head' || role === 'teacher');
        document.getElementById('programField').style.display = showProgram ? 'block' : 'none';
        if (showProgram) populateProgramOptions();
    }

    function populateProgramOptions() {
        const deptId = document.getElementById('departmentSelect').value;
        const progSelect = document.getElementById('programSelect');
        const oldSelected = "{{ old('program_id') }}";

        progSelect.innerHTML = '';

        if (!deptId) {
            progSelect.innerHTML = '<option value="">Select department first</option>';
            return;
        }

        const matches = programsData.filter(p => String(p.department_id) === String(deptId));

        if (matches.length === 0) {
            progSelect.innerHTML = '<option value="">No programs in this department</option>';
            return;
        }

        progSelect.innerHTML = '<option value="">Unassigned</option>';
        matches.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.code} - ${p.name}`;
            if (oldSelected && String(oldSelected) === String(p.id)) opt.selected = true;
            progSelect.appendChild(opt);
        });
    }

    document.getElementById('roleSelect').addEventListener('change', toggleDepartmentField);
    document.getElementById('departmentSelect').addEventListener('change', populateProgramOptions);
    document.addEventListener('DOMContentLoaded', toggleDepartmentField);
</script>

</x-sidebar-layout>
