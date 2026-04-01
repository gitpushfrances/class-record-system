<x-sidebar-layout>

<div class="mb-6">
    <a href="{{ route('teacher.classes.show', $section) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
    <h1 class="mt-1 text-2xl font-bold" style="color: #f0dfc0;">Grade Configuration</h1>
    <p class="mt-1 text-sm" style="color: rgba(200,169,126,0.6);">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
</div>

@if($errors->any())
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
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

<form method="POST" action="{{ route('teacher.grades.config.store', $section) }}" id="configForm">
    @csrf

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- MIDTERM Period --}}
        <div class="rounded-xl p-5" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold" style="color: #c8a97e;">Midterm Period</h2>
                <span id="midterm-total" class="text-sm font-bold">0%</span>
            </div>
            <div id="midterm-rows" class="space-y-3"></div>
            <button type="button" onclick="addRow('midterm')"
                    class="mt-3 w-full px-3 py-2 rounded-lg text-xs font-medium transition"
                    style="background: rgba(200,169,126,0.1); color: #c8a97e; border: 1px dashed rgba(200,169,126,0.3);">
                + Add Component
            </button>
        </div>

        {{-- FINAL Period --}}
        <div class="rounded-xl p-5" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold" style="color: #c8a97e;">Final Period</h2>
                <span id="final-total" class="text-sm font-bold">0%</span>
            </div>
            <div id="final-rows" class="space-y-3"></div>
            <button type="button" onclick="addRow('final')"
                    class="mt-3 w-full px-3 py-2 rounded-lg text-xs font-medium transition"
                    style="background: rgba(200,169,126,0.1); color: #c8a97e; border: 1px dashed rgba(200,169,126,0.3);">
                + Add Component
            </button>
        </div>

    </div>

    {{-- Submit --}}
    <div class="flex items-center justify-between mt-6">
        <div>
            <span class="text-sm" style="color: rgba(200,169,126,0.7);">Both periods must each sum to <strong style="color: #f0dfc0;">100%</strong></span>
        </div>
        <button type="submit" id="submitBtn" disabled
                class="px-6 py-2 rounded-lg text-sm font-medium transition disabled:opacity-50 disabled:cursor-not-allowed"
                style="background: linear-gradient(135deg, #9a7a50, #c8a97e); color: #1c1814;">
            Save Configuration
        </button>
    </div>

</form>

<script>
const initialComponents = {{!! json_encode($components) !!}};
let rowCounter = initialComponents.length;

function makeRow(comp, idx) {
    const isCustom = comp.key.startsWith('others_');
    return `
        <div class="flex items-center gap-2 row-item" data-idx="${idx}">
            <input type="hidden" name="components[${idx}][key]" value="${comp.key}">
            <input type="hidden" name="components[${idx}][period]" value="${comp.period}">
            <input type="${isCustom ? 'text' : 'hidden'}" name="components[${idx}][label]"
                   value="${comp.label}" placeholder="Component name"
                   class="flex-1 px-2 py-1.5 rounded text-sm"
                   style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;${isCustom ? '' : 'display:none;'}">
            ${isCustom ? '' : `<span class="text-sm font-medium" style="color: #f0dfc0; min-width:70px;">${comp.label}</span>`}
            <input type="number" name="components[${idx}][weight]"
                   value="${comp.weight}" min="0" max="100" step="0.01"
                   class="w-20 px-2 py-1.5 rounded text-sm text-right"
                   style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;"
                   oninput="updateTotals()">
            <span class="text-xs" style="color: rgba(200,169,126,0.5);">%</span>
            ${isCustom ? `<button type="button" onclick="removeRow(this)"
                class="text-xs px-2 py-1 rounded"
                style="color: #f87171; background: rgba(239,68,68,0.1);">X</button>` : ''}
        </div>
    `;
}

function renderRows() {
    const mid = initialComponents.filter(c => c.period === 'midterm');
    const fin = initialComponents.filter(c => c.period === 'final');

    document.getElementById('midterm-rows').innerHTML =
        mid.map((c, i) => makeRow(c, initialComponents.indexOf(c))).join('');
    document.getElementById('final-rows').innerHTML =
        fin.map((c, i) => makeRow(c, initialComponents.indexOf(c))).join('');

    updateTotals();
}

function addRow(period) {
    const newComp = { key: 'others_' + rowCounter, label: '', weight: 0, period };
    initialComponents.push(newComp);
    rowCounter++;
    renderRows();
}

function removeRow(button) {
    const idx = parseInt(button.closest('.row-item').dataset.idx);
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

renderRows();
</script>

</x-sidebar-layout>