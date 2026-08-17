<x-sidebar-layout>

    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
            {{ session('success') }}
        </div>
    @endif



    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Sections</h2>
        <a href="{{ route('dean.sections.create') }}" class="px-4 py-2 text-sm font-medium text-white rounded hover:opacity-90" style="background-color: #c8a97e;">+ Create Section</a>
    </div>

    @forelse($sections as $yearLevel => $sectionGroup)
        <div class="mb-8">
            <h3 class="mb-3 text-sm font-semibold tracking-wider text-gray-500 uppercase">{{ $yearLevel }}</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($sectionGroup as $section)
                    @php
                        $currentTerm = $section->terms->where('status', 'active')->first();
                        $enrolledCount = $currentTerm ? $currentTerm->enrollments->count() : 0;
                        $termSubjects = $currentTerm ? $currentTerm->subjects : collect();
                        $availableSubjects = $allSubjects->whereNotIn('id', $termSubjects->pluck('id'));
                    @endphp
                    <div onclick="openSectionModal({{ $section->id }})"
                         class="overflow-hidden transition bg-white border border-gray-200 rounded-lg shadow-sm cursor-pointer hover:shadow-md">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <div>
                                <div class="font-semibold text-gray-800">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</div>
                                <div class="text-xs text-gray-500">{{ $section->program->department->name }}</div>
                            </div>
                            <span class="px-2 py-1 text-xs rounded {{ $section->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ ucfirst($section->status) }}
                            </span>
                        </div>

                        <div class="px-5 py-4 space-y-2">
                            @if($currentTerm)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Current Term</span>
                                    <span class="font-medium text-gray-700">{{ $currentTerm->semester }}, {{ $currentTerm->academic_year }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Adviser</span>
                                    <span class="font-medium text-gray-700">{{ $currentTerm->adviser?->name ?? '—' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Enrolled</span>
                                    <span class="font-medium text-gray-700">{{ $enrolledCount }} students</span>
                                </div>
                            @else
                                <div class="py-2 text-sm text-center text-gray-400">No active term this semester</div>
                            @endif
                        </div>

                        <div class="px-5 py-3 text-xs text-center text-gray-400 border-t border-gray-100 bg-gray-50">
                            Click card for details
                        </div>
                    </div>

                    {{-- Section Details Modal --}}
                    <div id="sectionModal-{{ $section->id }}" class="fixed inset-0 z-50 hidden overflow-y-auto">
                        <div class="flex items-center justify-center min-h-screen px-4 py-8">
                            <div class="fixed inset-0 bg-black opacity-40" onclick="closeSectionModal({{ $section->id }})"></div>
                            <div class="relative z-10 w-full max-w-2xl bg-white rounded-lg shadow-xl">
                                <div class="flex items-center justify-between px-6 py-4 border-b">
                                    <h3 class="font-semibold text-gray-800">
                                        {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}
                                    </h3>
                                    <button onclick="closeSectionModal({{ $section->id }})" class="text-gray-400 hover:text-gray-600">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between"><span class="text-gray-500">Program</span><span class="font-medium">{{ $section->program->name }} ({{ $section->program->code }})</span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Department</span><span class="font-medium">{{ $section->program->department->name }}</span></div>
                                        <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="font-medium">{{ ucfirst($section->status) }}</span></div>
                                    </div>

                                    @if($currentTerm)
                                        <div class="pt-4 border-t">
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="text-sm font-semibold text-gray-700">{{ $currentTerm->semester }}, {{ $currentTerm->academic_year }}</h4>
                                                <button
                                                    onclick="closeSectionModal({{ $section->id }}); openAdviserModal({{ $section->id }}, '{{ addslashes($section->program->code . ' ' . $section->year_number . '-' . $section->section_letter) }}', {{ $currentTerm?->adviser_id ?? 'null' }}, '{{ $currentTerm?->academic_year ?? '' }}', '{{ $currentTerm?->semester ?? '' }}')"
                                                    class="text-xs text-blue-600 hover:text-blue-800">
                                                    Change Adviser
                                                </button>
                                            </div>
                                            <div class="space-y-1 text-sm">
                                                <div class="flex justify-between"><span class="text-gray-500">Adviser</span><span class="font-medium">{{ $currentTerm->adviser?->name ?? '—' }}</span></div>
                                                <div class="flex justify-between"><span class="text-gray-500">Enrolled</span><span class="font-medium">{{ $enrolledCount }} students</span></div>
                                            </div>
                                        </div>

                                        <div class="pt-4 border-t">
                                            <h4 class="mb-2 text-sm font-semibold text-gray-700">Subjects & Teachers</h4>
                                            <div class="overflow-hidden border border-gray-200 rounded-lg">
                                                <table class="w-full text-sm">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 uppercase">Subject</th>
                                                            <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 uppercase">Teacher</th>
                                                            <th class="px-3 py-2 text-xs font-medium text-left text-gray-500 uppercase">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        @forelse($termSubjects as $subj)
                                                            <tr>
                                                                <td class="px-3 py-2">{{ $subj->code }} — {{ $subj->name }}</td>
                                                                <td class="px-3 py-2">{{ $teachers->firstWhere('id', $subj->pivot->teacher_id)?->name ?? '—' }}</td>
                                                                <td class="px-3 py-2">
                                                                    <form method="POST" action="{{ route('dean.sections.change-subject-teacher', $section) }}" class="flex items-center gap-1">
                                                                        @csrf
                                                                        <input type="hidden" name="subject_id" value="{{ $subj->id }}">
                                                                        <select name="teacher_id" class="px-1 py-1 text-xs border border-gray-300 rounded">
                                                                            @foreach($teachers as $t)
                                                                                <option value="{{ $t->id }}" {{ $subj->pivot->teacher_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        <button type="submit" class="px-2 py-1 text-xs border rounded" style="border-color:#d1d5db;">Update</button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="3" class="px-3 py-3 text-xs text-center text-gray-400">No subjects added yet.</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                                <form method="POST" action="{{ route('dean.sections.attach-subject', $section) }}" class="flex items-center gap-2 px-3 py-3 border-t border-gray-100 bg-gray-50">
                                                    @csrf
                                                    <select name="subject_id" required class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded">
                                                        <option value="">— Select subject —</option>
                                                        @foreach($availableSubjects as $s)
                                                            <option value="{{ $s->id }}">{{ $s->code }} — {{ $s->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="teacher_id" required class="flex-1 px-2 py-1.5 text-xs border border-gray-300 rounded">
                                                        <option value="">— Select teacher —</option>
                                                        @foreach($teachers as $t)
                                                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white rounded" style="background:#1c1814;">Add</button>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <div class="pt-4 text-center border-t">
                                            <div class="py-2 text-sm text-gray-400">No active term this semester.</div>
                                            <button
                                                onclick="closeSectionModal({{ $section->id }}); openAdviserModal({{ $section->id }}, '{{ addslashes($section->program->code . ' ' . $section->year_number . '-' . $section->section_letter) }}', null, '', '')"
                                                class="text-xs text-blue-600 hover:text-blue-800">
                                                Set Term
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between px-6 py-4 border-t bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('dean.sections.edit', $section) }}" class="text-sm text-gray-600 hover:text-gray-800">Edit</a>
                                        <span class="text-gray-300">|</span>
                                        <form action="{{ route('dean.sections.destroy', $section) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800" onclick="return confirm('Delete this section? This cannot be undone if students are enrolled.')">Delete</button>
                                        </form>
                                    </div>
                                    <button onclick="closeSectionModal({{ $section->id }})" class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="py-12 text-center text-gray-500 bg-white rounded-lg shadow-sm">
            No sections found. <a href="{{ route('dean.sections.create') }}" class="text-blue-600 hover:underline">Create one now.</a>
        </div>
    @endforelse

    {{-- Change Adviser Modal --}}
    <div id="adviserModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-40" onclick="closeAdviserModal()"></div>
            <div class="relative z-10 w-full max-w-md bg-white rounded-lg shadow-xl">
                <div class="flex items-center justify-between px-6 py-4 border-b">
                    <h3 class="font-semibold text-gray-800">Change Adviser</h3>
                    <button onclick="closeAdviserModal()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <form id="adviserForm" method="POST">
                    @csrf
                    <div class="px-6 py-4 space-y-4">
                        <p class="text-sm text-gray-600">Section: <span id="modalSectionName" class="font-medium text-gray-800"></span></p>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Academic Year</label>
                            <input type="text" name="academic_year" id="modalAcademicYear" placeholder="e.g. 2024-2025" class="w-full px-3 py-2 border rounded" required>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Semester</label>
                            <select name="semester" id="modalSemester" class="w-full px-3 py-2 border rounded" required>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Adviser</label>
                            <select name="adviser_id" id="modalAdviser" class="w-full px-3 py-2 border rounded" required>
                                <option value="">Select Adviser</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                        <button type="button" onclick="closeAdviserModal()" class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm text-white rounded hover:opacity-90" style="background-color: #c8a97e;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openSectionModal(id) {
            document.getElementById('sectionModal-' + id).classList.remove('hidden');
        }
        function closeSectionModal(id) {
            document.getElementById('sectionModal-' + id).classList.add('hidden');
        }

        function openAdviserModal(sectionId, sectionName, adviserId, academicYear, semester) {
            document.getElementById('modalSectionName').textContent = sectionName;
            document.getElementById('adviserForm').action = '/dean/sections/' + sectionId + '/change-adviser';
            document.getElementById('modalAcademicYear').value = academicYear || '';
            document.getElementById('modalSemester').value = semester || '1st Semester';
            if (adviserId) {
                document.getElementById('modalAdviser').value = adviserId;
            }
            document.getElementById('adviserModal').classList.remove('hidden');
        }

        function closeAdviserModal() {
            document.getElementById('adviserModal').classList.add('hidden');
        }
    </script>

</x-sidebar-layout>
