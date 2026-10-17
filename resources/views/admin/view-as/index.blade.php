<x-sidebar-layout>
@php
    $viewAs = \App\Services\Scope::viewingAs();
    $card   = 'background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow:hidden;';
    $btn    = 'padding:6px 12px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; white-space:nowrap;';
@endphp

<div style="margin-bottom:20px;">
    <h1 style="margin:0; font-size:24px; font-weight:700; color:#1f2937;">View As</h1>
    <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">Work in a department as its Dean, or in a program as its Program Head. Every action is recorded under your Admin account.</p>
</div>

@if($viewAs)
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; padding:12px 16px; margin-bottom:20px; background:#eef2ff; border:1px solid #c7d2fe; border-radius:10px;">
        <span style="font-size:14px; color:#3730a3;"><i class="fa-solid fa-eye"></i> Currently viewing as <strong>{{ $viewAs['label'] }}</strong></span>
        <form method="POST" action="{{ route('admin.view-as.stop') }}">
            @csrf
            @method('DELETE')
            <button type="submit" style="{{ $btn }} color:#3730a3; background:#fff; border:1px solid #a5b4fc;">Exit</button>
        </form>
    </div>
@endif

<div style="display:grid; gap:16px; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));">
    @forelse($departments as $department)
        <div style="{{ $card }}">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:14px 18px; border-bottom:1px solid #e5e7eb;">
                <div>
                    <div style="font-size:15px; font-weight:700; color:#111827;">{{ $department->code }}</div>
                    <div style="font-size:12px; color:#6b7280;">{{ $department->name }}</div>
                </div>
                <form method="POST" action="{{ route('admin.view-as.start') }}">
                    @csrf
                    <input type="hidden" name="role" value="dean">
                    <input type="hidden" name="department_id" value="{{ $department->id }}">
                    <button type="submit" style="{{ $btn }} color:#fff; background:#4f46e5; border:none;">View as Dean</button>
                </form>
            </div>
            @forelse($department->programs as $program)
                <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding:10px 18px; border-bottom:1px solid #f3f4f6;">
                    <span style="font-size:14px; color:#374151;"><strong>{{ $program->code }}</strong> <span style="color:#6b7280;">{{ $program->name }}</span></span>
                    <form method="POST" action="{{ route('admin.view-as.start') }}">
                        @csrf
                        <input type="hidden" name="role" value="program_head">
                        <input type="hidden" name="program_id" value="{{ $program->id }}">
                        <button type="submit" style="{{ $btn }} color:#374151; background:#fff; border:1px solid #d1d5db;">View as Program Head</button>
                    </form>
                </div>
            @empty
                <p style="margin:0; padding:12px 18px; font-size:13px; color:#9ca3af;">No approved programs.</p>
            @endforelse
        </div>
    @empty
        <p style="font-size:14px; color:#6b7280;">No departments yet.</p>
    @endforelse
</div>
</x-sidebar-layout>
