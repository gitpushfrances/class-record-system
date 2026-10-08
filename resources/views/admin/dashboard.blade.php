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
                <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                    @foreach([
                        ['admin.deans.index',       'fa-users',         'Manage Faculty'],
                        ['admin.subjects.index',    'fa-book',          'Manage Subjects'],
                        ['admin.departments.index', 'fa-building',      'Departments'],
                        ['admin.academic.index',    'fa-calendar-days', 'Academic Period'],
                        ['admin.backup.index',      'fa-database',      'Backup & Restore'],
                    ] as [$r, $icon, $label])
                        <a href="{{ route($r) }}" class="flex flex-col items-center gap-3 p-5 text-center text-gray-700 transition border border-gray-200 rounded-lg hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700">
                            <i class="text-2xl fa-solid {{ $icon }}"></i>
                            <span class="text-sm font-semibold">{{ $label }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
</x-sidebar-layout>
