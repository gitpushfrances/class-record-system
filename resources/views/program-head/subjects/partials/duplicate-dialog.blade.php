{{-- Shown when the controller flashes 'duplicate_subjects'. Needs #subject-form and #confirm_duplicate on the page. --}}
@if(session('duplicate_subjects'))
<script>
(function () {
    const matches = @json(session('duplicate_subjects'));
    const esc = s => String(s ?? '—').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const rows = matches.map(m => `
        <div style="padding:10px 12px; margin-top:8px; border:1px solid #e5e7eb; border-radius:8px;">
            <div style="font-weight:600;">${esc(m.code)} — ${esc(m.name)}</div>
            <div style="font-size:12px; color:#6b7280;">${esc(m.program)} &bull; ${esc(m.department)} &bull; ${esc(m.status)}</div>
        </div>`).join('');

    Swal.fire({
        icon: 'warning',
        title: 'Subject code already in use',
        html: `<div style="text-align:left; font-size:14px;">
                   <p>This code is already used in your department by:</p>${rows}
                   <p style="margin-top:12px;">Continue with the same code?</p>
               </div>`,
        showCancelButton: true,
        confirmButtonText: 'Continue',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        allowOutsideClick: false,
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('confirm_duplicate').value = '1';
            document.getElementById('subject-form').submit();
        }
    });
})();
</script>
@endif
