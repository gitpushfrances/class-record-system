{{-- Student import: dialog + error report. Params: $action, $template, $programs (Dean only, else null) --}}
@php $programs ??= null; @endphp

@if(session('import_errors'))
    <div style="margin-bottom:16px; padding:14px 18px; background:#fef2f2; border:1px solid #fecaca; border-radius:10px;">
        <p style="margin:0 0 8px; font-size:14px; font-weight:700; color:#991b1b;">
            <i class="fa-solid fa-circle-exclamation"></i> Nothing was imported. Fix the problems below and upload the file again.
        </p>
        <ul style="margin:0; padding-left:18px; font-size:13px; color:#7f1d1d; max-height:220px; overflow-y:auto;">
            @foreach(array_slice(session('import_errors'), 0, 50) as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
        @if(count(session('import_errors')) > 50)
            <p style="margin:8px 0 0; font-size:12px; color:#7f1d1d;">…and {{ count(session('import_errors')) - 50 }} more.</p>
        @endif
    </div>
@endif

<dialog id="importStudentsDialog" style="padding:0; border:none; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,.25); width:min(92vw,440px);">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" style="padding:20px;">
        @csrf
        <h3 style="margin:0 0 4px; font-size:16px; font-weight:700; color:#111827;">Import Students</h3>
        <p style="margin:0 0 14px; font-size:13px; color:#6b7280;">
            Upload an .xlsx or .csv file with these columns:
            <strong>student_number, last_name, first_name, gender, year_level</strong>
            (required) and middle_name, student_type, email (optional).
            Up to {{ \App\Services\StudentImport::MAX_ROWS }} rows.
        </p>

        @if($programs)
            <label style="display:block; margin-bottom:12px; font-size:13px; font-weight:600; color:#374151;">Program
                <select name="program_id" required style="display:block; width:100%; margin-top:4px; padding:8px; border:1px solid #d1d5db; border-radius:8px;">
                    <option value="">Select a program…</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>{{ $program->code }} — {{ $program->name }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        <label style="display:block; font-size:13px; font-weight:600; color:#374151;">File
            <input type="file" name="file" accept=".xlsx,.csv" required style="display:block; width:100%; margin-top:4px; padding:8px; border:1px solid #d1d5db; border-radius:8px;">
        </label>

        <ul style="margin:12px 0 0; padding-left:18px; font-size:12px; color:#6b7280;">
            <li>Student number format: 00-0000. Gender: Male or Female. Year level: 1st Year to 5th Year (or 1–5).</li>
            <li>If any row has an error, nothing is imported.</li>
            <li>Student numbers that already exist are skipped, not changed.</li>
        </ul>

        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:18px;">
            <a href="{{ $template }}" style="font-size:13px; font-weight:600; color:#0a5b05;"><i class="fa-solid fa-download"></i> Template</a>
            <div style="display:flex; gap:8px;">
                <button type="button" onclick="document.getElementById('importStudentsDialog').close()" style="padding:8px 14px; font-size:13px; font-weight:600; color:#374151; background:#fff; border:1px solid #d1d5db; border-radius:8px; cursor:pointer;">Cancel</button>
                <button type="submit" style="padding:8px 14px; font-size:13px; font-weight:600; color:#fff; background:#0a5b05; border:none; border-radius:8px; cursor:pointer;">Import</button>
            </div>
        </div>
    </form>
</dialog>
