<x-sidebar-layout>

<div class="mb-6">
    <h1 class="text-2xl font-bold" style="color:#f0dfc0;">Grade Verification</h1>
    <p class="mt-1 text-sm" style="color:rgba(200,169,126,0.6);">Click a subject to review student grades before verifying.</p>
</div>

@if(session('success'))
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;">{{ session('error') }}</div>
@endif

@forelse($sectionTerms as $term)
<div class="mb-6 rounded-xl overflow-hidden" style="border:1px solid rgba(200,169,126,0.15);background:#1c1814;">

    <div class="px-6 py-4" style="background:#211a12;border-bottom:1px solid rgba(200,169,126,0.1);">
        <div class="font-semibold text-sm" style="color:#f0dfc0;">
            {{ $term->section->program->code ?? '—' }} {{ $term->section->year_number }}-{{ $term->section->section_letter }}
        </div>
        <div class="text-xs mt-0.5" style="color:rgba(200,169,126,0.5);">
            {{ $term->academic_year }} &bull; {{ $term->semester }} &bull; Adviser: {{ $term->adviser->name ?? '—' }}
        </div>
    </div>

    @forelse($term->subjects as $subject)
        @php
            $verification = $term->verifications->firstWhere('subject_id', $subject->id);
            $status = $verification->status ?? 'not_submitted';
            $teacher = \App\Models\User::find($subject->pivot->teacher_id);
        @endphp
        <div style="border-bottom:1px solid rgba(200,169,126,0.08);">
            <div class="flex items-center justify-between px-6 py-4 cursor-pointer select-none"
                 onclick="toggleSubject({{ $term->id }}, {{ $subject->id }})">
                <div>
                    <div class="text-sm font-medium" style="color:#f0dfc0;">
                        {{ $subject->code }} — {{ $subject->name }}
                    </div>
                    <div class="text-xs mt-0.5" style="color:rgba(200,169,126,0.5);">
                        Teacher: {{ $teacher->name ?? '—' }}
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @if($status === 'verified')
                        <span class="text-xs px-3 py-1 rounded-full font-medium" style="background:rgba(34,197,94,0.12);color:#86efac;border:1px solid rgba(34,197,94,0.25);">✓ Verified</span>
                    @elseif($status === 'rejected')
                        <span class="text-xs px-3 py-1 rounded-full font-medium" style="background:rgba(239,68,68,0.1);color:#fca5a5;border:1px solid rgba(239,68,68,0.2);">Rejected</span>
                    @elseif($status === 'pending')
                        <span class="text-xs px-3 py-1 rounded-full font-medium" style="background:rgba(234,179,8,0.12);color:#fde68a;border:1px solid rgba(234,179,8,0.25);">Pending</span>
                    @else
                        <span class="text-xs px-3 py-1 rounded-full font-medium" style="background:rgba(200,169,126,0.05);color:rgba(200,169,126,0.4);">Not submitted</span>
                    @endif
                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" id="chevron-{{ $term->id }}-{{ $subject->id }}" style="color:rgba(200,169,126,0.5);"></i>
                </div>
            </div>

            <div id="subject-{{ $term->id }}-{{ $subject->id }}" style="display:none;">

                @if($verification && $verification->status === 'verified')
                    <div class="px-6 py-3 text-xs flex items-center gap-2" style="background:rgba(34,197,94,0.05);border-top:1px solid rgba(34,197,94,0.1);color:#86efac;">
                        <i class="fa-solid fa-shield-halved"></i>
                        Verified by <strong class="ml-1">{{ $verification->verifiedBy->name ?? '—' }}</strong>
                        <span class="ml-1" style="color:rgba(200,169,126,0.4);">on {{ $verification->verified_at?->format('M d, Y') }}</span>
                    </div>
                @endif

                @if($verification && $verification->status === 'rejected')
                    <div class="px-6 py-3 text-xs" style="background:rgba(239,68,68,0.05);border-top:1px solid rgba(239,68,68,0.1);color:#fca5a5;">
                        <i class="fa-solid fa-circle-xmark"></i>
                        Rejected: {{ $verification->rejection_reason }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background:rgba(200,169,126,0.05);border-bottom:1px solid rgba(200,169,126,0.1);">
                                <th class="px-6 py-3 text-left text-xs font-medium" style="color:rgba(200,169,126,0.5);">Student</th>
                                <th class="px-6 py-3 text-center text-xs font-medium" style="color:rgba(200,169,126,0.5);">Midterm %</th>
                                <th class="px-6 py-3 text-center text-xs font-medium" style="color:rgba(200,169,126,0.5);">Midterm Grade</th>
                                <th class="px-6 py-3 text-center text-xs font-medium" style="color:rgba(200,169,126,0.5);">Final %</th>
                                <th class="px-6 py-3 text-center text-xs font-medium" style="color:rgba(200,169,126,0.5);">Final Grade</th>
                                <th class="px-6 py-3 text-center text-xs font-medium" style="color:rgba(200,169,126,0.5);">Average</th>
                                <th class="px-6 py-3 text-center text-xs font-medium" style="color:rgba(200,169,126,0.5);">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($term->enrollments as $enrollment)
                                @php $grade = ($finalGrades->get($enrollment->id . '-' . $subject->id))?->first(); @endphp
                                <tr style="border-bottom:1px solid rgba(200,169,126,0.06);">
                                    <td class="px-6 py-3">
                                        <div class="font-medium text-sm" style="color:#f0dfc0;">{{ $enrollment->student->full_name ?? '—' }}</div>
                                        <div class="text-xs" style="color:rgba(200,169,126,0.4);">{{ $enrollment->student->student_number ?? '' }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-center text-sm" style="color:rgba(200,169,126,0.8);">{{ $grade ? number_format($grade->midterm_percentage, 1).'%' : '—' }}</td>
                                    <td class="px-6 py-3 text-center text-sm font-medium" style="color:#f0dfc0;">{{ $grade ? number_format($grade->midterm_numerical, 2) : '—' }}</td>
                                    <td class="px-6 py-3 text-center text-sm" style="color:rgba(200,169,126,0.8);">{{ $grade ? number_format($grade->final_percentage, 1).'%' : '—' }}</td>
                                    <td class="px-6 py-3 text-center text-sm font-medium" style="color:#f0dfc0;">{{ $grade ? number_format($grade->final_numerical, 2) : '—' }}</td>
                                    <td class="px-6 py-3 text-center text-sm font-bold" style="color:#c8a97e;">{{ $grade ? number_format($grade->average_numerical, 2) : '—' }}</td>
                                    <td class="px-6 py-3 text-center">
                                        @if($grade)
                                            <span class="text-xs px-2 py-1 rounded-full" style="{{ $grade->average_numerical <= 3.00 ? 'background:rgba(34,197,94,0.1);color:#86efac;' : 'background:rgba(239,68,68,0.1);color:#fca5a5;' }}">
                                                {{ $grade->average_numerical <= 3.00 ? 'Passed' : 'Failed' }}
                                            </span>
                                        @else
                                            <span class="text-xs" style="color:rgba(200,169,126,0.3);">No grade</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-6 text-center text-xs" style="color:rgba(200,169,126,0.3);">No enrolled students.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4" style="border-top:1px solid rgba(200,169,126,0.1);background:rgba(200,169,126,0.03);">
                    @if($status === 'pending')
                        <div class="flex flex-wrap items-center gap-3">
                            <form method="POST" action="{{ route('program-head.verify', [$term, $subject]) }}" class="flex items-center gap-2">
                                @csrf
                                <input type="text" name="notes" placeholder="Notes (optional)"
                                       class="px-3 py-2 text-xs rounded-lg" style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.15);color:#f0dfc0;">
                                <button class="px-4 py-2 rounded-lg text-xs font-semibold whitespace-nowrap" style="background:linear-gradient(135deg,#9a7a50,#c8a97e);color:#1c1814;">
                                    <i class="fa-solid fa-check"></i> Verify
                                </button>
                            </form>
                            <form method="POST" action="{{ route('program-head.reject', [$term, $subject]) }}"
                                  onsubmit="return confirm('Reject this submission? The teacher will be able to edit again.')" class="flex items-center gap-2">
                                @csrf
                                <input type="text" name="reason" placeholder="Reason for rejection" required
                                       class="px-3 py-2 text-xs rounded-lg" style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.15);color:#f0dfc0;">
                                <button class="px-4 py-2 rounded-lg text-xs font-semibold whitespace-nowrap" style="background:rgba(239,68,68,0.1);color:#f87171;border:1px solid rgba(239,68,68,0.2);">
                                    <i class="fa-solid fa-xmark"></i> Reject
                                </button>
                            </form>
                        </div>
                    @elseif($status === 'verified')
                        <form method="POST" action="{{ route('program-head.unverify', [$term, $subject]) }}"
                              onsubmit="return confirm('Undo verification? This returns the submission to pending.')">
                            @csrf @method('DELETE')
                            <button class="text-xs px-4 py-2 rounded-lg" style="background:rgba(239,68,68,0.1);color:#f87171;border:1px solid rgba(239,68,68,0.2);">
                                <i class="fa-solid fa-rotate-left"></i> Undo Verification
                            </button>
                        </form>
                    @else
                        <div class="text-xs" style="color:rgba(200,169,126,0.4);">
                            @if($status === 'rejected')
                                Waiting for the teacher to resubmit after corrections.
                            @else
                                Not yet submitted by the teacher.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="px-6 py-6 text-xs" style="color:rgba(200,169,126,0.3);">No subjects assigned to this section yet.</div>
    @endforelse
</div>
@empty
<div class="rounded-xl px-6 py-16 text-center" style="border:1px solid rgba(200,169,126,0.1);background:#1c1814;">
    <i class="fa-solid fa-folder-open text-2xl mb-3" style="color:rgba(200,169,126,0.3);"></i>
    <p class="text-sm" style="color:rgba(200,169,126,0.4);">No active section terms found.</p>
</div>
@endforelse

<script>
function toggleSubject(termId, subjectId) {
    const panel = document.getElementById('subject-' + termId + '-' + subjectId);
    const chevron = document.getElementById('chevron-' + termId + '-' + subjectId);
    const isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : 'block';
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}
</script>

</x-sidebar-layout>
