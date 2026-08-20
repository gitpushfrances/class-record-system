@extends('layouts.app')

@section('title', 'My Subject Requests')

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Subject Requests</h1>
            <p class="text-sm mt-0.5" style="color:#6b7280;">Subjects you've submitted for Dean approval.</p>
        </div>
        <a href="{{ route('program-head.subjects.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold transition rounded-xl"
           style="background:#1c1814; color:#f0dfc0;">
            <i class="text-xs fas fa-plus"></i> Request Subject
        </a>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-sm text-green-700 border border-green-200 rounded-xl bg-green-50">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden border rounded-2xl" style="border-color:#e5e7eb;">
        <table class="w-full text-sm">
            <thead style="background:#f9fafb;">
                <tr>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Code</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Name</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Units</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Status</th>
                    <th class="px-4 py-3 font-semibold text-left text-gray-600">Submitted</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($subjects as $subject)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono font-semibold text-gray-800">{{ $subject->code }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $subject->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $subject->units }}</td>
                    <td class="px-4 py-3">
                        @if($subject->status === 'pending')
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#fef3c7; color:#92400e;">⏳ Pending</span>
                        @elseif($subject->status === 'approved')
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#d1fae5; color:#065f46;"><i class="fa-solid fa-circle-check"></i> Approved</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold" style="background:#fee2e2; color:#991b1b;"><i class="fa-solid fa-circle-xmark"></i> Rejected</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $subject->created_at->format('M d, Y') }}</td>
                    <td class="px-4 py-3">
                        @if($subject->status === 'pending')
                        <div class="flex items-center gap-2">
                            <a href="{{ route('program-head.subjects.edit', $subject) }}"
                               class="px-3 py-1 text-xs font-medium transition border rounded-lg"
                               style="border-color:#d1d5db; color:#374151;">Edit</a>
                            <form method="POST" action="{{ route('program-head.subjects.destroy', $subject) }}"
                                  class="inline delete-form">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1 text-xs font-medium transition border rounded-lg"
                                        style="border-color:#fecaca; color:#dc2626;">Cancel</button>
                            </form>
                        </div>
                        @elseif($subject->status === 'rejected')
                        <span class="text-xs italic text-gray-400" title="{{ $subject->rejected_reason }}">
                            {{ Str::limit($subject->rejected_reason, 40) }}
                        </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-sm text-center text-gray-400">
                        No subject requests yet. <a href="{{ route('program-head.subjects.create') }}" style="color:#c8a97e;">Request one.</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $subjects->links() }}</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
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
