<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Subjects') }}
            </h2>
            <a href="{{ route('admin.subjects.create') }}" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                Add Subject
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
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Code</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Units</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($subjects as $subject)
                                <tr>
                                    <td class="px-6 py-4">{{ $subject->code }}</td>
                                    <td class="px-6 py-4">{{ $subject->name }}</td>
                                    <td class="px-6 py-4">{{ $subject->units }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded {{ $subject->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($subject->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('admin.subjects.edit', $subject) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                        <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-2 text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No subjects found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $subjects->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
