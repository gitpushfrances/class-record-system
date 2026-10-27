<x-sidebar-layout>

    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('adviser_conflict'))
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const conflict = @json(session('adviser_conflict'));

        function proceed() {
            document.getElementById('confirmAdviserAdviserId').value = conflict.adviser_id;
            document.getElementById('confirmAdviserForm').action = '/program-head/sections/' + conflict.section_id + '/change-adviser';
            document.getElementById('confirmAdviserForm').submit();
        }

        if (typeof Swal === 'undefined') {
            const list = conflict.sections.join('\n - ');
            if (confirm(conflict.teacher_name + ' is already the adviser for:\n - ' + list + '\n\nAssign them here as well?')) {
                proceed();
            }
        } else {
            const rows = conflict.sections.map(s => {
                const parts = s.match(/^(.*)\s\((.*)\)$/);
                const name = parts ? parts[1] : s;
                const term = parts ? parts[2] : '';
                return '<div style="display:flex;justify-content:space-between;align-items:center;' +
                       'padding:10px 14px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:8px;">' +
                       '<span style="font-weight:600;color:#0b3d0b;">' + name + '</span>' +
                       '<span style="font-size:12px;color:#6b7280;">' + term + '</span>' +
                       '</div>';
            }).join('');

            Swal.fire({
                title: 'Already an Adviser',
                html: '<p style="margin-bottom:14px;color:#374151;">' +
                      '<strong>' + conflict.teacher_name + '</strong> is already advising:</p>' +
                      '<div style="text-align:left;max-height:180px;overflow-y:auto;">' + rows + '</div>' +
                      '<p style="margin-top:14px;color:#374151;">Assign them to this section as well?</p>',
                icon: 'warning',
                confirmButtonColor: '#d4af37',
                cancelButtonColor: '#6b7280',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) proceed();
            });
        }
    });
    </script>
    @endif

    @if(session('term_close_warning'))
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const warning = @json(session('term_close_warning'));
        const base    = @json(url('program-head/sections'));
        const text    = 'Starting the new term closes the current one. These subjects are not verified, and they can no longer be submitted or verified once the term is closed:';

        function proceed() {
            const form = document.getElementById('confirmAdviserForm');
            document.getElementById('confirmAdviserAdviserId').value = warning.adviser_id;
            document.getElementById('confirmAdviserClose').value = '1';
            form.action = base + '/' + warning.section_id + '/change-adviser';
            form.submit();
        }

        if (typeof Swal === 'undefined') {
            if (confirm(text + '\n - ' + warning.subjects.join('\n - ') + '\n\nClose the term anyway?')) proceed();
            return;
        }

        const box  = document.createElement('div');
        const intro = document.createElement('p');
        const list = document.createElement('ul');
        intro.textContent = text;
        intro.style.cssText = 'color:#374151;text-align:left;';
        list.style.cssText = 'margin:12px 0 0;padding-left:20px;list-style:disc;text-align:left;color:#0b3d0b;font-weight:600;';
        warning.subjects.forEach(function (s) { const li = document.createElement('li'); li.textContent = s; list.appendChild(li); });
        box.append(intro, list);

        Swal.fire({
            title: 'Subjects Not Verified',
            html: box,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Close Term Anyway',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#b91c1c',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
        }).then(function (result) { if (result.isConfirmed) proceed(); });
    });
    </script>
    @endif

    <form id="confirmAdviserForm" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="confirmed" value="1">
        <input type="hidden" name="adviser_id" id="confirmAdviserAdviserId">
        <input type="hidden" name="close_confirmed" id="confirmAdviserClose" value="0">
    </form>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Sections</h2>
        <a href="{{ route('program-head.sections.create') }}" class="px-4 py-2 text-sm font-medium text-white rounded hover:opacity-90" style="background-color: #d4af37;">+ Create Section</a>
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
                        $availableSubjects = $currentTerm ? \App\Services\SubjectOffering::forTerm($currentTerm) : collect();
                    @endphp
                    <div onclick="openSectionModal({{ $section->id }})"
                         class="overflow-hidden transition bg-white border border-gray-200 rounded-lg shadow-sm cursor-pointer hover:shadow-md">
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <div>
                                <div class="font-semibold text-gray-800">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</div>
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
                                        <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="font-medium">{{ ucfirst($section->status) }}</span></div>
                                    </div>

                                    @if($currentTerm)
                                        <div class="pt-4 border-t">
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="text-sm font-semibold text-gray-700">{{ $currentTerm->semester }}, {{ $currentTerm->academic_year }}</h4>
                                                <button
                                                    onclick="closeSectionModal({{ $section->id }}); openAdviserModal({{ $section->id }}, '{{ addslashes($section->program->code . ' ' . $section->year_number . '-' . $section->section_letter) }}', {{ $currentTerm?->adviser_id ?? 'null' }})"
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
                                            @include('program-head.sections.partials.subjects', ['subjects' => $termSubjects])
                                        </div>
                                    @else
                                        <div class="pt-4 text-center border-t">
                                            <div class="py-2 text-sm text-gray-400">No active term this semester.</div>
                                            <button
                                                onclick="closeSectionModal({{ $section->id }}); openAdviserModal({{ $section->id }}, '{{ addslashes($section->program->code . ' ' . $section->year_number . '-' . $section->section_letter) }}', null)"
                                                class="text-xs text-blue-600 hover:text-blue-800">
                                                Set Term
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between px-6 py-4 border-t bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('program-head.sections.edit', $section) }}" class="text-sm text-gray-600 hover:text-gray-800">Edit</a>
                                        <span class="text-gray-300">|</span>
                                        <form action="{{ route('program-head.sections.destroy', $section) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-800" onclick="return confirm('Delete this section? This cannot be undone if students are enrolled.')">Delete</button>
                                        </form>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('program-head.sections.show', $section) }}" class="px-4 py-2 text-sm font-semibold text-white rounded hover:opacity-90" style="background:#0a5b05;">
                                            <i class="fa-solid fa-users"></i> Manage Students
                                        </a>
                                        <button onclick="closeSectionModal({{ $section->id }})" class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="py-12 text-center text-gray-500 bg-white rounded-lg shadow-sm">
            No sections found. <a href="{{ route('program-head.sections.create') }}" class="text-blue-600 hover:underline">Create one now.</a>
        </div>
    @endforelse

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

                        @if($activePeriod)
                            <div class="px-3 py-2 text-sm rounded bg-gray-50" style="border:1px solid #e5e7eb;">
                                <span class="text-gray-500">Setting term for:</span>
                                <span class="font-medium text-gray-800">{{ $activePeriod->semester }}, {{ $activePeriod->school_year }}</span>
                            </div>
                        @else
                            <div class="px-3 py-2 text-sm text-red-700 rounded bg-red-50" style="border:1px solid #fecaca;">
                                No active academic period set. Contact your Administrator before assigning advisers.
                            </div>
                        @endif

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Adviser</label>
                            <select name="adviser_id" id="modalAdviser" class="w-full px-3 py-2 border rounded" required {{ $activePeriod ? '' : 'disabled' }}>
                                <option value="">Select Adviser</option>
                                @foreach($teachers->whereNull('borrowed_from') as $teacher)
                                    <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 px-6 py-4 border-t bg-gray-50">
                        <button type="button" onclick="closeAdviserModal()" class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm text-white rounded hover:opacity-90" style="background-color: #d4af37;" {{ $activePeriod ? '' : 'disabled' }}>Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openSectionModal(id) { document.getElementById('sectionModal-' + id).classList.remove('hidden'); }
        function closeSectionModal(id) { document.getElementById('sectionModal-' + id).classList.add('hidden'); }
        function openAdviserModal(sectionId, sectionName, adviserId) {
            document.getElementById('modalSectionName').textContent = sectionName;
            document.getElementById('adviserForm').action = '/program-head/sections/' + sectionId + '/change-adviser';
            if (adviserId) document.getElementById('modalAdviser').value = adviserId;
            document.getElementById('adviserModal').classList.remove('hidden');
        }
        function closeAdviserModal() { document.getElementById('adviserModal').classList.add('hidden'); }
    </script>

</x-sidebar-layout>
