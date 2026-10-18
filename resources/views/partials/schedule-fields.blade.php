{{--
    Schedule of Classes: day pills + time range.
    Expects $days (array), $start, $end ("H:i"); optional $prefix ("schedule" = class / lecture, "lab" = laboratory) and $title.
    A laboratory block is wrapped in [data-lab-schedule]: in a form whose subject <select data-lab-toggle> has
    <option data-lab="1">, it shows (and submits) only while a subject with a laboratory is selected.
--}}
@php
    $prefix = $prefix ?? 'schedule';
    $title  = $title ?? null;
@endphp
@once
<style>
    .sched-label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #374151; }
    .sched-title { margin-bottom: 8px; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #0a5b05; }
    .sched-days { display: flex; flex-wrap: wrap; gap: 6px; }
    .sched-day { position: relative; }
    .sched-day input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .sched-day span { display: inline-block; min-width: 38px; padding: 6px 0; text-align: center; font-size: 12px; font-weight: 600; color: #374151; background: #fff; border: 1px solid #d1d5db; border-radius: 9999px; cursor: pointer; user-select: none; }
    .sched-day input:checked + span { color: #fff; background: #0b3d0b; border-color: #0b3d0b; }
    .sched-day input:focus-visible + span { outline: 2px solid #0a5b05; outline-offset: 2px; }
    .sched-time { display: flex; align-items: center; gap: 8px; }
    .sched-time input { flex: 1; min-width: 0; padding: 6px 8px; font-size: 13px; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select[data-lab-toggle]').forEach(function (select) {
            const box = select.form ? select.form.querySelector('[data-lab-schedule]') : null;
            if (!box) return;
            const sync = function () {
                const option = select.options[select.selectedIndex];
                const hasLab = !!option && option.dataset.lab === '1';
                box.hidden = !hasLab;
                box.querySelectorAll('input').forEach(function (input) { input.disabled = !hasLab; });
            };
            select.addEventListener('change', sync);
            sync();
        });
    });
</script>
@endonce
<div @if($prefix === 'lab') data-lab-schedule @endif>
    @if($title)<div class="sched-title">{{ $title }}</div>@endif
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px;">
        <div>
            <span class="sched-label">{{ $prefix === 'lab' ? 'Laboratory days' : 'Class days' }}</span>
            <div class="sched-days" role="group" aria-label="{{ $prefix === 'lab' ? 'Laboratory days' : 'Class days' }}">
                @foreach(\App\Services\ClassSchedule::DAYS as $code => $dayName)
                    <label class="sched-day" title="{{ $dayName }}">
                        <input type="checkbox" name="{{ $prefix }}_days[]" value="{{ $code }}" @checked(in_array($code, $days, true))>
                        <span>{{ $code }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <div>
            <span class="sched-label">Time</span>
            <div class="sched-time">
                <input type="time" name="{{ $prefix }}_start" value="{{ $start }}" aria-label="{{ $prefix === 'lab' ? 'Laboratory start time' : 'Start time' }}">
                <span style="font-size:12px; color:#6b7280;">to</span>
                <input type="time" name="{{ $prefix }}_end" value="{{ $end }}" aria-label="{{ $prefix === 'lab' ? 'Laboratory end time' : 'End time' }}">
            </div>
        </div>
    </div>
</div>
