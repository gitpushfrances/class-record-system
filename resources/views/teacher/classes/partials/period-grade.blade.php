{{-- Midterm or Finals grade of one student in the Class Record. Expects $lg (GradeCalculator::result), $period ("midterm" / "final"), $enrollmentId, $method. --}}
@php
    $num = $lg[$period . '_numerical'] ?? null;
    $pct = $lg[$period . '_percentage'] ?? null;
    $inc = in_array($period, $lg['incomplete_periods'] ?? [], true);
@endphp
<td data-calc="r{{ $enrollmentId }}-{{ $period }}-grade" style="padding:10px; min-width:84px; text-align:center; background:#eef7ec; border:1px solid #c7e3c1;">
    @if($inc)
        <span style="padding:2px 8px; font-size:12px; font-weight:600; color:#b45309; background:#fef3c7; border-radius:9999px;">INC</span>
    @elseif($num !== null)
        <div style="font-weight:700; color:#0b3d0b;">{{ \App\Services\GradeCalculator::formatGrade($num, $method) }}</div>
        <div style="font-size:11px; color:#6b7280;">{{ number_format($pct, 2) }}%</div>
    @else
        <span style="color:#d1d5db;">—</span>
    @endif
</td>
