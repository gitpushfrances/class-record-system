{{-- resources/views/layouts/partials/sidebar.blade.php --}}

<style>
    #sidebar {
        width: 240px;
        transition: width 0.22s cubic-bezier(.4,0,.2,1);
    }
    #sidebar.collapsed {
        width: 64px;
    }
    /* Hide text labels + email + role badge when collapsed */
    #sidebar.collapsed .sidebar-label,
    #sidebar.collapsed .sidebar-user-info {
        display: none;
    }
    /* Center nav icons when collapsed */
    #sidebar.collapsed .sidebar-nav-link {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }
    #sidebar.collapsed .sidebar-nav-link .sidebar-active-bar {
        display: none;
    }
    /* Brand: hide wordmark, center icon */
    #sidebar.collapsed .brand-wordmark { display: none; }
    #sidebar.collapsed .brand-area {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }
    /* Rotate toggle chevron when collapsed */
    #sidebar.collapsed #toggle-icon {
        transform: rotate(180deg);
    }
    /* User footer when collapsed: center everything */
    #sidebar.collapsed .user-footer {
        padding-left: 0;
        padding-right: 0;
    }
    #sidebar.collapsed .user-profile-link {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }
    #sidebar.collapsed .logout-btn {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }
    /* Mobile: always full width, slide in/out */
    @media (max-width: 1023px) {
        #sidebar {
            position: fixed !important;
            inset-y: 0;
            left: 0;
            z-index: 40;
            width: 240px !important;
            transform: translateX(-100%);
            transition: transform 0.22s cubic-bezier(.4,0,.2,1);
        }
        #sidebar.is-open {
            transform: translateX(0);
        }
        #sidebar-toggle { display: none !important; }
    }
</style>

