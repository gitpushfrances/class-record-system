@extends('layouts.app')

@section('title', 'Subjects')

@section('content')
@php
    $th  = 'padding:10px 16px; font-size:12px; font-weight:600; text-align:left; color:#4b5563; background:#f9fafb;';
    $td  = 'padding:10px 16px; font-size:14px; border-top:1px solid #f3f4f6;';
    $sel = 'padding:4px 6px; font-size:12px; border:1px solid #d1d5db; border-radius:6px; background:#fff;';
    $btn = 'padding:4px 10px; font-size:12px; font-weight:600; border-radius:6px; cursor:pointer;';
@endphp
<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold" style="font-family:'Fraunces',serif; color:#0b3d0b;">Subjects</h1>
            <p class="text-sm mt-0.5" style="color:#6b7280;">Your program's subjects by year level and semester. New subjects go to the Dean for approval.</p>
        </div>
        <a href="{{ route('program-head.subjects.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold transition rounded-xl"
           style="background:#0b3d0b; color:#fbf3d0;">
            <i class="text-xs fas fa-plus"></i> Request Subject
        </a>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-sm text-green-700 border border-green-200 rounded-xl bg-green-50">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 mb-4 text-sm text-red-700 border border-red-200 rounded-xl bg-red-50">{{ $errors->first() }}</div>
    @endif

    @if($subjects->contains(fn ($s) => $s->placement === \App\Models\Subject::UNSPECIFIED))
        <div class="px-4 py-3 mb-4 text-sm border rounded-xl" style="color:#92400e; background:#fffbeb; border-color:#fde68a;">
            <i class="fa-solid fa-triangle-exclamation"></i> Some subjects have no year level or semester yet. Set them below so they appear in the right group and can be assigned in the right term.
        </div>
    @endif

    <div class="overflow-hidden border rounded-2xl" style="border-color:#e5e7eb;">
        <table style="width:100%; border-collapse:collapse;">
            <thead><tr>
                <th style="{{ $th }}">Code</th><th style="{{ $th }}">Name</th><th style="{{ $th }}">Units</th><th style="{{ $th }}">Status</th><th style="{{ $th }}">Year / Semester</th><th style="{{ $th }}"></th>
            </tr></thead>
            <tbody>
            @forelse(\App\Models\Subject::byPlacement($subjects) as $placement => $group)
                <tr><td colspan="6" style="padding:8px 16px; font-size:12px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:{{ $placement === \App\Models\Subject::UNSPECIFIED ? '#92400e' : '#0b3d0b' }}; background:{{ $placement === \App\Models\Subject::UNSPECIFIED ? '#fffbeb' : '#eef5ec' }};">{{ $placement }} <span style="font-weight:400; color:#6b7280;">({{ $group->count() }})</span></td></tr>
                @foreach($group as $subject)
                <tr>
                    <td style="{{ $td }} font-family:monospace; font-weight:600; color:#1f2937;">{{ $subject->code }}</td>
                    <td style="{{ $td }} color:#374151;">{{ $subject->name }}</td>
                    <td style="{{ $td }} color:#4b5563;">{{ $subject->units }}@if($subject->has_laboratory)<span style="margin-left:6px; padding:1px 7px; font-size:10px; font-weight:700; color:#0a5b05; background:#eef7ec; border-radius:9999px; white-space:nowrap;">Lec + Lab</span>@endif</td>
                    <td style="{{ $td }}">
                        @if($subject->status === 'pending')
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#fef3c7; color:#92400e;">Pending</span>
                        @elseif($subject->status === 'approved')
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#d1fae5; color:#065f46;">Approved</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#fee2e2; color:#991b1b;" title="{{ $subject->rejected_reason }}">Rejected</span>
                        @endif
                    </td>
                    <td style="{{ $td }}">
                        <form method="POST" action="{{ route('program-head.subjects.placement', $subject) }}" style="display:flex; flex-wrap:wrap; gap:4px; align-items:center;">
                            @csrf
                            @method('PATCH')
                            <select name="year_level" required style="{{ $sel }}">
                                <option value="">Year…</option>
                                @foreach(\App\Models\Subject::YEAR_LEVELS as $level)
                                    <option value="{{ $level }}" @selected($subject->year_level === $level)>{{ $level }}</option>
                                @endforeach
                            </select>
                            <select name="semester" required style="{{ $sel }}">
                                <option value="">Semester…</option>
                                @foreach(\App\Models\Subject::SEMESTERS as $sem)
                                    <option value="{{ $sem }}" @selected($subject->semester === $sem)>{{ $sem }}</option>
                                @endforeach
                            </select>
                            <button type="submit" style="{{ $btn }} color:#374151; background:#fff; border:1px solid #d1d5db;">Save</button>
                        </form>
                    </td>
                    <td style="{{ $td }}">
                        @if($subject->status === 'pending')
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('program-head.subjects.edit', $subject) }}" style="{{ $btn }} color:#374151; border:1px solid #d1d5db; text-decoration:none;">Edit</a>
                            <form method="POST" action="{{ route('program-head.subjects.destroy', $subject) }}" class="delete-form">
                                @csrf @method('DELETE')
                                <button type="submit" style="{{ $btn }} color:#dc2626; background:#fff; border:1px solid #fecaca;">Cancel</button>
                            </form>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" style="padding:40px 16px; font-size:14px; text-align:center; color:#9ca3af;">
                        No subjects yet. <a href="{{ route('program-head.subjects.create') }}" style="color:#d4af37;">Request one.</a>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (typeof Swal === 'undefined') { if (confirm('Cancel this subject request?')) form.submit(); return; }
        Swal.fire({
            title: 'Cancel this request?',
            text: 'This subject request will be permanently removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, cancel it',
            cancelButtonText: 'Keep it',
        }).then(result => { if (result.isConfirmed) form.submit(); });
    });
});
</script>
@endsection
