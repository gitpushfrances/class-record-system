{{-- Subject type: lecture only, or lecture and laboratory (stored as subjects.has_laboratory). Expects $hasLaboratory (bool). --}}
@php $isLab = (bool) old('has_laboratory', $hasLaboratory); @endphp
<fieldset>
    <legend class="block mb-1 text-sm font-semibold text-gray-700">Subject type</legend>
    <div style="display:flex; flex-wrap:wrap; gap:16px; font-size:14px; color:#374151;">
        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
            <input type="radio" name="has_laboratory" value="0" @checked(!$isLab)> Lecture only
        </label>
        <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
            <input type="radio" name="has_laboratory" value="1" @checked($isLab)> Lecture and laboratory
        </label>
    </div>
    <p class="mt-1 text-xs text-gray-500">Days and times are not set here. When you assign this subject to a section, you set the lecture schedule and the laboratory schedule separately, each with its own days and time. Attendance is also recorded separately.</p>
</fieldset>
