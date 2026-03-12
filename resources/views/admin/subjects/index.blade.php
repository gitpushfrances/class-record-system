@extends('layouts.app')

@section('title', 'Subject Approvals')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="mb-6">
        <h1 class="text-xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Subject Management</h1>
        <p class="text-sm mt-0.5 text-gray-500">Review and approve subject requests from Deans.</p>
    </div>

    {{-- Pending --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-base font-semibold text-gray-800">Pending Approval</h2>
            @if($pending->count())
                <span class="px-2 py-0.5 rounded-full text-xs font-bold" style="background:#fef3c7; color:#92400e;">
                    {{ $pending->count() }}
                </span>
            @endif
        </div>

        <div class="overflow-hidden border rounded-2xl" style="border-color:#e5e7eb;">
            <table class="w-full text-sm">
                <thead style="background:#fffbeb;">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Code</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Name</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Department</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Units</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Requested By</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Requested At</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pending as $subject)
                    <tr class="hover:bg-amber-50">
                        <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ $subject->code }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $subject->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $subject->department }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $subject->units }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $subject->requester?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $subject->created_at->format('M d, Y h:i A') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                {{-- Approve --}}
                                <form method="POST" action="{{ route('admin.subjects.approve', $subject) }}" class="approve-form">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs font-semibold px-3 py-1.5 rounded-lg transition"
                                            style="background:#d1fae5; color:#065f46;">
                                        ✓ Approve
                                    </button>
                                </form>
                                {{-- Reject --}}
                                <button type="button"
                                        class="text-xs font-semibold px-3 py-1.5 rounded-lg transition reject-btn"
                                        data-id="{{ $subject->id }}"
                                        data-code="{{ $subject->code }}"
                                        style="background:#fee2e2; color:#991b1b;">
                                    ✗ Reject
                                </button>
                                {{-- Hidden reject form --}}
                                <form method="POST"
                                      action="{{ route('admin.subjects.reject', $subject) }}"
                                      id="reject-form-{{ $subject->id }}"
                                      class="hidden">
                                    @csrf
                                    <input type="hidden" name="rejected_reason" id="reason-{{ $subject->id }}">
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-sm text-center text-gray-400">No pending subject requests.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Approved --}}
    <div>
        <h2 class="mb-3 text-base font-semibold text-gray-800">Approved Subjects</h2>
        <div class="overflow-hidden border rounded-2xl" style="border-color:#e5e7eb;">
            <table class="w-full text-sm">
                <thead style="background:#f9fafb;">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Code</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Name</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Department</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Units</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Requested By</th>
                        <th class="px-4 py-3 font-semibold text-left text-gray-600">Approved At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($approved as $subject)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ $subject->code }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $subject->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $subject->department }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $subject->units }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $subject->requester?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $subject->approved_at?->format('M d, Y h:i A') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-sm text-center text-gray-400">No approved subjects yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $approved->links() }}</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Approve confirm
document.querySelectorAll('.approve-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Approve this subject?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#065f46',
            confirmButtonText: 'Yes, Approve',
        }).then(r => { if (r.isConfirmed) form.submit(); });
    });
});

// Reject with reason
document.querySelectorAll('.reject-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const id   = this.dataset.id;
        const code = this.dataset.code;
        Swal.fire({
            title: `Reject ${code}?`,
            input: 'textarea',
            inputLabel: 'Reason for rejection (required)',
            inputPlaceholder: 'Enter reason...',
            inputAttributes: { maxlength: 500 },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#991b1b',
            confirmButtonText: 'Reject',
            preConfirm: (value) => {
                if (!value || !value.trim()) {
                    Swal.showValidationMessage('Reason is required.');
                }
                return value;
            }
        }).then(r => {
            if (r.isConfirmed) {
                document.getElementById(`reason-${id}`).value = r.value;
                document.getElementById(`reject-form-${id}`).submit();
            }
        });
    });
});
</script>
@endsection
