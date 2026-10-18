<x-sidebar-layout>
    @php
        $th  = 'padding:10px 24px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#6b7280; text-align:left; background:#f9fafb; border-bottom:1px solid #e5e7eb;';
        $td  = 'padding:10px 24px; font-size:14px; color:#374151; border-bottom:1px solid #f3f4f6;';
        $btn = 'padding:6px 12px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; white-space:nowrap;';
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

            {{-- Enroll Students --}}
            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Enroll Students</h3>
                </div>
                <div class="px-6 py-4">
                    @include('partials.student-picker', [
                        'action'    => route('dean.enrollments.store', $currentTerm),
                        'students'  => $availableStudents,
                        'emptyText' => 'Every active student of this program is already in a section this semester.',
                    ])
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
                                <th style="{{ $th }}">Student No.</th><th style="{{ $th }}">Name</th><th style="{{ $th }}">Year Level</th><th style="{{ $th }}"></th>
                            </tr></thead>
                            <tbody>
                            @foreach(\App\Models\Enrollment::sortRoster($currentTerm->enrollments) as $enrollment)
                                @if($enrollment->student)
                                <tr>
                                    <td style="{{ $td }} font-family:monospace;">{{ $enrollment->student->student_number }}</td>
                                    <td style="{{ $td }}">{{ $enrollment->student->full_name }}</td>
                                    <td style="{{ $td }}">{{ $enrollment->student->year_level }}</td>
                                    <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                        @if(in_array($enrollment->id, $removableIds, true))
                                            <form method="POST" action="{{ route('dean.enrollments.remove', [$currentTerm, $enrollment]) }}" style="display:inline;"
                                                  data-confirm="Remove {{ $enrollment->student->full_name }} from the section? Use this only for a student added by mistake: the enrollment is deleted.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" style="{{ $btn }} color:#374151; background:#fff; border:1px solid #d1d5db;">Remove</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('dean.enrollments.destroy', [$currentTerm, $enrollment]) }}" style="display:inline;"
                                              data-confirm="Drop {{ $enrollment->student->full_name }} from the section? Grades and attendance are kept, and the Report of Grades will show Dr.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" style="{{ $btn }} color:#b91c1c; background:#fff; border:1px solid #fca5a5;">Drop</button>
                                        </form>
                                    </td>
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
                            <td style="{{ $td }} text-align:right;">
                                <form method="POST" action="{{ route('dean.enrollments.store', $currentTerm) }}">
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
                No active term for this section. Set an adviser first to activate a term.
            </div>
        @endif

    </div>

    <script>
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const text = form.dataset.confirm;
                if (typeof Swal === 'undefined') {
                    if (confirm(text)) form.submit();
                    return;
                }
                Swal.fire({
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#b91c1c',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel',
                }).then(function (result) { if (result.isConfirmed) form.submit(); });
            });
        });
    </script>
</x-sidebar-layout>
