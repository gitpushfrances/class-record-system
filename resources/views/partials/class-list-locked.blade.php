{{-- Shown instead of the enrollment controls while a subject of the term is submitted or verified. Expects $codes (subject codes). --}}
<div role="status" style="padding:12px 14px; font-size:14px; line-height:1.5; color:#92400e; background:#fffbeb; border:1px solid #fcd34d; border-radius:8px;">
    <i class="fa-solid fa-lock"></i>
    <strong>Class list locked:</strong> grades are submitted for {{ implode(', ', $codes) }}.
    To add, drop or remove students, the Program Head rejects the submission on Grade Verification first (a verified one is undone, then rejected).
</div>