<aside id="sidebar"
       class="relative flex flex-col flex-shrink-0"
       style="background: linear-gradient(180deg, #1c1814 0%, #211a12 100%); border-right: 1px solid rgba(200,169,126,0.1);">

    {{-- ── Brand + Toggle ── --}}
    <div class="brand-area relative flex items-center gap-2.5 px-4 py-4 border-b flex-shrink-0"
         style="border-color: rgba(200,169,126,0.1); min-height: 57px;">
        <a href="#" class="flex items-center gap-2.5 min-w-0">
            <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg"
                 style="background: linear-gradient(135deg, #9a7a50, #c8a97e); box-shadow: 0 2px 8px rgba(200,169,126,0.25);">
                <i class="text-xs fas fa-book-open" style="color: #1c1814;"></i>
            </div>
            <span class="text-sm font-bold leading-none brand-wordmark sidebar-label whitespace-nowrap"
                  style="font-family: 'Fraunces', serif; color: #f0dfc0;">
                Class <em class="not-italic font-light" style="color: #c8a97e;">Record</em>
            </span>
        </a>

        {{-- Collapse toggle (desktop only) --}}
        <button id="sidebar-toggle"
                onclick="toggleSidebar()"
                class="absolute z-50 items-center justify-center hidden w-6 h-6 transition-all duration-150 -translate-y-1/2 rounded-full -right-3 top-1/2 lg:flex"
                style="background: #2a2018; border: 1px solid rgba(200,169,126,0.25); color: #c8a97e; box-shadow: 0 2px 8px rgba(0,0,0,0.4);"
                onmouseover="this.style.background='rgba(200,169,126,0.2)'; this.style.borderColor='rgba(200,169,126,0.5)';"
                onmouseout="this.style.background='#2a2018'; this.style.borderColor='rgba(200,169,126,0.25)';"
                title="Toggle sidebar">
            <i id="toggle-icon" class="text-xs transition-transform fas fa-chevron-left duration-220"></i>
        </button>
    </div>

    {{-- ── Nav links ── --}}
    <nav class="flex-1 px-3 py-3 space-y-0.5 overflow-y-auto overflow-x-hidden">

        @if(auth()->user()->role === 'super_admin')
            @include('layouts.partials.sidebar-link', [
                'href'   => route('admin.dashboard'),
                'active' => request()->routeIs('admin.dashboard'),
                'icon'   => 'fa-gauge-high',
                'label'  => 'Dashboard',
            ])
            @include('layouts.partials.sidebar-link', [
                'href'   => route('admin.deans.index'),
                'active' => request()->routeIs('admin.deans.*'),
                'icon'   => 'fa-user-tie',
                'label'  => 'Deans',
            ])
            @include('layouts.partials.sidebar-link', [
                'href'   => route('admin.subjects.index'),
                'active' => request()->routeIs('admin.subjects.*'),
                'icon'   => 'fa-book',
                'label'  => 'Subjects',
            ])
            @include('layouts.partials.sidebar-link', [
                'href'   => route('admin.academic.index'),
                'active' => request()->routeIs('admin.academic.*'),
                'icon'   => 'fa-calendar-days',
                'label'  => 'Academic Period',
            ])
        @endif

        @if(auth()->user()->role === 'dean')
            @include('layouts.partials.sidebar-link', [
                'href'   => route('dean.dashboard'),
                'active' => request()->routeIs('dean.dashboard'),
                'icon'   => 'fa-gauge-high',
                'label'  => 'Dashboard',
            ])
            @include('layouts.partials.sidebar-link', [
                'href'   => route('dean.teachers.pending'),
                'active' => request()->routeIs('dean.teachers.*'),
                'icon'   => 'fa-user-clock',
                'label'  => 'Pending Teachers',
            ])
            @include('layouts.partials.sidebar-link', [
                'href'   => route('dean.sections.index'),
                'active' => request()->routeIs('dean.sections.*'),
                'icon'   => 'fa-layer-group',
                'label'  => 'Sections',
            ])
            @include('layouts.partials.sidebar-link', [
                'href'   => route('dean.students.index'),
                'active' => request()->routeIs('dean.students.*'),
                'icon'   => 'fa-users',
                'label'  => 'Students',
            ])
            @include('layouts.partials.sidebar-link', [
                'href'   => route('dean.subjects.index'),
                'active' => request()->routeIs('dean.subjects.*'),
                'icon'   => 'fa-book',
                'label'  => 'Subjects',
            ])
            @include('layouts.partials.sidebar-link', [
                'href'   => route('dean.enrollments.index'),
                'active' => request()->routeIs('dean.enrollments.*'),
                'icon'   => 'fa-clipboard-list',
                'label'  => 'Enrollments',
            ])
        @endif

        @if(auth()->user()->role === 'teacher')
            @include('layouts.partials.sidebar-link', [
                'href'   => route('teacher.dashboard'),
                'active' => request()->routeIs('teacher.dashboard')
                           || request()->routeIs('teacher.classes.*')
                           || request()->routeIs('teacher.grades.*')
                           || request()->routeIs('teacher.attendance.*'),
                'icon'   => 'fa-chalkboard-teacher',
                'label'  => 'My Classes',
            ])
        @endif

    </nav>

    {{-- ── User Footer (Option A) ── --}}
    <div class="flex flex-col flex-shrink-0 gap-1 px-3 py-3 border-t user-footer"
         style="border-color: rgba(200,169,126,0.1);">

        {{-- Avatar + name + email → clicks to profile --}}
        <a href="{{ route('profile.edit') }}"
           class="user-profile-link flex items-center gap-2.5 px-2 py-2 rounded-lg transition-all duration-150 group min-w-0"
           onmouseover="this.style.background='rgba(200,169,126,0.08)';"
           onmouseout="this.style.background='transparent';"
           title="My Profile">

            {{-- Avatar initial --}}
            <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-xs font-bold rounded-full"
                 style="background: rgba(200,169,126,0.15); color: #c8a97e; border: 1px solid rgba(200,169,126,0.25);">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>

            {{-- Name + email (hidden when collapsed) --}}
            <div class="flex-1 min-w-0 sidebar-user-info">
                <div class="text-xs font-semibold truncate" style="color: #f0dfc0;">
                    {{ auth()->user()->name }}
                </div>
                <div class="text-xs truncate" style="color: rgba(200,169,126,0.45);">
                    {{ auth()->user()->email }}
                </div>
            </div>

            {{-- Subtle chevron hint on hover --}}
            <i class="flex-shrink-0 text-xs transition-opacity duration-150 opacity-0 sidebar-user-info fas fa-chevron-right group-hover:opacity-50"
               style="color: #c8a97e;"></i>
        </a>

        {{-- Logout --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="logout-btn w-full flex items-center gap-2.5 px-2 py-2 rounded-lg text-xs font-medium transition-all duration-150"
                    style="color: rgba(248,113,113,0.65); border: 1px solid transparent;"
                    onmouseover="this.style.background='rgba(239,68,68,0.08)'; this.style.borderColor='rgba(239,68,68,0.2)'; this.style.color='#f87171';"
                    onmouseout="this.style.background='transparent'; this.style.borderColor='transparent'; this.style.color='rgba(248,113,113,0.65)';"
                    title="Logout">
                <i class="flex-shrink-0 w-8 text-xs text-center fas fa-arrow-right-from-bracket"></i>
                <span class="sidebar-label">Logout</span>
            </button>
        </form>

    </div>

</aside>

<script>
    const STORAGE_KEY = 'cr_sidebar_collapsed';
    const sidebar     = document.getElementById('sidebar');

    function applyCollapsed(collapsed) {
        collapsed
            ? sidebar.classList.add('collapsed')
            : sidebar.classList.remove('collapsed');
    }

    function toggleSidebar() {
        const nowCollapsed = !sidebar.classList.contains('collapsed');
        applyCollapsed(nowCollapsed);
        localStorage.setItem(STORAGE_KEY, nowCollapsed ? '1' : '0');
    }

    // Mobile helpers (called from app.blade.php)
    function openSidebar() {
        sidebar.classList.add('is-open');
        document.getElementById('sidebar-overlay').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('is-open');
        document.getElementById('sidebar-overlay').classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Restore saved state on load
    (function () {
        if (localStorage.getItem(STORAGE_KEY) === '1') applyCollapsed(true);
    })();
</script>
