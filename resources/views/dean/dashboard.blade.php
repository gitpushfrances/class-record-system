<x-sidebar-layout>
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color: #f0dfc0;">Dean Dashboard</h1>
        </div>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="p-6 rounded-xl" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
                <div class="text-sm mb-1" style="color: rgba(200,169,126,0.6);">Active Teachers</div>
                <div class="text-3xl font-bold" style="color: #f0dfc0;">{{ $stats['total_teachers'] }}</div>
            </div>
            <div class="p-6 rounded-xl" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
                <div class="text-sm mb-1" style="color: rgba(200,169,126,0.6);">Total Sections</div>
                <div class="text-3xl font-bold" style="color: #f0dfc0;">{{ $stats['total_sections'] }}</div>
            </div>
            <div class="p-6 rounded-xl" style="background: #211a12; border: 1px solid rgba(200,169,126,0.15);">
                <div class="text-sm mb-1" style="color: rgba(200,169,126,0.6);">Total Students</div>
                <div class="text-3xl font-bold" style="color: #f0dfc0;">{{ $stats['total_students'] }}</div>
            </div>
        </div>
    </div>
</x-sidebar-layout>
