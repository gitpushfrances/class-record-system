<x-sidebar-layout>

@section('title', 'Enter Scores — ' . $gradeItem->name)



<div class="mb-6">
    <a href="{{ route('teacher.grades.items', $section) }}" class="text-sm text-indigo-600 hover:underline">← Back to Grade Items</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-800">{{ $gradeItem->name }}</h1>
    <p class="mt-1 text-sm text-gray-500">
        {{ $section->subject->code }} — {{ $section->section_name }} &bull;
        {{ ucfirst($gradeItem->component_type) }} &bull;
        Max Score: <strong>{{ $gradeItem->max_score }}</strong>
        @if($gradeItem->date_given)
            &bull; {{ $gradeItem->date_given->format('M d, Y') }}
        @endif
    </p>
</div>

@if($gradeItem->is_locked)
    <div class="px-4 py-3 mb-4 text-sm text-gray-600 bg-gray-100 border border-gray-300 rounded-lg">
        🔒 This grade item is locked. Scores cannot be modified.
    </div>
@endif

<div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">

    <form method="POST" action="{{ route('teacher.grades.scores.store', [$section, $gradeItem]) }}">
        @csrf

        <table class="w-full text-sm">
            <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">#</th>
                    <th class="px-6 py-3 text-left">Student No.</th>
                    <th class="px-6 py-3 text-left">Name</th>
                    <th class="w-40 px-6 py-3 text-center">Score / {{ $gradeItem->max_score }}</th>
                    <th class="px-6 py-3 text-center w-28">Percentage</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($enrollments as $i => $enrollment)
                    @php
                        $existing = $enrollment->studentGrades->first();
                        $score = $existing?->score ?? '';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $enrollment->student?->student_number ?? 'N/A' }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $enrollment->student?->full_name ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-center">
                            <input type="hidden" name="scores[{{ $i }}][enrollment_id]" value="{{ $enrollment->id }}">
                            @if(! $gradeItem->is_locked)
                                <input type="number"
                                       name="scores[{{ $i }}][score]"
                                       value="{{ old("scores.{$i}.score", $score) }}"
                                       min="0" max="{{ $gradeItem->max_score }}" step="0.01"
                                       class="w-24 border border-gray-300 rounded-lg px-3 py-1.5 text-center text-sm focus:ring-2 focus:ring-indigo-500 score-input"
                                       data-max="{{ $gradeItem->max_score }}"
                                       data-row="{{ $i }}">
                            @else
                                <span class="font-semibold text-gray-700">{{ $score !== '' ? $score : '—' }}</span>
                                <input type="hidden" name="scores[{{ $i }}][score]" value="{{ $score }}">
                            @endif
                        </td>
                        <td class="px-6 py-3 text-xs text-center text-gray-500" id="pct-{{ $i }}">
                            @if($score !== '')
                                {{ number_format(($score / $gradeItem->max_score) * 100, 1) }}%
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(! $gradeItem->is_locked)
            <div class="flex justify-end px-6 py-4 border-t border-gray-100">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition">
                    Save Scores
                </button>
            </div>
        @endif
    </form>
</div>

<script>
document.querySelectorAll('.score-input').forEach(input => {
    input.addEventListener('input', function () {
        const row  = this.dataset.row;
        const max  = parseFloat(this.dataset.max);
        const val  = parseFloat(this.value);
        const cell = document.getElementById('pct-' + row);

        if (!isNaN(val) && max > 0) {
            cell.textContent = ((val / max) * 100).toFixed(1) + '%';
            this.classList.toggle('border-red-400', val > max);
        } else {
            cell.textContent = '—';
        }
    });
});
</script>

</x-sidebar-layout>
