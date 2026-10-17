<x-sidebar-layout>

    @php
        $field = 'display:block; width:100%; padding:8px 10px; font-size:14px; border:1px solid #d1d5db; border-radius:6px;';
        $label = 'display:block; margin-bottom:4px; font-size:14px; font-weight:500; color:#374151;';
        $grid  = 'display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px; padding:16px 24px;';
    @endphp

    <div class="max-w-3xl mx-auto space-y-6">

        <div>
            <h2 class="text-lg font-semibold text-gray-800">Report of Grades Settings</h2>
            <p class="text-sm text-gray-500">Printed on every Report of Grades (form ESSU-ACAD-712.b).</p>
        </div>

        @if($errors->any())
            <div class="px-4 py-3 text-sm text-red-700 bg-red-100 border border-red-300 rounded">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.rog.update') }}" enctype="multipart/form-data"
              class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            @csrf
            @method('PUT')

            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Header logos</h3>
                <p class="text-xs text-gray-500">PNG or JPG, up to 2 MB. A transparent PNG prints best.</p>
            </div>
            <div style="{{ $grid }}">
                @foreach(['left' => 'Left logo (university)', 'right' => 'Right logo'] as $side => $title)
                    @php $key = "rog_logo_{$side}"; @endphp
                    <div>
                        <span style="{{ $label }}">{{ $title }}</span>
                        <div style="display:flex; align-items:center; justify-content:center; height:90px; margin-bottom:8px; background:#f9fafb; border:1px dashed #d1d5db; border-radius:8px;">
                            <img src="{{ \App\Models\Setting::rogLogoUrl($side) }}" alt="{{ $title }}" style="max-height:76px; max-width:100%;">
                        </div>
                        <input type="file" name="{{ $key }}" accept="image/png,image/jpeg" style="font-size:13px;">
                        @if($settings[$key])
                            <label style="display:flex; align-items:center; gap:6px; margin-top:8px; font-size:12px; color:#4b5563;">
                                <input type="checkbox" name="reset_logo_{{ $side }}" value="1"> Use the default logo
                            </label>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="px-6 py-4 border-t border-b border-gray-100">
                <h3 class="text-sm font-semibold text-gray-700">Footer and Registrar</h3>
            </div>
            <div style="{{ $grid }}">
                @foreach([
                    'rog_form_code'      => ['Form code', 'ESSU-ACAD-712.b'],
                    'rog_form_version'   => ['Version', 'Version 5'],
                    'rog_effectivity'    => ['Effectivity date', 'March 15, 2024'],
                    'rog_registrar_name' => ['Registrar (name and credentials)', 'e.g. JUAN A. DELA CRUZ, DBA'],
                ] as $key => [$title, $hint])
                    <div>
                        <label for="{{ $key }}" style="{{ $label }}">{{ $title }}</label>
                        <input id="{{ $key }}" type="text" name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" placeholder="{{ $hint }}" style="{{ $field }}">
                    </div>
                @endforeach
            </div>

            <div style="display:flex; justify-content:flex-end; padding:16px 24px; background:#f9fafb; border-top:1px solid #f3f4f6;">
                <button type="submit" style="padding:8px 18px; font-size:14px; font-weight:600; color:#fff; background:#0a5b05; border:none; border-radius:8px; cursor:pointer;">Save settings</button>
            </div>
        </form>
    </div>

</x-sidebar-layout>
