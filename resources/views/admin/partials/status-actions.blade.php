{{--
    Deactivate / Activate and Delete for a department or program (Admin).
    Expects $active (bool), $statusAction and $deleteAction (urls), $label (e.g. "BSIT").
    Delete works only while nothing uses it; otherwise the server answers "Deactivate it instead".
--}}
<form method="POST" action="{{ $statusAction }}" style="display:inline;"
      onsubmit="return confirm(@js($active ? "Deactivate {$label}? It disappears from new accounts and new records; existing records are kept." : "Activate {$label} again?"));">
    @csrf
    @method('PATCH')
    <button type="submit" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; font-size:13px; font-weight:600; border-radius:8px; cursor:pointer; color:#374151; background:#fff; border:1px solid #d1d5db;">
        <i class="fa-solid {{ $active ? 'fa-eye-slash' : 'fa-eye' }}"></i> {{ $active ? 'Deactivate' : 'Activate' }}
    </button>
</form>
<form method="POST" action="{{ $deleteAction }}" style="display:inline;"
      onsubmit="return confirm(@js("Delete {$label}? Only possible while nothing uses it yet. This cannot be undone."));">
    @csrf
    @method('DELETE')
    <button type="submit" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; font-size:13px; font-weight:600; border-radius:8px; cursor:pointer; color:#b91c1c; background:#fff; border:1px solid #fca5a5;">
        <i class="fa-solid fa-trash"></i> Delete
    </button>
</form>
