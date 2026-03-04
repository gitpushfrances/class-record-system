<x-sidebar-layout>

@section('title', 'Class Record — ' . $section->subject->code)



{{-- Header --}}
<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('teacher.classes.show', $section) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-800">Class Record</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $section->subject->code }} — {{ $section->subject->name }} &bull;
            {{ $section->section_name }} &bull; {{ $section->year_level }} &bull;
            {{ $section->semester }} &bull; {{ $section->academic_year }}
        </p>
    </div>
    <div class="flex gap-3 mt-1">
        <a href="{{ route('teacher.classes.record.export', $section) }}"
           class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
            📥 Export Excel
        </a>
        <a href="{{ route('teacher.classes.record.print', $section) }}" target="_blank"
           class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
            🖨️ Print
        </a>
    </div>
</div>

{{-- Grade Config Summary --}}
@php $config = $section->gradeConfiguration; @endphp
<div class="flex flex-wrap gap-4 px-5 py-3 mb-6 text-sm text-indigo-700 border border-indigo-100 bg-indigo-50 rounded-xl">
    <span>Quiz <strong>{{ $config->quiz_weight }}%</strong></span>
    <span>Exam <strong>{{ $config->exam_weight }}%</strong></span>
    <span>Project <strong>{{ $config->project_weight }}%</strong></span>
    <span>Assessment <strong>{{ $config->assessment_weight }}%</strong></span>
    <span>Attendance <strong>{{ $config->attendance_weight }}%</strong></span>
    <span class="ml-auto text-gray-400">{{ $enrollments->count() }} students</span>
</div>

