<x-sidebar-layout>
@php
    $card = 'background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,.04); overflow:hidden; margin-bottom:24px;';
    $th   = 'padding:10px 18px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:#6b7280; text-align:left; background:#f9fafb; border-bottom:1px solid #e5e7eb;';
    $td   = 'padding:12px 18px; font-size:14px; color:#374151; border-bottom:1px solid #f3f4f6; vertical-align:middle;';
    $btn  = 'padding:6px 12px; font-size:12px; font-weight:600; border-radius:8px; cursor:pointer; white-space:nowrap;';
    $pill = fn (string $status) => match ($status) {
        'pending'  => ['Pending',  '#92400e', '#fef3c7'],
        'approved' => ['Approved', '#166534', '#dcfce7'],
        'declined' => ['Declined', '#991b1b', '#fee2e2'],
        'revoked'  => ['Revoked',  '#991b1b', '#fee2e2'],
        default    => ['Cancelled', '#4b5563', '#f3f4f6'],
    };
    $input = 'width:100%; padding:8px 12px; font-size:14px; border:1px solid #d1d5db; border-radius:8px; background:#fff;';
@endphp

<div style="margin-bottom:20px;">
    <h1 style="margin:0; font-size:24px; font-weight:700; color:#1f2937;">Borrowed Faculty</h1>
    <p style="margin:4px 0 0; font-size:14px; color:#6b7280;">
        Ask another department to lend you a teacher for the semester. Once its Dean approves, you can assign that teacher to your subjects until the semester ends.
        The teacher keeps their own classes and records.
    </p>
</div>

@unless($period)
    <div style="margin-bottom:20px; padding:12px 16px; font-size:14px; color:#92400e; background:#fef3c7; border:1px solid #fde68a; border-radius:10px;">
        No active academic period is set, so requests cannot be sent right now. Contact your Administrator.
    </div>
@endunless

{{-- Requests from other departments --}}
<h2 style="margin:0 0 8px; font-size:16px; font-weight:700; color:#1f2937;">Requests to your department</h2>
<div style="{{ $card }}">
    @if($incoming->isEmpty())
        <p style="margin:0; padding:24px 18px; font-size:14px; text-align:center; color:#9ca3af;">No other department has asked for your teachers.</p>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead><tr>
                    <th style="{{ $th }}">Teacher</th><th style="{{ $th }}">Requested by</th><th style="{{ $th }}">Semester</th><th style="{{ $th }}">Status</th><th style="{{ $th }}"></th>
                </tr></thead>
                <tbody>
                @foreach($incoming as $loan)
                    @php [$label, $color, $bg] = $pill($loan->status); @endphp
                    <tr>
                        <td style="{{ $td }} font-weight:600;">{{ $loan->teacher?->name }}</td>
                        <td style="{{ $td }}">
                            {{ $loan->borrowingDepartment?->name }}
                            @if($loan->message)<div style="margin-top:2px; font-size:12px; color:#6b7280;">“{{ $loan->message }}”</div>@endif
                        </td>
                        <td style="{{ $td }}">{{ $loan->semester }}, {{ $loan->academic_year }}</td>
                        <td style="{{ $td }}">
                            <span style="padding:2px 10px; font-size:12px; font-weight:600; border-radius:999px; color:{{ $color }}; background:{{ $bg }};">{{ $label }}</span>
                            @if($loan->decision_note)<div style="margin-top:4px; font-size:12px; color:#6b7280;">{{ $loan->decision_note }}</div>@endif
                        </td>
                        <td style="{{ $td }} text-align:right;">
                            @if($loan->status === 'pending')
                                <div style="display:flex; gap:6px; justify-content:flex-end; align-items:center; flex-wrap:wrap;">
                                    <form method="POST" action="{{ route('dean.borrowing.approve', $loan) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" style="{{ $btn }} color:#fff; background:#0a5b05; border:none;">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('dean.borrowing.decline', $loan) }}" style="display:flex; gap:6px;">
                                        @csrf @method('PATCH')
                                        <input type="text" name="decision_note" maxlength="500" placeholder="Reason (optional)" style="padding:6px 10px; font-size:12px; border:1px solid #d1d5db; border-radius:8px; width:150px;">
                                        <button type="submit" style="{{ $btn }} color:#991b1b; background:#fff; border:1px solid #fca5a5;">Decline</button>
                                    </form>
                                </div>
                            @elseif($loan->status === 'approved')
                                @php $taught = $assignments[$loan->id] ?? 0; @endphp
                                <form method="POST" action="{{ route('dean.borrowing.revoke', $loan) }}"
                                      onsubmit="return confirm('Take back this loan? {{ $loan->borrowingDepartment?->code }} will no longer be able to assign this teacher.');">
                                    @csrf @method('PATCH')
                                    <button type="submit" @disabled($taught > 0) title="{{ $taught > 0 ? 'Assigned to ' . $taught . ' subject(s) of the borrowing department' : '' }}"
                                            style="{{ $btn }} color:#991b1b; background:#fff; border:1px solid #fca5a5; {{ $taught > 0 ? 'opacity:.5; cursor:not-allowed;' : '' }}">Revoke</button>
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

