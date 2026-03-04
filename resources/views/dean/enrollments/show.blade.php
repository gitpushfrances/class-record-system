<x-sidebar-layout>

    <div class="py-12">
        <div class="mx-auto space-y-6 max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="px-4 py-3 text-green-700 bg-green-100 border border-green-400 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Section Info -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h3 class="mb-4 text-lg font-semibold">Section Information</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="font-medium">Subject:</span> {{ $section->subject->code }} - {{ $section->subject->name }}</div>
                    <div><span class="font-medium">Teacher:</span> {{ $section->teacher->name }}</div>
                    <div><span class="font-medium">Year Level:</span> {{ $section->year_level }}</div>
                    <div><span class="font-medium">Semester:</span> {{ $section->semester }}</div>
                    <div><span class="font-medium">Schedule:</span> {{ $section->schedule ?? 'TBA' }}</div>
                    <div><span class="font-medium">Room:</span> {{ $section->room ?? 'TBA' }}</div>
                </div>
            </div>

            <!-- Add Students -->
            @if($availableStudents->isNotEmpty())
                <div class="p-6 bg-white rounded-lg shadow">
                    <h3 class="mb-4 text-lg font-semibold">Enroll Students</h3>
                    <form action="{{ route('dean.enrollments.store', $section) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-bold text-gray-700">Select Students</label>
                            <select name="student_ids[]" multiple size="10" class="w-full px-3 py-2 border rounded">
                                @foreach($availableStudents as $student)
                                    <option value="{{ $student->id }}">
                                        {{ $student->student_number }} - {{ $student->full_name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple students</p>
                            @error('student_ids')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                            Enroll Selected Students
                        </button>
                    </form>
                </div>
            @endif

            <!-- Enrolled Students -->
            <div class="p-6 bg-white rounded-lg shadow">
                <h3 class="mb-4 text-lg font-semibold">Enrolled Students ({{ $section->enrollments->count() }})</h3>

                @if($section->enrollments->isEmpty())
                    <p class="text-gray-500">No students enrolled yet.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Student Number</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Year Level</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($section->enrollments as $enrollment)
                                @if($enrollment->student)
                                <tr>
                                    <td class="px-6 py-4">{{ $enrollment->student->student_number }}</td>
                                    <td class="px-6 py-4">{{ $enrollment->student->full_name }}</td>
                                    <td class="px-6 py-4">{{ $enrollment->student->year_level }}</td>
                                    <td class="px-6 py-4">
                                        <form action="{{ route('dean.enrollments.destroy', [$section, $enrollment]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Remove this student from the section?')">
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div>
                <a href="{{ route('dean.enrollments.index') }}" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                    Back to All Sections
                </a>
            </div>
</x-sidebar-layout>
