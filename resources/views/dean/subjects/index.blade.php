@extends('layouts.app')

@section('title', 'Subjects')

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="mb-6">
        <h1 class="text-xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Subjects</h1>
        <p class="text-sm mt-0.5" style="color:#6b7280;">Subject catalog for your department. New subjects are requested by Program Heads for your approval.</p>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-sm text-green-700 border border-green-200 rounded-xl bg-green-50">
            {{ session('success') }}
        </div>
    @endif

    @if($pendingFromProgramHeads->isNotEmpty())
    <div class="mb-8">
        <h2 class="mb-3 text-sm font-semibold text-gray-700 uppercase">Pending Approval ({{ $pendingFromProgramHeads->count() }})</h2>
        <form method="POST" action="{{ route('dean.subjects.approve-selected') }}" id="bulkApproveForm" class="flex items-center justify-between mb-3">
            @csrf
            <label class="flex items-center gap-2 text-xs font-medium text-gray-600"><input type="checkbox" id="selectAll"> Select all</label>
            <button type="submit" id="bulkApproveBtn" disabled class="px-3 py-1.5 text-xs font-medium text-white rounded-lg disabled:opacity-40 disabled:cursor-not-allowed" style="background:#059669;">
                <i class="fa-solid fa-check-double"></i> Approve Selected (<span id="bulkCount">0</span>)
            </button>
        </form>
        <div class="space-y-3">
            @foreach($pendingFromProgramHeads as $subject)
            <div class="flex items-center justify-between p-4 border rounded-xl" style="border-color:#fde68a; background:#fffbeb;">
                <input type="checkbox" name="ids[]" value="{{ $subject->id }}" form="bulkApproveForm" class="mr-3 row-check">
                <div class="flex-1">
                    <div class="font-semibold text-gray-800">{{ $subject->code }} — {{ $subject->name }}</div>
                    <div class="mt-0.5 text-xs text-gray-500">
                        {{ $subject->units }} units · Requested by {{ $subject->requester->name ?? 'Unknown' }} for {{ $subject->program->code ?? '—' }}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('dean.subjects.approve', $subject) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white rounded-lg" style="background:#059669;">Approve</button>
                    </form>
                    <button type="button" onclick="openRejectModal({{ $subject->id }}, '{{ addslashes($subject->code) }}')"
                            class="px-3 py-1.5 text-xs font-medium border rounded-lg" style="border-color:#fecaca; color:#dc2626;">Reject</button>
                    <form id="reject-form-{{ $subject->id }}" method="POST" action="{{ route('dean.subjects.reject', $subject) }}" class="hidden">
                        @csrf
                        <input type="hidden" name="rejected_reason" id="reject-reason-{{ $subject->id }}">
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="overflow-hidden border rounded-2xl" style="border-color:#e5e7eb;">
        <table class="w-full text-sm">
            <thead style="background:#f9fafb;">
                <tr>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Code</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Name</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Program</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Units</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Assigned Teacher</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Status</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Submitted</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subjects as $subject)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ $subject->code }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $subject->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $subject->program->code ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $subject->units }}</td>
                    <td class="px-4 py-3">
                        @if($subject->sectionTerms->isNotEmpty())
                            <div class="text-sm text-gray-700">
                                {{ $subject->sectionTerms->pluck('pivot.teacher_id')->unique()->map(fn($id) => $teachers->firstWhere('id', $id)?->name ?? '—')->implode(', ') }}
                            </div>
                        @else
                            <span class="text-xs italic text-gray-400">Not assigned</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($subject->status === 'pending')
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#fef3c7; color:#92400e;">Pending</span>
                        @elseif($subject->status === 'approved')
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#d1fae5; color:#065f46;"><i class="fa-solid fa-circle-check"></i> Approved</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#fee2e2; color:#991b1b;" title="{{ $subject->rejected_reason }}"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $subject->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-sm text-center text-gray-400">No subjects in your department yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $subjects->links() }}</div>
</div>

<div id="rejectModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-black bg-opacity-40">
    <div class="w-full max-w-sm p-6 bg-white shadow-lg rounded-xl">
        <h3 class="mb-1 text-lg font-semibold text-gray-800">Reject Subject Request</h3>
        <p id="rejectSubjectLabel" class="mb-3 text-sm text-gray-500"></p>
        <textarea id="rejectReasonInput" rows="3" placeholder="Reason for rejection..."
                  class="w-full px-3 py-2 text-sm border rounded-lg" style="border-color:#d1d5db;"></textarea>
        <p id="rejectReasonError" class="hidden mt-1 text-xs text-red-500">A reason is required.</p>
        <div class="flex gap-3 mt-4">
            <button onclick="closeRejectModal()" class="flex-1 px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
            <button id="confirmRejectBtn" class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Reject</button>
        </div>
    </div>
</div>

<script>
let activeRejectId = null;

function openRejectModal(id, code) {
    activeRejectId = id;
    document.getElementById('rejectSubjectLabel').textContent = 'Subject: ' + code;
    document.getElementById('rejectReasonInput').value = '';
    document.getElementById('rejectReasonError').classList.add('hidden');
    document.getElementById('rejectModal').classList.replace('hidden', 'flex');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.replace('flex', 'hidden');
    activeRejectId = null;
}

document.getElementById('confirmRejectBtn').addEventListener('click', function () {
    const reason = document.getElementById('rejectReasonInput').value.trim();
    if (!reason) {
        document.getElementById('rejectReasonError').classList.remove('hidden');
        return;
    }
    document.getElementById('reject-reason-' + activeRejectId).value = reason;
    document.getElementById('reject-form-' + activeRejectId).submit();
});
</script>
<script>
(function () {
    var form = document.getElementById('bulkApproveForm');
    if (!form) return;
    var all = document.getElementById('selectAll');
    var btn = document.getElementById('bulkApproveBtn');
    var count = document.getElementById('bulkCount');
    var boxes = Array.prototype.slice.call(document.querySelectorAll('.row-check'));
    function refresh() {
        var n = boxes.filter(function (b) { return b.checked; }).length;
        count.textContent = n;
        btn.disabled = n === 0;
        if (all) all.checked = boxes.length > 0 && n === boxes.length;
    }
    if (all) all.addEventListener('change', function () {
        boxes.forEach(function (b) { b.checked = all.checked; });
        refresh();
    });
    boxes.forEach(function (b) { b.addEventListener('change', refresh); });
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var n = count.textContent;
        if (typeof Swal === 'undefined') {
            if (confirm('Approve ' + n + ' selected subject(s)?')) form.submit();
            return;
        }
        Swal.fire({
            title: 'Approve ' + n + ' selected subject(s)?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            confirmButtonText: 'Yes, Approve'
        }).then(function (r) { if (r.isConfirmed) form.submit(); });
    });
})();
</script>
@endsection
