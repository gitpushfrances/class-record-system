<x-sidebar-layout>

<div class="max-w-xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('program-head.subjects.index') }}" class="text-sm" style="color:#c8a97e;">
            ← Back to Subjects
        </a>
        <h1 class="mt-2 text-xl font-bold" style="font-family:'Fraunces',serif; color:#1c1814;">Request New Subject</h1>
        <p class="text-sm mt-0.5 text-gray-500">Submitted requests go to your Dean for approval.</p>
    </div>

    <div class="p-6 bg-white border rounded-2xl" style="border-color:#e5e7eb;">
        <form id="subject-form" method="POST" action="{{ route('program-head.subjects.store') }}">
            @csrf

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
                    <input type="number" name="units" id="units" value="{{ old('units', 3) }}"
                           min="1" max="10"
                           class="w-full px-3 py-2 text-sm border rounded-xl focus:outline-none focus:ring-2"
                           style="border-color:#d1d5db;">
                    @error('units')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
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
                        style="background:#1c1814; color:#f0dfc0;">
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
        confirmButtonColor: '#1c1814',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Submit Request',
        cancelButtonText: 'Go Back',
    }).then(result => {
        if (result.isConfirmed) document.getElementById('subject-form').submit();
    });
});
</script>

</x-sidebar-layout>
