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

    <div class="max-w-4xl mx-auto space-y-6">

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-800">
                {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
                — {{ $section->year_level }}
            </h2>
            <a href="{{ route('dean.enrollments.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
        </div>

        @if($currentTerm)
            {{-- Section Term Info --}}
            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Current Term</h3>
                </div>
                <div class="grid grid-cols-2 gap-4 px-6 py-4 text-sm">
                    <div><span class="text-gray-500">Semester:</span> <span class="font-medium">{{ $currentTerm->semester }}</span></div>
                    <div><span class="text-gray-500">Academic Year:</span> <span class="font-medium">{{ $currentTerm->academic_year }}</span></div>
                    <div><span class="text-gray-500">Adviser:</span> <span class="font-medium">{{ $currentTerm->adviser?->name ?? '—' }}</span></div>
                    <div><span class="text-gray-500">Status:</span>
                        <span class="px-2 py-1 text-xs rounded {{ $currentTerm->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($currentTerm->status) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Enroll Students --}}
            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Enroll Students</h3>
                </div>
                <div class="px-6 py-4">
                    @if($availableStudents->isNotEmpty())
                        <form action="{{ route('dean.enrollments.store', $section) }}" method="POST">
                            @csrf
                            <input type="hidden" name="academic_year" value="{{ $currentTerm->academic_year }}">
                            <input type="hidden" name="semester" value="{{ $currentTerm->semester }}">
                            <div class="mb-4">
                                <label class="block mb-1 text-sm font-medium text-gray-700">Select Student</label>
                                <select name="student_id" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-1" required>
                                    <option value="">— Choose a student —</option>
                                    @foreach($availableStudents as $student)
                                        <option value="{{ $student->id }}">
                                            {{ $student->student_number }} — {{ $student->last_name }}, {{ $student->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white rounded hover:opacity-90" style="background-color: #c8a97e;">
                                Enroll Student
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-gray-500">All students are already enrolled in this term.</p>
                    @endif
                </div>
            </div>

            {{-- Enrolled Students --}}
            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Enrolled Students ({{ $currentTerm->enrollments->count() }})</h3>
                </div>
                @if($currentTerm->enrollments->isEmpty())
                    <div class="px-6 py-4 text-sm text-gray-500">No students enrolled yet.</div>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Student No.</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Year Level</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($currentTerm->enrollments as $enrollment)
                                @if($enrollment->student)
                                <tr>
                                    <td class="px-6 py-4 font-mono text-sm">{{ $enrollment->student->student_number }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $enrollment->student->year_level }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded {{ $enrollment->status === 'enrolled' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($enrollment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <form action="{{ route('dean.enrollments.destroy', [$section, $enrollment]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-900" onclick="return confirm('Remove this student from the enrollment?')">
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

        @else
            <div class="py-8 text-sm text-center text-gray-400 bg-white border border-gray-200 rounded-lg">
                No active term for this section. Set an adviser first to activate a term.
            </div>
        @endif

    </div>

</x-sidebar-layout>
