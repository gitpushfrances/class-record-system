<x-sidebar-layout>
<div class="no-print" style="margin-bottom:12px;"><x-back-button :href="route('teacher.classes.show', $section)" label="Back to Class" /></div>

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
    <div class="flex flex-wrap gap-2 mt-1 no-print">
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
        <button type="button" onclick="window.print()"
                class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>
</div>

@if(collect($matrix)->where('type', 'items')->every(fn($c) => $c['items']->isEmpty()))
    <div class="px-4 py-3 mb-4 text-sm text-yellow-700 bg-yellow-100 rounded-lg">
        No grade items yet. Click <strong>+ Add</strong> on a component header to add the first quiz, activity or exam.
    </div>
@endif

<div class="flex flex-wrap items-center gap-2 mb-3 text-sm no-print">
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
    <div class="flex flex-wrap items-center gap-3 mb-3 no-print">
        <input type="text" id="recordSearch" placeholder="Search student name or number..." autocomplete="off"
               class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400"
               style="width:100%; max-width:320px;">
        <span id="recordCount" class="text-xs text-gray-400"></span>
    </div>
@endif

<style>
@media print {
    @page { size: landscape; margin: 10mm; }
    #sidebar, body header, .no-print { display: none !important; }
    body, main { background: #fff !important; }
    main { padding: 0 !important; }
    .overflow-x-auto { overflow: visible !important; }
    table { min-width: 0 !important; font-size: 10px; }
    th.sticky, td.sticky { position: static !important; }
    thead { display: table-header-group; }
    tr { break-inside: avoid; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

{{-- Spreadsheet --}}
<div class="overflow-x-auto bg-white border border-gray-200 shadow-sm rounded-xl">
    <table class="w-full text-sm border-collapse" style="min-width: max-content;">
        <thead>
            @php
                $periodCols = fn($p) => collect($matrix)->where('period', $p)->sum(fn($c) => $c['type'] === 'attendance' ? 2 : $c['items']->count() + 1);
                $midCols = $periodCols('midterm');
                $finCols = $periodCols('final');
                $addBtn  = 'margin-left:6px; padding:1px 8px; font-size:11px; font-weight:600; color:#fff; background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.5); border-radius:9999px; cursor:pointer; text-decoration:none; white-space:nowrap;';
            @endphp
            {{-- Period headers --}}
            <tr class="text-xs font-bold text-white uppercase">
                <th rowspan="3" class="sticky left-0 z-20 px-4 py-3 text-center text-gray-500 bg-gray-50 border border-gray-200 min-w-[40px]">#</th>
                <th rowspan="3" class="sticky left-[40px] z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[80px]">Stud. No.</th>
                <th rowspan="3" class="sticky left-[120px] z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[180px]">Student Name</th>
                @if($midCols > 0)
                    <th colspan="{{ $midCols }}" style="padding:8px; text-align:center; letter-spacing:.1em; background:#111827; border:1px solid #374151;">Midterm</th>
                @endif
                @if($finCols > 0)
                    <th colspan="{{ $finCols }}" style="padding:8px; text-align:center; letter-spacing:.1em; background:#111827; border:1px solid #374151; border-left:4px solid #9ca3af;">Finals</th>
                @endif
                <th colspan="3" rowspan="2" class="px-4 py-3 text-center bg-gray-700 border border-gray-600">Summary</th>
            </tr>

            {{-- Component group headers (click + Add / + Take) --}}
            <tr class="text-xs font-semibold text-white uppercase bg-gray-700">
                @foreach($matrix as $comp)
                    @php $colspan = $comp['type'] === 'attendance' ? 2 : $comp['items']->count() + 1; @endphp
                    <th colspan="{{ $colspan }}" class="px-4 py-3 text-center {{ $comp['color']['bg500'] }} border {{ $comp['color']['border400'] }}">
                        {{ $comp['label'] }} ({{ $comp['weight'] }}%)
                        @unless($locked)
                            @if($comp['type'] === 'attendance')
                                <a href="{{ route('teacher.attendance.index', [$section, $subject]) }}" class="no-print" style="{{ $addBtn }}">+ Take</a>
                            @else
                                @php $nextName = \App\Models\GradeItem::autoName($comp['items'], $comp['key'], $comp['label'], $comp['period']); @endphp
                                @if($nextName)
                                    <button type="button" class="no-print" style="{{ $addBtn }}" onclick="openAddItem(@js($comp['key']), @js($nextName))">+ Add</button>
                                @endif
                            @endif
                        @endunless
                    </th>
                @endforeach
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
                                    @php $shown = $sg ? rtrim(rtrim(number_format($sg->score, 2, '.', ''), '0'), '.') : ''; @endphp
                                    @if($item->is_locked)
                                        @if($sg)
                                            <span class="font-medium text-gray-800">{{ $shown }}</span>
                                            <span class="text-xs text-gray-400">/{{ number_format($item->max_score, 0) }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    @else
                                        <input type="number" step="any" min="0" max="{{ $item->max_score + 0 }}"
                                               value="{{ $shown }}" data-original="{{ $shown }}"
                                               data-save="{{ route('teacher.grades.score.cell', [$section, $subject, $item]) }}"
                                               data-enrollment="{{ $enrollment->id }}"
                                               class="score-input" style="width:64px; padding:4px; text-align:center; border:1px solid #d1d5db; border-radius:6px; font-size:13px;">
                                    @endif
                                </td>
                            @endforeach
                            <td data-calc="r{{ $enrollment->id }}-{{ $comp['key'] }}" class="px-3 py-3 font-semibold text-center {{ $comp['color']['text'] }} border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}">
                                @php $cg = $componentGrades[$enrollment->id][$comp['key']] ?? null; @endphp
                                {{ $cg !== null ? \App\Services\GradeCalculator::formatGrade($cg, $config->computation_method ?? 'standard') : '—' }}
                            </td>
                        @else
                            @php $ad = $attendanceDisplay[$enrollment->id][$comp['key']] ?? ['present' => 0, 'total' => 0]; @endphp
                            <td class="px-3 py-3 text-center border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}/30">
                                @if($ad['total'] > 0)
                                    <span class="font-medium text-gray-800">{{ $ad['present'] }}</span>
                                    <span class="text-xs text-gray-400">/{{ $ad['total'] }}</span>
                                    @if(($streaks[$enrollment->id] ?? 0) >= \App\Models\AttendanceRecord::ABSENCE_WARNING)
                                        <i class="ml-1 fa-solid fa-triangle-exclamation" style="color:#b45309;" title="{{ $streaks[$enrollment->id] }} absences in a row: guidance office permission slip needed"></i>
                                    @endif
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td data-calc="r{{ $enrollment->id }}-{{ $comp['key'] }}" class="px-3 py-3 font-semibold text-center {{ $comp['color']['text'] }} border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}">
                                @php $cg = $componentGrades[$enrollment->id][$comp['key']] ?? null; @endphp
                                {{ $cg !== null ? \App\Services\GradeCalculator::formatGrade($cg, $config->computation_method ?? 'standard') : '—' }}
                            </td>
                        @endif
                    @endforeach

                    <td data-calc="r{{ $enrollment->id }}-pct" class="px-3 py-3 font-bold text-center text-gray-800 border border-gray-200 bg-gray-50">{{ $lg['final_grade'] !== null ? number_format($lg['final_grade'], 2) . '%' : '—' }}</td>
                    <td data-calc="r{{ $enrollment->id }}-letter" class="px-3 py-3 font-bold text-center text-indigo-600 border border-gray-200 bg-gray-50">{{ $lg['letter_grade'] }}</td>
                    <td data-calc="r{{ $enrollment->id }}-remarks" class="px-3 py-3 text-center border border-gray-200 bg-gray-50">
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
        <tfoot data-calc="foot">
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

<dialog id="addItemDialog" style="padding:0; border:none; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,.25); width:min(92vw,360px);">
    <form method="POST" action="{{ route('teacher.grades.items.store', [$section, $subject]) }}" style="padding:20px;">
        @csrf
        <input type="hidden" name="component_type" id="addItemKey">
        <h3 id="addItemTitle" style="margin:0 0 14px; font-size:16px; font-weight:700; color:#111827;"></h3>
        <label style="display:block; font-size:13px; font-weight:600; color:#374151;">Date
            <input type="date" name="date_given" value="{{ today()->toDateString() }}" style="display:block; width:100%; margin-top:4px; padding:8px; border:1px solid #d1d5db; border-radius:8px;">
        </label>
        <label style="display:block; margin-top:12px; font-size:13px; font-weight:600; color:#374151;">Highest possible score
            <input type="number" name="max_score" min="1" step="any" required style="display:block; width:100%; margin-top:4px; padding:8px; border:1px solid #d1d5db; border-radius:8px;">
        </label>
        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:18px;">
            <button type="button" onclick="document.getElementById('addItemDialog').close()" style="padding:8px 14px; font-size:13px; font-weight:600; color:#374151; background:#fff; border:1px solid #d1d5db; border-radius:8px; cursor:pointer;">Cancel</button>
            <button type="submit" style="padding:8px 14px; font-size:13px; font-weight:600; color:#fff; background:#4f46e5; border:none; border-radius:8px; cursor:pointer;">Add</button>
        </div>
    </form>
</dialog>
<script>
function openAddItem(key, name) {
    document.getElementById('addItemKey').value = key;
    document.getElementById('addItemTitle').textContent = 'Add ' + name;
    document.getElementById('addItemDialog').showModal();
}
</script>

<div id="scoreToast" style="position:fixed; right:16px; bottom:16px; display:none; padding:8px 14px; border-radius:8px; font-size:13px; color:#fff; z-index:60;"></div>
<script>
(function () {
    var toast = document.getElementById('scoreToast');
    var timer;

    function say(msg, ok) {
        toast.textContent = msg;
        toast.style.background = ok ? '#15803d' : '#b91c1c';
        toast.style.display = 'block';
        clearTimeout(timer);
        timer = setTimeout(function () { toast.style.display = 'none'; }, 2500);
    }

    function refresh() {
        return fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                doc.querySelectorAll('[data-calc]').forEach(function (n) {
                    var cur = document.querySelector('[data-calc="' + n.getAttribute('data-calc') + '"]');
                    if (cur) cur.innerHTML = n.innerHTML;
                });
            });
    }

    document.addEventListener('change', function (e) {
        var el = e.target;
        if (!el.classList.contains('score-input')) return;

        var v = el.value.trim();
        if (v !== '' && (isNaN(v) || Number(v) < 0 || Number(v) > Number(el.max))) {
            el.value = el.dataset.original;
            say('Score must be between 0 and ' + el.max + '.', false);
            return;
        }

        fetch(el.dataset.save, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ enrollment_id: el.dataset.enrollment, score: v === '' ? null : v })
        }).then(function (res) {
            if (!res.ok) throw new Error(res.status === 403 ? 'Grades are locked.' : 'Could not save the score.');
            el.dataset.original = v;
            return refresh();
        }).then(function () {
            say('Saved', true);
        }).catch(function (err) {
            el.value = el.dataset.original;
            say(err.message, false);
        });
    });
})();
</script>

</x-sidebar-layout>
