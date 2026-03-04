<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Student Master List') }}
            </h2>
            <a href="{{ route('dean.students.create') }}" class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-700">
                Add Student
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="px-4 py-3 mb-4 text-green-700 bg-green-100 border border-green-400 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Student No.</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Year Level</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Program</th>
                                <th class="px-6 py-3 text-xs font-medium text-left text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($students as $student)
                                <tr>
                                    <td class="px-6 py-4 font-mono text-sm">{{ $student->student_number }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium">{{ $student->last_name }}, {{ $student->first_name }} {{ $student->middle_name }}</div>
                                        @if($student->email)
                                            <div class="text-sm text-gray-500">{{ $student->email }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">{{ $student->year_level }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded
                                            @if($student->student_type === 'regular') bg-green-100 text-green-800
                                            @else bg-yellow-100 text-yellow-800 @endif">
                                            {{ ucfirst($student->student_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $student->program ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('dean.students.edit', $student) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                        <form action="{{ route('dean.students.destroy', $student) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-2 text-red-600 hover:text-red-900" onclick="return confirm('Remove this student from the master list?')">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
