<x-sidebar-layout>

    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-800">
                {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
            </h2>
            <a href="{{ route('program-head.sections.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
        </div>

        <div class="mb-6 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Section Details</h3>
            </div>
            <div class="px-6 py-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Program</span>
                    <span class="font-medium">{{ $section->program->name }} ({{ $section->program->code }})</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Year Level</span>
                    <span class="font-medium">{{ $section->year_level }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="px-2 py-1 text-xs rounded {{ $section->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                        {{ ucfirst($section->status) }}
                    </span>
                </div>
            </div>
        </div>

        @if($currentTerm)
        <div class="mb-6 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Current Term — {{ $currentTerm->semester }}, {{ $currentTerm->academic_year }}</h3>
            </div>
            <div class="px-6 py-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Adviser</span>
                    <span class="font-medium">{{ $currentTerm->adviser?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Enrolled Students</span>
                    <span class="font-medium">{{ $currentTerm->enrollments->count() }}</span>
                </div>
            </div>
        </div>

        <div class="mb-6 overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Subjects & Teachers</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Subject</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Teacher</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($currentTerm->subjects as $subj)
                        <tr>
                            <td class="px-6 py-4 text-sm">{{ $subj->code }} — {{ $subj->name }}</td>
                            <td class="px-6 py-4 text-sm">{{ $subj->pivot->teacher_id ? \App\Models\User::find($subj->pivot->teacher_id)?->name : '—' }}</td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('program-head.sections.change-subject-teacher', $section) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="subject_id" value="{{ $subj->id }}">
                                    <select name="teacher_id" class="px-2 py-1 text-xs border border-gray-300 rounded-lg">
                                        @foreach($teachers as $t)
                                            <option value="{{ $t->id }}" {{ $subj->pivot->teacher_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="px-3 py-1 text-xs font-medium border rounded-lg" style="border-color:#d1d5db; color:#374151;">Update</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-4 text-sm text-center text-gray-400">No subjects added to this term yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4 border-t border-gray-100">
                <form method="POST" action="{{ route('program-head.sections.attach-subject', $section) }}" class="flex items-center gap-2">
                    @csrf
                    <select name="subject_id" required class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                        <option value="">— Select subject —</option>
                        @foreach($availableSubjects as $s)
                            <option value="{{ $s->id }}">{{ $s->code }} — {{ $s->name }}</option>
                        @endforeach
                    </select>
                    <select name="teacher_id" required class="flex-1 px-2 py-1.5 text-sm border border-gray-300 rounded-lg">
                        <option value="">— Select teacher —</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-1.5 text-sm font-medium text-white rounded-lg" style="background:#1c1814;">Add</button>
                </form>
            </div>
        </div>

        @if($currentTerm->enrollments->count() > 0)
        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Enrolled Students</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Student No.</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($currentTerm->enrollments as $enrollment)
                        <tr>
                            <td class="px-6 py-4 font-mono text-sm">{{ $enrollment->student->student_number }}</td>
                            <td class="px-6 py-4 text-sm">{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded {{ $enrollment->status === 'enrolled' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($enrollment->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @else
        <div class="py-8 text-sm text-center text-gray-400 bg-white border border-gray-200 rounded-lg">
            No active term this semester.
        </div>
        @endif
    </div>

</x-sidebar-layout>
