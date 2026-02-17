@extends('layouts.teacher')

@section('title', $section->subject->code . ' - ' . $section->section_name)

@section('content')

{{-- Header --}}
<div class="mb-6">
    <a href="{{ route('teacher.dashboard') }}" class="text-sm text-indigo-600 hover:underline">← Back to Dashboard</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-800">
        {{ $section->subject->code }} — {{ $section->section_name }}
    </h1>
    <p class="mt-1 text-sm text-gray-500">
        {{ $section->subject->name }} &bull; {{ $section->year_level }} &bull; {{ $section->semester }} &bull; {{ $section->academic_year }}
        @if($section->room) &bull; Room: {{ $section->room }} @endif
    </p>
</div>

{{-- No Config Warning --}}
@if(! $hasConfig)
    <div class="flex items-center justify-between px-4 py-4 mb-6 text-yellow-800 border border-yellow-300 rounded-lg bg-yellow-50">
        <span class="text-sm font-medium">⚠️ Grade configuration not set. Configure weights to enable grade entry.</span>
        <a href="{{ route('teacher.grades.config', $section) }}"
           class="px-4 py-2 ml-4 text-sm font-medium text-white bg-yellow-500 rounded-lg hover:bg-yellow-600">
            Configure Now
        </a>
    </div>
@endif

{{-- Quick Actions --}}
<div class="grid grid-cols-2 gap-4 mb-8 sm:grid-cols-5">
    <a href="{{ route('teacher.grades.config', $section) }}"
       class="p-4 transition bg-white border border-gray-200 rounded-xl hover:border-indigo-400 hover:shadow-sm group">
        <div class="mb-1 text-2xl text-indigo-500">⚙️</div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Grade Config</div>
        <div class="text-xs text-gray-400 mt-0.5">{{ $hasConfig ? 'Configured ✓' : 'Not set' }}</div>
    </a>
    <a href="{{ route('teacher.grades.items', $section) }}"
       class="{{ ! $hasConfig ? 'pointer-events-none opacity-50' : '' }} bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition group">
        <div class="mb-1 text-2xl text-indigo-500">📝</div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Grade Items</div>
        <div class="text-xs text-gray-400 mt-0.5">{{ $section->gradeItems->count() }} item(s)</div>
    </a>
    <a href="{{ route('teacher.attendance.index', $section) }}"
       class="{{ ! $hasConfig ? 'pointer-events-none opacity-50' : '' }} bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition group">
        <div class="mb-1 text-2xl text-indigo-500">📅</div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Attendance</div>
        <div class="text-xs text-gray-400 mt-0.5">Mark daily attendance</div>
    </a>
    <a href="{{ route('teacher.grades.final', $section) }}"
       class="{{ ! $hasConfig ? 'pointer-events-none opacity-50' : '' }} bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition group">
        <div class="mb-1 text-2xl text-indigo-500">🎓</div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Final Grades</div>
        <div class="text-xs text-gray-400 mt-0.5">View & compute</div>
    </a>
    <a href="{{ route('teacher.classes.record', $section) }}"
       class="{{ ! $hasConfig ? 'pointer-events-none opacity-50' : '' }} bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition group">
        <div class="mb-1 text-2xl text-indigo-500">📊</div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Class Record</div>
        <div class="text-xs text-gray-400 mt-0.5">Full spreadsheet view</div>
    </a>
</div>

{{-- Students Table --}}
<div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-700">Enrolled Students ({{ $section->enrollments->count() }})</h2>
    </div>

    @if($section->enrollments->isEmpty())
        <div class="px-6 py-10 text-sm text-center text-gray-400">No students enrolled yet.</div>
    @else
        <table class="w-full text-sm">
            <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">#</th>
                    <th class="px-6 py-3 text-left">Student No.</th>
                    <th class="px-6 py-3 text-left">Name</th>
                    <th class="px-6 py-3 text-center">Final Grade</th>
                    <th class="px-6 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($section->enrollments as $i => $enrollment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-6 py-3 font-mono text-gray-600">{{ $enrollment->student?->student_number ?? 'N/A' }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $enrollment->student?->full_name ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-center">
                            @if($enrollment->finalGrade)
                                <span class="font-semibold text-gray-800">{{ $enrollment->finalGrade->final_grade }}%</span>
                                <span class="ml-1 font-bold text-indigo-600">({{ $enrollment->finalGrade->letter_grade }})</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($enrollment->finalGrade)
                                @if($enrollment->finalGrade->is_locked)
                                    <span class="px-2 py-1 text-xs text-gray-600 bg-gray-100 rounded-full">Locked</span>
                                @elseif($enrollment->finalGrade->remarks === 'passed')
                                    <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">Passed</span>
                                @else
                                    <span class="px-2 py-1 text-xs text-red-700 bg-red-100 rounded-full">Failed</span>
                                @endif
                            @else
                                <span class="px-2 py-1 text-xs text-yellow-700 bg-yellow-100 rounded-full">Pending</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection
