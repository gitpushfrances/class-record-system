<x-sidebar-layout>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">My Classes</h1>
    <p class="mt-1 text-sm text-gray-500">Welcome back, {{ auth()->user()->name }}</p>
</div>

@if($sectionTerms->isEmpty())
    <div class="p-12 text-center bg-white border border-gray-200 rounded-xl">
        <div class="mb-3 text-4xl">í³š</div>
        <p class="text-sm text-gray-500">No classes assigned yet. Contact your Dean to get assigned to a section.</p>
    </div>
@else
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($sectionTerms as $term)
            <div class="overflow-hidden transition bg-white border border-gray-200 shadow-sm rounded-xl hover:shadow-md">
                <div class="px-5 py-4 border-b border-gray-100">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="font-bold text-gray-800">
                                {{ $term->section->program->code }} {{ $term->section->year_number }}-{{ $term->section->section_letter }}
                            </h3>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $term->section->program->name }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full font-medium
                            {{ $term->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ ucfirst($term->status) }}
                        </span>
                    </div>
                </div>
                <div class="px-5 py-3 space-y-1 text-sm text-gray-600">
                    <div>Year Level: <span class="font-medium text-gray-800">{{ $term->section->year_level }}</span></div>
                    <div>Department: <span class="font-medium text-gray-800">{{ $term->section->program->department->name }}</span></div>
                    <div>Students: <span class="font-medium text-gray-800">{{ $term->enrollments->count() }}</span></div>
                    <div>{{ $term->semester }} &bull; {{ $term->academic_year }}</div>
                </div>
                <div class="px-5 py-3 border-t border-gray-100">
                    <a href="{{ route('teacher.classes.show', $term->section) }}"
                       class="block w-full py-2 text-sm font-semibold text-center text-white transition bg-indigo-600 rounded-lg hover:bg-indigo-700">
                        Open Class
                    </a>
                </div>
            </div>
        @endforeach
    </div>
@endif

</x-sidebar-layout>
