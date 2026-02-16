<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Edit Subject') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.subjects.update', $subject) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Subject Code</label>
                            <input type="text" name="code" value="{{ old('code', $subject->code) }}" class="w-full px-3 py-2 border rounded" required>
                            @error('code')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Subject Name</label>
                            <input type="text" name="name" value="{{ old('name', $subject->name) }}" class="w-full px-3 py-2 border rounded" required>
                            @error('name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Description</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded">{{ old('description', $subject->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Units</label>
                            <input type="number" name="units" value="{{ old('units', $subject->units) }}" min="1" max="10" class="w-full px-3 py-2 border rounded" required>
                            @error('units')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Status</label>
                            <select name="status" class="w-full px-3 py-2 border rounded" required>
                                <option value="active" {{ old('status', $subject->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $subject->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                                Update Subject
                            </button>
                            <a href="{{ route('admin.subjects.index') }}" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
