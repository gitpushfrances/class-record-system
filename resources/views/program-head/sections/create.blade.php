<x-sidebar-layout>

    <div class="max-w-xl mx-auto">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Create Section</h2>
            <p class="text-sm text-gray-500">A section is a persistent student group under your program. Adviser and term details are set separately.</p>
        </div>

        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-6">
                @if($errors->any())
                    <div class="px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form id="createSectionForm" action="{{ route('program-head.sections.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Year Level</label>
                        <select name="year_level" id="year_level" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-1" required onchange="syncYearNumber()">
                            <option value="">Select Year Level</option>
                            @foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $level)
                                <option value="{{ $level }}" {{ old('year_level') == $level ? 'selected' : '' }}>{{ $level }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="year_number" id="year_number" value="{{ old('year_number') }}">
                        @error('year_level')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="mb-6">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Section</label>
                        <input
                            type="text"
                            name="section_letter"
                            id="section_letter"
                            value="{{ old('section_letter') }}"
                            placeholder="e.g., A, B, Block 1, Rizal"
                            list="section-suggestions"
                            class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-1"
                            required
                            autocomplete="off"
                        >
                        <datalist id="section-suggestions">
                            <option value="A"><option value="B"><option value="C">
                            <option value="D"><option value="E"><option value="F">
                            <option value="Block 1"><option value="Block 2">
                        </datalist>
                        <p class="mt-1 text-xs text-gray-400">Type freely or pick a suggestion.</p>
                        @error('section_letter')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-2">
                        <button type="button" onclick="confirmCreate()" class="px-4 py-2 text-sm font-medium text-white rounded hover:opacity-90" style="background-color: #c8a97e;">Create Section</button>
                        <a href="{{ route('program-head.sections.index') }}" class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded hover:bg-gray-300">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const yearMap = { '1st Year': '1', '2nd Year': '2', '3rd Year': '3', '4th Year': '4', '5th Year': '5' };
        function syncYearNumber() {
            document.getElementById('year_number').value = yearMap[document.getElementById('year_level').value] || '';
        }
        function confirmCreate() {
            const form = document.getElementById('createSectionForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const yearLevel = document.getElementById('year_level').value;
            const section = document.getElementById('section_letter').value;
            Swal.fire({
                title: 'Confirm Section',
                html: `<div style="text-align:left;font-size:14px;line-height:2">
                        <div><span style="color:#888">Year Level:</span> <strong>${yearLevel}</strong></div>
                        <div><span style="color:#888">Section:</span> <strong>${section}</strong></div>
                       </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Create',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#c8a97e',
                cancelButtonColor: '#6b7280',
            }).then((result) => { if (result.isConfirmed) form.submit(); });
        }
    </script>

</x-sidebar-layout>
