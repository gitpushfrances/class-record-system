<x-sidebar-layout>
@php
    $card  = 'background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.04); margin-bottom:20px; overflow:hidden;';
    $head  = 'display:flex; align-items:center; justify-content:space-between; gap:8px; padding:14px 18px; border-bottom:1px solid #e5e7eb;';
    $th    = 'padding:10px 18px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#6b7280; text-align:left; background:#f9fafb; border-bottom:1px solid #e5e7eb;';
    $td    = 'padding:10px 18px; font-size:14px; color:#374151; border-bottom:1px solid #f3f4f6;';
    $btn   = 'padding:6px 12px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; white-space:nowrap;';
    $badge = 'display:inline-block; margin-left:6px; padding:1px 8px; font-size:11px; font-weight:600; border-radius:9999px;';
@endphp

<div style="margin-bottom:12px;"><x-back-button :href="\App\Services\TeacherNav::route('teacher.classes.record', [$section, $subject])" label="Back to Class Record" /></div>

<div style="margin-bottom:20px;">
    <h1 style="margin:0; font-size:24px; font-weight:700; color:#1f2937;">Class List — {{ $subject->code }}</h1>
    <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">
        {{ $subject->name }} &bull; {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }} &bull; {{ $term->semester }} &bull; {{ $term->academic_year }}
    </p>
</div>

@if($locked)
    <div style="padding:12px 16px; margin-bottom:20px; font-size:14px; color:#92400e; background:#fef3c7; border:1px solid #fde68a; border-radius:10px;">
        <i class="fa-solid fa-lock"></i> Grades for this subject are submitted. The class list is locked.
    </div>
@else
    {{-- Add a student --}}
    <div style="{{ $card }}">
        <div style="{{ $head }}">
            <h2 style="margin:0; font-size:15px; font-weight:700; color:#111827;">Add a student</h2>
            <span style="font-size:12px; color:#6b7280;">Any program — for irregular students and retakes</span>
        </div>
        <form method="GET" action="{{ \App\Services\TeacherNav::route('teacher.classes.students', [$section, $subject]) }}" style="display:flex; flex-wrap:wrap; gap:8px; padding:14px 18px;">
            @include('teacher.partials.from-field')
            <input type="search" name="q" value="{{ $query }}" minlength="{{ \App\Http\Controllers\Teacher\SubjectRosterController::MIN_SEARCH }}" required autofocus
                   placeholder="Student number or name (at least {{ \App\Http\Controllers\Teacher\SubjectRosterController::MIN_SEARCH }} characters)"
                   style="flex:1; min-width:220px; padding:8px 12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px;">
            <button type="submit" style="{{ $btn }} padding:8px 16px; color:#fff; background:#0a5b05; border:none;"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>

        @if(mb_strlen($query) >= \App\Http\Controllers\Teacher\SubjectRosterController::MIN_SEARCH)
            @if($results->isEmpty())
                <p style="margin:0; padding:0 18px 16px; font-size:14px; color:#6b7280;">No active student matches “{{ $query }}” outside this class.</p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse;">
                        <thead><tr>
                            <th style="{{ $th }}">Student No.</th><th style="{{ $th }}">Name</th><th style="{{ $th }}">Program</th><th style="{{ $th }}">Year</th><th style="{{ $th }}"></th>
                        </tr></thead>
                        <tbody>
                        @foreach($results as $student)
                            <tr>
                                <td style="{{ $td }}">{{ $student->student_number }}</td>
                                <td style="{{ $td }}">{{ $student->full_name }}</td>
                                <td style="{{ $td }}">{{ $student->program?->code ?? '—' }}</td>
                                <td style="{{ $td }}">{{ $student->year_level }}</td>
                                <td style="{{ $td }} text-align:right;">
                                    <form method="POST" action="{{ \App\Services\TeacherNav::route('teacher.classes.students.store', [$section, $subject]) }}">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        <button type="submit" style="{{ $btn }} color:#fff; background:#16a34a; border:none;"><i class="fa-solid fa-plus"></i> Add</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($results->count() >= \App\Http\Controllers\Teacher\SubjectRosterController::MAX_RESULTS)
                    <p style="margin:0; padding:10px 18px; font-size:12px; color:#6b7280;">Showing the first {{ \App\Http\Controllers\Teacher\SubjectRosterController::MAX_RESULTS }} matches. Type more of the name or number to narrow it down.</p>
                @endif
            @endif
        @endif
    </div>
@endif

{{-- Current class list --}}
<div style="{{ $card }}">
    <div style="{{ $head }}">
        <h2 style="margin:0; font-size:15px; font-weight:700; color:#111827;">Students in this class</h2>
        <span style="font-size:12px; color:#6b7280;">{{ $roster->count() }} {{ \Illuminate\Support\Str::plural('student', $roster->count()) }}</span>
    </div>
    @if($roster->isEmpty())
        <p style="margin:0; padding:16px 18px; font-size:14px; color:#6b7280;">No students yet.</p>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead><tr>
                    <th style="{{ $th }}">#</th><th style="{{ $th }}">Student No.</th><th style="{{ $th }}">Name</th><th style="{{ $th }}">Program</th><th style="{{ $th }}"></th>
                </tr></thead>
                <tbody>
                @foreach($roster as $enrollment)
                    <tr>
                        <td style="{{ $td }} color:#9ca3af;">{{ $loop->iteration }}</td>
                        <td style="{{ $td }}">{{ $enrollment->student?->student_number }}</td>
                        <td style="{{ $td }}">
                            {{ $enrollment->student?->full_name }}
                            @if($enrollment->type === \App\Models\Enrollment::TYPE_IRREGULAR)
                                <span style="{{ $badge }} color:#7c3aed; background:#ede9fe;">Irregular</span>
                            @endif
                        </td>
                        <td style="{{ $td }}">{{ $enrollment->student?->program?->code ?? '—' }}</td>
                        <td style="{{ $td }} text-align:right;">
                            @unless($locked)
                                <form method="POST" action="{{ \App\Services\TeacherNav::route('teacher.classes.students.destroy', [$section, $subject, $enrollment]) }}"
                                      onsubmit="return confirm('Drop this student from {{ $subject->code }}? Scores and attendance are kept, and the Report of Grades will show Dr.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="{{ $btn }} color:#b91c1c; background:#fff; border:1px solid #fca5a5;">Drop</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Dropped --}}
@if($dropped->isNotEmpty())
    <div style="{{ $card }}">
        <div style="{{ $head }}">
            <h2 style="margin:0; font-size:15px; font-weight:700; color:#111827;">Dropped</h2>
            <span style="font-size:12px; color:#6b7280;">Printed as “Dr” on the Report of Grades</span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <tbody>
                @foreach($dropped as $enrollment)
                    @php $sectionDrop = $enrollment->status === \App\Models\Enrollment::STATUS_DROPPED; @endphp
                    <tr>
                        <td style="{{ $td }} width:160px;">{{ $enrollment->student?->student_number }}</td>
                        <td style="{{ $td }} color:#6b7280;">
                            {{ $enrollment->student?->full_name }}
                            @if($sectionDrop)
                                <span style="{{ $badge }} color:#6b7280; background:#f3f4f6;">Dropped from section</span>
                            @endif
                        </td>
                        <td style="{{ $td }} text-align:right;">
                            @if(!$locked && !$sectionDrop)
                                <form method="POST" action="{{ \App\Services\TeacherNav::route('teacher.classes.students.restore', [$section, $subject, $enrollment]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" style="{{ $btn }} color:#374151; background:#fff; border:1px solid #d1d5db;">Restore</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
</x-sidebar-layout>
