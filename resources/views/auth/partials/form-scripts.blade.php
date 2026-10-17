{{-- Show / hide password buttons and the submit spinner. Expects $form (form id). --}}
<script>
document.querySelectorAll('.eye-btn[data-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.toggle);
        var show  = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
document.getElementById(@json($form)).addEventListener('submit', function () {
    document.getElementById('submit-btn').classList.add('loading');
});
</script>
