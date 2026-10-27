<x-sidebar-layout>
    @php
        $th  = 'padding:10px 24px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#6b7280; text-align:left; background:#f9fafb; border-bottom:1px solid #e5e7eb;';
        $td  = 'padding:10px 24px; font-size:14px; color:#374151; border-bottom:1px solid #f3f4f6;';
    @endphp

    <div class="max-w-4xl mx-auto space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <x-back-button :href="route('dean.enrollments.index')" label="Back to Enrollments" />
                <h1 class="mt-1 text-2xl font-bold" style="font-family:'Fraunces',serif; color:#0b3d0b;">
                    {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">{{ $section->year_level }}</p>
            </div>
        </div>

        @if($currentTerm)
            {{-- Section Term Info --}}
            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Current Term</h3>
                </div>
                <div class="grid grid-cols-2 gap-4 px-6 py-4 text-sm">
                    <div><span class="text-gray-500">Semester:</span> <span class="font-medium text-gray-800">{{ $currentTerm->semester }}</span></div>
                    <div><span class="text-gray-500">Academic Year:</span> <span class="font-medium text-gray-800">{{ $currentTerm->academic_year }}</span></div>
                    <div><span class="text-gray-500">Adviser:</span> <span class="font-medium text-gray-800">{{ $currentTerm->adviser?->name ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Status:</span>
                        <span class="px-2 py-1 text-xs rounded {{ $currentTerm->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($currentTerm->status) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Enrolled Students --}}
            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Enrolled Students ({{ $currentTerm->enrollments->count() }})</h3>
                </div>
                @if($currentTerm->enrollments->isEmpty())
                    <div class="px-6 py-4 text-sm text-gray-500">No students enrolled yet.</div>
                @else
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead><tr>
                                <th style="{{ $th }}">Student No.</th><th style="{{ $th }}">Name</th><th style="{{ $th }}">Year Level</th>
                            </tr></thead>
                            <tbody>
                            @foreach(\App\Models\Enrollment::sortRoster($currentTerm->enrollments) as $enrollment)
                                @if($enrollment->student)
                                <tr>
                                    <td style="{{ $td }} font-family:monospace;">{{ $enrollment->student->student_number }}</td>
                                    <td style="{{ $td }}">{{ $enrollment->student->full_name }}</td>
                                    <td style="{{ $td }}">{{ $enrollment->student->year_level }}</td>
                                </tr>
                                @endif
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
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif

        @else
            <div class="py-8 text-sm text-center text-gray-400 bg-white border border-gray-200 rounded-lg">
                No active term for this section. Set an adviser first to activate a term.
            </div>
        @endif

    </div>

</x-sidebar-layout>
