{{--
    Search while typing: a GET form[data-live-search] submits itself 400 ms after the last keystroke in its
    [data-live-input] (an input with minlength waits for that many characters, and clearing it shows everything again).
    Its [data-live-button] is hidden, since there is nothing to click. Without JavaScript the button still works.
--}}
<script>
// Same rule as App\Services\NameSearch: each typed word must start a word (name, or a student-number part).
window.EssuSearch = (function () {
    const WORD = /[\p{L}\p{N}]/u;
    function terms(query) {
        return String(query).toLowerCase().split(/[\s,]+/).filter(Boolean);
    }
    function starts(text, term) {
        const found = [];
        let i = text.indexOf(term);
        while (i !== -1) {
            if (i === 0 || !WORD.test(text[i - 1])) found.push(i);
            i = text.indexOf(term, i + 1);
        }
        return found;
    }
    function matches(text, words) {
        const lower = String(text).toLowerCase();
        return words.every(function (term) { return starts(lower, term).length > 0; });
    }
    function escapeHtml(c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c] || c;
    }
    function highlight(el, words) {
        if (el.dataset.plain === undefined) el.dataset.plain = el.textContent;
        const plain = el.dataset.plain, lower = plain.toLowerCase(), mark = new Array(plain.length).fill(false);
        words.forEach(function (term) {
            starts(lower, term).forEach(function (p) { for (let k = p; k < p + term.length; k++) mark[k] = true; });
        });
        let html = '', open = false;
        for (let k = 0; k < plain.length; k++) {
            if (mark[k] !== open) { html += mark[k] ? '<mark class="search-hit">' : '</mark>'; open = mark[k]; }
            html += escapeHtml(plain[k]);
        }
        el.innerHTML = html + (open ? '</mark>' : '');
    }
    return { terms: terms, matches: matches, highlight: highlight };
})();
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
