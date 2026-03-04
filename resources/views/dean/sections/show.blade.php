<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $section->subject->code }} — {{ $section->section_name }}
            </h2>
            <a href="{{ route('dean.sections.index') }}" class="px-4 py-2 text-gray-700 bg-gray-200 rounded hover:bg-gray-300">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto space-y-6 max-w-7xl sm:px-6 lg:px-8">

            {{-- Section Info --}}
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="mb-4 text-sm font-semibold text-gray-500 uppercase">Section Details</h3>
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs text-gray-500">Subject</dt>
                            <dd class="font-medium">{{ $section->subject->code }} — {{ $section->subject->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Teacher</dt>
                            <dd class="font-medium">{{ $section->teacher->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Year Level</dt>
                            <dd class="font-medium">{{ $section->year_level }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Semester</dt>
                            <dd class="font-medium">{{ $section->semester }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Academic Year</dt>
                            <dd class="font-medium">{{ $section->academic_year }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Schedule</dt>
                            <dd class="font-medium">{{ $section->schedule ?? 'TBA' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Room</dt>
                            <dd class="font-medium">{{ $section->room ?? 'TBA' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Status</dt>
                            <dd>
                                <span class="px-2 py-1 text-xs rounded
                                    @if($section->status === 'active') bg-green-100 text-green-800
                                    @elseif($section->status === 'completed') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst($section->status) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Enrolled Students --}}
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase">
                            Enrolled Students ({{ $section->enrollments->count() }})
                        </h3>
                        <a href="{{ route('dean.enrollments.show', $section) }}" class="text-sm text-blue-600 hover:underline">
                            Manage Enrollments →
                        </a>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Student No.</th>
                                <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-xs font-medium text-left text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($section->enrollments as $enrollment)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-sm">{{ $enrollment->student->student_number }}</td>
                                    <td class="px-4 py-3">{{ $enrollment->student->last_name }}, {{ $enrollment->student->first_name }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded
                                            @if($enrollment->student->student_type === 'regular') bg-green-100 text-green-800
                                            @else bg-yellow-100 text-yellow-800 @endif">
                                            {{ ucfirst($enrollment->student->student_type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ ucfirst($enrollment->status) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-center text-gray-500">No students enrolled yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
