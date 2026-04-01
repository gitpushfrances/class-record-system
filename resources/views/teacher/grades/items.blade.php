<x-sidebar-layout>

<div class="mb-6">
    <a href="{{ route('teacher.classes.show', $section) }}" class="text-sm text-indigo-600 hover:underline">← Back to Class</a>
    <h1 class="mt-1 text-2xl font-bold" style="color:#f0dfc0;">Grade Items</h1>
    <p class="mt-1 text-sm" style="color:rgba(200,169,126,0.6);">{{ $section->program->code }} {{ $section->year_number }}-{{ $section->section_letter }}</p>
</div>

@if($errors->any())
    <div class="px-4 py-3 mb-4 rounded-lg text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;">
        @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
    </div>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- Add Item Form --}}
    <div class="rounded-xl p-5" style="background:#211a12;border:1px solid rgba(200,169,126,0.15);">
        <h2 class="mb-4 font-semibold text-sm" style="color:#c8a97e;">Add Grade Item</h2>
        <form method="POST" action="{{ route('teacher.grades.items.store', $section) }}">
            @csrf
            <div class="space-y-3">

                <div>
                    <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Period</label>
                    <select name="period" id="period-select"
                            class="w-full px-3 py-2 text-sm rounded-lg"
                            style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;"
                            onchange="filterComponents()">
                        <option value="midterm">Midterm</option>
                        <option value="final">Final</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Component</label>
                    <select name="component_type" id="component-select"
                            class="w-full px-3 py-2 text-sm rounded-lg"
                            style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
                    </select>
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Quiz 1"
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Max Score</label>
                    <input type="number" name="max_score" value="{{ old('max_score') }}" min="1" step="0.01"
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
                </div>

                <div>
                    <label class="block mb-1 text-xs font-medium" style="color:rgba(200,169,126,0.7);">Date Given</label>
                    <input type="date" name="date_given" value="{{ old('date_given') }}"
                           class="w-full px-3 py-2 text-sm rounded-lg"
                           style="background:rgba(200,169,126,0.07);border:1px solid rgba(200,169,126,0.2);color:#f0dfc0;">
                </div>

                <button type="submit"
                        class="w-full py-2.5 rounded-lg text-sm font-semibold"
                        style="background:linear-gradient(135deg,#9a7a50,#c8a97e);color:#1c1814;">
                    Add Item
                </button>
            </div>
        </form>
    </div>

    {{-- Items List --}}
    <div class="space-y-6 lg:col-span-2">

        @foreach(['midterm' => 'Midterm Period', 'final' => 'Final Period'] as $period => $periodLabel)
            <div class="rounded-xl overflow-hidden" style="background:#211a12;border:1px solid rgba(200,169,126,0.15);">
                <div class="px-5 py-3 border-b" style="border-color:rgba(200,169,126,0.1);">
                    <h3 class="text-sm font-semibold" style="color:#c8a97e;">{{ $periodLabel }}</h3>
                </div>

                @php
                    $periodItems = isset($gradeItems[$period]) ? $gradeItems[$period]->groupBy('component_type') : collect();
                @endphp

                @if($periodItems->isEmpty())
                    <p class="px-5 py-4 text-sm" style="color:rgba(200,169,126,0.4);">No items yet for this period.</p>
                @else
                    @foreach($periodItems as $type => $items)
                        @php
                            $comp   = $components->get($type);
                            $label  = $comp ? $comp['label'] : ucfirst($type);
                            $weight = $comp ? $comp['weight'] : 0;
                        @endphp
                        <div class="border-b last:border-0" style="border-color:rgba(200,169,126,0.07);">
                            <div class="flex items-center justify-between px-5 py-2" style="background:rgba(200,169,126,0.04);">
                                <span class="text-xs font-semibold" style="color:#f0dfc0;">{{ $label }}</span>
                                <span class="text-xs" style="color:rgba(200,169,126,0.5);">Weight: {{ $weight }}%
                                    @if($weight == 0)
                                        <span style="color:#f87171;">(disabled)</span>
                                    @endif
                                </span>
                            </div>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr style="border-bottom:1px solid rgba(200,169,126,0.07);">
                                        <th class="px-5 py-2 text-left text-xs" style="color:rgba(200,169,126,0.5);">Name</th>
                                        <th class="px-5 py-2 text-center text-xs" style="color:rgba(200,169,126,0.5);">Max Score</th>
                                        <th class="px-5 py-2 text-center text-xs" style="color:rgba(200,169,126,0.5);">Date</th>
                                        <th class="px-5 py-2 text-center text-xs" style="color:rgba(200,169,126,0.5);">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $item)
                                        <tr style="border-bottom:1px solid rgba(200,169,126,0.05);">
                                            <td class="px-5 py-2.5 font-medium" style="color:#f0dfc0;">
                                                {{ $item->name }}
                                                @if($item->is_locked) <i class="fa-solid fa-lock text-xs ml-1" style="color:rgba(200,169,126,0.4);"></i> @endif
                                            </td>
                                            <td class="px-5 py-2.5 text-center" style="color:rgba(200,169,126,0.7);">{{ $item->max_score }}</td>
                                            <td class="px-5 py-2.5 text-center text-xs" style="color:rgba(200,169,126,0.5);">
                                                {{ $item->date_given?->format('M d, Y') ?? '—' }}
                                            </td>
                                            <td class="px-5 py-2.5 text-center">
                                                <a href="{{ route('teacher.grades.scores', [$section, $item]) }}"
                                                   class="text-xs font-medium mr-3" style="color:#c8a97e;">Enter Scores</a>
                                                @if(!$item->is_locked)
                                                    <form method="POST"
                                                          action="{{ route('teacher.grades.items.destroy', [$section, $item]) }}"
                                                          class="inline"
                                                          onsubmit="return confirm('Delete this item?')">
                                                        @csrf @method('DELETE')
                                                        <button class="text-xs" style="color:#f87171;">Delete</button>
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
