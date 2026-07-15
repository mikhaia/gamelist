<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#070913">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.brand')) · {{ __('app.brand') }}</title>
    <meta name="description" content="{{ __('app.tagline') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="preload" href="{{ asset('fonts/material-symbols-outlined.woff2') }}" as="font" type="font/woff2" crossorigin>
    <style>
        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 300 600;
            font-display: block;
            src: url('{{ asset('fonts/material-symbols-outlined.woff2') }}') format('woff2');
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-slate-100 antialiased">
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <header class="sticky top-0 z-40 border-b border-white/8 bg-[#070913]/75 backdrop-blur-xl">
        <nav class="mx-auto flex h-17 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Основная навигация">
            <a href="{{ route('home') }}" class="group flex items-center gap-2.5 font-extrabold tracking-tight">
                <span class="brand-mark"><span class="material-symbols-outlined">stadia_controller</span></span>
                <span class="hidden text-lg sm:inline">Game<span class="text-violet-400">List</span></span>
            </a>
            <div class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a class="nav-link" href="{{ route('lists.index') }}">
                        <span class="material-symbols-outlined">view_list</span>
                        <span class="hidden sm:inline">{{ __('app.nav.lists') }}</span>
                    </a>
                    <a class="nav-link" href="{{ route('history.index') }}" title="{{ __('app.nav.history') }}">
                        <span class="material-symbols-outlined">history</span>
                        <span class="hidden sm:inline">{{ __('app.nav.history') }}</span>
                    </a>
                    <span class="hidden text-sm text-slate-400 md:inline">{{ '@'.auth()->user()->login }}</span>
                    <a href="{{ route('settings.edit') }}" class="grid size-10 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5 text-slate-400 transition hover:border-violet-400/30 hover:text-white" title="Настройки" aria-label="Настройки">
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="Аватар {{ auth()->user()->login }}" class="h-full w-full object-cover">
                        @else
                            <span class="material-symbols-outlined">person</span>
                        @endif
                    </a>
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
