<x-sidebar-layout>

<div class="mb-6">
    <a href="{{ route('teacher.classes.record', [$section, $subject]) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
    <h1 class="mt-1 text-2xl font-bold text-gray-800">Grade Items</h1>
    <p class="mt-1 text-sm text-gray-500">{{ $subject->code }} — {{ $subject->name }} &bull; {{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
</div>

@if($errors->any())
    <div class="px-4 py-3 mb-4 text-sm text-red-600 border border-red-200 rounded-lg bg-red-50">
        @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- Add Item Form --}}
    <div class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl">
        <h2 class="mb-4 font-semibold text-gray-700">Add Grade Item</h2>
        <form method="POST" action="{{ route('teacher.grades.items.store', [$section, $subject]) }}">
            @csrf
            <div class="space-y-3">

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Period</label>
                    <select name="period" id="period-select"
                            class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            onchange="filterComponents()">
                        <option value="midterm">Midterm</option>
                        <option value="final">Final</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Component</label>
                    <select name="component_type" id="component-select"
                            class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Quiz 1"
                           class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Max Score</label>
                    <input type="number" name="max_score" value="{{ old('max_score') }}" min="1" step="0.01"
                           class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-600">Date Given</label>
                    <input type="date" name="date_given" value="{{ old('date_given') }}"
                           class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                <button type="submit"
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    Add Item
                </button>
            </div>
        </form>
    </div>

    {{-- Items List --}}
    <div class="space-y-6 lg:col-span-2">

        @foreach(['midterm' => 'Midterm Period', 'final' => 'Final Period'] as $period => $periodLabel)
            <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-700">{{ $periodLabel }}</h3>
                </div>

                @php
                    $periodItems = isset($gradeItems[$period]) ? $gradeItems[$period]->groupBy('component_type') : collect();
                @endphp

                @if($periodItems->isEmpty())
                    <p class="px-5 py-4 text-sm text-gray-400">No items yet for this period.</p>
                @else
                    @foreach($periodItems as $type => $items)
                        @php
                            $comp   = $components->get($type);
                            $label  = $comp ? $comp['label'] : ucfirst($type);
                            $weight = $comp ? $comp['weight'] : 0;
                        @endphp
                        <div class="border-b border-gray-100 last:border-0">
                            <div class="flex items-center justify-between px-5 py-2 bg-gray-50">
                                <span class="text-xs font-semibold text-gray-700">{{ $label }}</span>
                                <span class="text-xs text-gray-500">Weight: {{ $weight }}%
                                    @if($weight == 0)
                                        <span class="text-red-500">(disabled)</span>
                                    @endif
                                </span>
                            </div>
                            <table class="w-full text-sm">
                                <thead class="text-xs text-gray-500">
                                    <tr class="border-b border-gray-100">
                                        <th class="px-5 py-2 text-left">Name</th>
                                        <th class="px-5 py-2 text-center">Max Score</th>
                                        <th class="px-5 py-2 text-center">Date</th>
                                        <th class="px-5 py-2 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-5 py-2.5 font-medium text-gray-800">
                                                {{ $item->name }}
                                                @if($item->is_locked) <i class="ml-1 text-xs text-gray-400 fa-solid fa-lock"></i> @endif
                                            </td>
                                            <td class="px-5 py-2.5 text-center text-gray-600">{{ $item->max_score }}</td>
                                            <td class="px-5 py-2.5 text-center text-xs text-gray-500">
                                                {{ $item->date_given?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="px-5 py-2.5 text-center">
                                                <a href="{{ route('teacher.grades.scores', [$section, $subject, $item]) }}"
                                                   class="mr-3 text-xs font-medium text-indigo-600 hover:underline">Enter Scores</a>
                                                @if(!$item->is_locked)
                                                    <form method="POST"
                                                          action="{{ route('teacher.grades.items.destroy', [$section, $subject, $item]) }}"
                                                          class="inline"
                                                          onsubmit="return confirm('Delete this item?')">
                                                        @csrf @method('DELETE')
                                                        <button class="text-xs font-medium text-red-500 hover:text-red-700 hover:underline">Delete</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @endif
            </div>
        @endforeach

    </div>
</div>

<script>
const allComponents = {!! json_encode($components->values()) !!};

function filterComponents() {
    const period = document.getElementById('period-select').value;
    const sel    = document.getElementById('component-select');
    sel.innerHTML = '';
    allComponents
        .filter(c => c.period === period)
        .forEach(c => {
            const opt = document.createElement('option');
            opt.value       = c.key;
            opt.textContent = c.label + (c.weight == 0 ? ' (0% — disabled)' : ' (' + c.weight + '%)');
            sel.appendChild(opt);
        });
}
filterComponents();
</script>

</x-sidebar-layout>
