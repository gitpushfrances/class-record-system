<x-sidebar-layout>
@php
    $status = [
        'verified' => ['Verified', '#166534', '#dcfce7'],
        'pending'  => ['Awaiting verification', '#92400e', '#fef3c7'],
        'rejected' => ['Returned, not resubmitted', '#991b1b', '#fee2e2'],
    ];
@endphp
<div class="mb-6">
    <h1 class="text-2xl font-bold" style="font-family:'Fraunces',serif; color:#0b3d0b;">Past Classes</h1>
    <p class="mt-1 text-sm text-gray-500">Subjects you taught in closed terms. Records are kept as they were and can be viewed, not changed.</p>
</div>

@if($terms->isEmpty())
    <div class="p-12 text-center bg-white border border-gray-200 rounded-xl">
        <div class="flex items-center justify-center mx-auto mb-4 rounded-full w-14 h-14" style="background:rgba(212,175,55,0.12);">
            <i class="text-xl fa-solid fa-box-archive" style="color:#d4af37;"></i>
        </div>
        <p class="text-sm font-medium text-gray-700">No past classes yet</p>
        <p class="mt-1 text-sm text-gray-500">A class appears here once its term is closed (Set Term in a new semester).</p>
    </div>
@else
    @foreach($terms as $period => $periodTerms)
        <h2 style="margin:24px 0 12px; font-size:13px; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#0a5b05;">{{ $period }}</h2>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($periodTerms as $term)
                @foreach($term->subjects as $subject)
                    @php
                        $state     = $term->verifications->firstWhere('subject_id', $subject->id)?->status;
                        [$label, $fg, $bg] = $status[$state] ?? ['Not submitted', '#374151', '#f3f4f6'];
                        $submitted = in_array($state, ['pending', 'verified'], true);
                    @endphp
                    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
                        <div class="px-5 py-4 border-b border-gray-100">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-bold text-gray-800">{{ $term->section->program->code }} {{ $term->section->year_number }}-{{ $term->section->section_letter }}</h3>
                                    <p class="text-sm text-gray-500 mt-0.5">{{ $subject->code }} — {{ $subject->name }}</p>
                                </div>
                                <span style="padding:2px 8px; font-size:11px; font-weight:600; color:{{ $fg }}; background:{{ $bg }}; border-radius:9999px; white-space:nowrap;">{{ $label }}</span>
                            </div>
                        </div>
                        <div class="flex gap-2 px-5 py-3">
                            <a href="{{ route('teacher.past-classes.show', [$term, $subject]) }}"
                               style="flex:1; padding:8px; font-size:13px; font-weight:600; text-align:center; color:#0b3d0b; background:linear-gradient(135deg,#a8861a,#d4af37); border-radius:8px; text-decoration:none;">
                                Class Record
                            </a>
                            @if($submitted)
                                <a href="{{ route('teacher.past-classes.rog', [$term, $subject]) }}" target="_blank"
                                   style="flex:1; padding:8px; font-size:13px; font-weight:600; text-align:center; color:#374151; background:#fff; border:1px solid #d1d5db; border-radius:8px; text-decoration:none;">
                                    Report of Grades
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    @endforeach
@endif
</x-sidebar-layout>
