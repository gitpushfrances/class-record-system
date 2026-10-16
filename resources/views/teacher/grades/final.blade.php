<x-sidebar-layout>

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <x-back-button :href="route('teacher.classes.record', [$section, $subject])" label="Back to Class" />
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



@if(session('submit_blocked'))
    <div class="px-4 py-3 mb-4 text-sm text-red-700 border border-red-200 rounded-lg bg-red-50">
        <i class="fa-solid fa-circle-xmark"></i> {{ session('submit_blocked') }}
    </div>
@endif

@if(!in_array($status, ['pending', 'verified']) && !empty($incReview))
    <div class="px-4 py-3 mb-4 text-sm border rounded-lg bg-amber-50 border-amber-200 text-amber-700">
        <div class="font-semibold"><i class="fa-solid fa-triangle-exclamation"></i> {{ count($incReview) }} {{ \Illuminate\Support\Str::plural('student', count($incReview)) }} will be marked INC on submit</div>
        <p class="mt-1 text-xs">A component with no scores, or a total of zero, makes that period incomplete. Blank scores are not counted; enter 0 for an unexcused miss.</p>
        <ul class="mt-2 text-xs" style="list-style:none; padding-left:0;">
            @foreach($enrollments as $enrollment)
                @continue(!isset($incReview[$enrollment->id]))
                <li style="margin-top:4px;">
                    <strong>{{ $enrollment->student?->full_name ?? 'N/A' }}</strong>
                    @foreach($incReview[$enrollment->id] as $period => $labels)
                        &mdash; {{ ucfirst($period) }}: {{ implode(', ', $labels) }}
                    @endforeach
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="flex gap-3 mb-4">
    @if(in_array($status, ['pending', 'verified']))
        <span class="px-4 py-2.5 rounded-lg text-sm font-semibold bg-gray-100 border border-gray-200 text-gray-400">
            <i class="fa-solid fa-lock"></i> {{ $status === 'verified' ? 'Verified — editing locked' : 'Pending review — editing locked' }}
        </span>
        <a href="{{ route('teacher.rog.show', [$section, $subject]) }}" target="_blank"
           style="display:inline-flex; align-items:center; gap:8px; padding:10px 16px; font-size:14px; font-weight:600; color:#fff; background:#15803d; border-radius:8px; text-decoration:none;">
            <i class="fa-solid fa-file-lines"></i> Report of Grades
        </a>
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
    if (typeof Swal === 'undefined') {
        if (confirm('Submit for verification? Grades, items, and attendance for this subject will be locked until the Program Head reviews them.')) {
            document.getElementById('submitVerificationForm').submit();
        }
        return;
    }
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
                        $m       = $config->computation_method ?? 'standard';
                        $pct     = fn($v) => $v !== null ? number_format($v, 2) . '%' : "\u{2014}";
                        $fmt     = fn($v) => $v !== null ? \App\Services\GradeCalculator::formatGrade($v, $m) : ($lg['remarks'] === 'incomplete' ? 'INC' : "\u{2014}");
                        $remarks = $lg['remarks'];
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ $enrollment->student?->full_name ?? 'N/A' }}</div>
                            <div class="font-mono text-xs text-gray-400">{{ $enrollment->student?->student_number }}</div>
                        </td>
                        <td class="px-5 py-3 text-center text-gray-700">{{ $pct($lg['midterm_percentage']) }}</td>
                        <td class="px-5 py-3 font-bold text-center" style="color:#8a6a3d;">{{ $fmt($lg['midterm_numerical']) }}</td>
                        <td class="px-5 py-3 text-center text-gray-700">{{ $pct($lg['final_percentage']) }}</td>
                        <td class="px-5 py-3 font-bold text-center" style="color:#8a6a3d;">{{ $fmt($lg['final_numerical']) }}</td>
                        <td class="px-5 py-3 text-lg font-bold text-center text-gray-900">{{ $fmt($lg['average_numerical']) }}</td>
                        <td class="px-5 py-3 text-center">
                            @if($remarks === 'incomplete')
                                <span class="px-2 py-1 text-xs font-medium rounded-full" style="color:#b45309; background:#fef3c7;">INC</span>
                            @elseif($remarks === null)
                                <span class="text-gray-300">&mdash;</span>
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
