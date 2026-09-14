<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Support inbox' }} · {{ config('app.name', 'MerebHub') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    <header class="border-b border-zinc-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4">
            <div>
                <a href="{{ route('staff.support.index') }}" class="text-lg font-extrabold">MerebHub Support</a>
                <p class="text-xs text-zinc-500">Staff workspace · {{ auth('staff')->user()->full_name }}</p>
            </div>
            <nav class="flex flex-wrap items-center gap-4 text-sm font-bold">
                <a href="{{ route('staff.support.index') }}" class="text-teal-800 hover:underline">Inbox</a>
                <a href="{{ url('/admin') }}" class="text-zinc-700 hover:text-teal-800">Admin panel</a>
            </nav>
        </div>
    </header>
    @if (session('status'))
        <div class="mx-auto mt-5 max-w-7xl px-5">
            <p class="rounded-lg bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800" role="status">{{ session('status') }}</p>
        </div>
    @endif
    @if ($errors->any())
        <div class="mx-auto mt-5 max-w-7xl px-5">
            <p class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800" role="alert">{{ $errors->first() }}</p>
        </div>
    @endif
    <main class="mx-auto max-w-7xl px-5 py-8 lg:py-12">
        @yield('content')
    </main>
</body>
</html>
