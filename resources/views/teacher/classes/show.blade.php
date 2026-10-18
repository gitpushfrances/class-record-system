<x-sidebar-layout>




{{-- Header --}}
<div class="mb-6">
    @php [$backUrl, $backLabel] = \App\Services\TeacherNav::sectionBack(); @endphp
    <x-back-button :href="$backUrl" :label="$backLabel" />
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
                        <a href="{{ \App\Services\TeacherNav::route('teacher.grades.config', [$section, $subject]) }}"
                           class="p-3 text-center transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:shadow-sm group">
                            <div class="mb-1 text-xl text-indigo-500"><i class="fa-solid fa-gear"></i></div>
                            <div class="text-xs font-semibold text-gray-700 group-hover:text-indigo-600">Grade Config</div>
                        </a>
                        <a href="{{ \App\Services\TeacherNav::route('teacher.grades.items', [$section, $subject]) }}"
                           class="{{ !$row['hasConfig'] ? 'pointer-events-none opacity-50' : '' }} p-3 text-center transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:shadow-sm group">
                            <div class="mb-1 text-xl text-indigo-500"><i class="fa-solid fa-pen-to-square"></i></div>
                            <div class="text-xs font-semibold text-gray-700 group-hover:text-indigo-600">Grade Items</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $row['itemCount'] }} item(s)</div>
                        </a>
                        <a href="{{ \App\Services\TeacherNav::route('teacher.grades.final', [$section, $subject]) }}"
                           class="{{ !$row['hasConfig'] ? 'pointer-events-none opacity-50' : '' }} p-3 text-center transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:shadow-sm group">
                            <div class="mb-1 text-xl text-indigo-500"><i class="fa-solid fa-graduation-cap"></i></div>
                            <div class="text-xs font-semibold text-gray-700 group-hover:text-indigo-600">Final Grades</div>
                        </a>
                        <a href="{{ \App\Services\TeacherNav::route('teacher.classes.record', [$section, $subject]) }}"
                           class="{{ !$row['hasConfig'] ? 'pointer-events-none opacity-50' : '' }} p-3 text-center transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:shadow-sm group">
                            <div class="mb-1 text-xl text-indigo-500"><i class="fa-solid fa-chart-bar"></i></div>
                            <div class="text-xs font-semibold text-gray-700 group-hover:text-indigo-600">Class Record</div>
                        </a>
                        <a href="{{ \App\Services\TeacherNav::route('teacher.attendance.index', [$section, $subject]) }}"
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

{{-- Students Table --}}
<div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-700">Enrolled Students ({{ $currentTerm ? $currentTerm->enrollments->count() : 0 }})</h2>
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
                            <td colspan="3" class="px-6 py-2 text-xs font-semibold text-gray-600 uppercase bg-gray-100">{{ $group }}</td>
                        </tr>
                    @endif
                    @php $lastGroup = $group; @endphp
                    <tr class="roster-row hover:bg-gray-50" data-group="{{ $group }}"
                        data-search="{{ mb_strtolower(($enrollment->student?->student_number ?? '') . ' ' . ($enrollment->student?->full_name ?? '')) }}">
                        <td class="px-6 py-3 text-gray-400">{{ $n }}</td>
                        <td class="px-6 py-3 font-mono text-gray-600"><span data-hl>{{ $enrollment->student?->student_number ?? 'N/A' }}</span></td>
                        <td class="px-6 py-3 font-medium text-gray-800"><span data-hl>{{ $enrollment->student?->full_name ?? 'N/A' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-10 text-sm text-center text-gray-400">No {{ $gender }} students enrolled.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var rosterSearch = document.getElementById('rosterSearch');
    if (rosterSearch) {
        var rosterRows  = document.querySelectorAll('tr.roster-row');
        var rosterCount = document.getElementById('rosterCount');
        rosterSearch.addEventListener('input', function () {
            var q = rosterSearch.value.trim().toLowerCase(), shown = 0, terms = EssuSearch.terms(q);
            rosterRows.forEach(function (r) {
                var match = q === '' || EssuSearch.matches(r.dataset.search, terms);
                r.style.display = match ? '' : 'none';
                r.querySelectorAll('[data-hl]').forEach(function (el) { EssuSearch.highlight(el, terms); });
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
</x-sidebar-layout>
