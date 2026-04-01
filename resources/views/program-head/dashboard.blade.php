<x-sidebar-layout>

<div class="mb-6">
    <h1 class="text-2xl font-bold" style="color:#f0dfc0;">Grade Verification</h1>
    <p class="mt-1 text-sm" style="color:rgba(200,169,126,0.6);">Review and verify final grades for active sections.</p>
</div>

@if(session('success'))
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;">{{ session('error') }}</div>
@endif

<div class="rounded-xl overflow-hidden" style="border:1px solid rgba(200,169,126,0.15);">
    <table class="w-full text-sm">
        <thead>
            <tr style="background:#211a12;border-bottom:1px solid rgba(200,169,126,0.15);">
                <th class="px-5 py-3 text-left text-xs" style="color:rgba(200,169,126,0.6);">Section</th>
                <th class="px-5 py-3 text-left text-xs" style="color:rgba(200,169,126,0.6);">Adviser</th>
                <th class="px-5 py-3 text-center text-xs" style="color:rgba(200,169,126,0.6);">Students</th>
                <th class="px-5 py-3 text-center text-xs" style="color:rgba(200,169,126,0.6);">Status</th>
                <th class="px-5 py-3 text-center text-xs" style="color:rgba(200,169,126,0.6);">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sectionTerms as $term)
                <tr style="border-bottom:1px solid rgba(200,169,126,0.07);">
                    <td class="px-5 py-3">
                        <div class="font-medium" style="color:#f0dfc0;">
                            {{ $term->section->program->code ?? '—' }}
                            {{ $term->section->year_number }}-{{ $term->section->section_letter }}
                        </div>
                        <div class="text-xs" style="color:rgba(200,169,126,0.4);">
                            {{ $term->academic_year }} · {{ $term->semester }}
                        </div>
                    </td>
                    <td class="px-5 py-3" style="color:rgba(200,169,126,0.8);">
                        {{ $term->adviser->name ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-center" style="color:rgba(200,169,126,0.7);">
                        {{ $term->enrollments->count() }}
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($term->verification)
                            <div>
                                <span class="px-2 py-1 text-xs rounded-full" style="background:rgba(34,197,94,0.1);color:#86efac;">
                                    ✓ Verified
                                </span>
                                <div class="mt-1 text-xs" style="color:rgba(200,169,126,0.4);">
                                    by {{ $term->verification->verifiedBy->name }}<br>
                                    {{ $term->verification->verified_at->format('M d, Y') }}
                                </div>
                            </div>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full" style="background:rgba(239,68,68,0.1);color:#fca5a5;">
                                Pending
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($term->verification)
                            <form method="POST" action="{{ route('program-head.unverify', $term) }}"
                                  onsubmit="return confirm('Remove verification?')">
                                @csrf @method('DELETE')
                                <button class="text-xs px-3 py-1.5 rounded-lg"
                                        style="background:rgba(239,68,68,0.1);color:#f87171;border:1px solid rgba(239,68,68,0.2);">
                                    Remove
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('program-head.verify', $term) }}">
                                @csrf
                                <input type="text" name="notes" placeholder="Notes (optional)"
                                       class="mb-2 w-full px-2 py-1 text-xs rounded"
                                       style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
                                <button class="w-full text-xs px-3 py-1.5 rounded-lg font-semibold"
                                        style="background:linear-gradient(135deg,#9a7a50,#c8a97e);color:#1c1814;">
                                    Verify Grades
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-10 text-center text-sm" style="color:rgba(200,169,126,0.4);">
                        No active section terms found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

</x-sidebar-layout>
