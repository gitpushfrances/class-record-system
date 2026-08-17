<x-sidebar-layout>

<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('teacher.attendance.index', [$section, $subject]) }}" class="text-sm text-indigo-600 hover:underline">← Back to Attendance</a>
        <h1 class="mt-1 text-2xl font-bold text-gray-800">Attendance Summary</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $subject->code }} — {{ $subject->name }} &bull; {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
        </p>
    </div>
</div>

<div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
    <table class="w-full text-sm">
        <thead class="text-xs text-gray-500 uppercase bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left">#</th>
                <th class="px-6 py-3 text-left">Student No.</th>
                <th class="px-6 py-3 text-left">Name</th>
                <th class="px-6 py-3 text-center">Total</th>
                <th class="px-6 py-3 text-center">Present</th>
                <th class="px-6 py-3 text-center">Absent</th>
                <th class="px-6 py-3 text-center">Excused</th>
                <th class="px-6 py-3 text-center">Rate</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($enrollments as $i => $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-400">{{ $i + 1 }}</td>
                    <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $row['student']?->student_number ?? 'N/A' }}</td>
                    <td class="px-6 py-3 font-medium text-gray-800">{{ $row['student']?->full_name ?? 'N/A' }}</td>
                    <td class="px-6 py-3 text-center">{{ $row['total'] }}</td>
                    <td class="px-6 py-3 text-center text-green-700">{{ $row['present'] }}</td>
                    <td class="px-6 py-3 text-center text-red-600">{{ $row['absent'] }}</td>
                    <td class="px-6 py-3 text-center text-blue-600">{{ $row['excused'] }}</td>
                    <td class="px-6 py-3 text-center">
                        <span class="px-2 py-1 text-xs rounded-full font-medium
                            {{ $row['percent'] >= 75 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $row['percent'] }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-400">No attendance records yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

</x-sidebar-layout>
