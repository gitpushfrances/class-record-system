<x-sidebar-layout>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Dean Dashboard</h1>
</div>

<div class="grid grid-cols-1 gap-6 md:grid-cols-3">
    <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-xl">
        <div class="mb-1 text-sm text-gray-500">Active Teachers</div>
        <div class="text-3xl font-bold text-gray-800">{{ $stats['total_teachers'] }}</div>
    </div>
    <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-xl">
        <div class="mb-1 text-sm text-gray-500">Total Sections</div>
        <div class="text-3xl font-bold text-gray-800">{{ $stats['total_sections'] }}</div>
    </div>
    <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-xl">
        <div class="mb-1 text-sm text-gray-500">Total Students</div>
        <div class="text-3xl font-bold text-gray-800">{{ $stats['total_students'] }}</div>
    </div>
</div>

</x-sidebar-layout>
