<x-sidebar-layout>




{{-- Header --}}
<div class="mb-6">
    <a href="{{ route('teacher.dashboard') }}" class="text-sm text-indigo-600 hover:underline">← Back to Dashboard</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-800">
        {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
    </h1>
    <p class="mt-1 text-sm text-gray-500">
        {{ $section->program->name }} &bull; {{ $section->year_level }}
    </p>
</div>

{{-- Per-subject grading actions --}}
@if($subjectsData->isEmpty())
    <div class="p-6 mb-8 text-sm text-center text-gray-400 bg-white border border-gray-200 rounded-xl">
        No subjects assigned to this section yet.
    </div>
@else
    <div class="mb-8 space-y-4">
        @foreach($subjectsData as $row)
            @php $subject = $row['subject']; @endphp
            <div class="overflow-hidden bg-white border border-gray-200 rounded-xl">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <div>
                        <span class="font-semibold text-gray-800">{{ $subject->code }}</span>
                        <span class="ml-1 text-sm text-gray-500">{{ $subject->name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(!$row['isMine'])
                            <span class="px-2 py-1 text-xs text-gray-500 bg-gray-100 rounded-full">Not your subject</span>
                        @elseif(!$row['hasConfig'])
                            <span class="px-2 py-1 text-xs text-yellow-700 bg-yellow-100 rounded-full">
                                <i class="fa-solid fa-triangle-exclamation"></i> Not configured
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs text-green-700 bg-green-100 rounded-full">Configured</span>
                        @endif
                    </div>
                </div>

                @if($row['isMine'])
                    <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-5">
                        <a href="{{ route('teacher.grades.config', [$section, $subject]) }}"
                           class="p-3 text-center transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:shadow-sm group">
                            <div class="mb-1 text-xl text-indigo-500"><i class="fa-solid fa-gear"></i></div>
                            <div class="text-xs font-semibold text-gray-700 group-hover:text-indigo-600">Grade Config</div>
                        </a>
                        <a href="{{ route('teacher.grades.items', [$section, $subject]) }}"
                           class="{{ !$row['hasConfig'] ? 'pointer-events-none opacity-50' : '' }} p-3 text-center transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:shadow-sm group">
                            <div class="mb-1 text-xl text-indigo-500"><i class="fa-solid fa-pen-to-square"></i></div>
                            <div class="text-xs font-semibold text-gray-700 group-hover:text-indigo-600">Grade Items</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $row['itemCount'] }} item(s)</div>
                        </a>
                        <a href="{{ route('teacher.grades.final', [$section, $subject]) }}"
                           class="{{ !$row['hasConfig'] ? 'pointer-events-none opacity-50' : '' }} p-3 text-center transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:shadow-sm group">
                            <div class="mb-1 text-xl text-indigo-500"><i class="fa-solid fa-graduation-cap"></i></div>
                            <div class="text-xs font-semibold text-gray-700 group-hover:text-indigo-600">Final Grades</div>
                        </a>
                        <a href="{{ route('teacher.classes.record', [$section, $subject]) }}"
                           class="{{ !$row['hasConfig'] ? 'pointer-events-none opacity-50' : '' }} p-3 text-center transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:shadow-sm group">
                            <div class="mb-1 text-xl text-indigo-500"><i class="fa-solid fa-chart-bar"></i></div>
                            <div class="text-xs font-semibold text-gray-700 group-hover:text-indigo-600">Class Record</div>
                        </a>
                        <a href="{{ route('teacher.attendance.index', [$section, $subject]) }}"
                           class="p-3 text-center transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:shadow-sm group">
                            <div class="mb-1 text-xl text-indigo-500"><i class="fa-solid fa-calendar-days"></i></div>
                            <div class="text-xs font-semibold text-gray-700 group-hover:text-indigo-600">Attendance</div>
                        </a>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif

{{-- Add Student Modal --}}
<div id="enrollModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black bg-opacity-40">
    <div class="w-full max-w-md p-6 bg-white shadow-lg rounded-xl">
        <h3 class="mb-4 text-lg font-semibold text-gray-800">Add Student to Class</h3>

        @if(session('error'))
            <div class="px-4 py-2 mb-3 text-sm text-red-600 border border-red-200 rounded-lg bg-red-50">
                {{ session('error') }}
            </div>
        @endif

        {{-- Search box --}}
        <input type="text" id="studentSearch"
               placeholder="Search by name or student number..."
               class="w-full px-4 py-2 mb-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400"
               oninput="filterStudents(this.value)">

        {{-- Student list --}}
        <div id="studentList" class="mb-4 overflow-y-auto border border-gray-200 divide-y divide-gray-100 rounded-lg max-h-56">
            @forelse($availableStudents as $student)
                <form method="POST" action="{{ route('teacher.classes.enroll', $section) }}">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                    <button type="submit"
                            class="w-full px-4 py-3 text-sm text-left transition student-row hover:bg-indigo-50"
                            data-name="{{ strtolower($student->full_name) }}"
                            data-number="{{ strtolower($student->student_number) }}">
                        <span class="font-medium text-gray-800">{{ $student->full_name }}</span>
                        <span class="ml-2 text-xs text-gray-400">{{ $student->student_number }}</span>
                        <span class="ml-2 text-xs text-indigo-500">{{ $student->student_type }}</span>
                    </button>
                </form>
            @empty
                <div class="px-4 py-4 text-sm text-center text-gray-400">No available students to enroll.</div>
            @endforelse
        </div>

        <button onclick="closeEnrollModal()"
                class="w-full px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancel
        </button>
    </div>
</div>

{{-- Students Table --}}
<div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-700">Enrolled Students ({{ $currentTerm ? $currentTerm->enrollments->count() : 0 }})</h2>
        @if($currentTerm)
            <button onclick="openEnrollModal()"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                + Add Student
            </button>
        @endif
    </div>

    @php
        $gender = in_array(request('gender'), ['male', 'female'], true) ? request('gender') : 'all';
        $order  = request('order') === 'id' ? 'id' : 'name';
        $roster = $currentTerm
            ? \App\Models\Enrollment::sortRoster($currentTerm->enrollments, $gender === 'all' ? null : $gender, $order)
            : collect();
    @endphp

    @if(!$currentTerm || $currentTerm->enrollments->isEmpty())
        <div class="px-6 py-10 text-sm text-center text-gray-400">No students enrolled yet.</div>
    @else
        <div class="px-6 py-4 border-b border-gray-100">
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
            <div class="flex flex-wrap items-center gap-3">
                <input type="text" id="rosterSearch" placeholder="Search student name or number..." autocomplete="off"
                       class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400"
                       style="width:100%; max-width:320px;">
                <span id="rosterCount" class="text-xs text-gray-400"></span>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">#</th>
                    <th class="px-6 py-3 text-left">Student No.</th>
                    <th class="px-6 py-3 text-left">Name</th>
                    <th class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @php $lastGroup = null; $n = 0; @endphp
                @forelse($roster as $enrollment)
                    @php
                        $g     = $enrollment->student?->gender;
                        $group = $g === 'male' ? 'Male' : ($g === 'female' ? 'Female' : 'No gender set');
                        if ($group !== $lastGroup) { $n = 0; }
                        $n++;
                    @endphp
                    @if($gender === 'all' && $group !== $lastGroup)
                        <tr class="roster-divider" data-group="{{ $group }}">
                            <td colspan="4" class="px-6 py-2 text-xs font-semibold text-gray-600 uppercase bg-gray-100">{{ $group }}</td>
                        </tr>
                    @endif
                    @php $lastGroup = $group; @endphp
                    <tr class="roster-row hover:bg-gray-50" data-group="{{ $group }}"
                        data-search="{{ mb_strtolower(($enrollment->student?->student_number ?? '') . ' ' . ($enrollment->student?->full_name ?? '')) }}">
                        <td class="px-6 py-3 text-gray-400">{{ $n }}</td>
                        <td class="px-6 py-3 font-mono text-gray-600">{{ $enrollment->student?->student_number ?? 'N/A' }}</td>
                        <td class="px-6 py-3 font-medium text-gray-800">{{ $enrollment->student?->full_name ?? 'N/A' }}</td>
                        <td class="px-6 py-3 text-center">
                            <form id="removeForm-{{ $enrollment->id }}" method="POST" action="{{ route('teacher.classes.unenroll', [$section, $enrollment]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                        onclick="openRemoveModal('{{ $enrollment->id }}', '{{ addslashes($enrollment->student?->full_name) }}')"
                                        class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-10 text-sm text-center text-gray-400">No {{ $gender }} students enrolled.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    window.openEnrollModal  = function () { document.getElementById('enrollModal').classList.replace('hidden','flex'); };
    window.closeEnrollModal = function () { document.getElementById('enrollModal').classList.replace('flex','hidden'); };
    window.filterStudents   = function (q) {
        q = q.toLowerCase();
        document.querySelectorAll('.student-row').forEach(row => {
            const match = row.dataset.name.includes(q) || row.dataset.number.includes(q);
            row.closest('form').style.display = match ? '' : 'none';
        });
    };

    let activeRemoveFormId = null;

    window.openRemoveModal = function (enrollmentId, studentName) {
        activeRemoveFormId = enrollmentId;
        document.getElementById('removeStudentName').textContent = studentName;
        document.getElementById('removeModal').classList.replace('hidden', 'flex');
    };

    window.closeRemoveModal = function () {
        activeRemoveFormId = null;
        document.getElementById('removeModal').classList.replace('flex', 'hidden');
    };

    document.getElementById('confirmRemoveBtn').addEventListener('click', function () {
        if (activeRemoveFormId) {
            document.getElementById('removeForm-' + activeRemoveFormId).submit();
        }
    });

    var rosterSearch = document.getElementById('rosterSearch');
    if (rosterSearch) {
        var rosterRows  = document.querySelectorAll('tr.roster-row');
        var rosterCount = document.getElementById('rosterCount');
        rosterSearch.addEventListener('input', function () {
            var q = rosterSearch.value.trim().toLowerCase(), shown = 0;
            rosterRows.forEach(function (r) {
                var match = q === '' || r.dataset.search.indexOf(q) !== -1;
                r.style.display = match ? '' : 'none';
                if (match) shown++;
            });
            document.querySelectorAll('tr.roster-divider').forEach(function (d) {
                var any = Array.prototype.some.call(rosterRows, function (r) {
                    return r.dataset.group === d.dataset.group && r.style.display !== 'none';
                });
                d.style.display = any ? '' : 'none';
            });
            rosterCount.textContent = q === '' ? '' : 'Showing ' + shown + ' of ' + rosterRows.length;
        });
    }
});
</script>
{{-- Remove Student Confirmation Modal --}}
<div id="removeModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black bg-opacity-40 backdrop-blur-sm">
    <div class="w-full max-w-sm p-6 bg-white shadow-xl rounded-2xl">
        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h3 class="mb-1 text-lg font-semibold text-center text-gray-800">Remove Student</h3>
        <p class="text-sm text-center text-gray-500">You are about to remove</p>
        <p id="removeStudentName" class="mb-1 text-sm font-semibold text-center text-gray-800"></p>
        <p class="mb-6 text-xs text-center text-gray-400">This will unenroll them from the class.<br>This action cannot be undone.</p>
        <div class="flex gap-3">
            <button onclick="closeRemoveModal()"
                    class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancel
            </button>
            <button id="confirmRemoveBtn"
                    class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                Yes, Remove
            </button>
        </div>
    </div>
</div>
</x-sidebar-layout>
