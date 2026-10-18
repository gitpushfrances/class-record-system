{{--
    Subjects & Teachers of a section's active term (Program Head and Dean section pages).
    Expects $section, $subjects (with pivot), $availableSubjects, $teachers.
    Optional: $attachRoute, $updateRoute (route names; Program Head routes by default), $rogRoute + $term (adds "View ROG" once submitted).
--}}
@php
    $attachRoute = $attachRoute ?? 'program-head.sections.attach-subject';
    $updateRoute = $updateRoute ?? 'program-head.sections.change-subject-teacher';
    $rogRoute    = $rogRoute ?? null;
@endphp
@once
<style>
    .sst-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .sst-table th { padding: 8px 12px; text-align: left; font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .sst-table td { padding: 10px 12px; vertical-align: middle; color: #1f2937; border-bottom: 1px solid #f3f4f6; }
    .sst-muted { color: #9ca3af; }
    .sst-input { display: block; width: 100%; padding: 7px 10px; font-size: 13px; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; }
    .sst-btn { padding: 6px 14px; font-size: 12px; font-weight: 600; border-radius: 8px; cursor: pointer; white-space: nowrap; }
    .sst-btn-light { color: #374151; background: #fff; border: 1px solid #d1d5db; }
    .sst-btn-dark { color: #fff; background: #0b3d0b; border: 1px solid #0b3d0b; }
    .sst-panel { display: grid; gap: 14px; padding: 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; }
    .sst-badge { display: inline-block; margin-left: 6px; padding: 1px 7px; font-size: 10px; font-weight: 700; color: #0a5b05; background: #eef7ec; border-radius: 9999px; white-space: nowrap; }
</style>
@endonce

<div style="overflow-x:auto; border:1px solid #e5e7eb; border-radius:10px;">
    <table class="sst-table">
        <thead>
            <tr><th>Subject</th><th>Teacher</th><th>Schedule</th><th style="width:1%;"></th></tr>
        </thead>
        <tbody>
            @forelse($subjects as $subj)
                @php
                    $rowId = 'sst-' . $section->id . '-' . $subj->id;
                    $label = \App\Services\ClassSchedule::summary($subj->pivot, (bool) $subj->has_laboratory);
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:600;">{{ $subj->code }}@if($subj->has_laboratory)<span class="sst-badge">Lec + Lab</span>@endif</div>
                        <div class="sst-muted" style="font-size:12px;">{{ $subj->name }}</div>
                    </td>
                    <td>
                        {{ $teachers->firstWhere('id', $subj->pivot->teacher_id)?->name ?? \App\Models\User::find($subj->pivot->teacher_id)?->name ?? '—' }}
                        @if($rogRoute && isset($term) && \App\Services\ReportOfGrades::isSubmitted($term, $subj))
                            <a href="{{ route($rogRoute, [$term, $subj]) }}" target="_blank" style="margin-left:6px; font-size:12px; font-weight:600; color:#0a5b05;">View ROG</a>
                        @endif
                    </td>
                    <td>
                        @if($label) {{ $label }} @else <span class="sst-muted">Not set</span> @endif
                    </td>
                    <td style="text-align:right;">
                        <button type="button" class="sst-btn sst-btn-light" aria-controls="{{ $rowId }}"
                                onclick="var r = document.getElementById('{{ $rowId }}'); r.hidden = !r.hidden;">Edit</button>
                    </td>
                </tr>
                <tr id="{{ $rowId }}" hidden>
                    <td colspan="4" style="background:#f9fafb;">
                        <form method="POST" action="{{ route($updateRoute, $section) }}" class="sst-panel" style="background:#fff;">
                            @csrf
                            <input type="hidden" name="subject_id" value="{{ $subj->id }}">
                            <div>
                                <label class="sched-label" for="{{ $rowId }}-teacher">Teacher</label>
                                <select id="{{ $rowId }}-teacher" name="teacher_id" class="sst-input" required>
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}" @selected($subj->pivot->teacher_id == $t->id)>{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @include('partials.schedule-fields', \App\Services\ClassSchedule::formValues($subj->pivot) + ['title' => $subj->has_laboratory ? 'Lecture schedule' : null])
                            @if($subj->has_laboratory)
                                @include('partials.schedule-fields', \App\Services\ClassSchedule::formValues($subj->pivot, 'lab') + ['title' => 'Laboratory schedule'])
                            @endif
                            <div style="display:flex; justify-content:flex-end; gap:8px;">
                                <button type="button" class="sst-btn sst-btn-light" onclick="document.getElementById('{{ $rowId }}').hidden = true;">Cancel</button>
                                <button type="submit" class="sst-btn sst-btn-dark">Save changes</button>
                            </div>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="sst-muted" style="padding:16px; text-align:center;">No subjects assigned to this term yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<form method="POST" action="{{ route($attachRoute, $section) }}" class="sst-panel" style="margin-top:16px;">
    @csrf
    <div style="font-size:13px; font-weight:700; color:#1f2937;">Assign a subject</div>
    @if($availableSubjects->isEmpty())
        <p class="sst-muted" style="margin:0; font-size:13px;">No subject left to assign: every approved subject of the program for this term's semester is already in this section.</p>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px;">
            <div>
                <label class="sched-label" for="sst-add-subject-{{ $section->id }}">Subject</label>
                <select id="sst-add-subject-{{ $section->id }}" name="subject_id" class="sst-input" required data-lab-toggle>
                    <option value="">Select subject</option>
                    @foreach($availableSubjects as $s)
                        <option value="{{ $s->id }}" data-lab="{{ $s->has_laboratory ? 1 : 0 }}" @selected(old('subject_id') == $s->id)>{{ $s->code }} — {{ $s->name }} ({{ $s->placement }}){{ $s->has_laboratory ? ' · Lec + Lab' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sched-label" for="sst-add-teacher-{{ $section->id }}">Teacher</label>
                <select id="sst-add-teacher-{{ $section->id }}" name="teacher_id" class="sst-input" required>
                    <option value="">Select teacher</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" @selected(old('teacher_id') == $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @include('partials.schedule-fields', \App\Services\ClassSchedule::formValues(null) + ['title' => 'Class schedule'])
        @include('partials.schedule-fields', \App\Services\ClassSchedule::formValues(null, 'lab') + ['title' => 'Laboratory schedule'])
        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="sst-btn sst-btn-dark">Assign subject</button>
        </div>
    @endif
</form>
