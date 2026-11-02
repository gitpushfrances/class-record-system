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
        @forelse($terms as $term)
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
                    <a href="{{ route('dean.enrollments.show', $term) }}" class="text-blue-600 hover:text-blue-900">
                        View Class List
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-gray-500">{{ $empty }}</td>
            </tr>
        @endforelse
    </tbody>
</table>
