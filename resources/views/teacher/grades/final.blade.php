<x-sidebar-layout>

@if(session('success'))
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;">{{ session('error') }}</div>
@endif

<div class="flex items-start justify-between mb-6">
    <div>
        <a href="{{ route('teacher.classes.show', $section) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
        <h1 class="mt-1 text-2xl font-bold" style="color:#f0dfc0;">Final Grades</h1>
        <p class="mt-1 text-sm" style="color:rgba(200,169,126,0.6);">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
    </div>
    @php
    $currentTerm = $section->terms()->where('status', 'active')->first();
    $verification = $currentTerm?->verification()->with('verifiedBy')->first();
@endphp

@if($verification)
    <div class="mb-4 px-4 py-3 rounded-lg text-sm flex items-center gap-3" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;">
        <i class="fa-solid fa-shield-halved"></i>
        <span>✓ Verified by <strong>{{ $verification->verifiedBy->name }}</strong> on {{ $verification->verified_at->format('M d, Y') }}</span>
    </div>
@else
    <div class="mb-4 px-4 py-3 rounded-lg text-sm flex items-center gap-3" style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:rgba(248,113,113,0.8);">
        <i class="fa-solid fa-clock"></i>
        <span>Pending verification by Program Head</span>
    </div>
@endif

<div class="flex gap-3 mt-1">
        <form method="POST" action="{{ route('teacher.grades.final.compute', $section) }}">
            @csrf
            <button class="px-4 py-2.5 rounded-lg text-sm font-semibold" style="background:linear-gradient(135deg,#9a7a50,#c8a97e);color:#1c1814;">
                <i class="fa-solid fa-floppy-disk"></i> Save Grades
            </button>
        </form>
        @if(!isset($verification) || !$verification)
        <form method="POST" action="{{ route('teacher.grades.final.lock', $section) }}"
              onsubmit="return confirm('Lock all final grades? This cannot be undone.')">
            @csrf
            <button class="px-4 py-2.5 rounded-lg text-sm font-semibold" style="background:rgba(200,169,126,0.1);color:#c8a97e;border:1px solid rgba(200,169,126,0.2);">
                <i class="fa-solid fa-lock"></i> Lock All
            </button>
        </form>
        @else
        <span class="px-4 py-2.5 rounded-lg text-sm font-semibold" style="background:rgba(200,169,126,0.05);color:rgba(200,169,126,0.3);border:1px solid rgba(200,169,126,0.1);">
            <i class="fa-solid fa-lock"></i> Locked by Verification
        </span>
        @endif
    </div>
</div>

<div class="overflow-x-auto rounded-xl" style="border:1px solid rgba(200,169,126,0.15);">
    <table class="w-full text-sm">
        <thead>
            <tr style="background:#211a12;border-bottom:1px solid rgba(200,169,126,0.15);">
                <th class="px-5 py-3 text-left text-xs" style="color:rgba(200,169,126,0.6);">#</th>
                <th class="px-5 py-3 text-left text-xs" style="color:rgba(200,169,126,0.6);">Student</th>
                <th class="px-5 py-3 text-center text-xs" style="color:rgba(200,169,126,0.6);">Midterm %</th>
                <th class="px-5 py-3 text-center text-xs" style="color:rgba(200,169,126,0.6);">Midterm Grade</th>
                <th class="px-5 py-3 text-center text-xs" style="color:rgba(200,169,126,0.6);">Final %</th>
                <th class="px-5 py-3 text-center text-xs" style="color:rgba(200,169,126,0.6);">Final Grade</th>
                <th class="px-5 py-3 text-center text-xs" style="color:rgba(200,169,126,0.6);">Average</th>
                <th class="px-5 py-3 text-center text-xs" style="color:rgba(200,169,126,0.6);">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($enrollments as $i => $enrollment)
                @php
                    $lg = $liveGrades[$enrollment->id];
                    $fg = $enrollment->finalGrade;
                    $midPct  = $lg['midterm_percentage'] ?? 0;
                    $midNum  = $lg['midterm_numerical']  ?? 5.00;
                    $finPct  = $lg['final_percentage']   ?? 0;
                    $finNum  = $lg['final_numerical']    ?? 5.00;
                    $avgNum  = $lg['average_numerical']  ?? 5.00;
                    $remarks = $avgNum <= 3.00 ? 'passed' : 'failed';
                @endphp
                <tr style="border-bottom:1px solid rgba(200,169,126,0.07);">
                    <td class="px-5 py-3" style="color:rgba(200,169,126,0.4);">{{ $i + 1 }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium" style="color:#f0dfc0;">{{ $enrollment->student?->full_name ?? 'N/A' }}</div>
                        <div class="text-xs font-mono" style="color:rgba(200,169,126,0.4);">{{ $enrollment->student?->student_number }}</div>
                    </td>
                    <td class="px-5 py-3 text-center" style="color:rgba(200,169,126,0.8);">{{ number_format($midPct, 2) }}%</td>
                    <td class="px-5 py-3 text-center font-bold" style="color:#c8a97e;">{{ number_format($midNum, 2) }}</td>
                    <td class="px-5 py-3 text-center" style="color:rgba(200,169,126,0.8);">{{ number_format($finPct, 2) }}%</td>
                    <td class="px-5 py-3 text-center font-bold" style="color:#c8a97e;">{{ number_format($finNum, 2) }}</td>
                    <td class="px-5 py-3 text-center font-bold text-lg" style="color:#f0dfc0;">{{ number_format($avgNum, 2) }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($fg && $fg->is_locked)
                            <span class="px-2 py-1 text-xs rounded-full" style="background:rgba(200,169,126,0.1);color:rgba(200,169,126,0.6);"><i class="fa-solid fa-lock"></i> Locked</span>
                        @elseif($remarks === 'passed')
                            <span class="px-2 py-1 text-xs rounded-full" style="background:rgba(34,197,94,0.1);color:#86efac;">Passed</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full" style="background:rgba(239,68,68,0.1);color:#fca5a5;">Failed</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-sm" style="color:rgba(200,169,126,0.4);">No students enrolled.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

</x-sidebar-layout>
