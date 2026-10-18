{{--
    Enroll by search: type a student number or name and the matching students appear, each with its own Enroll button.
    Expects $action (POST url) and $students (Student collection). Optional $emptyText.
--}}
<form method="POST" action="{{ $action }}" class="student-picker" style="display:grid; gap:8px;">
    @csrf
    @if($students->isEmpty())
        <p style="margin:0; font-size:14px; color:#6b7280;">{{ $emptyText ?? 'No students left to enroll.' }}</p>
    @else
        <input type="search" data-picker-input autocomplete="off" aria-label="Search students to enroll"
               placeholder="Type a student number or name ({{ $students->count() }} available)"
               style="width:100%; padding:9px 12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px;">
        <div style="max-height:320px; overflow-y:auto; border:1px solid #e5e7eb; border-radius:8px;">
            @foreach($students as $student)
                <div data-picker-row data-search="{{ mb_strtolower($student->student_number . ' ' . $student->first_name . ' ' . $student->last_name . ' ' . $student->full_name) }}"
                     style="display:flex; align-items:center; gap:12px; padding:8px 12px; border-bottom:1px solid #f3f4f6;">
                    <span style="width:110px; font-family:monospace; font-size:13px; color:#374151;">{{ $student->student_number }}</span>
                    <span style="flex:1; font-size:14px; color:#111827;">{{ $student->full_name }}</span>
                    <span style="font-size:12px; color:#6b7280;">{{ $student->year_level }}</span>
                    <button type="submit" name="student_id" value="{{ $student->id }}"
                            style="padding:5px 12px; font-size:12px; font-weight:600; color:#fff; background:#0a5b05; border:none; border-radius:8px; cursor:pointer;">Enroll</button>
                </div>
            @endforeach
            <p data-picker-none hidden style="margin:0; padding:10px 12px; font-size:13px; color:#6b7280;">No student matches.</p>
        </div>
        <p data-picker-count style="margin:0; font-size:12px; color:#6b7280;"></p>
    @endif
</form>
@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form.student-picker').forEach(function (form) {
        const input = form.querySelector('[data-picker-input]');
        if (!input) return;
        const rows  = form.querySelectorAll('[data-picker-row]');
        const none  = form.querySelector('[data-picker-none]');
        const count = form.querySelector('[data-picker-count]');
        const LIMIT = 20;
        const filter = function () {
            const terms = input.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
            let matched = 0;
            rows.forEach(function (row) {
                const hit = terms.every(function (t) { return row.dataset.search.indexOf(t) !== -1; });
                row.style.display = hit && matched < LIMIT ? '' : 'none';
                if (hit) matched++;
            });
            none.hidden = matched > 0;
            count.textContent = matched > LIMIT ? 'Showing ' + LIMIT + ' of ' + matched + '. Type more of the name or number to narrow it down.' : '';
        };
        // Enter in the search box must not enroll the first student in the list.
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
        input.addEventListener('input', filter);
        filter();
    });
});
</script>
@endonce
