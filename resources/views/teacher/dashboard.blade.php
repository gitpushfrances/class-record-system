<x-sidebar-layout>
<style>
    .class-card > summary { list-style: none; cursor: pointer; }
    .class-card > summary::-webkit-details-marker { display: none; }
    .class-chevron { transition: transform .15s; }
    .class-card[open] .class-chevron { transform: rotate(90deg); }
</style>

<div class="mb-6">
    <h1 class="text-2xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">My Classes</h1>
    <p class="mt-1 text-sm text-gray-500">Welcome back, {{ auth()->user()->display_name }}</p>
</div>

@if($classes->isEmpty())
    <div class="p-12 text-center bg-white border border-gray-200 rounded-xl">
        <div class="flex items-center justify-center mx-auto mb-4 rounded-full w-14 h-14" style="background:rgba(200,169,126,0.12);">
            <i class="text-xl fa-solid fa-chalkboard-user" style="color:#c8a97e;"></i>
        </div>
        <p class="text-sm font-medium text-gray-700">No subject classes assigned yet</p>
        <p class="mt-1 text-sm text-gray-500">Contact your Dean or Program Head to get assigned to a subject. Advisory classes are under My Advisory.</p>
    </div>
@else
    <div style="display:flex; flex-direction:column; gap:16px;">
        @foreach($classes as $class)
            @php
                $term    = $class['term'];
                $subject = $class['subject'];
                $section = $term->section;
                $roster  = $class['roster'];
                $groups  = ['Male' => $roster['male'], 'Female' => $roster['female']];
                if ($roster['other']->isNotEmpty()) {
                    $groups['No gender set'] = $roster['other'];
                }
                $total = $roster['male']->count() + $roster['female']->count() + $roster['other']->count();
            @endphp
            <details class="class-card overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl" @if($loop->first) open @endif>
                <summary style="padding:16px 20px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                        <div style="display:flex; align-items:flex-start; gap:12px;">
                            <i class="class-chevron fa-solid fa-chevron-right" style="margin-top:6px; font-size:12px; color:#c8a97e;"></i>
                            <div>
                                <h3 class="font-bold text-gray-800" style="font-size:16px;">{{ $subject->code }} — {{ $subject->name }}</h3>
                                <div style="margin-top:4px; display:flex; flex-wrap:wrap; gap:4px 16px; font-size:13px; color:#6b7280;">
                                    <span>Year &amp; Section: <strong style="color:#1f2937;">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</strong></span>
                                    <span>Semester: <strong style="color:#1f2937;">{{ $term->semester }}</strong></span>
                                    <span>S.Y.: <strong style="color:#1f2937;">{{ $term->academic_year }}</strong></span>
                                    <span>{{ $total }} {{ \Illuminate\Support\Str::plural('student', $total) }}</span>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('teacher.classes.record', [$section, $subject]) }}"
                           onclick="event.stopPropagation()"
                           style="display:inline-block; padding:8px 16px; font-size:14px; font-weight:600; border-radius:8px; text-decoration:none; background:linear-gradient(135deg,#9a7a50,#c8a97e); color:#1c1814;">
                            Open Class
                        </a>
                    </div>
                </summary>

                <div style="padding:0 20px 20px;">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        @foreach($groups as $label => $rows)
                            <div class="overflow-hidden border border-gray-200 rounded-lg">
                                <div class="px-4 py-2 text-xs font-semibold tracking-wider text-gray-600 uppercase" style="background:#f9fafb;">
                                    {{ $label }} ({{ $rows->count() }})
                                </div>
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200">
                                            <th class="px-4 py-2 text-xs font-semibold text-left text-gray-500" style="width:110px;">Stud. ID</th>
                                            <th class="px-4 py-2 text-xs font-semibold text-left text-gray-500">Names</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($rows as $enrollment)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-600">{{ $enrollment->student?->student_number }}</td>
                                                <td class="px-4 py-2 text-gray-800">{{ $enrollment->student?->full_name }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="px-4 py-3 text-xs text-gray-400">No students.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>
            </details>
        @endforeach
    </div>
@endif
</x-sidebar-layout>
