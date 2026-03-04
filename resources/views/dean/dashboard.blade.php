<x-sidebar-layout>

    
            <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-4">
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Pending Teachers</div>
                    <div class="text-3xl font-bold text-orange-600">{{ $stats['pending_teachers'] }}</div>
                </div>
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Active Teachers</div>
                    <div class="text-3xl font-bold">{{ $stats['total_teachers'] }}</div>
                </div>
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Total Sections</div>
                    <div class="text-3xl font-bold">{{ $stats['total_sections'] }}</div>
                </div>
                <div class="p-6 bg-white rounded-lg shadow">
                    <div class="text-sm text-gray-500">Total Students</div>
                    <div class="text-3xl font-bold">{{ $stats['total_students'] }}</div>
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
                                <a href="{{ route('dean.teachers.pending') }}" class="text-blue-600 hover:text-blue-900">
                                    Review
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
</x-sidebar-layout>
