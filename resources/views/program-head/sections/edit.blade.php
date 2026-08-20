<x-sidebar-layout>

    <div class="max-w-xl mx-auto">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Edit Section</h2>
            <p class="text-sm text-gray-500">Update section details. Adviser and term info are managed separately.</p>
        </div>

        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-6">
                @if($errors->any())
                    <div class="px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form id="editSectionForm" action="{{ route('program-head.sections.update', $section) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Year</label>
                            <select name="year_number" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-1" required>
                                @foreach(['1','2','3','4','5'] as $y)
                                    <option value="{{ $y }}" {{ old('year_number', $section->year_number) == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                                @endforeach
                            </select>
                            @error('year_number')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Section Letter</label>
                            <select name="section_letter" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-1" required>
                                @foreach(['A','B','C','D','E','F'] as $l)
                                    <option value="{{ $l }}" {{ old('section_letter', $section->section_letter) == $l ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                            @error('section_letter')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Year Level</label>
                        <select name="year_level" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-1" required>
                            @foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $level)
                                <option value="{{ $level }}" {{ old('year_level', $section->year_level) == $level ? 'selected' : '' }}>{{ $level }}</option>
                            @endforeach
                        </select>
                        @error('year_level')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-6">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-1" required>
                            <option value="active" {{ old('status', $section->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $section->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded hover:opacity-90" style="background-color: #c8a97e;">Save Changes</button>
                        <a href="{{ route('program-head.sections.index') }}" class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-sidebar-layout>
