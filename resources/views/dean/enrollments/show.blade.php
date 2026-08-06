<x-sidebar-layout>

    <div class="max-w-4xl mx-auto space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('dean.enrollments.index') }}" class="text-sm text-indigo-600 hover:underline">← Back to Enrollments</a>
                <h1 class="mt-1 text-2xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">
                    {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">{{ $section->year_level }}</p>
            </div>
        </div>

        @if($currentTerm)
            {{-- Section Term Info --}}
            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700">Current Term</h3>
                </div>
                <div class="grid grid-cols-2 gap-4 px-6 py-4 text-sm">
                    <div><span class="text-gray-500">Semester:</span> <span class="font-medium text-gray-800">{{ $currentTerm->semester }}</span></div>
                    <div><span class="text-gray-500">Academic Year:</span> <span class="font-medium text-gray-800">{{ $currentTerm->academic_year }}</span></div>
                    <div><span class="text-gray-500">Adviser:</span> <span class="font-medium text-gray-800">{{ $currentTerm->adviser?->name ?? '—' }}</span></div>
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
                        <form action="{{ route('dean.enrollments.store', $section) }}" method="POST" class="flex items-end gap-3">
                            @csrf
                            <input type="hidden" name="academic_year" value="{{ $currentTerm->academic_year }}">
                            <input type="hidden" name="semester" value="{{ $currentTerm->semester }}">
                            <div class="flex-1">
                                <label class="block mb-1 text-sm font-medium text-gray-700">Select Student</label>
                                <select name="student_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1" required>
                                    <option value="">— Choose a student —</option>
                                    @foreach($availableStudents as $student)
                                        <option value="{{ $student->id }}">
                                            {{ $student->student_number }} — {{ $student->last_name }}, {{ $student->first_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('student_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-semibold rounded-lg"
                                    style="background:linear-gradient(135deg,#9a7a50,#c8a97e); color:#1c1814;">
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
                        <thead style="background:#f9fafb;">
                            <tr>
                                <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Student No.</th>
                                <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Year Level</th>
                                <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-xs font-semibold text-center text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($currentTerm->enrollments as $enrollment)
                                @if($enrollment->student)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-mono text-sm text-gray-700">{{ $enrollment->student->student_number }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $enrollment->student->year_level }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $enrollment->status === 'enrolled' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ ucfirst($enrollment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center">
                                            <form action="{{ route('dean.enrollments.destroy', [$section, $enrollment]) }}" method="POST" class="remove-enrollment-form">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" class="enrollment-label" value="{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }} ({{ $enrollment->student->student_number }})">
                                                <button type="submit" title="Remove"
                                                        class="flex items-center justify-center w-8 h-8 rounded-lg transition hover:opacity-80"
                                                        style="color:#dc2626; background:rgba(239,68,68,0.15); border:1px solid rgba(239,68,68,0.3);">
                                                    <i class="text-xs fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.remove-enrollment-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const label = form.querySelector('.enrollment-label').value;
                Swal.fire({
                    title: 'Remove this student?',
                    text: label,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, remove',
                    cancelButtonText: 'Cancel',
                }).then(result => { if (result.isConfirmed) form.submit(); });
            });
        });
    </script>
</x-sidebar-layout>
