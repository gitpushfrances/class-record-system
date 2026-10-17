<x-sidebar-layout>
<div style="margin-bottom:20px;">
    <h1 style="margin:0; font-size:24px; font-weight:700; color:#1f2937;">Batch Promotion</h1>
    <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">Choose a program, then a section, then tick the students to move into the new term.</p>
</div>

<div style="display:grid; gap:16px; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));">
    @forelse($programs as $program)
        <a href="{{ route($prefix . '.promotions.program', $program) }}" style="display:block; padding:18px; text-decoration:none; background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.04);">
            <div style="font-size:18px; font-weight:700; color:#111827;">{{ $program->code }}</div>
            <div style="margin-top:2px; font-size:13px; color:#6b7280;">{{ $program->name }}</div>
            <div style="margin-top:12px; font-size:12px; color:#4b5563;">{{ $program->sections_count }} active {{ \Illuminate\Support\Str::plural('section', $program->sections_count) }} <i class="fa-solid fa-arrow-right" style="margin-left:4px;"></i></div>
        </a>
    @empty
        <p style="font-size:14px; color:#6b7280;">No approved programs in your department.</p>
    @endforelse
</div>
</x-sidebar-layout>
