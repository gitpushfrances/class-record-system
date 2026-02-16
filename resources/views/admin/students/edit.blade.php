<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Edit Student') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('admin.students.update', $student) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Student Number</label>
                            <input type="text" name="student_number" value="{{ old('student_number', $student->student_number) }}" class="w-full px-3 py-2 border rounded" required>
                            @error('student_number')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">First Name</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}" class="w-full px-3 py-2 border rounded" required>
                            @error('first_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Middle Name</label>
                            <input type="text" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}" class="w-full px-3 py-2 border rounded">
                            @error('middle_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Last Name</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}" class="w-full px-3 py-2 border rounded" required>
                            @error('last_name')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Year Level</label>
                            <select name="year_level" class="w-full px-3 py-2 border rounded" required>
                                <option value="">Select Year Level</option>
                                <option value="1" {{ old('year_level', $student->year_level) == '1' ? 'selected' : '' }}>1st Year</option>
                                <option value="2" {{ old('year_level', $student->year_level) == '2' ? 'selected' : '' }}>2nd Year</option>
                                <option value="3" {{ old('year_level', $student->year_level) == '3' ? 'selected' : '' }}>3rd Year</option>
                                <option value="4" {{ old('year_level', $student->year_level) == '4' ? 'selected' : '' }}>4th Year</option>
                            </select>
                            @error('year_level')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                                Update Student
                            </button>
                            <a href="{{ route('admin.students.index') }}" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
