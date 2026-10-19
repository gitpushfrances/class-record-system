<x-sidebar-layout>
<div style="margin-bottom:12px;"><x-back-button :href="route('teacher.past-classes.index')" label="Back to Past Classes" /></div>
<h1 class="text-2xl font-bold text-gray-800">{{ $subject->code }} — {{ $subject->name }}</h1>
<p class="mt-1 text-sm text-gray-500">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }} &bull; {{ $sectionTerm->semester }} &bull; {{ $sectionTerm->academic_year }}</p>
<div class="p-8 mt-6 text-center bg-white border border-gray-200 rounded-xl">
    <p class="text-sm font-medium text-gray-700">No class record was kept for this class</p>
    <p class="mt-1 text-sm text-gray-500">The grade configuration was never saved in this term, so there are no items or scores to show.</p>
    @if($rogUrl)
        <a href="{{ $rogUrl }}" target="_blank" class="inline-block mt-4 text-sm font-semibold" style="color:#0a5b05;">Open the Report of Grades</a>
    @endif
</div>
</x-sidebar-layout>
