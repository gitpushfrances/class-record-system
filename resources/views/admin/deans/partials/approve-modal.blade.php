{{-- Approve sign-up request modal. Uses $managedRoles, $departments, $programs from the parent view. --}}
<div id="approveModal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-black/50" role="dialog" aria-modal="true" aria-labelledby="approveModalTitle">
    <div class="w-full max-w-md bg-white rounded-lg shadow-xl">
        <form id="approveForm" method="POST" action="">
            @csrf

            <div class="px-6 py-5 border-b border-gray-100">
                <h2 id="approveModalTitle" class="text-lg font-semibold text-gray-900">Approve Account Request</h2>
                <p class="mt-0.5 text-sm text-gray-500">Assign this applicant's role and access scope.</p>
            </div>

            <div class="px-6 pt-4">
                <dl class="p-3 space-y-1 text-sm border border-gray-200 rounded-md bg-gray-50">
                    <div class="flex gap-2"><dt class="w-24 text-gray-500">Name</dt><dd id="approveName" class="font-medium text-gray-900"></dd></div>
                    <div class="flex gap-2"><dt class="w-24 text-gray-500">Email</dt><dd id="approveEmail" class="font-medium text-gray-900"></dd></div>
                </dl>
            </div>

            <div class="px-6 py-4 space-y-4">
                <div>
                    <label for="approve_role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select id="approve_role" name="role" required class="block w-full mt-1 text-sm border-gray-300 rounded-md shadow-sm">
                        <option value="">Select role</option>
                        @foreach($managedRoles as $role)
                            <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="approve_department_id" class="block text-sm font-medium text-gray-700">Department</label>
                    <select id="approve_department_id" name="department_id" required class="block w-full mt-1 text-sm border-gray-300 rounded-md shadow-sm">
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }} ({{ $department->code }})</option>
                        @endforeach
                    </select>
                    <p id="approve_dean_note" class="hidden mt-1 text-xs text-gray-500">Only one Dean can be assigned per department.</p>
                </div>

                <div id="approve_program_wrap" class="hidden">
                    <label for="approve_program_id" class="block text-sm font-medium text-gray-700">
                        Program <span id="approve_program_optional" class="hidden font-normal text-gray-400">(optional)</span>
                    </label>
                    <select id="approve_program_id" name="program_id" disabled class="block w-full mt-1 text-sm border-gray-300 rounded-md shadow-sm">
                        <option value="">Select department first</option>
                    </select>
                    <p id="approve_program_note" class="mt-1 text-xs text-gray-500"></p>
                </div>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 rounded-b-lg bg-gray-50">
                <button type="button" onclick="closeApproveModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
                <button type="submit" id="approveSubmit" class="px-4 py-2 text-sm font-semibold text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50">Approve</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const programs = @json($programs);
    const modal = document.getElementById('approveModal');
    const form = document.getElementById('approveForm');
    const submitBtn = document.getElementById('approveSubmit');
    const roleSel = document.getElementById('approve_role');
    const deptSel = document.getElementById('approve_department_id');
    const progWrap = document.getElementById('approve_program_wrap');
    const progSel = document.getElementById('approve_program_id');
    const progOptional = document.getElementById('approve_program_optional');
    const progNote = document.getElementById('approve_program_note');
    const deanNote = document.getElementById('approve_dean_note');
    let confirmed = false;

    function selectedText(sel) {
        return sel.value ? sel.options[sel.selectedIndex].text : '';
    }

    function fillPrograms() {
        const isTeacher = roleSel.value === 'teacher';
        progSel.innerHTML = '';

        if (!deptSel.value) {
            progSel.add(new Option('Select department first', ''));
            return;
        }

        const matches = programs.filter(p => String(p.department_id) === String(deptSel.value));

        if (!matches.length) {
            progSel.add(new Option('No approved programs in this department', ''));
            return;
        }

        progSel.add(new Option(isTeacher ? 'No specific program' : 'Select program', ''));
        matches.forEach(p => progSel.add(new Option(p.code + ' - ' + p.name, p.id)));
    }

    function syncFields() {
        const role = roleSel.value;
        const usesProgram = role === 'program_head' || role === 'teacher';

        progWrap.classList.toggle('hidden', !usesProgram);
        progSel.disabled = !usesProgram;
        progSel.required = role === 'program_head';
        progOptional.classList.toggle('hidden', role !== 'teacher');
        progNote.textContent = role === 'program_head'
            ? 'Only one Program Head can be assigned per program.'
            : 'Optional. Leave unassigned if the teacher is not tied to one program.';
        deanNote.classList.toggle('hidden', role !== 'dean');

        if (usesProgram) fillPrograms();
    }

    function submitConfirmed() {
        confirmed = true;
        submitBtn.disabled = true;
        form.submit();
    }

    window.openApproveModal = function (btn) {
        form.reset();
        confirmed = false;
        form.action = btn.dataset.approveUrl;
        document.getElementById('approveName').textContent = btn.dataset.name;
        document.getElementById('approveEmail').textContent = btn.dataset.email;
        submitBtn.disabled = false;
        syncFields();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.closeApproveModal = function () {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    roleSel.addEventListener('change', syncFields);
    deptSel.addEventListener('change', function () {
        if (roleSel.value === 'program_head' || roleSel.value === 'teacher') fillPrograms();
    });

    form.addEventListener('submit', function (e) {
        if (confirmed) return;
        e.preventDefault();

        const name = document.getElementById('approveName').textContent;
        const parts = [selectedText(roleSel), selectedText(deptSel)];
        if (progSel.value) parts.push(selectedText(progSel));
        const summary = name + ' will be approved as ' + parts.join(' · ') + '.';

        if (typeof Swal === 'undefined') {
            if (confirm(summary)) submitConfirmed();
            return;
        }

        Swal.fire({
            title: 'Approve this request?',
            text: summary,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, approve',
        }).then(function (result) {
            if (result.isConfirmed) submitConfirmed();
        });
    });

    modal.addEventListener('click', function (e) { if (e.target === modal) closeApproveModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeApproveModal(); });
})();
</script>
