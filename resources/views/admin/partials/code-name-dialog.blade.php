{{-- Code + name form in a dialog. Params: $id, $title, $action, $method (POST|PUT), $code, $name, $submit --}}
@php $reopen = $errors->any() && old('_form') === $id; @endphp
<dialog id="{{ $id }}" style="padding:0; border:none; border-radius:12px; box-shadow:0 20px 40px rgba(0,0,0,.25); width:min(92vw,420px);">
    <form method="POST" action="{{ $action }}" style="padding:20px;">
        @csrf
        @if($method !== 'POST') @method($method) @endif
        <input type="hidden" name="_form" value="{{ $id }}">
        <h3 style="margin:0 0 14px; font-size:16px; font-weight:700; color:#111827;">{{ $title }}</h3>

        @if($reopen)
            <ul style="margin:0 0 12px; padding:10px 14px 10px 28px; font-size:13px; color:#991b1b; background:#fef2f2; border:1px solid #fecaca; border-radius:8px;">
                @foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        @endif

        <label style="display:block; font-size:13px; font-weight:600; color:#374151;">Code
            <input type="text" name="code" value="{{ $reopen ? old('code') : $code }}" required maxlength="20" placeholder="e.g. CCS"
                   style="display:block; width:100%; margin-top:4px; padding:8px; text-transform:uppercase; border:1px solid #d1d5db; border-radius:8px;">
        </label>
        <label style="display:block; margin-top:12px; font-size:13px; font-weight:600; color:#374151;">Name
            <input type="text" name="name" value="{{ $reopen ? old('name') : $name }}" required maxlength="255" placeholder="e.g. College of Computer Studies"
                   style="display:block; width:100%; margin-top:4px; padding:8px; border:1px solid #d1d5db; border-radius:8px;">
        </label>

        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:18px;">
            <button type="button" onclick="document.getElementById('{{ $id }}').close()" style="padding:8px 14px; font-size:13px; font-weight:600; color:#374151; background:#fff; border:1px solid #d1d5db; border-radius:8px; cursor:pointer;">Cancel</button>
            <button type="submit" style="padding:8px 14px; font-size:13px; font-weight:600; color:#fff; background:#4f46e5; border:none; border-radius:8px; cursor:pointer;">{{ $submit }}</button>
        </div>
    </form>
</dialog>
@if($reopen)
    <script>document.addEventListener('DOMContentLoaded', function () { document.getElementById(@json($id)).showModal(); });</script>
@endif
