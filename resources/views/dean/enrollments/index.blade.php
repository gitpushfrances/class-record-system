<x-sidebar-layout>

    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Enrollments</h2>
            <p class="mt-1 text-sm text-gray-500">Read-only. The Program Head enrolls students; instructors add students to their own subjects.</p>
        </div>
    </div>

    <h3 class="mb-2 text-sm font-semibold text-gray-700 uppercase">
        Current term{{ $period ? ': ' . $period->semester . ', ' . $period->school_year : '' }}
    </h3>
    <div class="mb-8 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="p-6">
            @include('dean.enrollments._table', [
                'terms' => $current,
                'empty' => $period ? 'No sections have a term in the current semester yet.' : 'No active academic period is set.',
            ])
        </div>
    </div>

    <details class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm" @if(request()->has('past')) open @endif>
        <summary class="px-6 py-4 text-sm font-semibold text-gray-700 cursor-pointer">
            Past terms ({{ $past->total() }})
        </summary>
        <div class="p-6 border-t border-gray-100">
            @include('dean.enrollments._table', ['terms' => $past, 'empty' => 'No past terms.'])
            <div class="mt-4">
                {{ $past->links() }}
            </div>
        </div>
    </details>

</x-sidebar-layout>
