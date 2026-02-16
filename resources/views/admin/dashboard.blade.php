<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Super Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-3">
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Deans</div>
                    <div class="text-3xl font-bold">{{ $stats['deans'] }}</div>
                </div>
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Active Teachers</div>
                    <div class="text-3xl font-bold">{{ $stats['teachers'] }}</div>
                </div>
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Pending Teachers</div>
                    <div class="text-3xl font-bold text-orange-600">{{ $stats['pending_teachers'] }}</div>
                </div>
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Students</div>
                    <div class="text-3xl font-bold">{{ $stats['students'] }}</div>
                </div>
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Subjects</div>
                    <div class="text-3xl font-bold">{{ $stats['subjects'] }}</div>
                </div>
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Sections</div>
                    <div class="text-3xl font-bold">{{ $stats['sections'] }}</div>
                </div>
            </div>

            @if($pendingTeachers->isNotEmpty())
                <div class="p-6 bg-white rounded-lg shadow">
                    <h3 class="mb-4 text-lg font-semibold">Recent Pending Teachers</h3>
                    <div class="space-y-2">
                        @foreach($pendingTeachers as $teacher)
                            <div class="flex items-center justify-between p-3 border rounded">
                                <div>
                                    <div class="font-medium">{{ $teacher->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $teacher->email }}</div>
                                </div>
                                <a href="{{ route('admin.teachers.pending') }}" class="text-blue-600 hover:text-blue-900">
                                    Review
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
