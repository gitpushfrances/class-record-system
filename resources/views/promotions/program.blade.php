<x-sidebar-layout>
@if($prefix === 'dean')
    <div style="margin-bottom:12px;"><x-back-button :href="route('dean.promotions.index')" label="Back to Programs" /></div>
@endif

<div style="margin-bottom:20px;">
    <h1 style="margin:0; font-size:24px; font-weight:700; color:#1f2937;">Batch Promotion — {{ $program->code }}</h1>
    <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">
        Active period: <strong>{{ $period ? "{$period->semester} {$period->school_year}" : 'none set' }}</strong>.
        Open a section to move the students of its last closed term into this period.
    </p>
</div>

@forelse($sections as $yearLevel => $group)
    <h2 style="margin:24px 0 10px; font-size:13px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#6b7280;">{{ $yearLevel }}</h2>
    <div style="display:grid; gap:12px; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr));">
        @foreach($group as $section)
            @php
                $closed = $section->terms->where('status', 'completed')->sortByDesc('id')->first();
                $active = $section->terms->where('status', 'active')->first();
            @endphp
            <a href="{{ route($prefix . '.promotions.section', $section) }}" style="display:block; padding:16px; text-decoration:none; background:#fff; border:1px solid #e5e7eb; border-radius:12px;">
                <div style="font-size:16px; font-weight:700; color:#111827;">{{ $program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</div>
                <div style="margin-top:8px; font-size:12px; color:#4b5563;">
                    From: {{ $closed ? "{$closed->semester} {$closed->academic_year} ({$closed->enrollments()->count()} students)" : 'no closed term yet' }}
                </div>
                <div style="margin-top:2px; font-size:12px; color:#4b5563;">
                    Now: {{ $active ? "{$active->semester} {$active->academic_year} ({$active->enrollments()->count()} students)" : 'no active term' }}
                </div>
            </a>
        @endforeach
    </div>
@empty
    <p style="font-size:14px; color:#6b7280;">No active sections in this program.</p>
@endforelse
</x-sidebar-layout>
