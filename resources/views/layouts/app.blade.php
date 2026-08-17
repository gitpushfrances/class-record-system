{{-- resources/views/layouts/app.blade.php --}}
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-stone-100" style="font-family: 'DM Sans', sans-serif;">

    {{-- Mobile overlay --}}
    <div id="sidebar-overlay"
         onclick="closeSidebar()"
         class="fixed inset-0 z-30 hidden bg-black/50 backdrop-blur-sm lg:hidden">
    </div>

    <div class="flex min-h-screen">

        @include('layouts.partials.sidebar')

        {{-- ── MAIN AREA ── --}}
        <div id="main-area" class="flex flex-col flex-1 min-w-0">

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
                <div class="text-xs truncate max-w-[140px]" style="color: rgba(200,169,126,0.5);">
                    {{ auth()->user()->name }}
                </div>
            </header>

            {{-- Flash messages --}}
            <div class="px-6 pt-5 space-y-2">
                @if(session('success'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: @json(session('success')),
                                timer: 2500,
                                showConfirmButton: false,
                            });
                        });
                    </script>
                @endif
                @if(session('warning'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Warning',
                                text: @json(session('warning')),
                            });
                        });
                    </script>
                @endif
                @if(session('error'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: @json(session('error')),
                            });
                        });
                    </script>
                @endif
                @if($errors->any())
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                html: @json(implode('<br>', $errors->all())),
                            });
                        });
                    </script>
                @endif
            </div>

            {{-- Page content --}}
            <main class="flex-1 px-6 py-6 pb-10">
                {{ $slot ?? '' }}
                @yield('content')
            </main>

        </div>
    </div>

</body>
</html>