{{-- Spreadsheet --}}
<div class="overflow-x-auto bg-white border border-gray-200 shadow-sm rounded-xl">
    <table class="text-sm border-collapse" style="min-width: max-content;">
        {{-- ============================================================ --}}
        {{-- ROW 1: Component Group Headers --}}
        {{-- ============================================================ --}}
        <thead>
            <tr class="text-xs font-semibold text-white uppercase">
                {{-- Frozen columns --}}
                <th class="sticky left-0 z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[40px]">#</th>
                <th class="sticky left-[40px] z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[80px]">No.</th>
                <th class="sticky left-[120px] z-20 px-4 py-3 text-left text-gray-500 bg-gray-50 border border-gray-200 min-w-[180px]">Student Name</th>

                {{-- Quiz group --}}
                @if($gradeItems->has('quiz') && $gradeItems['quiz']->count())
                    <th colspan="{{ $gradeItems['quiz']->count() + 1 }}" class="px-4 py-3 text-center bg-blue-500 border border-blue-400">
                        Quiz ({{ $config->quiz_weight }}%)
                    </th>
                @endif

                {{-- Exam group --}}
                @if($gradeItems->has('exam') && $gradeItems['exam']->count())
                    <th colspan="{{ $gradeItems['exam']->count() + 1 }}" class="px-4 py-3 text-center bg-purple-500 border border-purple-400">
                        Exam ({{ $config->exam_weight }}%)
                    </th>
                @endif

                {{-- Project group --}}
                @if($gradeItems->has('project') && $gradeItems['project']->count())
                    <th colspan="{{ $gradeItems['project']->count() + 1 }}" class="px-4 py-3 text-center bg-green-500 border border-green-400">
                        Project ({{ $config->project_weight }}%)
                    </th>
                @endif

                {{-- Assessment group --}}
                @if($gradeItems->has('assessment') && $gradeItems['assessment']->count())
                    <th colspan="{{ $gradeItems['assessment']->count() + 1 }}" class="px-4 py-3 text-center bg-orange-500 border border-orange-400">
                        Assessment ({{ $config->assessment_weight }}%)
                    </th>
                @endif

                {{-- Attendance --}}
                @if((float) $config->attendance_weight > 0)
                    <th colspan="2" class="px-4 py-3 text-center bg-teal-500 border border-teal-400">
                        Attendance ({{ $config->attendance_weight }}%)
                    </th>
                @endif

                {{-- Summary --}}
                <th colspan="3" class="px-4 py-3 text-center bg-gray-700 border border-gray-600">
                    Summary
                </th>
            </tr>

            {{-- ============================================================ --}}
            {{-- ROW 2: Individual Column Headers --}}
            {{-- ============================================================ --}}
            <tr class="text-xs text-gray-500 uppercase bg-gray-50">
                <th class="sticky left-0 z-20 px-4 py-3 text-center border border-gray-200 bg-gray-50">#</th>
                <th class="sticky left-[40px] z-20 px-4 py-3 text-left bg-gray-50 border border-gray-200">Stud. No.</th>
                <th class="sticky left-[120px] z-20 px-4 py-3 text-left bg-gray-50 border border-gray-200">Name</th>

                {{-- Quiz columns --}}
                @if($gradeItems->has('quiz'))
                    @foreach($gradeItems['quiz'] as $item)
                        <th class="px-3 py-3 text-center bg-blue-50 border border-blue-200 min-w-[90px]">
                            <div class="font-semibold text-blue-700">{{ $item->name }}</div>
                            <div class="font-normal text-blue-400 normal-case">/{{ number_format($item->max_score, 0) }}</div>
                        </th>
                    @endforeach
                    <th class="px-3 py-3 text-center bg-blue-100 border border-blue-200 min-w-[80px]">
                        <div class="font-semibold text-blue-700">Wtd.</div>
                        <div class="font-normal text-blue-400 normal-case">Score</div>
                    </th>
                @endif

                {{-- Exam columns --}}
                @if($gradeItems->has('exam'))
                    @foreach($gradeItems['exam'] as $item)
                        <th class="px-3 py-3 text-center bg-purple-50 border border-purple-200 min-w-[90px]">
                            <div class="font-semibold text-purple-700">{{ $item->name }}</div>
                            <div class="font-normal text-purple-400 normal-case">/{{ number_format($item->max_score, 0) }}</div>
                        </th>
                    @endforeach
                    <th class="px-3 py-3 text-center bg-purple-100 border border-purple-200 min-w-[80px]">
                        <div class="font-semibold text-purple-700">Wtd.</div>
                        <div class="font-normal text-purple-400 normal-case">Score</div>
                    </th>
                @endif

                {{-- Project columns --}}
                @if($gradeItems->has('project'))
                    @foreach($gradeItems['project'] as $item)
                        <th class="px-3 py-3 text-center bg-green-50 border border-green-200 min-w-[90px]">
                            <div class="font-semibold text-green-700">{{ $item->name }}</div>
                            <div class="font-normal text-green-400 normal-case">/{{ number_format($item->max_score, 0) }}</div>
                        </th>
                    @endforeach
                    <th class="px-3 py-3 text-center bg-green-100 border border-green-200 min-w-[80px]">
                        <div class="font-semibold text-green-700">Wtd.</div>
                        <div class="font-normal text-green-400 normal-case">Score</div>
                    </th>
                @endif

                {{-- Assessment columns --}}
                @if($gradeItems->has('assessment'))
                    @foreach($gradeItems['assessment'] as $item)
                        <th class="px-3 py-3 text-center bg-orange-50 border border-orange-200 min-w-[90px]">
                            <div class="font-semibold text-orange-700">{{ $item->name }}</div>
                            <div class="font-normal text-orange-400 normal-case">/{{ number_format($item->max_score, 0) }}</div>
                        </th>
                    @endforeach
                    <th class="px-3 py-3 text-center bg-orange-100 border border-orange-200 min-w-[80px]">
                        <div class="font-semibold text-orange-700">Wtd.</div>
                        <div class="font-normal text-orange-400 normal-case">Score</div>
                    </th>
                @endif

                {{-- Attendance columns --}}
                @if((float) $config->attendance_weight > 0)
                    <th class="px-3 py-3 text-center bg-teal-50 border border-teal-200 min-w-[70px]">
                        <div class="font-semibold text-teal-700">Days</div>
                        <div class="font-normal text-teal-400 normal-case">Present</div>
                    </th>
                    <th class="px-3 py-3 text-center bg-teal-100 border border-teal-200 min-w-[80px]">
                        <div class="font-semibold text-teal-700">Wtd.</div>
                        <div class="font-normal text-teal-400 normal-case">Score</div>
                    </th>
                @endif

                {{-- Summary columns --}}
                <th class="px-3 py-3 text-center bg-gray-100 border border-gray-200 min-w-[80px]">
                    <div class="font-semibold text-gray-700">Final %</div>
                </th>
                <th class="px-3 py-3 text-center bg-gray-100 border border-gray-200 min-w-[70px]">
                    <div class="font-semibold text-gray-700">Grade</div>
                </th>
                <th class="px-3 py-3 text-center bg-gray-100 border border-gray-200 min-w-[80px]">
                    <div class="font-semibold text-gray-700">Remarks</div>
                </th>
            </tr>
        </thead>

        {{-- ============================================================ --}}
        {{-- BODY: Student Rows --}}
        {{-- ============================================================ --}}
        <tbody class="divide-y divide-gray-100">
            @forelse($enrollments as $i => $enrollment)
                @php
                    $lg = $liveGrades[$enrollment->id];
                    $fg = $enrollment->finalGrade;
                    // Index student grades by grade_item_id for O(1) lookup
                    $gradeMap = $enrollment->studentGrades->keyBy('grade_item_id');
                    // Attendance
                    $totalDays    = $enrollment->attendanceRecords->count();
                    $presentDays  = $enrollment->attendanceRecords->whereIn('status', ['present', 'late'])->count();
                @endphp
                <tr class="hover:bg-gray-50">
                    {{-- Frozen: # --}}
                    <td class="sticky left-0 z-10 px-4 py-3 text-center text-gray-400 bg-white border border-gray-100">{{ $i + 1 }}</td>
                    {{-- Frozen: Student No --}}
                    <td class="sticky left-[40px] z-10 px-4 py-3 font-mono text-xs text-gray-500 bg-white border border-gray-100">
                        {{ $enrollment->student?->student_number ?? '—' }}
                    </td>
                    {{-- Frozen: Name --}}
                    <td class="sticky left-[120px] z-10 px-4 py-3 font-medium text-gray-800 bg-white border border-gray-100">
                        {{ $enrollment->student?->full_name ?? 'N/A' }}
                        @if($fg && $fg->is_locked)
                            <span class="ml-1 text-xs text-gray-400">🔒</span>
                        @endif
                    </td>

                    {{-- Quiz scores --}}
                    @if($gradeItems->has('quiz'))
                        @foreach($gradeItems['quiz'] as $item)
                            @php $sg = $gradeMap->get($item->id); @endphp
                            <td class="px-3 py-3 text-center border border-blue-100 bg-blue-50/30">
                                @if($sg)
                                    <span class="font-medium text-gray-800">{{ number_format($sg->score, 0) }}</span>
                                    <span class="text-xs text-gray-400">/{{ number_format($item->max_score, 0) }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-3 py-3 font-semibold text-center text-blue-700 border border-blue-100 bg-blue-50">
                            {{ number_format($lg['quiz_score'], 2) }}
                        </td>
                    @endif

                    {{-- Exam scores --}}
                    @if($gradeItems->has('exam'))
                        @foreach($gradeItems['exam'] as $item)
                            @php $sg = $gradeMap->get($item->id); @endphp
                            <td class="px-3 py-3 text-center border border-purple-100 bg-purple-50/30">
                                @if($sg)
                                    <span class="font-medium text-gray-800">{{ number_format($sg->score, 0) }}</span>
                                    <span class="text-xs text-gray-400">/{{ number_format($item->max_score, 0) }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-3 py-3 font-semibold text-center text-purple-700 border border-purple-100 bg-purple-50">
                            {{ number_format($lg['exam_score'], 2) }}
                        </td>
                    @endif

                    {{-- Project scores --}}
                    @if($gradeItems->has('project'))
                        @foreach($gradeItems['project'] as $item)
                            @php $sg = $gradeMap->get($item->id); @endphp
                            <td class="px-3 py-3 text-center border border-green-100 bg-green-50/30">
                                @if($sg)
                                    <span class="font-medium text-gray-800">{{ number_format($sg->score, 0) }}</span>
                                    <span class="text-xs text-gray-400">/{{ number_format($item->max_score, 0) }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-3 py-3 font-semibold text-center text-green-700 border border-green-100 bg-green-50">
                            {{ number_format($lg['project_score'], 2) }}
                        </td>
                    @endif

                    {{-- Assessment scores --}}
                    @if($gradeItems->has('assessment'))
                        @foreach($gradeItems['assessment'] as $item)
                            @php $sg = $gradeMap->get($item->id); @endphp
                            <td class="px-3 py-3 text-center border border-orange-100 bg-orange-50/30">
                                @if($sg)
                                    <span class="font-medium text-gray-800">{{ number_format($sg->score, 0) }}</span>
                                    <span class="text-xs text-gray-400">/{{ number_format($item->max_score, 0) }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-3 py-3 font-semibold text-center text-orange-700 border border-orange-100 bg-orange-50">
                            {{ number_format($lg['assessment_score'], 2) }}
                        </td>
                    @endif

                    {{-- Attendance --}}
                    @if((float) $config->attendance_weight > 0)
                        <td class="px-3 py-3 text-center border border-teal-100 bg-teal-50/30">
                            @if($totalDays > 0)
                                <span class="font-medium text-gray-800">{{ $presentDays }}</span>
                                <span class="text-xs text-gray-400">/{{ $totalDays }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-3 font-semibold text-center text-teal-700 border border-teal-100 bg-teal-50">
                            {{ number_format($lg['attendance_score'], 2) }}
                        </td>
                    @endif

                    {{-- Summary --}}
                    <td class="px-3 py-3 font-bold text-center text-gray-800 border border-gray-200 bg-gray-50">
                        {{ number_format($lg['final_grade'], 2) }}%
                    </td>
                    <td class="px-3 py-3 font-bold text-center text-indigo-600 border border-gray-200 bg-gray-50">
                        {{ $lg['letter_grade'] }}
                    </td>
                    <td class="px-3 py-3 text-center border border-gray-200 bg-gray-50">
                        @if($fg && $fg->is_locked)
                            <span class="px-2 py-1 text-xs text-gray-600 bg-gray-200 rounded-full">🔒 Locked</span>
                        @elseif($lg['remarks'] === 'passed')
                            <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">Passed</span>
                        @else
                            <span class="px-2 py-1 text-xs text-red-700 bg-red-100 rounded-full">Failed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="20" class="px-6 py-10 text-sm text-center text-gray-400">No students enrolled.</td>
                </tr>
            @endforelse
        </tbody>

        {{-- ============================================================ --}}
        {{-- FOOTER: Class Averages --}}
        {{-- ============================================================ --}}
        @if($enrollments->count() > 0)
        <tfoot>
            <tr class="text-xs font-semibold text-gray-600 uppercase bg-gray-100">
                <td class="sticky left-0 z-10 px-4 py-3 text-center bg-gray-100 border border-gray-200" colspan="3">
                    Class Average
                </td>

                {{-- Quiz averages --}}
                @if($gradeItems->has('quiz'))
                    @foreach($gradeItems['quiz'] as $item)
                        @php
                            $avg = $enrollments->map(fn($e) => optional($e->studentGrades->firstWhere('grade_item_id', $item->id))->score ?? null)
                                ->filter()->avg();
                        @endphp
                        <td class="px-3 py-3 text-center border border-blue-200 bg-blue-50">
                            {{ $avg !== null ? number_format($avg, 1) : '—' }}
                        </td>
                    @endforeach
                    <td class="px-3 py-3 text-center text-blue-700 bg-blue-100 border border-blue-200">
                        {{ number_format(collect($liveGrades)->avg('quiz_score'), 2) }}
                    </td>
                @endif

                {{-- Exam averages --}}
                @if($gradeItems->has('exam'))
                    @foreach($gradeItems['exam'] as $item)
                        @php
                            $avg = $enrollments->map(fn($e) => optional($e->studentGrades->firstWhere('grade_item_id', $item->id))->score ?? null)
                                ->filter()->avg();
                        @endphp
                        <td class="px-3 py-3 text-center border border-purple-200 bg-purple-50">
                            {{ $avg !== null ? number_format($avg, 1) : '—' }}
                        </td>
                    @endforeach
                    <td class="px-3 py-3 text-center text-purple-700 bg-purple-100 border border-purple-200">
                        {{ number_format(collect($liveGrades)->avg('exam_score'), 2) }}
                    </td>
                @endif

                {{-- Project averages --}}
                @if($gradeItems->has('project'))
                    @foreach($gradeItems['project'] as $item)
                        @php
                            $avg = $enrollments->map(fn($e) => optional($e->studentGrades->firstWhere('grade_item_id', $item->id))->score ?? null)
                                ->filter()->avg();
                        @endphp
                        <td class="px-3 py-3 text-center border border-green-200 bg-green-50">
                            {{ $avg !== null ? number_format($avg, 1) : '—' }}
                        </td>
                    @endforeach
                    <td class="px-3 py-3 text-center text-green-700 bg-green-100 border border-green-200">
                        {{ number_format(collect($liveGrades)->avg('project_score'), 2) }}
                    </td>
                @endif

                {{-- Assessment averages --}}
                @if($gradeItems->has('assessment'))
                    @foreach($gradeItems['assessment'] as $item)
                        @php
                            $avg = $enrollments->map(fn($e) => optional($e->studentGrades->firstWhere('grade_item_id', $item->id))->score ?? null)
                                ->filter()->avg();
                        @endphp
                        <td class="px-3 py-3 text-center border border-orange-200 bg-orange-50">
                            {{ $avg !== null ? number_format($avg, 1) : '—' }}
                        </td>
                    @endforeach
                    <td class="px-3 py-3 text-center text-orange-700 bg-orange-100 border border-orange-200">
                        {{ number_format(collect($liveGrades)->avg('assessment_score'), 2) }}
                    </td>
                @endif

                {{-- Attendance average --}}
                @if((float) $config->attendance_weight > 0)
                    <td class="px-3 py-3 text-center border border-teal-200 bg-teal-50">—</td>
                    <td class="px-3 py-3 text-center text-teal-700 bg-teal-100 border border-teal-200">
                        {{ number_format(collect($liveGrades)->avg('attendance_score'), 2) }}
                    </td>
                @endif

                {{-- Summary averages --}}
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
