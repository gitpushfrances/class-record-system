<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Dean Management') }}
            </h2>
            <a href="{{ route('admin.deans.create') }}" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                Add Dean
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
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Created</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($deans as $dean)
                                <tr>
                                    <td class="px-6 py-4">{{ $dean->name }}</td>
                                    <td class="px-6 py-4">{{ $dean->email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded {{ $dean->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($dean->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $dean->created_at->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 space-x-2">
                                        <a href="{{ route('admin.deans.edit', $dean) }}" class="text-blue-600 hover:text-blue-900">Edit</a>

                                        @if($dean->status === 'active')
                                            <form action="{{ route('admin.deans.deactivate', $dean) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Deactivate this dean?')">Deactivate</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.deans.activate', $dean) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-green-600 hover:text-green-900">Activate</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No deans found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $deans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
