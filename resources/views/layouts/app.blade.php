<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>@yield('title', __('app.brand')) · {{ __('app.brand') }}</title>
    <meta name="description" content="{{ __('app.tagline') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,300..600,0..1,0&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-slate-100 antialiased">
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <header class="sticky top-0 z-40 border-b border-white/8 bg-[#070913]/75 backdrop-blur-xl">
        <nav class="mx-auto flex h-17 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Основная навигация">
            <a href="{{ route('home') }}" class="group flex items-center gap-2.5 font-extrabold tracking-tight">
                <span class="brand-mark"><span class="material-symbols-outlined">stadia_controller</span></span>
                <span class="text-lg">Game<span class="text-violet-400">List</span></span>
            </a>
            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a class="nav-link" href="{{ route('lists.index') }}">
                        <span class="material-symbols-outlined">view_list</span>
                        <span class="hidden sm:inline">{{ __('app.nav.lists') }}</span>
                    </a>
                    <span class="hidden text-sm text-slate-400 md:inline">{{ '@'.auth()->user()->login }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="icon-button" title="{{ __('app.nav.logout') }}" aria-label="{{ __('app.nav.logout') }}">
                            <span class="material-symbols-outlined">logout</span>
                        </button>
                    </form>
                @else
                    <a class="nav-link" href="{{ route('login') }}">{{ __('app.nav.login') }}</a>
                    <a class="button button-primary button-sm" href="{{ route('register') }}">{{ __('app.nav.register') }}</a>
                @endauth
            </div>
        </nav>
    </header>

    @if (session('success'))
        <div class="toast" role="status">
            <span class="material-symbols-outlined text-emerald-300">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <main class="relative z-10 mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        @yield('content')
    </main>

    <footer class="relative z-10 mx-auto max-w-7xl px-4 py-10 text-center text-xs text-slate-600">
        GameList · {{ date('Y') }} · {{ __('app.tagline') }}
    </footer>
</body>
</html>