{{-- Ask another department --}}
<h2 style="margin:0 0 8px; font-size:16px; font-weight:700; color:#1f2937;">Request a teacher</h2>
<div style="{{ $card }} padding:18px;">
    @if($teachers->isEmpty())
        <p style="margin:0; font-size:14px; color:#9ca3af;">No active teachers in other departments yet.</p>
    @else
        <form method="POST" action="{{ route('dean.borrowing.store') }}" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px; align-items:end;">
            @csrf
            <div>
                <label for="borrow-teacher" style="display:block; margin-bottom:4px; font-size:12px; font-weight:600; color:#6b7280;">Teacher</label>
                <select id="borrow-teacher" name="teacher_id" required style="{{ $input }}">
                    <option value="">Select teacher</option>
                    @foreach($teachers as $department => $group)
                        <optgroup label="{{ $department }}">
                            @foreach($group as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="borrow-message" style="display:block; margin-bottom:4px; font-size:12px; font-weight:600; color:#6b7280;">Note to the other Dean (optional)</label>
                <input id="borrow-message" type="text" name="message" maxlength="500" value="{{ old('message') }}" placeholder="e.g. Needed for IT 301 this semester" style="{{ $input }}">
            </div>
            <div>
                <button type="submit" @disabled(!$period) style="{{ $btn }} padding:9px 16px; font-size:14px; color:#fff; background:#0a5b05; border:none; {{ $period ? '' : 'opacity:.5; cursor:not-allowed;' }}">
                    Send request{{ $period ? ' for ' . $period->semester : '' }}
                </button>
            </div>
        </form>
    @endif
</div>

{{-- My requests --}}
<h2 style="margin:0 0 8px; font-size:16px; font-weight:700; color:#1f2937;">Your requests</h2>
<div style="{{ $card }}">
    @if($mine->isEmpty())
        <p style="margin:0; padding:24px 18px; font-size:14px; text-align:center; color:#9ca3af;">You have not asked to borrow any teacher yet.</p>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead><tr>
                    <th style="{{ $th }}">Teacher</th><th style="{{ $th }}">From</th><th style="{{ $th }}">Semester</th><th style="{{ $th }}">Status</th><th style="{{ $th }}"></th>
                </tr></thead>
                <tbody>
                @foreach($mine as $loan)
                    @php [$label, $color, $bg] = $pill($loan->status); @endphp
                    <tr>
                        <td style="{{ $td }} font-weight:600;">{{ $loan->teacher?->name }}</td>
                        <td style="{{ $td }}">{{ $loan->lendingDepartment?->name }}</td>
                        <td style="{{ $td }}">{{ $loan->semester }}, {{ $loan->academic_year }}</td>
                        <td style="{{ $td }}">
                            <span style="padding:2px 10px; font-size:12px; font-weight:600; border-radius:999px; color:{{ $color }}; background:{{ $bg }};">{{ $label }}</span>
                            @if($loan->decision_note)<div style="margin-top:4px; font-size:12px; color:#6b7280;">{{ $loan->decision_note }}</div>@endif
                        </td>
                        <td style="{{ $td }} text-align:right;">
                            @if($loan->status === 'pending')
                                <form method="POST" action="{{ route('dean.borrowing.cancel', $loan) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" style="{{ $btn }} color:#374151; background:#fff; border:1px solid #d1d5db;">Cancel request</button>
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
</x-sidebar-layout>
