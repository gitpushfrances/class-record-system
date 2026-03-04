<x-sidebar-layout>

    
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

            <div class="p-6 bg-white rounded-lg shadow">
                <h3 class="mb-4 text-lg font-semibold">Quick Links</h3>
                <div class="space-x-4">
                    <a href="{{ route('admin.deans.index') }}" class="text-blue-600 hover:text-blue-900">Manage Deans</a>
                    <a href="{{ route('admin.subjects.index') }}" class="text-blue-600 hover:text-blue-900">Manage Subjects</a>
</x-sidebar-layout>
