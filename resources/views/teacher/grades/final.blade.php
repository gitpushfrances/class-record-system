<x-sidebar-layout>




{{-- Alerts --}}
@if(session('success'))
    <div class="px-4 py-3 mb-4 text-sm text-green-700 bg-green-100 border border-green-200 rounded-lg">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="px-4 py-3 mb-4 text-sm text-red-700 bg-red-100 border border-red-200 rounded-lg">
        {{ session('error') }}
    </div>
@endif

<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('teacher.classes.show', $section) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-800">Final Grades</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
    </div>
    <div class="flex gap-3 mt-1">
        <form method="POST" action="{{ route('teacher.grades.final.compute', $section) }}">
            @csrf
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
                💾 Save Grades
            </button>
        </form>
        <form method="POST" action="{{ route('teacher.grades.final.lock', $section) }}"
              onsubmit="return confirm('Lock all final grades? This cannot be undone.')">
            @csrf
            <button class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition">
                🔒 Lock All
            </button>
        </form>
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
</div>

<div class="overflow-x-auto bg-white border border-gray-200 shadow-sm rounded-xl">
    <table class="w-full text-sm">
        <thead class="text-xs text-gray-500 uppercase bg-gray-50">
            <tr>
                <th class="px-5 py-3 text-left">#</th>
                <th class="px-5 py-3 text-left">Student</th>
                <th class="px-5 py-3 text-center">Quiz<br><span class="font-normal normal-case">({{ $config->quiz_weight }}%)</span></th>
                <th class="px-5 py-3 text-center">Exam<br><span class="font-normal normal-case">({{ $config->exam_weight }}%)</span></th>
                <th class="px-5 py-3 text-center">Project<br><span class="font-normal normal-case">({{ $config->project_weight }}%)</span></th>
                <th class="px-5 py-3 text-center">Assess.<br><span class="font-normal normal-case">({{ $config->assessment_weight }}%)</span></th>
                <th class="px-5 py-3 text-center">Attend.<br><span class="font-normal normal-case">({{ $config->attendance_weight }}%)</span></th>
                <th class="px-5 py-3 text-center">Final %</th>
                <th class="px-5 py-3 text-center">Grade</th>
                <th class="px-5 py-3 text-center">Remarks</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($enrollments as $i => $enrollment)
                @php
                    $lg = $liveGrades[$enrollment->id];
                    $fg = $enrollment->finalGrade;
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-400">{{ $i + 1 }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $enrollment->student?->full_name ?? 'N/A' }}</div>
                        <div class="font-mono text-xs text-gray-400">{{ $enrollment->student?->student_number }}</div>
                    </td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ number_format($lg['quiz_score'], 2) }}</td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ number_format($lg['exam_score'], 2) }}</td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ number_format($lg['project_score'], 2) }}</td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ number_format($lg['assessment_score'], 2) }}</td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ number_format($lg['attendance_score'], 2) }}</td>
                    <td class="px-5 py-3 font-semibold text-center text-gray-800">
                        {{ number_format($lg['final_grade'], 2) }}%
                    </td>
                    <td class="px-5 py-3 font-bold text-center text-indigo-600">
                        {{ $lg['letter_grade'] }}
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($fg && $fg->is_locked)
                            <span class="px-2 py-1 text-xs text-gray-600 bg-gray-100 rounded-full">🔒 Locked</span>
                        @elseif($lg['remarks'] === 'passed')
                            <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">Passed</span>
                        @else
                            <span class="px-2 py-1 text-xs text-red-700 bg-red-100 rounded-full">Failed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="px-5 py-10 text-sm text-center text-gray-400">No students enrolled.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

</x-sidebar-layout>
