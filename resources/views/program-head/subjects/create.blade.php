<x-sidebar-layout>

<div class="max-w-xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('program-head.subjects.index') }}" class="text-sm" style="color:#d4af37;">
            ← Back to Subjects
        </a>
        <h1 class="mt-2 text-xl font-bold" style="font-family:'Fraunces',serif; color:#0b3d0b;">Request New Subject</h1>
        <p class="text-sm mt-0.5 text-gray-500">Submitted requests go to your Dean for approval.</p>
    </div>

    <div class="p-6 bg-white border rounded-2xl" style="border-color:#e5e7eb;">
        <form id="subject-form" method="POST" action="{{ route('program-head.subjects.store') }}">
            @csrf
            <input type="hidden" name="confirm_duplicate" id="confirm_duplicate" value="0">

            <div class="space-y-4">
                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Subject Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}"
                           placeholder="e.g. IT101"
                           class="w-full px-3 py-2 text-sm border rounded-xl focus:outline-none focus:ring-2"
                           style="border-color:#d1d5db;">
                    @error('code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Subject Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                           placeholder="e.g. Introduction to Computing"
                           class="w-full px-3 py-2 text-sm border rounded-xl focus:outline-none focus:ring-2"
                           style="border-color:#d1d5db;">
                    @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Units <span class="text-red-500">*</span></label>
                    <input type="number" name="units" step="0.1" id="units" value="{{ old('units', 3) }}"
                           min="0.5" max="10"
                           class="w-full px-3 py-2 text-sm border rounded-xl focus:outline-none focus:ring-2"
                           style="border-color:#d1d5db;">
                    @error('units')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Year Level <span class="text-red-500">*</span></label>
                        <select name="year_level" required class="w-full px-3 py-2 text-sm border rounded-xl" style="border-color:#d1d5db;">
                            <option value="">Select…</option>
                            @foreach(\App\Models\Subject::YEAR_LEVELS as $level)
                                <option value="{{ $level }}" @selected(old('year_level', $subject->year_level ?? null) === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                        @error('year_level')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-semibold text-gray-700">Semester <span class="text-red-500">*</span></label>
                        <select name="semester" required class="w-full px-3 py-2 text-sm border rounded-xl" style="border-color:#d1d5db;">
                            <option value="">Select…</option>
                            @foreach(\App\Models\Subject::SEMESTERS as $sem)
                                <option value="{{ $sem }}" @selected(old('semester', $subject->semester ?? null) === $sem)>{{ $sem }}</option>
                            @endforeach
                        </select>
                        @error('semester')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                        <input type="checkbox" name="has_laboratory" value="1" {{ old('has_laboratory', false) ? 'checked' : '' }}>
                        Has laboratory (lecture and laboratory attendance are recorded separately)
                    </label>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-semibold text-gray-700">Description</label>
                    <textarea name="description" id="description" rows="3"
                              placeholder="Optional short description..."
                              class="w-full px-3 py-2 text-sm border rounded-xl focus:outline-none focus:ring-2"
                              style="border-color:#d1d5db;">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('program-head.subjects.index') }}"
                   class="px-4 py-2 text-sm font-medium border rounded-xl"
                   style="border-color:#d1d5db; color:#374151;">Cancel</a>
                <button type="button" id="preview-btn"
                        class="px-5 py-2 text-sm font-semibold transition rounded-xl"
                        style="background:#0b3d0b; color:#fbf3d0;">
                    Review & Submit
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('preview-btn').addEventListener('click', function () {
    const code  = document.getElementById('code').value.trim();
    const name  = document.getElementById('name').value.trim();
    const units = document.getElementById('units').value.trim();
    const desc  = document.getElementById('description').value.trim();

    if (!code || !name || !units) {
        Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Please fill in all required fields.' });
        return;
    }

    Swal.fire({
        title: 'Confirm Subject Request',
        html: `
            <div style="text-align:left; font-size:14px; line-height:1.8;">
                <div><strong>Code:</strong> ${code}</div>
                <div><strong>Name:</strong> ${name}</div>
                <div><strong>Units:</strong> ${units}</div>
                ${desc ? `<div><strong>Description:</strong> ${desc}</div>` : ''}
                <div style="margin-top:10px; padding:8px 12px; background:#fef3c7; border-radius:8px; font-size:12px; color:#92400e;">
                    <i class="fa-solid fa-clock"></i> This will be sent to your Dean for approval.
                </div>
            </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0b3d0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Submit Request',
        cancelButtonText: 'Go Back',
    }).then(result => {
        if (result.isConfirmed) document.getElementById('subject-form').submit();
    });
});
</script>

@include('program-head.subjects.partials.duplicate-dialog')

</x-sidebar-layout>
