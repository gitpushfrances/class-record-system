<x-sidebar-layout>
{{--
    Attendance summary: one column per recorded session, ✓ present / ✗ absent per student.
    Expects $section, $subject, $term, $sessions and $rows (App\Services\AttendanceSheet), $backUrl, $backLabel.
--}}
@php
    $groups = $sessions->groupBy(fn ($s) => $s['period'] ?? 'all');
    $titles = ['midterm' => 'Midterm', 'final' => 'Finals', 'all' => 'Sessions'];
    $cell   = 'padding:6px 4px; min-width:44px; text-align:center; border:1px solid #e5e7eb;';
    $head   = 'padding:8px; font-size:11px; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:#6b7280; background:#f9fafb; border:1px solid #e5e7eb;';
    $sticky = 'position:sticky; left:0; z-index:1; min-width:220px; text-align:left; background:#fff;';
@endphp

<div class="no-print" style="margin-bottom:12px;"><x-back-button :href="$backUrl" :label="$backLabel" /></div>
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Attendance Summary</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $subject->code }} — {{ $subject->name }} &bull; {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }} &bull; {{ $term->semester }} &bull; {{ $term->academic_year }}
        </p>
        <p class="mt-1 text-xs text-gray-500">
            <span style="color:#15803d; font-weight:700;">✓</span> present &nbsp; <span style="color:#b91c1c; font-weight:700;">✗</span> absent &nbsp; <span style="color:#9ca3af;">–</span> no record
        </p>
    </div>
    <button type="button" onclick="window.print()" class="no-print"
            style="padding:10px 16px; font-size:14px; font-weight:600; color:#fff; background:#374151; border:none; border-radius:8px; cursor:pointer;">
        <i class="fa-solid fa-print"></i> Print
    </button>
</div>

<style>
@media print {
    @page { size: landscape; margin: 10mm; }
    #sidebar, body header, .no-print { display: none !important; }
    body, main { background: #fff !important; }
    main { padding: 0 !important; }
    .attendance-sheet { overflow: visible !important; border: none !important; }
    .attendance-sheet table { font-size: 9px; }
    .attendance-sheet [style*="sticky"] { position: static !important; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<div class="attendance-sheet" style="overflow-x:auto; background:#fff; border:1px solid #e5e7eb; border-radius:12px;">
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr>
                <th rowspan="2" style="{{ $head }} {{ $sticky }} background:#f9fafb;">Student</th>
                @foreach($groups as $period => $periodSessions)
                    <th colspan="{{ $periodSessions->count() }}" style="{{ $head }} color:#fff; background:#0b3d0b;">{{ $titles[$period] }}</th>
                @endforeach
                <th rowspan="2" style="{{ $head }}">Present</th>
                <th rowspan="2" style="{{ $head }}">Absent</th>
                <th rowspan="2" style="{{ $head }}">Absent streak</th>
                <th rowspan="2" style="{{ $head }}">Rate</th>
            </tr>
            <tr>
                @foreach($groups as $periodSessions)
                    @foreach($periodSessions as $session)
                        <th style="{{ $head }} white-space:nowrap;">
                            {{ $session['date']->format('M j') }}
                            @if($subject->has_laboratory)
                                <div style="font-weight:500; text-transform:none;">{{ $session['type'] === 'laboratory' ? 'Lab' : 'Lec' }}</div>
                            @endif
                        </th>
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                <tr>
                    <td style="{{ $sticky }} padding:8px 12px; border:1px solid #e5e7eb;">
                        <div style="font-weight:600; color:#1f2937;">{{ $i + 1 }}. {{ $row['student']?->full_name ?? 'N/A' }}</div>
                        <div style="font-family:monospace; font-size:11px; color:#6b7280;">{{ $row['student']?->student_number ?? '' }}</div>
                    </td>
                    @foreach($groups as $periodSessions)
                        @foreach($periodSessions as $session)
                            @php $mark = $row['marks'][$session['key']] ?? null; @endphp
                            <td style="{{ $cell }}">
                                @if($mark === 'present')
                                    <span style="color:#15803d; font-weight:700;" title="Present">✓</span>
                                @elseif($mark === 'absent')
                                    <span style="color:#b91c1c; font-weight:700;" title="Absent">✗</span>
                                @else
                                    <span style="color:#9ca3af;" title="No record">–</span>
                                @endif
                            </td>
                        @endforeach
                    @endforeach
                    <td style="{{ $cell }} color:#15803d; font-weight:600;">{{ $row['present'] }}</td>
                    <td style="{{ $cell }} color:#b91c1c; font-weight:600;">{{ $row['absent'] }}</td>
                    <td style="{{ $cell }}">
                        @if($row['streak'] >= \App\Models\AttendanceRecord::ABSENCE_WARNING)
                            <span style="padding:2px 8px; font-size:11px; font-weight:600; color:#b45309; background:#fef3c7; border-radius:9999px; white-space:nowrap;" title="Needs a guidance office permission slip before re-entering class">{{ $row['streak'] }} in a row</span>
                        @else
                            <span style="color:#9ca3af;">{{ $row['streak'] }}</span>
                        @endif
                    </td>
                    <td style="{{ $cell }} white-space:nowrap; font-weight:600; color:{{ $row['percent'] >= 75 ? '#15803d' : '#b91c1c' }};">{{ $row['percent'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="{{ $sessions->count() + 5 }}" style="padding:16px; text-align:center; color:#9ca3af;">No students in this class.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($sessions->isEmpty() && $rows->isNotEmpty())
        <p style="margin:0; padding:12px 16px; font-size:13px; color:#6b7280;">No attendance recorded yet.</p>
    @endif
</div>
</x-sidebar-layout>
