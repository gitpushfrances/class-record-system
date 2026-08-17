<x-sidebar-layout>

<div class="mb-6">
    <a href="{{ route('teacher.classes.record', [$section, $subject]) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
    <h1 class="mt-1 text-2xl font-bold" style="color: #f0dfc0;">Grade Configuration</h1>
    <p class="mt-1 text-sm" style="color: rgba(200,169,126,0.6);">{{ $subject->code }} — {{ $subject->name }} &bull; {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
</div>

@if($errors->any())
    <div class="px-4 py-3 mb-4 text-sm rounded-lg" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

@php
    $defaultComponents = [
        ['key' => 'quiz',       'label' => 'Quiz',        'weight' => 20, 'period' => 'midterm'],
        ['key' => 'exam',        'label' => 'Exam',        'weight' => 30, 'period' => 'midterm'],
        ['key' => 'project',     'label' => 'Project',     'weight' => 20, 'period' => 'midterm'],
        ['key' => 'assessment',  'label' => 'Assessment',  'weight' => 15, 'period' => 'midterm'],
        ['key' => 'attendance',  'label' => 'Attendance',  'weight' => 15, 'period' => 'midterm'],
        ['key' => 'security',    'label' => 'Security',    'weight' => 20, 'period' => 'final'],
        ['key' => 'exam_final',  'label' => 'Final Exam',  'weight' => 30, 'period' => 'final'],
        ['key' => 'project_final','label' => 'Final Project','weight' => 25, 'period' => 'final'],
        ['key' => 'attendance_f', 'label' => 'Attendance (F)','weight' => 15, 'period' => 'final'],
        ['key' => 'others_1',    'label' => 'Others',      'weight' => 10, 'period' => 'final'],
    ];
    $components = $config ? $config->getComponents() : $defaultComponents;
@endphp

<form method="POST" action="{{ route('teacher.grades.config.store', [$section, $subject]) }}" id="configForm">
    @csrf

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- MIDTERM Period --}}
        <div class="p-5 rounded-xl" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold" style="color: #c8a97e;">Midterm Period</h2>
                <span id="midterm-total" class="text-sm font-bold">0%</span>
            </div>
            <div id="midterm-rows" class="space-y-2"></div>
            <button type="button" onclick="addRow('midterm')"
                    class="w-full px-3 py-2 mt-3 text-xs font-medium transition rounded-lg"
                    style="background: rgba(200,169,126,0.1); color: #c8a97e; border: 1px dashed rgba(200,169,126,0.3);">
                <i class="fas fa-plus" style="font-size:10px; margin-right:4px;"></i>Add Component
            </button>
        </div>

        {{-- FINAL Period --}}
        <div class="p-5 rounded-xl" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold" style="color: #c8a97e;">Final Period</h2>
                <span id="final-total" class="text-sm font-bold">0%</span>
            </div>
            <div id="final-rows" class="space-y-2"></div>
            <button type="button" onclick="addRow('final')"
                    class="w-full px-3 py-2 mt-3 text-xs font-medium transition rounded-lg"
                    style="background: rgba(200,169,126,0.1); color: #c8a97e; border: 1px dashed rgba(200,169,126,0.3);">
                <i class="fas fa-plus" style="font-size:10px; margin-right:4px;"></i>Add Component
            </button>
        </div>

    </div>

    {{-- Validation error banner --}}
    <div id="configErrorBox" class="mt-4" style="display:none; padding:12px 16px; border-radius:10px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; font-size: 13px;">
        <i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>
        <span id="configErrorText"></span>
    </div>

    {{-- Submit --}}
    <div class="flex items-center justify-between mt-6">
        <div>
            <span class="text-sm" style="color: rgba(200,169,126,0.7);">Both periods must each sum to <strong style="color: #f0dfc0;">100%</strong></span>
        </div>
        <button type="submit" id="submitBtn" disabled
                class="px-6 py-2 text-sm font-medium transition rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                style="background: linear-gradient(135deg, #9a7a50, #c8a97e); color: #1c1814;">
            Save Configuration
        </button>
    </div>

</form>

<script>
const initialComponents = {!! json_encode($components) !!};
initialComponents.forEach(c => { if (typeof c.editing === 'undefined') c.editing = false; });
let rowCounter = initialComponents.length;

const ROW_GRID = 'display:grid; grid-template-columns: 1fr 84px 22px 34px 34px; align-items:center; column-gap:8px;';

function showConfigError(msg) {
    const box = document.getElementById('configErrorBox');
    document.getElementById('configErrorText').textContent = msg;
    box.style.display = 'block';
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearConfigError() {
    document.getElementById('configErrorBox').style.display = 'none';
}

function iconBtn(icon, color, bg, onclick, title) {
    return `<button type="button" onclick="${onclick}" title="${title}"
                class="flex items-center justify-center transition rounded-lg"
                style="width:34px; height:34px; color:${color}; background:${bg};">
                <i class="fas ${icon}" style="font-size:11px;"></i>
            </button>`;
}

function weightInput(idx, value) {
    return `<input type="number" name="components[${idx}][weight]"
                   value="${value}" min="0" max="100" step="0.01"
                   class="w-full text-sm text-right rounded-lg"
                   style="height:34px; padding:0 8px; background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;"
                   oninput="updateTotals()">`;
}

function makeRow(comp, idx) {
    const isCustom = comp.key.startsWith('others_');

    if (!isCustom) {
        return `
            <div class="row-item" data-idx="${idx}" style="${ROW_GRID}">
                <input type="hidden" name="components[${idx}][key]" value="${comp.key}">
                <input type="hidden" name="components[${idx}][period]" value="${comp.period}">
                <input type="hidden" name="components[${idx}][label]" value="${comp.label}">
                <span class="text-sm font-medium truncate" style="color: #f0dfc0;">${comp.label}</span>
                ${weightInput(idx, comp.weight)}
                <span class="text-center" style="font-size:11px; color: rgba(200,169,126,0.45);">%</span>
                <span></span>
                ${iconBtn('fa-trash', '#f87171', 'rgba(239,68,68,0.1)', 'removeRow(this)', 'Delete')}
            </div>
        `;
    }

    if (comp.editing) {
        return `
            <div class="row-item" data-idx="${idx}" style="${ROW_GRID}">
                <input type="hidden" name="components[${idx}][key]" value="${comp.key}">
                <input type="hidden" name="components[${idx}][period]" value="${comp.period}">
                <input type="text" name="components[${idx}][label]"
                       value="${comp.label}" placeholder="Component name"
                       class="w-full text-sm rounded-lg"
                       style="height:34px; padding:0 10px; background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                ${weightInput(idx, comp.weight)}
                <span class="text-center" style="font-size:11px; color: rgba(200,169,126,0.45);">%</span>
                ${iconBtn('fa-check', '#86efac', 'rgba(34,197,94,0.14)', 'confirmRow(this)', 'Confirm')}
                ${iconBtn('fa-trash', '#f87171', 'rgba(239,68,68,0.1)', 'removeRow(this)', 'Delete')}
            </div>
        `;
    }

    return `
        <div class="row-item" data-idx="${idx}" style="${ROW_GRID}">
            <input type="hidden" name="components[${idx}][key]" value="${comp.key}">
            <input type="hidden" name="components[${idx}][period]" value="${comp.period}">
            <input type="hidden" name="components[${idx}][label]" value="${comp.label}">
            <input type="hidden" name="components[${idx}][weight]" value="${comp.weight}">
            <span class="text-sm font-medium truncate" style="color: #f0dfc0;">${comp.label}</span>
            <span class="text-right" style="font-size:14px; color: #f0dfc0; padding-right:4px;">${comp.weight}</span>
            <span class="text-center" style="font-size:11px; color: rgba(200,169,126,0.45);">%</span>
            ${iconBtn('fa-pen', '#c8a97e', 'rgba(200,169,126,0.12)', 'editRow(this)', 'Edit')}
            ${iconBtn('fa-trash', '#f87171', 'rgba(239,68,68,0.1)', 'removeRow(this)', 'Delete')}
        </div>
    `;
}

function renderRows() {
    const mid = initialComponents.filter(c => c.period === 'midterm');
    const fin = initialComponents.filter(c => c.period === 'final');

    document.getElementById('midterm-rows').innerHTML =
        mid.map(c => makeRow(c, initialComponents.indexOf(c))).join('');
    document.getElementById('final-rows').innerHTML =
        fin.map(c => makeRow(c, initialComponents.indexOf(c))).join('');

    updateTotals();
}

function syncFromDOM() {
    document.querySelectorAll('.row-item').forEach(row => {
        const idx = parseInt(row.dataset.idx);
        if (!initialComponents[idx]) return;
        const labelInput  = row.querySelector('input[name$="[label]"]');
        const weightInput = row.querySelector('input[name$="[weight]"]');
        if (labelInput)  initialComponents[idx].label  = labelInput.value;
        if (weightInput) initialComponents[idx].weight = parseFloat(weightInput.value) || 0;
    });
}

function addRow(period) {
    syncFromDOM();
    clearConfigError();
    const newComp = { key: 'others_' + rowCounter, label: '', weight: 0, period, editing: true };
    initialComponents.push(newComp);
    rowCounter++;
    renderRows();
}

function editRow(button) {
    syncFromDOM();
    clearConfigError();
    const idx = parseInt(button.closest('.row-item').dataset.idx);
    initialComponents[idx].editing = true;
    renderRows();
}

function confirmRow(button) {
    const row = button.closest('.row-item');
    const idx = parseInt(row.dataset.idx);
    const labelInput  = row.querySelector('input[name$="[label]"]');
    const weightInput = row.querySelector('input[name$="[weight]"]');
    const label = labelInput ? labelInput.value.trim() : '';

    if (!label) {
        showConfigError('Component name cannot be blank. Please enter a name before confirming.');
        labelInput.style.borderColor = '#f87171';
        labelInput.focus();
        return;
    }

    clearConfigError();
    initialComponents[idx].label  = label;
    initialComponents[idx].weight = weightInput ? (parseFloat(weightInput.value) || 0) : 0;
    initialComponents[idx].editing = false;
    renderRows();
}

function removeRow(button) {
    syncFromDOM();
    const idx = parseInt(button.closest('.row-item').dataset.idx);
    const comp = initialComponents[idx];
    const confirmed = confirm(
        `Remove "${comp.label || 'this component'}" from ${comp.period === 'midterm' ? 'Midterm' : 'Final'} configuration?\n\n` +
        `If grade items were already created using this component, they will remain but won't be scorable until you re-add it.`
    );
    if (!confirmed) return;

    clearConfigError();
    initialComponents.splice(idx, 1);
    renderRows();
}

function updateTotals() {
    let mid = 0, fin = 0;
    document.querySelectorAll('input[name$="[weight]"]').forEach(input => {
        const idx = parseInt(input.name.match(/\d+/)[0]);
        const periodInput = document.querySelector(`input[name="components[${idx}][period]"]`);
        if (periodInput && periodInput.value === 'midterm') mid += parseFloat(input.value) || 0;
        if (periodInput && periodInput.value === 'final') fin += parseFloat(input.value) || 0;
    });
    mid = Math.round(mid * 100) / 100;
    fin = Math.round(fin * 100) / 100;

    const midOk = Math.abs(mid - 100) < 0.01;
    const finOk = Math.abs(fin - 100) < 0.01;

    const mTotal = document.getElementById('midterm-total');
    const fTotal = document.getElementById('final-total');
    mTotal.textContent = mid + '%';
    fTotal.textContent = fin + '%';
    mTotal.style.color = midOk ? '#86efac' : '#fca5a5';
    fTotal.style.color = finOk ? '#86efac' : '#fca5a5';
    document.getElementById('submitBtn').disabled = !(midOk && finOk);
}

document.getElementById('configForm').addEventListener('submit', function (e) {
    syncFromDOM();
    const blank = initialComponents.find(c => !c.label || !c.label.trim());
    if (blank) {
        e.preventDefault();
        showConfigError('One or more components have a blank name. Please name every component before saving, or delete the unfinished one.');
    }
});

renderRows();
</script>

</x-sidebar-layout>
