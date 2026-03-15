<x-sidebar-layout>




<div class="mb-6">
    <a href="{{ route('teacher.classes.show', $section) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-800">Grade Configuration</h1>
    <p class="mt-1 text-sm text-gray-500">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
</div>

<div class="max-w-lg p-6 bg-white border border-gray-200 shadow-sm rounded-xl">

    <p class="mb-6 text-sm text-gray-500">Set the weight for each grade component. Total must equal <strong>100%</strong>.</p>

    <form method="POST" action="{{ route('teacher.grades.config.store', $section) }}" id="configForm">
        @csrf

        @php
            $fields = [
                'quiz_weight'       => 'Quizzes',
                'exam_weight'       => 'Exams',
                'project_weight'    => 'Projects',
                'assessment_weight' => 'Assessments',
                'attendance_weight' => 'Attendance',
            ];
        @endphp

        <div class="space-y-4">
            @foreach($fields as $field => $label)
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700 w-36">{{ $label }}</label>
                    <div class="flex items-center flex-1 gap-2">
                        <input type="number"
                               name="{{ $field }}"
                               id="{{ $field }}"
                               value="{{ old($field, $config?->$field ?? 0) }}"
                               min="0" max="100" step="0.01"
                               class="w-24 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                               oninput="updateTotal()">
                        <span class="text-sm text-gray-500">%</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Live Total --}}
        <div class="flex items-center justify-between pt-4 mt-6 border-t border-gray-100">
            <span class="text-sm font-semibold text-gray-700">Total</span>
            <span id="totalDisplay" class="text-lg font-bold text-gray-800">0%</span>
        </div>

        <div id="totalWarning" class="hidden mt-2 text-xs text-red-500">Total must equal 100% before saving.</div>
        <div id="totalOk" class="hidden mt-2 text-xs text-green-600">✓ Weights are valid.</div>

        <div class="mt-6">
            <button type="submit" id="submitBtn"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg text-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                Save Configuration
            </button>
        </div>
    </form>
</div>

<script>
function updateTotal() {
    const fields = ['quiz_weight','exam_weight','project_weight','assessment_weight','attendance_weight'];
    let total = 0;
    fields.forEach(f => {
        const val = parseFloat(document.getElementById(f).value) || 0;
        total += val;
    });
    total = Math.round(total * 100) / 100;

    document.getElementById('totalDisplay').textContent = total + '%';
    const isValid = Math.abs(total - 100) < 0.01;

    document.getElementById('totalWarning').classList.toggle('hidden', isValid);
    document.getElementById('totalOk').classList.toggle('hidden', !isValid);
    document.getElementById('submitBtn').disabled = !isValid;

    document.getElementById('totalDisplay').className = isValid
        ? 'text-lg font-bold text-green-600'
        : 'text-lg font-bold text-red-500';
}
updateTotal();
</script>

</x-sidebar-layout>
