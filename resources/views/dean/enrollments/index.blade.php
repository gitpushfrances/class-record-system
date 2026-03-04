<x-sidebar-layout>

    
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Section</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Subject</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Teacher</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Enrolled</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($sections as $section)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium">{{ $section->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $section->year_level }} • {{ $section->semester }}</div>
                                    </td>
                                    <td class="px-6 py-4">{{ $section->subject->code }}</td>
                                    <td class="px-6 py-4">{{ $section->teacher->name }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded">
                                            {{ $section->enrollments->count() }} students
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('dean.enrollments.show', $section) }}" class="text-blue-600 hover:text-blue-900">
                                            Manage Enrollments
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No sections found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $sections->links() }}
                    </div>
                </div>
            </div>
</x-sidebar-layout>
