<x-sidebar-layout>

{{-- Header --}}
@php
    $passedCount = collect($liveGrades)->where('remarks', 'passed')->count();
    $failedCount = collect($liveGrades)->where('remarks', 'failed')->count();
    $gender = in_array(request('gender'), ['male', 'female'], true) ? request('gender') : 'all';
    $order = request('order') === 'id' ? 'id' : 'name';
    $totalCols = 6 + collect($matrix)->sum(fn($c) => $c['type'] === 'attendance' ? 2 : $c['items']->count() + 1);
@endphp
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">{{ $subject->code }} — {{ $subject->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }} &bull; {{ $section->year_level }} &bull; {{ $currentTerm?->semester }} &bull; {{ $currentTerm?->academic_year }}
        </p>
        <div class="flex flex-wrap items-center gap-2" style="margin-top:12px;">
            <span class="px-2 py-1 text-xs font-semibold text-indigo-700 rounded-full bg-indigo-50">Grading: {{ \App\Models\FinalGrade::COMPUTATION_METHODS[$config->computation_method ?? 'standard'] ?? 'Standard conversion table' }}</span>
            <span class="px-2 py-1 text-xs text-gray-600 bg-gray-100 rounded-full">{{ $enrollments->count() }} {{ \Illuminate\Support\Str::plural('student', $enrollments->count()) }}</span>
            @if($passedCount > 0)
                <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">{{ $passedCount }} passed</span>
            @endif
            @if($failedCount > 0)
                <span class="px-2 py-1 text-xs text-red-700 bg-red-100 rounded-full">{{ $failedCount }} failed</span>
            @endif
        </div>
    </div>
    <div class="flex flex-wrap gap-2 mt-1">
        <a href="{{ route('teacher.grades.config', [$section, $subject]) }}"
           class="bg-white border border-gray-300 text-gray-700 text-sm font-semibold px-3 py-2.5 rounded-lg transition hover:bg-gray-50">
            <i class="fa-solid fa-sliders"></i> Config
        </a>
        <a href="{{ route('teacher.grades.items', [$section, $subject]) }}"
           class="bg-white border border-gray-300 text-gray-700 text-sm font-semibold px-3 py-2.5 rounded-lg transition hover:bg-gray-50">
            <i class="fa-solid fa-list-check"></i> Items
        </a>
        <a href="{{ route('teacher.grades.final', [$section, $subject]) }}"
           class="bg-white border border-gray-300 text-gray-700 text-sm font-semibold px-3 py-2.5 rounded-lg transition hover:bg-gray-50">
            <i class="fa-solid fa-graduation-cap"></i> Final Grades
        </a>
        <a href="{{ route('teacher.attendance.index', [$section, $subject]) }}"
           class="bg-white border border-gray-300 text-gray-700 text-sm font-semibold px-3 py-2.5 rounded-lg transition hover:bg-gray-50">
            <i class="fa-solid fa-clipboard-check"></i> Attendance
        </a>
        <a href="{{ route('teacher.classes.record.export', ['section' => $section, 'subject' => $subject, 'gender' => $gender, 'order' => $order]) }}"
           class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
            <i class="fa-solid fa-file-excel"></i> Export Excel
        </a>
        <a href="{{ route('teacher.classes.record.print', ['section' => $section, 'subject' => $subject, 'gender' => $gender, 'order' => $order]) }}" target="_blank"
           class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
            <i class="fa-solid fa-print"></i> Print
        </a>
    </div>
</div>

@if(collect($matrix)->where('type', 'items')->isEmpty())
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 mb-4 text-sm text-yellow-700 bg-yellow-100 rounded-lg">
        <span>No grade items yet. Components appear in this record once items are added.</span>
        <a href="{{ route('teacher.grades.items', [$section, $subject]) }}" class="font-semibold underline">Add grade items</a>
    </div>
@endif

