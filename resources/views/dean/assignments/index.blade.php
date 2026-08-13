<x-sidebar-layout>
    <div class="p-6 space-y-6">

        <div>
            <h1 class="text-2xl font-bold" style="color: #f0dfc0;">Teacher Assignments</h1>
            <p class="mt-1 text-sm" style="color: rgba(200,169,126,0.6);">Assign teachers to subjects per section.</p>
        </div>

        @if(session('success'))
            <div class="px-4 py-3 text-sm rounded-lg" style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #86efac;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="px-4 py-3 text-sm rounded-lg" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
                {{ session('error') }}
            </div>
        @endif

        {{-- Add Assignment Form --}}
        <div class="p-5 rounded-xl" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <h2 class="mb-4 text-base font-semibold" style="color: #c8a97e;">New Assignment</h2>
            <form method="POST" action="{{ route('dean.assignments.store') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
                @csrf
                <div>
                    <label class="block mb-1 text-xs font-medium" style="color: rgba(200,169,126,0.7);">Section</label>
                    <select name="section_id" required
                            class="w-full px-3 py-2 text-sm rounded-lg"
                            style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium" style="color: rgba(200,169,126,0.7);">Subject</label>
                    <select name="subject_id" required
                            class="w-full px-3 py-2 text-sm rounded-lg"
                            style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                        <option value="">Select Subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->code }} — {{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium" style="color: rgba(200,169,126,0.7);">Teacher</label>
                    <select name="teacher_id" required
                            class="w-full px-3 py-2 text-sm rounded-lg"
                            style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                        <option value="">Select Teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit"
                            class="w-full px-4 py-2 text-sm font-medium rounded-lg"
                            style="background: linear-gradient(135deg, #9a7a50, #c8a97e); color: #1c1814;">
                        Assign
                    </button>
                </div>
            </form>
        </div>

        {{-- Assignments Table --}}
        <div class="p-5 rounded-xl" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <h2 class="mb-4 text-base font-semibold" style="color: #c8a97e;">Current Assignments</h2>
            @if($assignments->isEmpty())
                <p class="text-sm" style="color: rgba(200,169,126,0.5);">No assignments yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(200,169,126,0.15);">
                                <th class="px-3 py-2 text-left" style="color: rgba(200,169,126,0.6);">Section</th>
                                <th class="px-3 py-2 text-left" style="color: rgba(200,169,126,0.6);">Subject</th>
                                <th class="px-3 py-2 text-left" style="color: rgba(200,169,126,0.6);">Teacher</th>
                                <th class="px-3 py-2 text-left" style="color: rgba(200,169,126,0.6);">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignments as $a)
                            <tr style="border-bottom: 1px solid rgba(200,169,126,0.07);">
                                <td class="px-3 py-2" style="color: #f0dfc0;">
                                    {{ $a->program_code }} {{ $a->year_number }}-{{ $a->section_letter }}
                                    <span class="block text-xs" style="color: rgba(200,169,126,0.5);">{{ $a->semester }}, {{ $a->academic_year }}</span>
                                </td>
                                <td class="px-3 py-2" style="color: #f0dfc0;">{{ $a->subject_code }} — {{ $a->subject_name }}</td>
                                <td class="px-3 py-2" style="color: #f0dfc0;">{{ $a->teacher_name }}</td>
                                <td class="px-3 py-2">
                                    <form method="POST" action="{{ route('dean.assignments.destroy', $a->id) }}" class="remove-assignment-form">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" class="assignment-label" value="{{ $a->subject_code }} — {{ $a->teacher_name }} ({{ $a->program_code }} {{ $a->year_number }}-{{ $a->section_letter }})">
                                        <button type="submit"
                                                class="px-3 py-1 text-xs rounded"
                                                style="background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2);">
                                            Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.remove-assignment-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const label = form.querySelector('.assignment-label').value;
                if (typeof Swal === 'undefined') {
                    if (confirm('Remove this assignment?\n' + label)) form.submit();
                    return;
                }
                Swal.fire({
                    title: 'Remove this assignment?',
                    text: label,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, remove it',
                    cancelButtonText: 'Cancel',
                }).then(result => { if (result.isConfirmed) form.submit(); });
            });
        });
    </script>
</x-sidebar-layout>
