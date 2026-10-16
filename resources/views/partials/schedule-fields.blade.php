{{-- Schedule of Classes: day pills + time range. Expects $days (array), $start, $end ("H:i"). --}}
@once
<style>
    .sched-label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 600; color: #374151; }
    .sched-days { display: flex; flex-wrap: wrap; gap: 6px; }
    .sched-day { position: relative; }
    .sched-day input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .sched-day span { display: inline-block; min-width: 38px; padding: 6px 0; text-align: center; font-size: 12px; font-weight: 600; color: #374151; background: #fff; border: 1px solid #d1d5db; border-radius: 9999px; cursor: pointer; user-select: none; }
    .sched-day input:checked + span { color: #fff; background: #1c1814; border-color: #1c1814; }
    .sched-day input:focus-visible + span { outline: 2px solid #6366f1; outline-offset: 2px; }
    .sched-time { display: flex; align-items: center; gap: 8px; }
    .sched-time input { flex: 1; min-width: 0; padding: 6px 8px; font-size: 13px; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; }
</style>
@endonce
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px;">
    <div>
        <span class="sched-label">Class days</span>
        <div class="sched-days" role="group" aria-label="Class days">
            @foreach(\App\Services\ClassSchedule::DAYS as $code => $dayName)
                <label class="sched-day" title="{{ $dayName }}">
                    <input type="checkbox" name="schedule_days[]" value="{{ $code }}" @checked(in_array($code, $days, true))>
                    <span>{{ $code }}</span>
                </label>
            @endforeach
        </div>
    </div>
    <div>
        <span class="sched-label">Time</span>
        <div class="sched-time">
            <input type="time" name="schedule_start" value="{{ $start }}" aria-label="Start time">
            <span style="font-size:12px; color:#6b7280;">to</span>
            <input type="time" name="schedule_end" value="{{ $end }}" aria-label="End time">
        </div>
    </div>
</div>