<div class="flex flex-wrap items-center gap-2 mb-3 text-sm">
    @foreach(['all' => 'All', 'male' => 'Male', 'female' => 'Female'] as $key => $label)
        <a href="{{ request()->fullUrlWithQuery(['gender' => $key === 'all' ? null : $key]) }}"
           class="px-3 py-1.5 font-semibold border rounded-lg transition {{ $gender === $key ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">{{ $label }}</a>
    @endforeach
    <span class="mx-2 text-gray-300">|</span>
    @foreach(['name' => 'A-Z', 'id' => 'Student No.'] as $key => $label)
        <a href="{{ request()->fullUrlWithQuery(['order' => $key === 'name' ? null : $key]) }}"
           class="px-3 py-1.5 font-semibold border rounded-lg transition {{ $order === $key ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">{{ $label }}</a>
    @endforeach
</div>
@if($enrollments->count() > 0)
    <div class="flex flex-wrap items-center gap-3 mb-3">
        <input type="text" id="recordSearch" placeholder="Search student name or number..." autocomplete="off"
               class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400"
               style="width:100%; max-width:320px;">
        <span id="recordCount" class="text-xs text-gray-400"></span>
    </div>
@endif

{{-- Spreadsheet --}}
<div class="overflow-x-auto bg-white border border-gray-200 shadow-sm rounded-xl">
    <table class="w-full text-sm border-collapse" style="min-width: max-content;">
        <thead>
            {{-- Component group headers --}}
            <tr class="text-xs font-semibold text-white uppercase bg-gray-700">
                <th rowspan="2" class="sticky left-0 z-20 px-4 py-3 text-center text-gray-500 bg-gray-50 border border-gray-200 min-w-[40px]">#</th>
                <th rowspan="2" class="sticky left-[40px] z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[80px]">Stud. No.</th>
                <th rowspan="2" class="sticky left-[120px] z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[180px]">Student Name</th>

                @foreach($matrix as $comp)
                    @php $colspan = $comp['type'] === 'attendance' ? 2 : $comp['items']->count() + 1; @endphp
                    <th colspan="{{ $colspan }}" class="px-4 py-3 text-center {{ $comp['color']['bg500'] }} border {{ $comp['color']['border400'] }}">
                        {{ $comp['label'] }} ({{ $comp['weight'] }}%)
                    </th>
                @endforeach

                <th colspan="3" class="px-4 py-3 text-center bg-gray-700 border border-gray-600">Summary</th>
            </tr>

            {{-- Sub-headers --}}
            <tr class="text-xs text-gray-500 uppercase bg-gray-50">
                

                @foreach($matrix as $comp)
                    @if($comp['type'] === 'items')
                        @foreach($comp['items'] as $item)
                            <th class="px-3 py-3 text-center {{ $comp['color']['bg50'] }} border {{ $comp['color']['bg200'] }} min-w-[90px]">
                                <div class="font-semibold {{ $comp['color']['text'] }}">{{ $item->name }}</div>
                                <div class="font-normal {{ $comp['color']['text400'] }} normal-case">/{{ number_format($item->max_score, 0) }}</div>
                            </th>
                        @endforeach
                        <th class="px-3 py-3 text-center {{ $comp['color']['bg100'] }} border {{ $comp['color']['bg200'] }} min-w-[80px]">
                            <div class="font-semibold {{ $comp['color']['text'] }}">Grade</div>
                        </th>
                    @else
                        <th class="px-3 py-3 text-center {{ $comp['color']['bg50'] }} border {{ $comp['color']['bg200'] }} min-w-[70px]">
                            <div class="font-semibold {{ $comp['color']['text'] }}">Days</div>
                            <div class="font-normal {{ $comp['color']['text400'] }} normal-case">Present</div>
                        </th>
                        <th class="px-3 py-3 text-center {{ $comp['color']['bg100'] }} border {{ $comp['color']['bg200'] }} min-w-[80px]">
                            <div class="font-semibold {{ $comp['color']['text'] }}">Grade</div>
                        </th>
                    @endif
                @endforeach

                <th class="px-3 py-3 text-center bg-gray-100 border border-gray-200 min-w-[80px]"><div class="font-semibold text-gray-700">Final %</div></th>
                <th class="px-3 py-3 text-center bg-gray-100 border border-gray-200 min-w-[70px]"><div class="font-semibold text-gray-700">Grade</div></th>
                <th class="px-3 py-3 text-center bg-gray-100 border border-gray-200 min-w-[80px]"><div class="font-semibold text-gray-700">Remarks</div></th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
            @forelse($enrollments as $i => $enrollment)
                @php
                    $lg = $liveGrades[$enrollment->id];
                    $fg = $enrollment->finalGrade;
                    $gradeMap = $enrollment->studentGrades->keyBy('grade_item_id');
        $grp = $enrollment->student?->gender ?? 'none';
        $newGroup = $grp !== ($prevGroup ?? null);
        $n = $newGroup ? 1 : $n + 1;
        $prevGroup = $grp;
                @endphp
                @if($newGroup && $gender === 'all')
                    <tr class="group-row">
                        <td colspan="3" class="sticky left-0 z-10 px-4 py-2 text-xs font-bold text-gray-600 uppercase bg-gray-100 border border-gray-200">{{ ['male' => 'Male', 'female' => 'Female'][$grp] ?? 'No gender set' }}</td>
                        <td colspan="{{ $totalCols - 3 }}" class="bg-gray-100 border border-gray-200"></td>
                    </tr>
                @endif
                <tr class="hover:bg-gray-50 student-row" data-search="{{ mb_strtolower(($enrollment->student?->student_number ?? '') . ' ' . ($enrollment->student?->full_name ?? '')) }}">
                    <td class="sticky left-0 z-10 px-4 py-3 text-center text-gray-400 bg-white border border-gray-100">{{ $n }}</td>
                    <td class="sticky left-[40px] z-10 px-4 py-3 font-mono text-xs text-gray-500 bg-white border border-gray-100">
                        {{ $enrollment->student?->student_number ?? '—' }}
                    </td>
                    <td class="sticky left-[120px] z-10 px-4 py-3 font-medium text-gray-800 bg-white border border-gray-100">
                        {{ $enrollment->student?->full_name ?? 'N/A' }}
                        @if($fg && $fg->is_locked)
                            <span class="ml-1 text-xs text-gray-400"><i class="fa-solid fa-lock"></i></span>
                        @endif
                    </td>

                    @foreach($matrix as $comp)
                        @if($comp['type'] === 'items')
                            @foreach($comp['items'] as $item)
                                @php $sg = $gradeMap->get($item->id); @endphp
                                <td class="px-3 py-3 text-center border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}/30">
                                    @if($sg)
                                        <span class="font-medium text-gray-800">{{ number_format($sg->score, 0) }}</span>
                                        <span class="text-xs text-gray-400">/{{ number_format($item->max_score, 0) }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-3 font-semibold text-center {{ $comp['color']['text'] }} border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}">
                                @php $cg = $componentGrades[$enrollment->id][$comp['key']] ?? null; @endphp
                                {{ $cg !== null ? \App\Services\GradeCalculator::formatGrade($cg, $config->computation_method ?? 'standard') : '—' }}
                            </td>
                        @else
                            @php $ad = $attendanceDisplay[$enrollment->id][$comp['key']] ?? ['present' => 0, 'total' => 0]; @endphp
                            <td class="px-3 py-3 text-center border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}/30">
                                @if($ad['total'] > 0)
                                    <span class="font-medium text-gray-800">{{ $ad['present'] }}</span>
                                    <span class="text-xs text-gray-400">/{{ $ad['total'] }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 font-semibold text-center {{ $comp['color']['text'] }} border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}">
                                @php $cg = $componentGrades[$enrollment->id][$comp['key']] ?? null; @endphp
                                {{ $cg !== null ? \App\Services\GradeCalculator::formatGrade($cg, $config->computation_method ?? 'standard') : '—' }}
                            </td>
                        @endif
                    @endforeach

                    <td class="px-3 py-3 font-bold text-center text-gray-800 border border-gray-200 bg-gray-50">{{ $lg['final_grade'] !== null ? number_format($lg['final_grade'], 2) . '%' : '—' }}</td>
                    <td class="px-3 py-3 font-bold text-center text-indigo-600 border border-gray-200 bg-gray-50">{{ $lg['letter_grade'] }}</td>
                    <td class="px-3 py-3 text-center border border-gray-200 bg-gray-50">
                        @if($fg && $fg->is_locked)
                            <span class="px-2 py-1 text-xs text-gray-600 bg-gray-200 rounded-full"><i class="fa-solid fa-lock"></i> Locked</span>
                        @elseif($lg['remarks'] === null)
                            <span class="text-gray-300">—</span>
                        @elseif($lg['remarks'] === 'passed')
                            <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">Passed</span>
                        @else
                            <span class="px-2 py-1 text-xs text-red-700 bg-red-100 rounded-full">Failed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="20" class="px-6 py-10 text-sm text-center text-gray-400">{{ $gender === 'all' ? 'No students enrolled.' : 'No ' . $gender . ' students enrolled.' }}</td></tr>
            @endforelse
        </tbody>

        @if($enrollments->count() > 0)
        <tfoot>
            <tr class="text-xs font-semibold text-gray-600 uppercase bg-gray-100">
                <td class="sticky left-0 z-10 px-4 py-3 text-center bg-gray-100 border border-gray-200" colspan="3">Class Average</td>

                @foreach($matrix as $comp)
                    @if($comp['type'] === 'items')
                        @foreach($comp['items'] as $item)
                            @php
                                $avg = $enrollments->map(fn($e) => optional($e->studentGrades->firstWhere('grade_item_id', $item->id))->score ?? null)
                                    ->filter()->avg();
                            @endphp
                            <td class="px-3 py-3 text-center border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}">
                                {{ $avg !== null ? number_format($avg, 1) : '—' }}
                            </td>
                        @endforeach
                        <td class="px-3 py-3 text-center {{ $comp['color']['text'] }} {{ $comp['color']['bg100'] }} border {{ $comp['color']['bg200'] }}">
                            @php $classAvg = collect($componentGrades)->pluck($comp['key'])->filter(fn($v) => $v !== null)->avg(); @endphp
                            {{ $classAvg !== null ? \App\Services\GradeCalculator::formatGrade($classAvg, $config->computation_method ?? 'standard') : '—' }}
                        </td>
                    @else
                        <td class="px-3 py-3 text-center border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}">—</td>
                        <td class="px-3 py-3 text-center {{ $comp['color']['text'] }} {{ $comp['color']['bg100'] }} border {{ $comp['color']['bg200'] }}">
                            @php $classAvg = collect($componentGrades)->pluck($comp['key'])->filter(fn($v) => $v !== null)->avg(); @endphp
                            {{ $classAvg !== null ? \App\Services\GradeCalculator::formatGrade($classAvg, $config->computation_method ?? 'standard') : '—' }}
                        </td>
                    @endif
                @endforeach

                <td class="px-3 py-3 font-bold text-center text-gray-800 bg-gray-200 border border-gray-300">
                    {{ collect($liveGrades)->avg('final_grade') !== null ? number_format(collect($liveGrades)->avg('final_grade'), 2) . '%' : '—' }}
                </td>
                <td class="px-3 py-3 font-bold text-center text-indigo-600 border border-gray-200 bg-gray-50">
                    {{ collect($liveGrades)->avg('numerical_grade') !== null ? \App\Services\GradeCalculator::formatGrade(collect($liveGrades)->avg('numerical_grade'), $config->computation_method ?? 'standard') : '—' }}
                </td>
                <td class="px-3 py-3 text-center bg-gray-200 border border-gray-300">
                    @php
                        $passCount = collect($liveGrades)->where('remarks', 'passed')->count();
                        $total     = collect($liveGrades)->count();
                    @endphp
                    {{ $passCount }}/{{ $total }} Passed
                </td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('recordSearch');
    if (!input) return;
    var rows  = document.querySelectorAll('tr.student-row');
    var label = document.getElementById('recordCount');
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase(), shown = 0;
        rows.forEach(function (r) {
            var match = q === '' || r.dataset.search.indexOf(q) !== -1;
            r.style.display = match ? '' : 'none';
            if (match) shown++;
        });
        label.textContent = q === '' ? '' : 'Showing ' + shown + ' of ' + rows.length;
        document.querySelectorAll('tr.group-row').forEach(function (g) {
            var next = g.nextElementSibling, any = false;
            while (next && !next.classList.contains('group-row')) {
                if (next.style.display !== 'none') any = true;
                next = next.nextElementSibling;
            }
            g.style.display = any ? '' : 'none';
        });
    });
});
</script>

</x-sidebar-layout>
