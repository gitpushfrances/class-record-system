{{-- Separator above one department's classes on a teacher's page. Expects $group (App\Services\DepartmentGroups). --}}
<div style="display:flex; align-items:center; gap:10px; margin:8px 0 12px;">
    <h2 style="margin:0; font-size:15px; font-weight:700; color:#0b3d0b;">{{ $group['department']?->name ?? 'No department' }}</h2>
    @if($group['borrowed'])
        <span style="padding:2px 10px; font-size:11px; font-weight:700; color:#92400e; background:#fef3c7; border-radius:999px;">Borrowed</span>
    @endif
    <span style="flex:1; border-top:2px solid #d4af37;"></span>
    <span style="font-size:12px; font-weight:600; color:#4b5563;">{{ $group['items']->count() }} {{ \Illuminate\Support\Str::plural('class', $group['items']->count()) }}</span>
</div>
