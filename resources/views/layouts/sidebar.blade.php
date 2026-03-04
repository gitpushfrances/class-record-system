<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Class Record'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;1,9..144,300&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-stone-100" style="font-family: 'DM Sans', sans-serif;">

{{-- Mobile overlay --}}
<div id="sidebar-overlay"
     onclick="closeSidebar()"
     class="fixed inset-0 z-30 hidden bg-black/50 backdrop-blur-sm lg:hidden">
</div>

<div class="flex min-h-screen">

    {{-- ── SIDEBAR ── --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex flex-col transition-transform duration-200 ease-in-out -translate-x-full w-60 lg:static lg:translate-x-0 lg:z-auto"
           style="background: linear-gradient(180deg, #1c1814 0%, #211a12 100%); border-right: 1px solid rgba(200,169,126,0.1);">

        {{-- Brand --}}
        <a href="#" class="flex items-center gap-2.5 px-4 py-4 border-b"
           style="border-color: rgba(200,169,126,0.1);">
            <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg"
                 style="background: linear-gradient(135deg, #9a7a50, #c8a97e); box-shadow: 0 2px 8px rgba(200,169,126,0.25);">
                <i class="text-xs fas fa-book-open" style="color: #1c1814;"></i>
            </div>
            <span class="text-sm font-bold leading-none" style="font-family: 'Fraunces', serif; color: #f0dfc0;">
                Class <em class="not-italic font-light" style="color: #c8a97e;">Record</em>
            </span>
        </a>

        {{-- Role badge --}}
        <div class="px-4 py-2.5 border-b" style="border-color: rgba(200,169,126,0.08);">
            @if(auth()->user()->role === 'super_admin')
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-widest uppercase px-2.5 py-1 rounded-full"
                      style="background: rgba(251,191,36,0.1); color: #fbbf24; border: 1px solid rgba(251,191,36,0.2);">
                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-400"></span> Super Admin
                </span>
            @elseif(auth()->user()->role === 'dean')
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-widest uppercase px-2.5 py-1 rounded-full"
                      style="background: rgba(52,211,153,0.1); color: #34d399; border: 1px solid rgba(52,211,153,0.2);">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Dean
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-widest uppercase px-2.5 py-1 rounded-full"
                      style="background: rgba(200,169,126,0.1); color: #e0c99a; border: 1px solid rgba(200,169,126,0.2);">
                    <span class="w-1.5 h-1.5 rounded-full" style="background:#c8a97e;"></span> Teacher
                </span>
            @endif
        </div>

        {{-- Nav links --}}
        <nav class="flex-1 px-3 py-3 space-y-0.5 overflow-y-auto">

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

        {{-- User footer --}}
        <div class="px-3 py-3 space-y-2 border-t" style="border-color: rgba(200,169,126,0.1);">
            {{-- User info --}}
            <div class="flex items-center gap-2.5 px-2 py-1.5">
                <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-xs font-bold rounded-full"
                     style="background: rgba(200,169,126,0.15); color: #c8a97e; border: 1px solid rgba(200,169,126,0.2);">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-semibold truncate" style="color: #f0dfc0;">
                        {{ auth()->user()->name }}
                    </div>
                    <div class="text-xs truncate" style="color: rgba(200,169,126,0.5);">
                        {{ auth()->user()->email }}
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex gap-1.5 pt-1">
                <a href="{{ route('profile.edit') }}"
                   class="flex-1 flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg text-xs font-medium transition-all duration-150"
                   style="color: #c8a97e; border: 1px solid rgba(200,169,126,0.2); background: rgba(200,169,126,0.06);"
                   onmouseover="this.style.background='rgba(200,169,126,0.14)'; this.style.borderColor='rgba(200,169,126,0.4)';"
                   onmouseout="this.style.background='rgba(200,169,126,0.06)'; this.style.borderColor='rgba(200,169,126,0.2)';">
                    <i class="fas fa-user-pen"></i>
                    <span>Profile</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-1.5 px-2 py-2 rounded-lg text-xs font-medium transition-all duration-150"
                            style="color: #f87171; border: 1px solid rgba(239,68,68,0.25); background: rgba(239,68,68,0.06);"
                            onmouseover="this.style.background='rgba(239,68,68,0.14)'; this.style.borderColor='rgba(239,68,68,0.45)';"
                            onmouseout="this.style.background='rgba(239,68,68,0.06)'; this.style.borderColor='rgba(239,68,68,0.25)';">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- ── MAIN AREA ── --}}
    <div class="flex flex-col flex-1 min-w-0">

        {{-- Mobile topbar --}}
        <header class="flex items-center justify-between flex-shrink-0 px-4 h-14 lg:hidden"
                style="background: #1c1814; border-bottom: 1px solid rgba(200,169,126,0.1);">
            <div class="flex items-center gap-2.5">
                <button onclick="openSidebar()"
                        class="flex items-center justify-center w-8 h-8 transition rounded-lg"
                        style="color: #c8a97e;"
                        onmouseover="this.style.background='rgba(200,169,126,0.1)';"
                        onmouseout="this.style.background='transparent';">
                    <i class="text-sm fas fa-bars"></i>
                </button>
                <span class="text-sm font-bold" style="font-family: 'Fraunces', serif; color: #f0dfc0;">
                    Class <em class="not-italic font-light" style="color: #c8a97e;">Record</em>
                </span>
            </div>
            <div class="text-xs" style="color: rgba(200,169,126,0.5);">
                {{ auth()->user()->name }}
            </div>
        </header>

        {{-- Flash messages --}}
        <div class="px-6 pt-5 space-y-2">
            @if(session('success'))
                <div class="flex items-center gap-2.5 px-4 py-3 text-sm font-medium rounded-xl border"
                     style="background: #f0fdf4; color: #166534; border-color: #bbf7d0;">
                    <i class="text-xs fas fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('warning'))
                <div class="flex items-center gap-2.5 px-4 py-3 text-sm font-medium rounded-xl border"
                     style="background: #fffbeb; color: #92400e; border-color: #fde68a;">
                    <i class="text-xs fas fa-triangle-exclamation"></i>
                    {{ session('warning') }}
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-center gap-2.5 px-4 py-3 text-sm font-medium rounded-xl border"
                     style="background: #fef2f2; color: #991b1b; border-color: #fecaca;">
                    <i class="text-xs fas fa-circle-xmark"></i>
                    {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="px-4 py-3 text-sm border rounded-xl"
                     style="background: #fef2f2; color: #991b1b; border-color: #fecaca;">
                    <ul class="pl-4 space-y-1 list-disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Page content --}}
        <main class="flex-1 px-6 py-6 pb-10">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

    </div>
</div>

<script>
    function openSidebar() {
        document.getElementById('sidebar').classList.add('is-open');
        document.getElementById('sidebar-overlay').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('is-open');
        document.getElementById('sidebar-overlay').classList.add('hidden');
        document.body.style.overflow = '';
    }
</script>

<style>
    #sidebar.is-open { transform: translateX(0) !important; }
    @media (max-width: 1023px) {
        #sidebar { transform: translateX(-100%); }
    }
</style>

</body>
</html>
