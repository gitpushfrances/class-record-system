<x-sidebar-layout>

    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Enrollments</h2>
    </div>

    <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Section</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Term</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Adviser</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Enrolled</th>
                        <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($sectionTerms as $term)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium">
                                    {{ $term->section->program->code }} {{ $term->section->year_number }}-{{ $term->section->section_letter }}
                                </div>
                                <div class="text-sm text-gray-500">{{ $term->section->year_level }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                {{ $term->semester }}, {{ $term->academic_year }}
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $term->adviser?->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded">
                                    {{ $term->enrollments->count() }} students
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('dean.enrollments.show', $term->section) }}" class="text-blue-600 hover:text-blue-900">
                                    Manage Enrollments
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">No section terms found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $sectionTerms->links() }}
            </div>
        </div>
    </div>

</x-sidebar-layout>
