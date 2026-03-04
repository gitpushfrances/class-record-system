{{-- resources/views/layouts/partials/sidebar-link.blade.php --}}
<a href="{{ $href }}"
   class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 group"
   title="{{ $label }}"
   @if($active)
       style="background: rgba(200,169,126,0.12); color: #e0c99a; border: 1px solid rgba(200,169,126,0.15);"
   @else
       style="color: rgba(200,169,126,0.45); border: 1px solid transparent;"
       onmouseover="this.style.background='rgba(200,169,126,0.06)'; this.style.color='rgba(200,169,126,0.85)';"
       onmouseout="this.style.background='transparent'; this.style.color='rgba(200,169,126,0.45)';"
   @endif
>
    <i class="fas {{ $icon }} w-4 text-center text-xs flex-shrink-0 {{ $active ? '' : 'opacity-60' }}"></i>
    <span class="truncate sidebar-label">{{ $label }}</span>
    @if($active)
        <span class="flex-shrink-0 w-1 h-4 ml-auto rounded-full sidebar-active-bar"
              style="background: #c8a97e;"></span>
    @endif
</a>
