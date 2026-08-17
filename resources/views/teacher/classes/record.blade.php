<x-sidebar-layout>

{{-- Header --}}
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <a href="{{ route('teacher.dashboard') }}" class="text-sm text-indigo-600 hover:underline">← Back to My Classes</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-800">{{ $subject->code }} — {{ $subject->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }} &bull; {{ $section->year_level }} &bull; {{ $currentTerm?->semester }} &bull; {{ $currentTerm?->academic_year }}
        </p>
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
        <a href="{{ route('teacher.classes.record.export', [$section, $subject]) }}"
           class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
            <i class="fa-solid fa-file-excel"></i> Export Excel
        </a>
        <a href="{{ route('teacher.classes.record.print', [$section, $subject]) }}" target="_blank"
           class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
            <i class="fa-solid fa-print"></i> Print
        </a>
    </div>
</div>

{{-- Grade Config Summary --}}
<div class="flex flex-wrap items-center gap-4 px-5 py-3 mb-6 text-sm text-indigo-700 border border-indigo-100 bg-indigo-50 rounded-xl">
    @foreach($config->getComponents() as $comp)
        <span>{{ $comp['label'] }} <strong>{{ $comp['weight'] }}%</strong> <span class="text-xs text-indigo-400">({{ ucfirst($comp['period']) }})</span></span>
    @endforeach
    <span class="ml-auto text-gray-400">{{ $enrollments->count() }} students</span>
</div>

{{-- Midterm Cutoff Date — required for attendance-based grading to compute --}}
<div class="flex flex-wrap items-center gap-3 px-5 py-3 mb-6 text-sm bg-white border border-gray-200 rounded-xl">
    <span class="font-medium text-gray-600">Midterm Cutoff Date:</span>
    @if($currentTerm?->midterm_cutoff_date)
        <span class="font-semibold text-gray-800">{{ $currentTerm->midterm_cutoff_date->format('M d, Y') }}</span>
    @else
        <span class="text-yellow-700">Not set — attendance scores will not compute until this is set.</span>
    @endif
    <form method="POST" action="{{ route('teacher.grades.final.cutoff', [$section, $subject]) }}" class="flex items-center gap-2 ml-auto">
        @csrf
        <input type="date" name="midterm_cutoff_date"
               value="{{ $currentTerm?->midterm_cutoff_date?->format('Y-m-d') }}"
               class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
            Save
        </button>
    </form>
</div>

{{-- Spreadsheet --}}
<div class="overflow-x-auto bg-white border border-gray-200 shadow-sm rounded-xl">
    <table class="text-sm border-collapse" style="min-width: max-content;">
        <thead>
            {{-- Component group headers --}}
            <tr class="text-xs font-semibold text-white uppercase">
                <th class="sticky left-0 z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[40px]">#</th>
                <th class="sticky left-[40px] z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[80px]">No.</th>
                <th class="sticky left-[120px] z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[180px]">Student Name</th>

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
                <th class="sticky left-0 z-20 px-4 py-3 text-center border border-gray-200 bg-gray-50">#</th>
                <th class="sticky left-[40px] z-20 px-4 py-3 text-left bg-gray-50 border border-gray-200">Stud. No.</th>
                <th class="sticky left-[120px] z-20 px-4 py-3 text-left bg-gray-50 border border-gray-200">Name</th>

                @foreach($matrix as $comp)
                    @if($comp['type'] === 'items')
                        @foreach($comp['items'] as $item)
                            <th class="px-3 py-3 text-center {{ $comp['color']['bg50'] }} border {{ $comp['color']['bg200'] }} min-w-[90px]">
                                <div class="font-semibold {{ $comp['color']['text'] }}">{{ $item->name }}</div>
                                <div class="font-normal {{ $comp['color']['text400'] }} normal-case">/{{ number_format($item->max_score, 0) }}</div>
                            </th>
                        @endforeach
                        <th class="px-3 py-3 text-center {{ $comp['color']['bg100'] }} border {{ $comp['color']['bg200'] }} min-w-[80px]">
                            <div class="font-semibold {{ $comp['color']['text'] }}">Wtd.</div>
                            <div class="font-normal {{ $comp['color']['text400'] }} normal-case">Score</div>
                        </th>
                    @else
                        <th class="px-3 py-3 text-center {{ $comp['color']['bg50'] }} border {{ $comp['color']['bg200'] }} min-w-[70px]">
                            <div class="font-semibold {{ $comp['color']['text'] }}">Days</div>
                            <div class="font-normal {{ $comp['color']['text400'] }} normal-case">Present</div>
                        </th>
                        <th class="px-3 py-3 text-center {{ $comp['color']['bg100'] }} border {{ $comp['color']['bg200'] }} min-w-[80px]">
                            <div class="font-semibold {{ $comp['color']['text'] }}">Wtd.</div>
                            <div class="font-normal {{ $comp['color']['text400'] }} normal-case">Score</div>
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
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="sticky left-0 z-10 px-4 py-3 text-center text-gray-400 bg-white border border-gray-100">{{ $i + 1 }}</td>
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
                                {{ number_format($lg['scores'][$comp['key']] ?? 0, 2) }}
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
                                {{ number_format($lg['scores'][$comp['key']] ?? 0, 2) }}
                            </td>
                        @endif
                    @endforeach

                    <td class="px-3 py-3 font-bold text-center text-gray-800 border border-gray-200 bg-gray-50">{{ number_format($lg['final_grade'], 2) }}%</td>
                    <td class="px-3 py-3 font-bold text-center text-indigo-600 border border-gray-200 bg-gray-50">{{ $lg['letter_grade'] }}</td>
                    <td class="px-3 py-3 text-center border border-gray-200 bg-gray-50">
                        @if($fg && $fg->is_locked)
                            <span class="px-2 py-1 text-xs text-gray-600 bg-gray-200 rounded-full"><i class="fa-solid fa-lock"></i> Locked</span>
                        @elseif($lg['remarks'] === 'passed')
                            <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">Passed</span>
                        @else
                            <span class="px-2 py-1 text-xs text-red-700 bg-red-100 rounded-full">Failed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="20" class="px-6 py-10 text-sm text-center text-gray-400">No students enrolled.</td></tr>
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
                            {{ number_format(collect($liveGrades)->avg(fn($g) => $g['scores'][$comp['key']] ?? 0), 2) }}
                        </td>
                    @else
                        <td class="px-3 py-3 text-center border {{ $comp['color']['bg200'] }} {{ $comp['color']['bg50'] }}">—</td>
                        <td class="px-3 py-3 text-center {{ $comp['color']['text'] }} {{ $comp['color']['bg100'] }} border {{ $comp['color']['bg200'] }}">
                            {{ number_format(collect($liveGrades)->avg(fn($g) => $g['scores'][$comp['key']] ?? 0), 2) }}
                        </td>
                    @endif
                @endforeach

                <td class="px-3 py-3 font-bold text-center text-gray-800 bg-gray-200 border border-gray-300">
                    {{ number_format(collect($liveGrades)->avg('final_grade'), 2) }}%
                </td>
                <td class="px-3 py-3 font-bold text-center text-indigo-600 bg-gray-200 border border-gray-300">
                    {{ number_format(\App\Models\FinalGrade::convertToNumericalGrade(collect($liveGrades)->avg('final_grade')), 2) }}
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

</x-sidebar-layout>
