<x-sidebar-layout>

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <a href="{{ route('teacher.classes.record', [$section, $subject]) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
        <h1 class="mt-1 text-2xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Final Grades</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $subject->code }} — {{ $subject->name }} &bull; {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
    </div>

    @php
        $verification = $currentTerm?->verifications->firstWhere('subject_id', $subject->id);
        $status = $verification->status ?? 'not_submitted';
    @endphp

    @if($status === 'verified')
        <div class="px-4 py-2.5 rounded-lg text-sm flex items-center gap-2 bg-green-50 border border-green-200 text-green-700">
            <i class="fa-solid fa-shield-halved"></i>
            Verified by <strong>{{ $verification->verifiedBy->name ?? '—' }}</strong> on {{ $verification->verified_at?->format('M d, Y') }}
        </div>
    @elseif($status === 'pending')
        <div class="px-4 py-2.5 rounded-lg text-sm flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-700">
            <i class="fa-solid fa-clock"></i>
            Pending verification by Program Head
        </div>
    @elseif($status === 'rejected')
        <div class="px-4 py-2.5 rounded-lg text-sm bg-red-50 border border-red-200 text-red-700">
            <div class="flex items-center gap-2 font-medium"><i class="fa-solid fa-circle-xmark"></i> Rejected</div>
            <div class="mt-1 text-xs">{{ $verification->rejection_reason }}</div>
        </div>
    @else
        <div class="px-4 py-2.5 rounded-lg text-sm flex items-center gap-2 bg-gray-50 border border-gray-200 text-gray-500">
            <i class="fa-solid fa-circle-info"></i>
            Not yet submitted
        </div>
    @endif
</div>



<div class="flex gap-3 mb-4">
    @if(in_array($status, ['pending', 'verified']))
        <span class="px-4 py-2.5 rounded-lg text-sm font-semibold bg-gray-100 border border-gray-200 text-gray-400">
            <i class="fa-solid fa-lock"></i> {{ $status === 'verified' ? 'Verified — editing locked' : 'Pending review — editing locked' }}
        </span>
    @else
        <form id="submitVerificationForm" method="POST" action="{{ route('teacher.grades.submit', [$section, $subject]) }}">
            @csrf
            <button type="button" onclick="confirmSubmitVerification()" class="px-4 py-2.5 rounded-lg text-sm font-semibold" style="background:linear-gradient(135deg,#9a7a50,#c8a97e); color:#1c1814;">
                <i class="fa-solid fa-paper-plane"></i> Submit for Verification
            </button>
        </form>
    @endif
</div>

<script>
function confirmSubmitVerification() {
    Swal.fire({
        title: 'Submit for verification?',
        text: 'Grades, items, and attendance for this subject will be locked until the Program Head reviews them.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, submit',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#9a7a50',
        cancelButtonColor: '#9ca3af',
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('submitVerificationForm').submit();
        }
    });
}
</script>

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
