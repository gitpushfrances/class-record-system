<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Sections') }}
            </h2>
            <a href="{{ route('dean.sections.create') }}" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                Add Section
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Section</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Subject</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Teacher</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Schedule</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($sections as $section)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium">{{ $section->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $section->year_level }} • {{ $section->semester }}</div>
                                        <div class="text-sm text-gray-500">{{ $section->academic_year }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div>{{ $section->subject->code }}</div>
                                        <div class="text-sm text-gray-500">{{ $section->subject->name }}</div>
                                    </td>
                                    <td class="px-6 py-4">{{ $section->teacher->name }}</td>
                                    <td class="px-6 py-4">
                                        <div>{{ $section->schedule ?? 'TBA' }}</div>
                                        <div class="text-sm text-gray-500">{{ $section->room ?? 'TBA' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded
                                            @if($section->status === 'active') bg-green-100 text-green-800
                                            @elseif($section->status === 'completed') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($section->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('dean.sections.edit', $section) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                        <form action="{{ route('dean.sections.destroy', $section) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-2 text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No sections found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $sections->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
