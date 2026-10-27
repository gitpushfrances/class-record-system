{{-- Student import: upload dialog + problem report dialog. Params: $action, $template --}}
@php
    $groups = session('import_groups') ?: (session('import_errors')
        ? [['title' => 'Problems found', 'hint' => 'Fix the file and upload it again.', 'items' => session('import_errors')]]
        : []);
    $problemCount = collect($groups)->sum(fn ($group) => count($group['items']));
    $shownRows    = 10;
@endphp

@if($groups)
<dialog id="importErrorsDialog" style="padding:0; border:none; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,.25); width:min(94vw,560px); max-height:86vh;">
    <div style="padding:20px 22px 12px; border-bottom:1px solid #f3f4f6;">
        <div style="display:flex; align-items:flex-start; gap:12px;">
            <span style="flex:none; display:flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:999px; background:#fee2e2; color:#b91c1c;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </span>
            <div>
                <h3 style="margin:0; font-size:16px; font-weight:700; color:#111827;">Nothing was imported</h3>
                <p style="margin:2px 0 0; font-size:13px; color:#6b7280;">
                    {{ $problemCount }} {{ \Illuminate\Support\Str::plural('problem', $problemCount) }}
                    in {{ count($groups) }} {{ \Illuminate\Support\Str::plural('group', count($groups)) }}.
                    Fix them in the file and upload it again: no student was saved.
                </p>
            </div>
        </div>
    </div>

    <div style="padding:14px 22px; overflow-y:auto; max-height:52vh;">
        @foreach($groups as $group)
            <details @if($loop->first) open @endif style="margin-bottom:10px; border:1px solid #e5e7eb; border-radius:10px;">
                <summary style="cursor:pointer; padding:10px 14px; font-size:14px; font-weight:600; color:#111827; list-style:none; display:flex; align-items:center; justify-content:space-between; gap:8px;">
                    <span>{{ $group['title'] }}</span>
                    <span style="flex:none; padding:1px 8px; font-size:12px; font-weight:700; color:#991b1b; background:#fee2e2; border-radius:999px;">{{ count($group['items']) }}</span>
                </summary>
                <div style="padding:0 14px 12px;">
                    @if(!empty($group['hint']))
                        <p style="margin:0 0 8px; font-size:12px; color:#6b7280;">{{ $group['hint'] }}</p>
                    @endif
                    <ul style="margin:0; padding-left:18px; font-size:13px; color:#7f1d1d;">
                        @foreach(array_slice($group['items'], 0, $shownRows) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                    @if(count($group['items']) > $shownRows)
                        <p style="margin:6px 0 0; font-size:12px; color:#6b7280;">…and {{ count($group['items']) - $shownRows }} more.</p>
                    @endif
                </div>
            </details>
        @endforeach
    </div>

    <div style="display:flex; justify-content:flex-end; gap:8px; padding:12px 22px 18px; border-top:1px solid #f3f4f6;">
        <button type="button" onclick="document.getElementById('importErrorsDialog').close()" style="padding:8px 14px; font-size:13px; font-weight:600; color:#374151; background:#fff; border:1px solid #d1d5db; border-radius:8px; cursor:pointer;">Close</button>
        <button type="button" onclick="document.getElementById('importErrorsDialog').close(); document.getElementById('importStudentsDialog').showModal()" style="padding:8px 14px; font-size:13px; font-weight:600; color:#fff; background:#0a5b05; border:none; border-radius:8px; cursor:pointer;">Upload again</button>
    </div>
</dialog>
<script>document.getElementById('importErrorsDialog').showModal();</script>
@endif

<dialog id="importStudentsDialog" style="padding:0; border:none; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,.25); width:min(92vw,460px);">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" style="padding:20px;">
        @csrf
        <h3 style="margin:0 0 4px; font-size:16px; font-weight:700; color:#111827;">Import Students</h3>
        <p style="margin:0 0 14px; font-size:13px; color:#6b7280;">
            Upload an .xlsx or .csv file with these columns:
            <strong>student_number, last_name, first_name, gender, year_level</strong>
            (required) and middle_name, student_type, email, <strong>section</strong> (optional).
            Up to {{ \App\Services\StudentImport::MAX_ROWS }} rows.
        </p>

        <label style="display:block; font-size:13px; font-weight:600; color:#374151;">File
            <input type="file" name="file" accept=".xlsx,.csv" required style="display:block; width:100%; margin-top:4px; padding:8px; border:1px solid #d1d5db; border-radius:8px;">
        </label>

        <ul style="margin:12px 0 0; padding-left:18px; font-size:12px; color:#6b7280;">
            <li>Student number format: 00-0000. Gender: Male or Female. Year level: 1st Year to 5th Year (or 1–5).</li>
            <li><strong>Section:</strong> the letter (A, B…) or 1-A. The student is enrolled into that section of their year level. Leave it empty to add the student without a section.</li>
            <li>If any row has a problem, nothing is imported, and every problem is listed.</li>
            <li>Student numbers that already exist are skipped, not changed or enrolled.</li>
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
