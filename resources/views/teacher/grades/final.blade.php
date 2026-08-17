<x-sidebar-layout>

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <a href="{{ route('teacher.classes.record', [$section, $subject]) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
        <h1 class="mt-1 text-2xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Final Grades</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $subject->code }} — {{ $subject->name }} &bull; {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
    </div>

    @php
        $verification = $currentTerm?->verification()->with('verifiedBy')->first();
    @endphp

    @if($verification)
        <div class="px-4 py-2.5 rounded-lg text-sm flex items-center gap-2 bg-green-50 border border-green-200 text-green-700">
            <i class="fa-solid fa-shield-halved"></i>
            Verified by <strong>{{ $verification->verifiedBy->name }}</strong> on {{ $verification->verified_at->format('M d, Y') }}
        </div>
    @else
        <div class="px-4 py-2.5 rounded-lg text-sm flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-700">
            <i class="fa-solid fa-clock"></i>
            Pending verification by Program Head
        </div>
    @endif
</div>

<div class="px-4 py-3 mb-4 bg-white border border-gray-200 rounded-lg">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-sm text-gray-700">
            <i class="fa-solid fa-calendar-check" style="color:#c8a97e;"></i>
            <span>
                Midterm cutoff date:
                <strong class="text-gray-900">
                    {{ $currentTerm?->midterm_cutoff_date ? $currentTerm->midterm_cutoff_date->format('M d, Y') : 'Not set' }}
                </strong>
            </span>
        </div>
        <form method="POST" action="{{ route('teacher.grades.final.cutoff', [$section, $subject]) }}" class="flex items-center gap-2">
            @csrf
            <input type="date" name="midterm_cutoff_date"
                   value="{{ $currentTerm?->midterm_cutoff_date?->format('Y-m-d') }}"
                   required
                   class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg text-gray-700">
            <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg" style="background:rgba(200,169,126,0.15); color:#8a6a3d; border:1px solid rgba(200,169,126,0.4);">
                Save
            </button>
        </form>
    </div>
    @if(!$currentTerm?->midterm_cutoff_date)
        <p class="mt-2 text-xs text-red-500">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Attendance won't count toward grades until this is set — attendance components will be treated as not-yet-active.
        </p>
    @endif
</div>

<div class="flex gap-3 mb-4">
    <form method="POST" action="{{ route('teacher.grades.final.compute', [$section, $subject]) }}">
        @csrf
        <button class="px-4 py-2.5 rounded-lg text-sm font-semibold" style="background:linear-gradient(135deg,#9a7a50,#c8a97e); color:#1c1814;">
            <i class="fa-solid fa-floppy-disk"></i> Save Grades
        </button>
    </form>
    @if(!$verification)
        <form method="POST" action="{{ route('teacher.grades.final.lock', [$section, $subject]) }}"
              onsubmit="return confirm('Lock all final grades? This cannot be undone.')">
            @csrf
            <button class="px-4 py-2.5 rounded-lg text-sm font-semibold bg-white border border-gray-300 text-gray-700">
                <i class="fa-solid fa-lock"></i> Lock All
            </button>
        </form>
    @else
        <span class="px-4 py-2.5 rounded-lg text-sm font-semibold bg-gray-100 border border-gray-200 text-gray-400">
            <i class="fa-solid fa-lock"></i> Locked by Verification
        </span>
    @endif
</div>

<div class="overflow-hidden bg-white border border-gray-200 rounded-xl">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead style="background:#f9fafb;">
                <tr class="border-b border-gray-200">
                    <th class="px-5 py-3 text-xs font-semibold text-left text-gray-500 uppercase">#</th>
                    <th class="px-5 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Student</th>
                    <th class="px-5 py-3 text-xs font-semibold text-center text-gray-500 uppercase">Midterm %</th>
                    <th class="px-5 py-3 text-xs font-semibold text-center text-gray-500 uppercase">Midterm Grade</th>
                    <th class="px-5 py-3 text-xs font-semibold text-center text-gray-500 uppercase">Final %</th>
                    <th class="px-5 py-3 text-xs font-semibold text-center text-gray-500 uppercase">Final Grade</th>
                    <th class="px-5 py-3 text-xs font-semibold text-center text-gray-500 uppercase">Average</th>
                    <th class="px-5 py-3 text-xs font-semibold text-center text-gray-500 uppercase">Remarks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
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
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ $enrollment->student?->full_name ?? 'N/A' }}</div>
                            <div class="font-mono text-xs text-gray-400">{{ $enrollment->student?->student_number }}</div>
                        </td>
                        <td class="px-5 py-3 text-center text-gray-700">{{ number_format($midPct, 2) }}%</td>
                        <td class="px-5 py-3 font-bold text-center" style="color:#8a6a3d;">{{ number_format($midNum, 2) }}</td>
                        <td class="px-5 py-3 text-center text-gray-700">{{ number_format($finPct, 2) }}%</td>
                        <td class="px-5 py-3 font-bold text-center" style="color:#8a6a3d;">{{ number_format($finNum, 2) }}</td>
                        <td class="px-5 py-3 text-lg font-bold text-center text-gray-900">{{ number_format($avgNum, 2) }}</td>
                        <td class="px-5 py-3 text-center">
                            @if($fg && $fg->is_locked)
                                <span class="px-2 py-1 text-xs text-gray-500 bg-gray-100 rounded-full"><i class="fa-solid fa-lock"></i> Locked</span>
                            @elseif($remarks === 'passed')
                                <span class="px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full">Passed</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full">Failed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-sm text-center text-gray-400">No students enrolled.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</x-sidebar-layout>
