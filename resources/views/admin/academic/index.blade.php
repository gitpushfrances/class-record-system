<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Academic Year / Semester') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto space-y-6 sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="px-4 py-3 text-green-700 bg-green-100 border border-green-400 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="px-4 py-3 text-red-700 bg-red-100 border border-red-400 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Add New Period --}}
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h3 class="mb-4 text-lg font-semibold">Add Academic Period</h3>
                <form method="POST" action="{{ route('admin.academic.store') }}" class="flex items-end gap-4">
                    @csrf
                    <div>
                        <label class="block mb-1 text-sm text-gray-600">School Year</label>
                        <input type="text" name="school_year" placeholder="e.g. 2025-2026"
                            class="border-gray-300 rounded-md shadow-sm" value="{{ old('school_year') }}" required>
                        @error('school_year')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-sm text-gray-600">Semester</label>
                        <select name="semester" class="border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select</option>
                            <option value="1st Semester">1st Semester</option>
                            <option value="2nd Semester">2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                        @error('semester')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                        Add Period
                    </button>
                </form>
            </div>

            {{-- Periods List --}}
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <h3 class="mb-4 text-lg font-semibold">All Academic Periods</h3>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">School Year</th>
                            <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Semester</th>
                            <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($periods as $period)
                            <tr>
                                <td class="px-4 py-3">{{ $period->school_year }}</td>
                                <td class="px-4 py-3">{{ $period->semester }}</td>
                                <td class="px-4 py-3">
                                    @if($period->is_active)
                                        <span class="px-2 py-1 text-xs text-green-800 bg-green-100 rounded">Active</span>
                                    @else
                                        <span class="px-2 py-1 text-xs text-gray-600 bg-gray-100 rounded">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 space-x-2">
                                    @if(!$period->is_active)
                                        <form action="{{ route('admin.academic.setActive', $period) }}" method="POST" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-sm text-blue-600 hover:text-blue-900">Set Active</button>
                                        </form>
                                        <form action="{{ route('admin.academic.destroy', $period) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Delete this period?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-center text-gray-500">No periods added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
