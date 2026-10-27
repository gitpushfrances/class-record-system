<x-sidebar-layout>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold" style="font-family:'Fraunces',serif; color:#0b3d0b;">Student Master List</h1>
            <p class="mt-1 text-sm text-gray-500">Student records of your department. Read-only: the Program Head enrolls and imports students.</p>
        </div>
    </div>

    <style>
        .filter-select { height:38px; padding:0 2.25rem 0 .75rem; font-size:.875rem; border:1px solid #d1d5db; border-radius:.5rem; background-color:#fff; background-image:url(data:image/svg+xml,%3Csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20viewBox=%270%200%2020%2020%27%20fill=%27none%27%3E%3Cpath%20d=%27M6%208l4%204%204-4%27%20stroke=%27%236b7280%27%20stroke-width=%271.5%27%20stroke-linecap=%27round%27%20stroke-linejoin=%27round%27/%3E%3C/svg%3E); background-repeat:no-repeat; background-position:right .75rem center; background-size:1rem; -webkit-appearance:none; appearance:none; cursor:pointer; }
        .filter-select.is-active { border-color:#d4af37; background-color:#fbf7e6; font-weight:600; }
        .filter-select:focus { outline:none; border-color:#d4af37; box-shadow:0 0 0 2px rgba(212,175,55,.25); }
    </style>
    <form method="GET" class="mb-4" data-live-search style="display:flex; flex-wrap:wrap; align-items:center; gap:0.75rem;">
        <select name="program_id" onchange="this.form.submit()" class="filter-select {{ request()->filled('program_id') ? 'is-active' : '' }}">
            <option value="">All Programs</option>
            @foreach($programs as $program)
                <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->code }}</option>
            @endforeach
        </select>
        <div style="position:relative; flex:1 1 260px; max-width:360px;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:12px; color:#9ca3af; pointer-events:none;"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, ID, or email…" autocomplete="off" data-live-input
                   class="text-sm border rounded-lg" style="width:100%; height:38px; padding:0 12px 0 34px; border-color:#d1d5db; background:#fff;">
        </div>
        <button type="submit" data-live-button class="text-sm font-semibold rounded-lg" style="height:38px; padding:0 16px; background:linear-gradient(135deg,#a8861a,#d4af37); color:#0b3d0b;">Search</button>
        @if(request()->filled('search') || request()->filled('program_id'))
            <a href="{{ route('dean.students.index') }}" class="text-sm text-gray-500 hover:text-gray-800" style="display:inline-flex; align-items:center; gap:4px;">
                <i class="text-xs fa-solid fa-xmark"></i> Clear
            </a>
        @endif
        <span class="text-xs text-gray-400" style="margin-left:auto;">{{ $students->total() }} {{ $students->total() === 1 ? 'student' : 'students' }}</span>
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
    </form>

    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
        <table class="min-w-full divide-y divide-gray-200">
            <thead style="background:#f9fafb;">
                <tr>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'student_number', 'direction' => $sort === 'student_number' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-800">
                            Student No.
                            @if($sort === 'student_number')<i class="fa-solid fa-sort-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>@endif
                        </a>
                    </th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'last_name', 'direction' => $sort === 'last_name' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-800">
                            Name
                            @if($sort === 'last_name')<i class="fa-solid fa-sort-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>@endif
                        </a>
                    </th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Year Level</th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">Program</th>
                    <th class="px-6 py-3 text-xs font-semibold text-left text-gray-500 uppercase">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => $sort === 'created_at' && $direction === 'asc' ? 'desc' : 'asc']) }}" class="flex items-center gap-1 hover:text-gray-800">
                            Date Added
                            @if($sort === 'created_at')<i class="fa-solid fa-sort-{{ $direction === 'asc' ? 'up' : 'down' }}"></i>@endif
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($students as $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-mono text-sm text-gray-700">{{ \App\Services\NameSearch::highlight($student->student_number, request('search')) }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ \App\Services\NameSearch::highlight($student->full_name, request('search')) }}</div>
                            @if($student->email)
                                <div class="text-sm text-gray-500">{{ \App\Services\NameSearch::highlight($student->email, request('search'), true) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->year_level }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($student->student_type === 'regular') bg-green-100 text-green-700
                                @else bg-amber-100 text-amber-700 @endif">
                                {{ ucfirst($student->student_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->program->code ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-sm text-center text-gray-400">No students found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $students->links() }}
    </div>

</x-sidebar-layout>
