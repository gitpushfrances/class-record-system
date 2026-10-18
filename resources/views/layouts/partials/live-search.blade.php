{{--
    Search while typing: a GET form[data-live-search] submits itself 400 ms after the last keystroke in its
    [data-live-input] (an input with minlength waits for that many characters, and clearing it shows everything again).
    Its [data-live-button] is hidden, since there is nothing to click. Without JavaScript the button still works.
--}}
<script>
(function () {
    document.querySelectorAll('form[data-live-search]').forEach(function (form) {
        const input = form.querySelector('[data-live-input]');
        if (!input) return;
        form.querySelectorAll('[data-live-button]').forEach(function (button) { button.style.display = 'none'; });

        const min = parseInt(input.getAttribute('minlength') || '0', 10);
        let timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            const length = input.value.trim().length;
            if (length > 0 && length < min) return;
            timer = setTimeout(function () { form.submit(); }, 400);
        });

        // After the page reloads with results, keep typing where you left off.
        if (input.value !== '') {
            input.focus();
            try { input.setSelectionRange(input.value.length, input.value.length); } catch (e) {}
        }
    });
})();
</script>
