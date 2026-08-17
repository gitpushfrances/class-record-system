<x-sidebar-layout>
<div class="mb-6">
    <h1 class="text-2xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">My Subjects</h1>
    <p class="mt-1 text-sm text-gray-500">Subjects you are currently teaching</p>
</div>

@if($teachingTerms->isEmpty())
    <div class="p-12 text-center bg-white border border-gray-200 rounded-xl">
        <div class="flex items-center justify-center mx-auto mb-4 rounded-full w-14 h-14" style="background:rgba(200,169,126,0.12);">
            <i class="text-xl fa-solid fa-chalkboard-teacher" style="color:#c8a97e;"></i>
        </div>
        <p class="text-sm font-medium text-gray-700">No subjects assigned yet</p>
        <p class="mt-1 text-sm text-gray-500">Contact your Dean to get assigned to a subject.</p>
    </div>
@else
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($teachingTerms as $term)
            @foreach($term->subjects as $subject)
                <div class="overflow-hidden transition bg-white border border-gray-200 shadow-sm rounded-xl hover:shadow-md">
                    <div class="px-5 py-4 border-b border-gray-100">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-bold text-gray-800">
                                    {{ $term->section->program->code }} {{ $term->section->year_number }}-{{ $term->section->section_letter }}
                                </h3>
                                <p class="text-sm text-gray-500 mt-0.5">{{ $subject->code }} — {{ $subject->name }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-full">Teaching</span>
                        </div>
                    </div>
                    <div class="px-5 py-3 space-y-1.5 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <i class="w-4 text-xs fa-solid fa-layer-group" style="color:#c8a97e;"></i>
                            Year Level: <span class="font-medium text-gray-800">{{ $term->section->year_level }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="w-4 text-xs fa-solid fa-users" style="color:#c8a97e;"></i>
                            Students: <span class="font-medium text-gray-800">{{ $term->enrollments->count() }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="w-4 text-xs fa-solid fa-calendar" style="color:#c8a97e;"></i>
                            {{ $term->semester }} &bull; {{ $term->academic_year }}
                        </div>
                    </div>
                    <div class="px-5 py-3 border-t border-gray-100">
                        <a href="{{ route('teacher.classes.record', [$term->section, $subject]) }}"
                           class="block w-full py-2 text-sm font-semibold text-center transition rounded-lg"
                           style="background:linear-gradient(135deg,#9a7a50,#c8a97e); color:#1c1814;"
                           onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                            Open Class
                        </a>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>
@endif
</x-sidebar-layout>
