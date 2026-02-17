<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Class Record') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans bg-gray-100">

{{-- Navbar --}}
<nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="flex items-center justify-between h-16 px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex items-center gap-6">
            <span class="text-lg font-bold text-indigo-600">Class Record</span>
            <a href="{{ route('teacher.dashboard') }}" class="text-sm font-medium text-gray-600 hover:text-indigo-600">Dashboard</a>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-500">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="text-sm text-red-500 hover:underline">Logout</button>
            </form>
        </div>
    </div>
</nav>

<div class="px-4 py-8 mx-auto max-w-7xl sm:px-6 lg:px-8">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="px-4 py-3 mb-4 text-sm text-green-700 border border-green-200 rounded-lg bg-green-50">
            {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="px-4 py-3 mb-4 text-sm text-yellow-700 border border-yellow-200 rounded-lg bg-yellow-50">
            {{ session('warning') }}
        </div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 mb-4 text-sm text-red-700 border border-red-200 rounded-lg bg-red-50">
            <ul class="pl-4 space-y-1 list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</div>

</body>
</html>
