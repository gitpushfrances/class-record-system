<x-sidebar-layout>
    <div class="p-6 space-y-6">

        <div>
            <h1 class="text-2xl font-bold" style="color: #f0dfc0;">Teacher Assignments</h1>
            <p class="text-sm mt-1" style="color: rgba(200,169,126,0.6);">Assign teachers to subjects per section.</p>
        </div>

        @if(session('success'))
            <div class="px-4 py-3 rounded-lg text-sm" style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #86efac;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="px-4 py-3 rounded-lg text-sm" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5;">
                {{ session('error') }}
            </div>
        @endif

        {{-- Add Assignment Form --}}
        <div class="rounded-xl p-5" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <h2 class="text-base font-semibold mb-4" style="color: #c8a97e;">New Assignment</h2>
            <form method="POST" action="{{ route('dean.assignments.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: rgba(200,169,126,0.7);">Section</label>
                    <select name="section_id" required
                            class="w-full px-3 py-2 rounded-lg text-sm"
                            style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: rgba(200,169,126,0.7);">Subject</label>
                    <select name="subject_id" required
                            class="w-full px-3 py-2 rounded-lg text-sm"
                            style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                        <option value="">Select Subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->code }} — {{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: rgba(200,169,126,0.7);">Teacher</label>
                    <select name="teacher_id" required
                            class="w-full px-3 py-2 rounded-lg text-sm"
                            style="background: rgba(200,169,126,0.07); border: 1px solid rgba(200,169,126,0.2); color: #f0dfc0;">
                        <option value="">Select Teacher</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit"
                            class="w-full px-4 py-2 rounded-lg text-sm font-medium"
                            style="background: linear-gradient(135deg, #9a7a50, #c8a97e); color: #1c1814;">
                        Assign
                    </button>
                </div>
            </form>
        </div>

        {{-- Assignments Table --}}
        <div class="rounded-xl p-5" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
            <h2 class="text-base font-semibold mb-4" style="color: #c8a97e;">Current Assignments</h2>
            @if($assignments->isEmpty())
                <p class="text-sm" style="color: rgba(200,169,126,0.5);">No assignments yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(200,169,126,0.15);">
                                <th class="text-left py-2 px-3" style="color: rgba(200,169,126,0.6);">Section</th>
                                <th class="text-left py-2 px-3" style="color: rgba(200,169,126,0.6);">Subject</th>
                                <th class="text-left py-2 px-3" style="color: rgba(200,169,126,0.6);">Teacher</th>
                                <th class="text-left py-2 px-3" style="color: rgba(200,169,126,0.6);">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignments as $a)
                            <tr style="border-bottom: 1px solid rgba(200,169,126,0.07);">
                                <td class="py-2 px-3" style="color: #f0dfc0;">{{ $a->program_code }} {{ $a->year_number }}-{{ $a->section_letter }}</td>
                                <td class="py-2 px-3" style="color: #f0dfc0;">{{ $a->subject_code }} — {{ $a->subject_name }}</td>
                                <td class="py-2 px-3" style="color: #f0dfc0;">{{ $a->teacher_name }}</td>
                                <td class="py-2 px-3">
                                    <form method="POST" action="{{ route('dean.assignments.destroy', $a->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1 rounded text-xs"
                                                style="background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2);"
                                                onclick="return confirm('Remove this assignment?')">
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
</x-sidebar-layout>
