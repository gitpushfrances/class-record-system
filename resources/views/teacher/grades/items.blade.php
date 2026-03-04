<x-sidebar-layout>

@section('title', 'Grade Items')



<div class="mb-6">
    <a href="{{ route('teacher.classes.show', $section) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-800">Grade Items</h1>
    <p class="mt-1 text-sm text-gray-500">{{ $section->subject->code }} — {{ $section->section_name }}</p>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- Add Item Form --}}
    <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-xl">
        <h2 class="mb-4 font-semibold text-gray-700">Add Grade Item</h2>

        <form method="POST" action="{{ route('teacher.grades.items.store', $section) }}">
            @csrf

            <div class="space-y-4">
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Component Type</label>
                    <select name="component_type"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        @foreach(['quiz','exam','project','assessment'] as $type)
                            <option value="{{ $type }}" {{ old('component_type') === $type ? 'selected' : '' }}>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           placeholder="e.g. Quiz 1, Midterm Exam"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Max Score</label>
                    <input type="number" name="max_score" value="{{ old('max_score') }}"
                           placeholder="e.g. 50"
                           min="1" step="0.01"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Date Given</label>
                    <input type="date" name="date_given" value="{{ old('date_given') }}"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Description (optional)</label>
                    <textarea name="description" rows="2"
                              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    Add Item
                </button>
            </div>
        </form>
    </div>

    {{-- Items List --}}
    <div class="space-y-4 lg:col-span-2">

        @if($gradeItems->isEmpty())
            <div class="p-10 text-sm text-center text-gray-400 bg-white border border-gray-200 rounded-xl">
                No grade items yet. Add one on the left.
            </div>
        @else
            @php
                $componentLabels = [
                    'quiz'       => ['label' => 'Quizzes',     'color' => 'blue'],
                    'exam'       => ['label' => 'Exams',       'color' => 'purple'],
                    'project'    => ['label' => 'Projects',    'color' => 'green'],
                    'assessment' => ['label' => 'Assessments', 'color' => 'orange'],
                ];
            @endphp

            @foreach($componentLabels as $type => $meta)
                @if(isset($gradeItems[$type]))
                    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
                        <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">{{ $meta['label'] }}</h3>
                            <span class="text-xs text-gray-400">
                                Weight: {{ $config ? $config->{$type . '_weight'} : '—' }}%
                            </span>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-5 py-2 text-left">Name</th>
                                    <th class="px-5 py-2 text-center">Max Score</th>
                                    <th class="px-5 py-2 text-center">Date</th>
                                    <th class="px-5 py-2 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($gradeItems[$type] as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 font-medium text-gray-800">
                                            {{ $item->name }}
                                            @if($item->is_locked)
                                                <span class="ml-1 text-xs text-gray-400">🔒</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-center text-gray-600">{{ $item->max_score }}</td>
                                        <td class="px-5 py-3 text-xs text-center text-gray-400">
                                            {{ $item->date_given?->format('M d, Y') ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <a href="{{ route('teacher.grades.scores', [$section, $item]) }}"
                                               class="mr-3 text-xs font-medium text-indigo-600 hover:underline">Enter Scores</a>
                                            @if(! $item->is_locked)
                                                <form method="POST"
                                                      action="{{ route('teacher.grades.items.destroy', [$section, $item]) }}"
                                                      class="inline"
                                                      onsubmit="return confirm('Delete this item?')">
                                                    @csrf @method('DELETE')
                                                    <button class="text-xs text-red-500 hover:underline">Delete</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</div>

</x-sidebar-layout>
