@props(['href', 'label' => 'Back'])
<a href="{{ $href }}"
   style="display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:14px; font-weight:500; line-height:20px; color:#374151; background:#ffffff; border:1px solid #d1d5db; border-radius:8px; text-decoration:none; transition:background .15s;"
   onmouseover="this.style.background='#f3f4f6';"
   onmouseout="this.style.background='#ffffff';">
    <i class="fas fa-arrow-left" style="font-size:12px;"></i>
    <span>{{ $label }}</span>
</a>
