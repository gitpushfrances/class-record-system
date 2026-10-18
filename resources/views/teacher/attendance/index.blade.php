<x-sidebar-layout>




<div class="flex items-start justify-between mb-6">
    <div>
        <x-back-button :href="\App\Services\TeacherNav::route('teacher.classes.record', [$section, $subject])" label="Back to Class Record" />
        <h1 class="mt-1 text-2xl font-bold text-gray-800">Attendance</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $subject->code }} — {{ $subject->name }} &bull; {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
    </div>
    <a href="{{ \App\Services\TeacherNav::route('teacher.attendance.summary', [$section, $subject]) }}"
       class="px-4 py-2 mt-1 text-sm font-medium text-indigo-600 transition border border-indigo-300 rounded-lg hover:bg-indigo-50">
        View Summary
    </a>
</div>

<div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">

    {{-- Date Selector --}}
    <div class="flex items-center gap-4 px-6 py-4 border-b border-gray-100">
        <form method="GET" action="{{ \App\Services\TeacherNav::route('teacher.attendance.index', [$section, $subject]) }}" class="flex items-center gap-3">
            @include('teacher.partials.from-field')
            <label class="text-sm font-medium text-gray-600">Date:</label>
            <input type="date" name="date" value="{{ $date }}"
                   class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                   onchange="this.form.submit()">
            @if($subject->has_laboratory)
                <label class="text-sm font-medium text-gray-600">Session:</label>
                <select name="session" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" onchange="this.form.submit()">
                    <option value="lecture" @selected($session === 'lecture')>Lecture</option>
                    <option value="laboratory" @selected($session === 'laboratory')>Laboratory</option>
                </select>
            @endif
        </form>
    </div>

    <form method="POST" action="{{ \App\Services\TeacherNav::route('teacher.attendance.store', [$section, $subject]) }}" id="attendanceForm">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <input type="hidden" name="session" value="{{ $session }}">

        {{-- Quick Actions --}}
        <div class="flex gap-3 px-6 py-3 border-b border-gray-100 bg-gray-50">
            <span class="self-center text-xs font-medium text-gray-500">Quick:</span>
            <button type="button" onclick="markAll('present')"
                    class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1.5 rounded-lg font-medium transition">
                <i class="fa-solid fa-circle-check"></i> All Present
            </button>
            <button type="button" onclick="markAll('absent')"
                    class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded-lg font-medium transition">
                <i class="fa-solid fa-circle-xmark"></i> All Absent
            </button>
        </div>

        <table class="w-full text-sm">
            <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">#</th>
                    <th class="px-6 py-3 text-left">Student No.</th>
                    <th class="px-6 py-3 text-left">Name</th>
                    <th class="px-6 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($enrollments as $i => $enrollment)
                    @php $record = $enrollment->attendanceRecords->first(); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ $enrollment->student?->student_number ?? 'N/A' }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $enrollment->student?->full_name ?? 'N/A' }}@if(($streaks[$enrollment->id] ?? 0) >= \App\Models\AttendanceRecord::ABSENCE_WARNING)<span class="px-2 py-1 ml-2 text-xs font-medium rounded-full" style="background:#fef3c7; color:#b45309;" title="Needs a guidance office permission slip before re-entering class">{{ $streaks[$enrollment->id] }} absences in a row</span>@endif</td>
                        <td class="px-6 py-3 text-center">
                            <input type="hidden" name="attendance[{{ $i }}][enrollment_id]" value="{{ $enrollment->id }}">
                            <div class="flex flex-wrap justify-center gap-2">
                                @foreach(['present' => ['bg-green-100 text-green-700 border-green-300', '<i class="fa-solid fa-circle-check"></i> Present'],
                                          'absent'  => ['bg-red-100 text-red-700 border-red-300', '<i class="fa-solid fa-circle-xmark"></i> Absent']] as $val => [$cls, $lbl])
                                    <label class="cursor-pointer">
                                        <input type="radio"
                                               name="attendance[{{ $i }}][status]"
                                               value="{{ $val }}"
                                               class="sr-only status-radio"
                                               {{ ($record?->status ?? 'present') === $val ? 'checked' : '' }}>
                                        <span class="status-btn border rounded-lg px-3 py-1 text-xs font-medium transition
                                                     {{ ($record?->status ?? 'present') === $val ? $cls . ' border-current' : 'border-gray-200 text-gray-400' }}">
                                            {!! $lbl !!}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end px-6 py-4 border-t border-gray-100">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition">
                Save Attendance
            </button>
        </div>
    </form>
</div>

<script>
// Highlight selected radio button
document.querySelectorAll('.status-radio').forEach(radio => {
    radio.addEventListener('change', function () {
        const row = this.closest('tr');
        row.querySelectorAll('.status-btn').forEach(btn => {
            btn.className = 'status-btn border rounded-lg px-3 py-1 text-xs font-medium transition border-gray-200 text-gray-400';
        });
        const colors = {
            present: 'bg-green-100 text-green-700 border-green-300 border-current',
            absent:  'bg-red-100 text-red-700 border-red-300 border-current',
        };
        this.nextElementSibling.className = 'status-btn border rounded-lg px-3 py-1 text-xs font-medium transition ' + colors[this.value];
    });
});

function markAll(status) {
    document.querySelectorAll('.status-radio[value="' + status + '"]').forEach(r => {
        r.checked = true;
        r.dispatchEvent(new Event('change'));
    });
}
</script>

</x-sidebar-layout>
