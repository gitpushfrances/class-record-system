<x-sidebar-layout>

<div class="mb-6">
    <h1 class="text-2xl font-bold" style="color:#f0dfc0;">Grade Verification</h1>
    <p class="mt-1 text-sm" style="color:rgba(200,169,126,0.6);">Click a section to review student grades before verifying.</p>
</div>

@if(session('success'))
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;">{{ session('error') }}</div>
@endif

@forelse($sectionTerms as $term)
<div class="mb-4 rounded-xl overflow-hidden" style="border:1px solid rgba(200,169,126,0.15);background:#1c1814;">

    {{-- Section Header --}}
    <div class="flex items-center justify-between px-6 py-4 cursor-pointer select-none"
         style="background:#211a12;border-bottom:1px solid rgba(200,169,126,0.1);"
         onclick="toggleSection({{ $term->id }})">
        <div class="flex items-center gap-4">
            <div>
                <div class="font-semibold text-sm" style="color:#f0dfc0;">
                    {{ $term->section->program->code ?? '—' }}
                    {{ $term->section->year_number }}-{{ $term->section->section_letter }}
                </div>
                <div class="text-xs mt-0.5" style="color:rgba(200,169,126,0.5);">
                    {{ $term->academic_year }} · {{ $term->semester }} · Adviser: {{ $term->adviser->name ?? '—' }}
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs px-2 py-1 rounded-full"
                  style="{{ $term->enrollments->count() > 0 ? 'background:rgba(200,169,126,0.1);color:#c8a97e;' : 'background:rgba(200,169,126,0.05);color:rgba(200,169,126,0.4);' }}">
                {{ $term->enrollments->count() }} students
            </span>
            @if($term->verification)
                <span class="text-xs px-3 py-1 rounded-full font-medium" style="background:rgba(34,197,94,0.12);color:#86efac;border:1px solid rgba(34,197,94,0.25);">
                    ✓ Verified
                </span>
            @else
                <span class="text-xs px-3 py-1 rounded-full font-medium" style="background:rgba(239,68,68,0.1);color:#fca5a5;border:1px solid rgba(239,68,68,0.2);">
                    Pending
                </span>
            @endif
            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" id="chevron-{{ $term->id }}" style="color:rgba(200,169,126,0.5);"></i>
        </div>
    </div>

    {{-- Expandable Grade Table --}}
    <div id="section-{{ $term->id }}" style="display:none;">

        {{-- Verified badge --}}
        @if($term->verification)
        <div class="px-6 py-3 text-xs flex items-center gap-2" style="background:rgba(34,197,94,0.05);border-bottom:1px solid rgba(34,197,94,0.1);color:#86efac;">
            <i class="fa-solid fa-shield-halved"></i>
            Verified by <strong class="ml-1">{{ $term->verification->verifiedBy->name }}</strong>
            <span class="ml-1" style="color:rgba(200,169,126,0.4);">on {{ $term->verification->verified_at->format('M d, Y') }}</span>
        </div>
        @endif

        {{-- Student grades table --}}
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
                    @php $grade = $enrollment->finalGrade; @endphp
                    <tr style="border-bottom:1px solid rgba(200,169,126,0.06);">
                        <td class="px-6 py-3">
                            <div class="font-medium text-sm" style="color:#f0dfc0;">{{ $enrollment->student->full_name ?? '—' }}</div>
                            <div class="text-xs" style="color:rgba(200,169,126,0.4);">{{ $enrollment->student->student_id ?? '' }}</div>
                        </td>
                        <td class="px-6 py-3 text-center text-sm" style="color:rgba(200,169,126,0.8);">
                            {{ $grade ? number_format($grade->midterm_percentage, 1).'%' : '—' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm font-medium" style="color:#f0dfc0;">
                            {{ $grade ? number_format($grade->midterm_numerical, 2) : '—' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm" style="color:rgba(200,169,126,0.8);">
                            {{ $grade ? number_format($grade->final_percentage, 1).'%' : '—' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm font-medium" style="color:#f0dfc0;">
                            {{ $grade ? number_format($grade->final_numerical, 2) : '—' }}
                        </td>
                        <td class="px-6 py-3 text-center text-sm font-bold" style="color:#c8a97e;">
                            {{ $grade ? number_format($grade->average_numerical, 2) : '—' }}
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($grade)
                                <span class="text-xs px-2 py-1 rounded-full"
                                      style="{{ $grade->average_numerical <= 3.00 ? 'background:rgba(34,197,94,0.1);color:#86efac;' : 'background:rgba(239,68,68,0.1);color:#fca5a5;' }}">
                                    {{ $grade->average_numerical <= 3.00 ? 'Passed' : 'Failed' }}
                                </span>
                            @else
                                <span class="text-xs" style="color:rgba(200,169,126,0.3);">No grade</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-6 text-center text-xs" style="color:rgba(200,169,126,0.3);">No enrolled students.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Action bar --}}
        <div class="px-6 py-4 flex items-center justify-between" style="border-top:1px solid rgba(200,169,126,0.1);background:rgba(200,169,126,0.03);">
            @if($term->verification)
                <div class="text-xs" style="color:rgba(200,169,126,0.4);">
                    <i class="fa-solid fa-lock mr-1"></i> Grades locked after verification
                </div>
                <form method="POST" action="{{ route('program-head.unverify', $term) }}"
                      onsubmit="return confirm('Remove verification? Teachers will be able to edit grades again.')">
                    @csrf @method('DELETE')
                    <button class="text-xs px-4 py-2 rounded-lg"
                            style="background:rgba(239,68,68,0.1);color:#f87171;border:1px solid rgba(239,68,68,0.2);">
                        <i class="fa-solid fa-shield-xmark mr-1"></i> Remove Verification
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('program-head.verify', $term) }}" class="flex items-center gap-3 w-full">
                    @csrf
                    <input type="text" name="notes" placeholder="Add verification notes (optional)"
                           class="flex-1 px-3 py-2 text-xs rounded-lg"
                           style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.15);color:#f0dfc0;">
                    <button class="px-5 py-2 rounded-lg text-xs font-semibold whitespace-nowrap"
                            style="background:linear-gradient(135deg,#9a7a50,#c8a97e);color:#1c1814;">
                        <i class="fa-solid fa-shield-halved mr-1"></i> Verify Grades
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@empty
<div class="rounded-xl px-6 py-16 text-center" style="border:1px solid rgba(200,169,126,0.1);background:#1c1814;">
    <i class="fa-solid fa-folder-open text-2xl mb-3" style="color:rgba(200,169,126,0.3);"></i>
    <p class="text-sm" style="color:rgba(200,169,126,0.4);">No active section terms found.</p>
</div>
@endforelse

<script>
function toggleSection(id) {
    const panel = document.getElementById('section-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : 'block';
    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}
</script>

</x-sidebar-layout>