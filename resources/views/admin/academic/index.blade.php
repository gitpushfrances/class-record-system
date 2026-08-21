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

    <div class="max-w-3xl mx-auto space-y-6">

        <h2 class="text-lg font-semibold text-gray-800">Academic Periods</h2>

        {{-- Add Period Form --}}
        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Add New Period</h3>
            </div>
            <div class="px-6 py-4">
                <form action="{{ route('admin.academic.store') }}" method="POST" class="flex flex-wrap items-end gap-4">
                    @csrf
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">School Year</label>
                        <input type="text" name="school_year" placeholder="e.g. 2024-2025"
                               value="{{ old('school_year') }}"
                               class="w-40 px-3 py-2 border rounded focus:outline-none focus:ring-1" required>
                        @error('school_year')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Semester</label>
                        <select name="semester" class="px-3 py-2 border rounded focus:outline-none focus:ring-1" required>
                            <option value="">Select</option>
                            <option value="1st Semester" {{ old('semester') == '1st Semester' ? 'selected' : '' }}>1st Semester</option>
                            <option value="2nd Semester" {{ old('semester') == '2nd Semester' ? 'selected' : '' }}>2nd Semester</option>
                            <option value="Summer" {{ old('semester') == 'Summer' ? 'selected' : '' }}>Summer</option>
                        </select>
                        @error('semester')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Midterm Cutoff</label>
                        <input type="date" name="midterm_cutoff_date" value="{{ old('midterm_cutoff_date') }}"
                               class="px-3 py-2 border rounded focus:outline-none focus:ring-1" required>
                        @error('midterm_cutoff_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Finals Cutoff</label>
                        <input type="date" name="finals_cutoff_date" value="{{ old('finals_cutoff_date') }}"
                               class="px-3 py-2 border rounded focus:outline-none focus:ring-1" required>
                        @error('finals_cutoff_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded hover:opacity-90" style="background-color: #c8a97e;">
                        Add Period
                    </button>
                </form>
            </div>
        </div>

        {{-- Periods List --}}
        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">All Periods</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">School Year</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Semester</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Midterm Cutoff</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Finals Cutoff</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($periods as $period)
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $period->school_year }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $period->semester }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $period->midterm_cutoff_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $period->finals_cutoff_date?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @if($period->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded">Active</span>
                                @else
                                    <span class="px-2 py-1 text-xs text-gray-500 bg-gray-100 rounded">Inactive</span>
                                @endif
                            </td>
                            <td class="flex items-center gap-3 px-6 py-4">
                                @if(!$period->is_active)
                                    <form action="{{ route('admin.academic.setActive', $period) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm text-blue-600 hover:text-blue-800">Set Active</button>
                                    </form>
                                    <span class="text-gray-300">|</span>
                                    <form action="{{ route('admin.academic.destroy', $period) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800"
                                                onclick="return confirm('Delete this period?')">Delete</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">Currently active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No periods found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</x-sidebar-layout>
