@extends('layouts.teacher')

@section('title', 'Attendance Summary')

@section('content')

<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('teacher.attendance.index', $section) }}" class="text-sm text-indigo-600 hover:underline">← Back to Attendance</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-800">Attendance Summary</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $section->subject->code }} — {{ $section->section_name }}</p>
    </div>
</div>

<div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
    <table class="w-full text-sm">
        <thead class="text-xs text-gray-500 uppercase bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left">#</th>
                <th class="px-6 py-3 text-left">Student</th>
                <th class="px-6 py-3 text-center">Present</th>
                <th class="px-6 py-3 text-center">Absent</th>
                <th class="px-6 py-3 text-center">Excused</th>
                <th class="px-6 py-3 text-center">Total</th>
                <th class="px-6 py-3 text-center">Attendance %</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($enrollments as $i => $data)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-400">{{ $i + 1 }}</td>
                    <td class="px-6 py-3">
                        <div class="font-medium text-gray-800">{{ $data['student']?->full_name ?? 'N/A' }}</div>
                        <div class="font-mono text-xs text-gray-400">{{ $data['student']?->student_number }}</div>
                    </td>
                    <td class="px-6 py-3 font-medium text-center text-green-600">{{ $data['present'] }}</td>
                    <td class="px-6 py-3 font-medium text-center text-red-500">{{ $data['absent'] }}</td>
                    <td class="px-6 py-3 font-medium text-center text-blue-500">{{ $data['excused'] }}</td>
                    <td class="px-6 py-3 text-center text-gray-600">{{ $data['total'] }}</td>
                    <td class="px-6 py-3 text-center">
                        @php $pct = $data['percent']; @endphp
                        <span class="font-semibold {{ $pct >= 80 ? 'text-green-600' : ($pct >= 60 ? 'text-yellow-600' : 'text-red-500') }}">
                            {{ $pct }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-10 text-sm text-center text-gray-400">No enrollment data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
