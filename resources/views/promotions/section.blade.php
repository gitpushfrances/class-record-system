<x-sidebar-layout>
@php
    $name  = "{$section->program->code} {$section->year_number}-{$section->section_letter}";
    $card  = 'background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow:hidden; margin-bottom:20px;';
    $th    = 'padding:10px 16px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#6b7280; text-align:left; background:#f9fafb; border-bottom:1px solid #e5e7eb;';
    $td    = 'padding:10px 16px; font-size:14px; color:#374151; border-bottom:1px solid #f3f4f6;';
    $badge = 'display:inline-block; margin:2px 4px 2px 0; padding:1px 8px; font-size:11px; font-weight:600; border-radius:9999px;';
    $note  = 'padding:12px 16px; margin-bottom:20px; font-size:14px; border-radius:10px;';
@endphp

<div style="margin-bottom:12px;"><x-back-button :href="route($prefix . '.promotions.program', $section->program)" label="Back to {{ $section->program->code }}" /></div>

<div style="margin-bottom:20px;">
    <h1 style="margin:0; font-size:24px; font-weight:700; color:#1f2937;">Promote — {{ $name }}</h1>
    <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">
        @if($source) From <strong>{{ $source->semester }} {{ $source->academic_year }}</strong> ({{ $candidates->count() }} students) into <strong>{{ $period ? "{$period->semester} {$period->school_year}" : 'the active period' }}</strong>. @endif
        Nobody is moved unless ticked. Their year level becomes the target section's year level.
    </p>
</div>

@if(!$source)
    <div style="{{ $note }} color:#1e3a8a; background:#eff6ff; border:1px solid #bfdbfe;">
        This section has no closed term yet. When the new academic period starts, use <strong>Sections → Set Term</strong> on this section; its previous term closes and its students appear here.
    </div>
@elseif($targets->isEmpty())
    <div style="{{ $note }} color:#92400e; background:#fffbeb; border:1px solid #fde68a;">
        No section of {{ $section->program->code }} has a term for {{ $period ? "{$period->semester} {$period->school_year}" : 'the active period' }} yet. Use <strong>Sections → Set Term</strong> on the target section first.
    </div>
@else
    <form method="POST" action="{{ route($prefix . '.promotions.store', $section) }}" id="promoteForm">
        @csrf
        <div style="{{ $card }} padding:16px; display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
            <label style="font-size:13px; font-weight:600; color:#374151;">Move to
                <select name="target_term_id" id="targetTerm" required style="margin-left:6px; padding:8px 10px; font-size:14px; border:1px solid #d1d5db; border-radius:8px;">
                    <option value="">Select target section…</option>
                    @foreach($targets as $t)
                        <option value="{{ $t->id }}" @selected(old('target_term_id') == $t->id)>
                            {{ $section->program->code }} {{ $t->section->year_number }}-{{ $t->section->section_letter }} · {{ $t->section->year_level }}{{ $t->section_id === $section->id ? ' (same section, next term)' : '' }}
                        </option>
                    @endforeach
                </select>
            </label>
            <span id="selectedCount" style="margin-left:auto; font-size:13px; color:#6b7280;">0 selected</span>
            <button type="submit" id="promoteBtn" disabled style="padding:8px 16px; font-size:13px; font-weight:600; color:#fff; background:#15803d; border:none; border-radius:8px; cursor:pointer; opacity:.45;">
                <i class="fa-solid fa-arrow-trend-up"></i> Promote selected
            </button>
        </div>

        <div style="{{ $card }}">
            <table style="width:100%; border-collapse:collapse;">
                <thead><tr>
                    <th style="{{ $th }} width:40px;"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
                    <th style="{{ $th }}">Student No.</th><th style="{{ $th }}">Name</th><th style="{{ $th }}">Last term</th>
                </tr></thead>
                <tbody>
                @forelse($candidates as $c)
                    @php $e = $c['enrollment']; @endphp
                    <tr style="{{ $c['failed']->isNotEmpty() || $c['incomplete']->isNotEmpty() ? 'background:#fffbeb;' : '' }}">
                        <td style="{{ $td }}"><input type="checkbox" class="pick" name="student_ids[]" value="{{ $e->student_id }}" @checked(in_array($e->student_id, old('student_ids', [])))></td>
                        <td style="{{ $td }} font-family:monospace;">{{ $e->student?->student_number }}</td>
                        <td style="{{ $td }}">{{ $e->student?->full_name }} <span style="font-size:12px; color:#9ca3af;">{{ $e->student?->year_level }}</span></td>
                        <td style="{{ $td }}">
                            @if($c['failed']->isNotEmpty())<span style="{{ $badge }} color:#991b1b; background:#fee2e2;">Failed: {{ $c['failed']->implode(', ') }}</span>@endif
                            @if($c['incomplete']->isNotEmpty())<span style="{{ $badge }} color:#92400e; background:#fef3c7;">INC: {{ $c['incomplete']->implode(', ') }}</span>@endif
                            @if($c['failed']->isEmpty() && $c['incomplete']->isEmpty())<span style="font-size:12px; color:#9ca3af;">No INC or failed grade</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="{{ $td }} text-align:center; color:#9ca3af;">No students in the closed term.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </form>
@endif

@if($history->isNotEmpty())
    <div style="{{ $card }}">
        <div style="padding:12px 16px; font-size:14px; font-weight:700; color:#111827; border-bottom:1px solid #e5e7eb;">Promotions from this term</div>
        @foreach($history as $h)
            <div style="padding:10px 16px; font-size:13px; color:#4b5563; border-bottom:1px solid #f3f4f6;">
                {{ $h->created_at->format('M d, Y h:i A') }} · {{ $h->promoter?->name ?? '—' }} moved {{ count($h->student_ids) }} to
                {{ $section->program->code }} {{ $h->targetTerm?->section?->year_number }}-{{ $h->targetTerm?->section?->section_letter }} ({{ $h->year_level }}){{ count($h->skipped_ids) ? ', ' . count($h->skipped_ids) . ' skipped' : '' }}
            </div>
        @endforeach
    </div>
@endif

<script>
(function () {
    var form = document.getElementById('promoteForm');
    if (!form) return;
    var picks  = Array.prototype.slice.call(form.querySelectorAll('.pick'));
    var all    = document.getElementById('selectAll');
    var target = document.getElementById('targetTerm');
    var btn    = document.getElementById('promoteBtn');
    var count  = document.getElementById('selectedCount');

    function refresh() {
        var n = picks.filter(function (p) { return p.checked; }).length;
        count.textContent = n + ' selected';
        var ready = n > 0 && target.value !== '';
        btn.disabled = !ready;
        btn.style.opacity = ready ? '1' : '.45';
        all.checked = picks.length > 0 && n === picks.length;
    }

    all.addEventListener('change', function () { picks.forEach(function (p) { p.checked = all.checked; }); refresh(); });
    picks.forEach(function (p) { p.addEventListener('change', refresh); });
    target.addEventListener('change', refresh);
    form.addEventListener('submit', function (e) {
        var n = picks.filter(function (p) { return p.checked; }).length;
        if (!confirm('Move ' + n + ' student(s) to ' + target.options[target.selectedIndex].text.trim() + '? Their year level will be updated.')) e.preventDefault();
    });
    refresh();
})();
</script>
</x-sidebar-layout>
