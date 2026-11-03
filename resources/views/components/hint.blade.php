@props(['text'])
<span class="hint" tabindex="0" role="note" aria-label="{{ $text }}"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span class="hint-text" role="tooltip">{{ $text }}</span></span>
@once
<style>
    .hint { display:inline-flex; vertical-align:middle; margin-left:6px; color:#9ca3af; font-size:14px; cursor:help; outline:none; }
    .hint:hover, .hint:focus { color:#0a5b05; }
    .hint-text { display:none; position:fixed; z-index:60; max-width:260px; padding:8px 10px; font-size:12px; font-weight:400; line-height:1.4; color:#fff; background:#1f2937; border-radius:8px; white-space:normal; text-align:left; pointer-events:none; }
    .hint-text.is-open { display:block; }
</style>
<script>
(function () {
    var open = null;
    function show(hint) {
        var tip = hint.querySelector('.hint-text');
        if (!tip) return;
        hide();
        tip.classList.add('is-open');
        tip.style.left = '0px';
        tip.style.top = '0px';
        var r = hint.getBoundingClientRect(), t = tip.getBoundingClientRect();
        var left = Math.min(Math.max(8, r.left + r.width / 2 - t.width / 2), window.innerWidth - t.width - 8);
        var top = r.top - t.height - 8;
        if (top < 8) top = r.bottom + 8;
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
        open = tip;
    }
    function hide() {
        if (open) { open.classList.remove('is-open'); open = null; }
    }
    function find(e) { return e.target.closest ? e.target.closest('.hint') : null; }
    document.addEventListener('mouseover', function (e) { var h = find(e); if (h) show(h); });
    document.addEventListener('mouseout', function (e) { if (find(e)) hide(); });
    document.addEventListener('focusin', function (e) { var h = find(e); if (h) show(h); });
    document.addEventListener('focusout', function (e) { if (find(e)) hide(); });
    window.addEventListener('scroll', hide, true);
})();
</script>
@endonce
