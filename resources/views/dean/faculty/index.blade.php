<x-sidebar-layout>
@php
    $card  = 'background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow:hidden;';
    $th    = 'padding:10px 18px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#6b7280; text-align:left; background:#f9fafb; border-bottom:1px solid #e5e7eb;';
    $td    = 'padding:12px 18px; font-size:14px; color:#374151; border-bottom:1px solid #f3f4f6; vertical-align:middle;';
    $btn   = 'padding:6px 12px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; white-space:nowrap;';
    $tab   = fn ($on) => 'padding:6px 14px; font-size:13px; font-weight:600; border-radius:8px; text-decoration:none; border:1px solid ' . ($on ? '#0a5b05; color:#fff; background:#0a5b05;' : '#d1d5db; color:#374151; background:#fff;');
    $pending = ($counts['pending'] ?? 0) + ($counts['pending_review'] ?? 0);
@endphp

<div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:20px;">
    <div>
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#1f2937;">Faculty</h1>
        <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">Deactivate teachers who no longer teach in your department. Their grades and records are kept.</p>
    </div>
    @if($pending > 0)
        <a href="{{ route('dean.teachers.pending') }}" style="{{ $btn }} padding:8px 14px; color:#92400e; background:#fef3c7; border:1px solid #fde68a; text-decoration:none;">
            <i class="fa-solid fa-user-clock"></i> {{ $pending }} pending {{ \Illuminate\Support\Str::plural('approval', $pending) }}
        </a>
    @endif
</div>

<form method="GET" style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-bottom:16px;">
    <a href="{{ route('dean.faculty.index') }}" style="{{ $tab($status === 'active') }}">Active ({{ $counts['active'] ?? 0 }})</a>
    <a href="{{ route('dean.faculty.index', ['status' => 'inactive']) }}" style="{{ $tab($status === 'inactive') }}">Inactive ({{ $counts['inactive'] ?? 0 }})</a>
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or email…"
           style="flex:1 1 220px; max-width:320px; margin-left:auto; padding:8px 12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px;">
    <button type="submit" style="{{ $btn }} padding:8px 14px; color:#fff; background:#0a5b05; border:none;">Search</button>
</form>

<div style="{{ $card }}">
    @if($teachers->isEmpty())
        <p style="margin:0; padding:24px 18px; font-size:14px; text-align:center; color:#9ca3af;">No {{ $status }} teachers{{ request()->filled('search') ? ' match "' . request('search') . '"' : '' }}.</p>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead><tr>
                    <th style="{{ $th }}">Name</th><th style="{{ $th }}">Email</th><th style="{{ $th }}">This term</th><th style="{{ $th }}"></th>
                </tr></thead>
                <tbody>
                @foreach($teachers as $teacher)
                    @php $l = $load[$teacher->id]; $busy = $l['subjects'] > 0 || $l['advisory'] > 0; @endphp
                    <tr>
                        <td style="{{ $td }} font-weight:600;">{{ $teacher->name }}</td>
                        <td style="{{ $td }} color:#6b7280;">{{ $teacher->email }}</td>
                        <td style="{{ $td }}">
                            @if($busy)
                                {{ $l['subjects'] }} {{ \Illuminate\Support\Str::plural('subject', $l['subjects']) }}{{ $l['advisory'] > 0 ? ', adviser of ' . $l['advisory'] . ' ' . \Illuminate\Support\Str::plural('section', $l['advisory']) : '' }}
                            @else
                                <span style="color:#9ca3af;">No load</span>
                            @endif
                        </td>
                        <td style="{{ $td }} text-align:right;">
                            @if($status === 'active')
                                <form method="POST" action="{{ route('dean.faculty.deactivate', $teacher) }}"
                                      onsubmit="return confirm('Deactivate this teacher? They will no longer be able to sign in. Grades and records are kept.');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" @disabled($busy) title="{{ $busy ? 'Reassign their subjects and advisory sections first' : '' }}"
                                            style="{{ $btn }} color:#b91c1c; background:#fff; border:1px solid #fca5a5; {{ $busy ? 'opacity:.45; cursor:not-allowed;' : '' }}">Deactivate</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('dean.faculty.activate', $teacher) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" style="{{ $btn }} color:#15803d; background:#fff; border:1px solid #86efac;">Reactivate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div style="margin-top:16px;">{{ $teachers->links() }}</div>
</x-sidebar-layout>
