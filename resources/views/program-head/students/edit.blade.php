<x-sidebar-layout>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('program-head.students.update', $student) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-4 mb-4 sm:grid-cols-2">
                            <div>
                                <label class="block mb-2 text-sm font-bold text-gray-700">Last Name</label>
                                <input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}" class="w-full px-3 py-2 border rounded" required>
                                @error('last_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-bold text-gray-700">First Name</label>
                                <input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}" class="w-full px-3 py-2 border rounded" required>
                                @error('first_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Middle Name <span class="font-normal text-gray-400">(optional)</span></label>
                            <input type="text" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}" class="w-full px-3 py-2 border rounded">
                            @error('middle_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Student Number</label>
                            <input type="text" name="student_number" value="{{ old('student_number', $student->student_number) }}" inputmode="numeric" pattern="[0-9]*" class="w-full px-3 py-2 font-mono border rounded" required>
                            @error('student_number')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-2">
                            <div>
                                <label class="block mb-2 text-sm font-bold text-gray-700">Year Level</label>
                                <select name="year_level" class="w-full px-3 py-2 border rounded" required>
                                    @foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $level)
                                        <option value="{{ $level }}" {{ old('year_level', $student->year_level) == $level ? 'selected' : '' }}>{{ $level }}</option>
                                    @endforeach
                                </select>
                                @error('year_level')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-bold text-gray-700">Student Type</label>
                                <select name="student_type" class="w-full px-3 py-2 border rounded" required>
                                    <option value="regular" {{ old('student_type', $student->student_type) == 'regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="irregular" {{ old('student_type', $student->student_type) == 'irregular' ? 'selected' : '' }}>Irregular</option>
                                </select>
                                @error('student_type')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Email <span class="font-normal text-gray-400">(optional)</span></label>
                            <input type="email" name="email" value="{{ old('email', $student->email) }}" class="w-full px-3 py-2 border rounded">
                            @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">Update Student</button>
                            <a href="{{ route('program-head.students.index') }}" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</x-sidebar-layout>
