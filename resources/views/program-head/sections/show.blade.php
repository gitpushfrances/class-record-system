<x-sidebar-layout>

    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-800">
                {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
            </h2>
            <a href="{{ route('program-head.sections.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
        </div>

        <div class="mb-6 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Section Details</h3>
            </div>
            <div class="px-6 py-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Program</span>
                    <span class="font-medium">{{ $section->program->name }} ({{ $section->program->code }})</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Year Level</span>
                    <span class="font-medium">{{ $section->year_level }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="px-2 py-1 text-xs rounded {{ $section->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($section->status) }}
                    </span>
                </div>
            </div>
        </div>

        @if($currentTerm)
        <div class="mb-6 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Current Term — {{ $currentTerm->semester }}, {{ $currentTerm->academic_year }}</h3>
            </div>
            <div class="px-6 py-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Adviser</span>
                    <span class="font-medium">{{ $currentTerm->adviser?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Enrolled Students</span>
                    <span class="font-medium">{{ $currentTerm->enrollments->count() }}</span>
                </div>
            </div>
        </div>

        <div class="mb-6 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Subjects & Teachers</h3>
            </div>
            <div style="padding:16px 24px;">
                @include('program-head.sections.partials.subjects', ['subjects' => $currentTerm->subjects])
            </div>
        </div>

        @php
            $th  = 'padding:10px 24px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#6b7280; text-align:left; background:#f9fafb; border-bottom:1px solid #e5e7eb;';
            $td  = 'padding:10px 24px; font-size:14px; color:#374151; border-bottom:1px solid #f3f4f6;';
            $btn = 'padding:6px 12px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; white-space:nowrap;';
        @endphp
        <div class="mb-6 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 24px; border-bottom:1px solid #f3f4f6;">
                <h3 class="text-sm font-semibold text-gray-700">Enrolled Students</h3>
                <span style="font-size:12px; color:#6b7280;">{{ $currentTerm->enrollments->count() }} {{ \Illuminate\Support\Str::plural('student', $currentTerm->enrollments->count()) }}</span>
            </div>

            <form method="POST" action="{{ route('program-head.sections.enroll', $section) }}" style="display:flex; flex-wrap:wrap; gap:8px; padding:16px 24px; border-bottom:1px solid #f3f4f6;">
                @csrf
                <select name="student_id" required style="flex:1; min-width:220px; padding:8px 12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px;">
                    <option value="">{{ $availableStudents->isEmpty() ? 'All active students in this program are enrolled' : 'Select a student to enroll…' }}</option>
                    @foreach($availableStudents as $student)
                        <option value="{{ $student->id }}">{{ $student->full_name }} — {{ $student->student_number }} ({{ $student->year_level }})</option>
                    @endforeach
                </select>
                <button type="submit" @disabled($availableStudents->isEmpty()) style="{{ $btn }} padding:8px 16px; color:#fff; background:#4f46e5; border:none;">
                    <i class="fa-solid fa-user-plus"></i> Enroll
                </button>
            </form>

            @if($currentTerm->enrollments->isEmpty())
                <p style="margin:0; padding:16px 24px; font-size:14px; color:#9ca3af;">No students enrolled yet.</p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead><tr>
                            <th style="{{ $th }}">Student No.</th><th style="{{ $th }}">Name</th><th style="{{ $th }}"></th>
                        </tr></thead>
                        <tbody>
                        @foreach(\App\Models\Enrollment::sortRoster($currentTerm->enrollments) as $enrollment)
                            <tr>
                                <td style="{{ $td }} font-family:monospace;">{{ $enrollment->student?->student_number }}</td>
                                <td style="{{ $td }}">{{ $enrollment->student?->full_name }}</td>
                                <td style="{{ $td }} text-align:right;">
                                    <form method="POST" action="{{ route('program-head.sections.drop', [$section, $enrollment]) }}"
                                          onsubmit="return confirm('Drop this student from the section? Grades and attendance are kept, and the Report of Grades will show Dr.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="{{ $btn }} color:#b91c1c; background:#fff; border:1px solid #fca5a5;">Drop</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if($droppedEnrollments->isNotEmpty())
        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:16px 24px; border-bottom:1px solid #f3f4f6;">
                <h3 class="text-sm font-semibold text-gray-700">Dropped</h3>
                <span style="font-size:12px; color:#6b7280;">Printed as “Dr” on the Report of Grades</span>
            </div>
            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                @foreach(\App\Models\Enrollment::sortRoster($droppedEnrollments) as $enrollment)
                    <tr>
                        <td style="{{ $td }} font-family:monospace;">{{ $enrollment->student?->student_number }}</td>
                        <td style="{{ $td }} color:#6b7280;">{{ $enrollment->student?->full_name }}</td>
                        <td style="{{ $td }} text-align:right;">
                            <form method="POST" action="{{ route('program-head.sections.enroll', $section) }}">
                                @csrf
                                <input type="hidden" name="student_id" value="{{ $enrollment->student_id }}">
                                <button type="submit" style="{{ $btn }} color:#374151; background:#fff; border:1px solid #d1d5db;">Re-enroll</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @else
        <div class="py-8 text-sm text-center text-gray-400 bg-white border border-gray-200 rounded-lg">
            No active term this semester.
        </div>
        @endif
    </div>

</x-sidebar-layout>
