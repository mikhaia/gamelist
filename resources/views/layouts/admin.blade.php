<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#eef2ff">
    <title>@yield('title', 'Статистика') · GameList Admin</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preload" href="{{ asset('fonts/material-symbols-outlined.woff2') }}?v=20260723-2" as="font" type="font/woff2" crossorigin>
    <style>
        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url('{{ asset('fonts/material-symbols-outlined.woff2') }}?v=20260723-2') format('woff2');
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body min-h-screen text-slate-900 antialiased">
    <div class="relative min-h-screen lg:grid lg:grid-cols-[17rem_minmax(0,1fr)]">
        <aside class="relative z-20 overflow-hidden bg-[#080a14]/95 text-slate-300 shadow-2xl shadow-slate-950/25 backdrop-blur-2xl lg:sticky lg:top-0 lg:h-screen">
            <div class="pointer-events-none absolute -top-24 -left-20 size-64 rounded-full bg-violet-600/20 blur-3xl"></div>
            <div class="pointer-events-none absolute right-0 bottom-24 size-56 rounded-full bg-cyan-500/10 blur-3xl"></div>

            <div class="relative flex h-full flex-col p-4 lg:p-5">
                <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4 lg:pb-6">
                    <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-3">
                        <span class="brand-mark"><span class="material-symbols-outlined">stadia_controller</span></span>
                        <span>
                            <span class="block text-base font-extrabold tracking-tight text-white">Game<span class="text-violet-400">List</span></span>
                            <span class="block text-[9px] font-extrabold uppercase tracking-[.22em] text-slate-500">Control room</span>
                        </span>
                    </a>
                    <a href="{{ route('home') }}" class="grid size-9 cursor-pointer place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-400 transition hover:bg-white/10 hover:text-white" title="Вернуться на сайт" aria-label="Вернуться на сайт">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                </div>

                <nav class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:mt-7 lg:grid-cols-1" aria-label="Разделы админки">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-2xl border px-3 py-3 text-sm font-bold transition {{ request()->routeIs('admin.dashboard') ? 'border-violet-400/25 bg-violet-500/15 text-white shadow-lg shadow-violet-950/20' : 'border-transparent text-slate-400 hover:border-white/8 hover:bg-white/5 hover:text-white' }}">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/6"><span class="material-symbols-outlined">grid_view</span></span>
                        <span>Статистика</span>
                    </a>
                    <a href="{{ route('admin.files.index') }}" class="flex items-center gap-3 rounded-2xl border px-3 py-3 text-sm font-bold transition {{ request()->routeIs('admin.files.*') ? 'border-violet-400/25 bg-violet-500/15 text-white shadow-lg shadow-violet-950/20' : 'border-transparent text-slate-400 hover:border-white/8 hover:bg-white/5 hover:text-white' }}">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/6"><span class="material-symbols-outlined">photo_library</span></span>
                        <span>Файлы</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 rounded-2xl border px-3 py-3 text-sm font-bold transition {{ request()->routeIs('admin.users.*') ? 'border-violet-400/25 bg-violet-500/15 text-white shadow-lg shadow-violet-950/20' : 'border-transparent text-slate-400 hover:border-white/8 hover:bg-white/5 hover:text-white' }}">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/6"><span class="material-symbols-outlined">groups</span></span>
                        <span>Пользователи</span>
                    </a>
                    <a href="{{ route('admin.games.index') }}" class="flex items-center gap-3 rounded-2xl border px-3 py-3 text-sm font-bold transition {{ request()->routeIs('admin.games.*') ? 'border-violet-400/25 bg-violet-500/15 text-white shadow-lg shadow-violet-950/20' : 'border-transparent text-slate-400 hover:border-white/8 hover:bg-white/5 hover:text-white' }}">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/6"><span class="material-symbols-outlined">sports_esports</span></span>
                        <span>Игры</span>
                    </a>
                    <a href="{{ route('admin.errors.index') }}" class="flex items-center gap-3 rounded-2xl border px-3 py-3 text-sm font-bold transition {{ request()->routeIs('admin.errors.*') ? 'border-violet-400/25 bg-violet-500/15 text-white shadow-lg shadow-violet-950/20' : 'border-transparent text-slate-400 hover:border-white/8 hover:bg-white/5 hover:text-white' }}">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/6"><span class="material-symbols-outlined">bug_report</span></span>
                        <span>Ошибки</span>
                    </a>
                </nav>

                <div class="mt-4 border-t border-white/10 pt-4 lg:mt-auto">
                    <div class="flex items-center gap-3 rounded-2xl border border-white/8 bg-white/5 p-3">
                        <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-xl bg-violet-500/15 text-violet-300">
                            @if (auth()->user()->avatar_url)
                                <img src="{{ auth()->user()->avatar_url }}" alt="" class="h-full w-full object-cover">
                            @else
                                <span class="material-symbols-outlined">person</span>
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-extrabold text-white">{{ '@'.auth()->user()->login }}</span>
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-emerald-300">Administrator</span>
                        </span>
                    </div>
                </div>
            </div>
        </aside>

        <div class="relative z-10 min-w-0">
            <header class="sticky top-0 z-30 border-b border-white/70 bg-white/55 px-4 py-3 shadow-sm shadow-slate-900/5 backdrop-blur-2xl sm:px-7 lg:px-10">
                <div class="mx-auto flex max-w-[100rem] items-center justify-between gap-4">
                    <div>
                        <p class="admin-kicker">GameList Admin</p>
                        <p class="mt-0.5 text-sm font-extrabold text-slate-900">@yield('title', 'Статистика')</p>
                    </div>
                    <a href="{{ route('profiles.show', auth()->user()->login) }}" class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200/80 bg-white/70 px-3 py-2 text-xs font-bold text-slate-600 shadow-sm transition hover:bg-white hover:text-violet-700">
                        <span class="material-symbols-outlined text-base">person</span>
                        <span class="hidden sm:inline">Открыть профиль</span>
                    </a>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[100rem] p-4 sm:p-7 lg:p-10">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
