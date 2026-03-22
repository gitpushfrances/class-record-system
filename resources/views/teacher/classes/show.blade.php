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

{{-- No Config Warning --}}
@if(! $hasConfig)
    <div class="flex items-center justify-between px-4 py-4 mb-6 text-yellow-800 border border-yellow-300 rounded-lg bg-yellow-50">
        <span class="text-sm font-medium"><i class="fa-solid fa-triangle-exclamation"></i> Grade configuration not set. Configure weights to enable grade entry.</span>
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
        <div class="mb-1 text-2xl text-indigo-500"><i class="fa-solid fa-gear"></i></div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Grade Config</div>
        <div class="text-xs text-gray-400 mt-0.5">{{ $hasConfig ? 'Configured' : 'Not set' }}</div>
    </a>
    <a href="{{ route('teacher.grades.items', $section) }}"
       class="{{ ! $hasConfig ? 'pointer-events-none opacity-50' : '' }} bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition group">
        <div class="mb-1 text-2xl text-indigo-500"><i class="fa-solid fa-pen-to-square"></i></div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Grade Items</div>
        <div class="text-xs text-gray-400 mt-0.5">{{ $section->gradeItems->count() }} item(s)</div>
    </a>
    <a href="{{ route('teacher.attendance.index', $section) }}"
       class="{{ ! $hasConfig ? 'pointer-events-none opacity-50' : '' }} bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition group">
        <div class="mb-1 text-2xl text-indigo-500"><i class="fa-solid fa-calendar-days"></i></div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Attendance</div>
        <div class="text-xs text-gray-400 mt-0.5">Mark daily attendance</div>
    </a>
    <a href="{{ route('teacher.grades.final', $section) }}"
       class="{{ ! $hasConfig ? 'pointer-events-none opacity-50' : '' }} bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition group">
        <div class="mb-1 text-2xl text-indigo-500"><i class="fa-solid fa-graduation-cap"></i></div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Final Grades</div>
        <div class="text-xs text-gray-400 mt-0.5">View & compute</div>
    </a>
    <a href="{{ route('teacher.classes.record', $section) }}"
       class="{{ ! $hasConfig ? 'pointer-events-none opacity-50' : '' }} bg-white border border-gray-200 rounded-xl p-4 hover:border-indigo-400 hover:shadow-sm transition group">
        <div class="mb-1 text-2xl text-indigo-500"><i class="fa-solid fa-chart-bar"></i></div>
        <div class="text-sm font-semibold text-gray-700 group-hover:text-indigo-600">Class Record</div>
        <div class="text-xs text-gray-400 mt-0.5">Full spreadsheet view</div>
    </a>
</div>

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

    @if(!$currentTerm || $currentTerm->enrollments->isEmpty())
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
                    <th class="px-6 py-3 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach(($currentTerm ? $currentTerm->enrollments : collect()) as $i => $enrollment)
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
                            <form method="POST" action="{{ route('teacher.classes.unenroll', [$section, $enrollment]) }}"
                                  onsubmit="return confirm('Remove this student from the class?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:underline">Remove</button>
                            </form>
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

<script>
function openEnrollModal()  { document.getElementById('enrollModal').classList.replace('hidden','flex'); }
function closeEnrollModal() { document.getElementById('enrollModal').classList.replace('flex','hidden'); }
function filterStudents(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.student-row').forEach(row => {
        const match = row.dataset.name.includes(q) || row.dataset.number.includes(q);
        row.closest('form').style.display = match ? '' : 'none';
    });
}
</script>
</x-sidebar-layout>$enrolledIds = $currentTerm
            ? $currentTerm->enrollments->pluck('student_id')->toArray()
            : [];

        $availableStudents = Student::where('status', 'active')
            ->whereNotIn('id', $enrolledIds)
            ->orderBy('last_name')
            ->get();

        return view('teacher.classes.show', compact('section', 'currentTerm', 'hasConfig', 'availableStudents'));
