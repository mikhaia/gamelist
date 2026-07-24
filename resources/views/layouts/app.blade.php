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
    <link rel="preload" href="{{ asset('fonts/material-symbols-outlined.woff2') }}?v=20260724-2" as="font" type="font/woff2" crossorigin>
    <style>
        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url('{{ asset('fonts/material-symbols-outlined.woff2') }}?v=20260724-2') format('woff2');
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-1SYC1T2FGV"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-1SYC1T2FGV');
    </script>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=110936413', 'ym');

        ym(110936413, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <!-- /Yandex.Metrika counter -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-slate-100 antialiased">
    <noscript><div><img src="https://mc.yandex.ru/watch/110936413" style="position:absolute; left:-9999px;" alt="" /></div></noscript>

    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <header class="sticky top-0 z-40 border-b border-white/8 bg-[#070913]/75 backdrop-blur-xl">
        <nav class="mx-auto flex h-17 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8" aria-label="Основная навигация">
            <a href="{{ route('home') }}" class="group flex items-center gap-2.5 font-extrabold tracking-tight">
                <span class="brand-mark"><span class="material-symbols-outlined">stadia_controller</span></span>
                <span class="hidden text-lg sm:inline">Game<span class="text-violet-400">List</span></span>
            </a>
            <div class="flex min-w-0 items-center gap-0.5 sm:gap-2 lg:gap-3">
                @auth
                    <a class="nav-link" href="{{ route('lists.index') }}">
                        <span class="material-symbols-outlined">view_list</span>
                        <span class="hidden sm:inline">{{ __('app.nav.lists') }}</span>
                    </a>
                    <a class="nav-link" href="{{ route('history.index') }}" title="{{ __('app.nav.history') }}">
                        <span class="material-symbols-outlined">history</span>
                        <span class="hidden sm:inline">{{ __('app.nav.history') }}</span>
                    </a>
                    <a class="nav-link" href="{{ route('friends.index') }}" title="{{ __('app.nav.friends') }}">
                        <span class="material-symbols-outlined">groups</span>
                        <span class="hidden lg:inline">{{ __('app.nav.friends') }}</span>
                    </a>
                    <span class="hidden text-sm text-slate-400 md:inline">{{ '@'.auth()->user()->login }}</span>
                    <a href="{{ route('profiles.show', auth()->user()->login) }}" class="grid size-8 cursor-pointer place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5 text-slate-400 transition hover:border-violet-400/30 hover:text-white sm:size-10" title="Мой профиль" aria-label="Мой профиль">
                        @if (auth()->user()->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="Аватар {{ auth()->user()->login }}" class="h-full w-full object-cover">
                        @else
                            <span class="material-symbols-outlined">person</span>
                        @endif
                    </a>
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="grid size-8 cursor-pointer place-items-center rounded-xl border border-violet-400/20 bg-violet-500/10 text-violet-300 transition hover:border-violet-300/40 hover:bg-violet-500/20 hover:text-white sm:size-10" title="Админка" aria-label="Открыть админку" data-admin-link>
                            <span class="material-symbols-outlined">grid_view</span>
                        </a>
                    @endif
                    <a href="{{ route('settings.edit') }}" class="grid size-8 cursor-pointer place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-400 transition hover:border-violet-400/30 hover:text-white sm:size-10" title="Настройки" aria-label="Настройки">
                        <span class="material-symbols-outlined">settings</span>
                    </a>
                    <a href="{{ route('search.index') }}" class="grid size-8 cursor-pointer place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-400 transition hover:border-violet-400/30 hover:text-white sm:size-10" title="Поиск игр" aria-label="Поиск игр">
                        <span class="material-symbols-outlined">search</span>
                    </a>
                    <div class="relative" data-notification-center data-notification-count="{{ $navigationNotificationCount }}">
                        <button type="button" class="relative grid size-8 cursor-pointer place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-400 transition hover:border-violet-400/30 hover:text-white sm:size-10" data-notification-toggle aria-label="Уведомления" aria-expanded="false">
                            <span class="material-symbols-outlined">notifications</span>
                            <span class="{{ $navigationNotificationCount ? '' : 'hidden' }} absolute -top-1 -right-1 grid min-w-5 place-items-center rounded-full border-2 border-[#090b16] bg-violet-500 px-1 text-[10px] font-extrabold leading-4 text-white" data-notification-badge>
                                {{ $navigationNotificationCount > 99 ? '99+' : $navigationNotificationCount }}
                            </span>
                        </button>

                        <div class="absolute top-12 right-0 z-50 hidden w-[min(23rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-white/10 bg-[#0b0e1a] shadow-2xl shadow-black/50" data-notification-panel role="dialog" aria-label="Уведомления">
                            <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-3">
                                <div>
                                    <p class="text-sm font-extrabold text-white">Уведомления</p>
                                    <p class="text-[11px] text-slate-500">Сохраняются до удаления</p>
                                </div>
                                <form method="POST" action="{{ route('notifications.clear') }}" data-notification-clear>
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-bold text-slate-400 transition hover:text-white" {{ $navigationNotificationCount ? '' : 'disabled' }}>Очистить</button>
                                </form>
                            </div>

                            <div class="max-h-96 overflow-y-auto overscroll-contain" data-notification-list>
                                @foreach ($navigationNotifications as $notification)
                                    <article class="group flex gap-3 border-b border-white/8 px-3 py-3 last:border-b-0 hover:bg-white/[.04]" data-notification-item="{{ $notification->id }}">
                                        <a href="{{ $notification->data['url'] ?? '#' }}" class="flex min-w-0 flex-1 gap-3">
                                            <span class="grid size-9 shrink-0 place-items-center rounded-xl border border-violet-400/15 bg-violet-500/10 text-violet-300">
                                                <span class="material-symbols-outlined text-lg">{{ $notification->data['icon'] ?? 'notifications' }}</span>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block text-xs leading-5 text-slate-200">{{ $notification->data['message'] ?? 'Новое событие' }}</span>
                                                <span class="mt-1 block text-[10px] font-semibold text-slate-600">{{ $notification->created_at->diffForHumans() }}</span>
                                            </span>
                                        </a>
                                        <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}" class="shrink-0" data-notification-dismiss>
                                            @csrf @method('DELETE')
                                            <button class="grid size-8 place-items-center rounded-lg text-slate-600 transition hover:bg-white/8 hover:text-white" aria-label="Удалить уведомление" title="Удалить">
                                                <span class="material-symbols-outlined text-base">close</span>
                                            </button>
                                        </form>
                                    </article>
                                @endforeach
                            </div>

                            <div class="{{ $navigationNotificationCount ? 'hidden' : '' }} px-5 py-10 text-center" data-notification-empty>
                                <span class="material-symbols-outlined text-4xl text-violet-300/30">notifications</span>
                                <p class="mt-2 text-xs text-slate-500">Новых событий пока нет.</p>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="icon-button cursor-pointer" title="{{ __('app.nav.logout') }}" aria-label="{{ __('app.nav.logout') }}">
                            <span class="material-symbols-outlined">logout</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('search.index') }}" class="grid size-8 cursor-pointer place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-400 transition hover:border-violet-400/30 hover:text-white sm:size-10" title="Поиск игр" aria-label="Поиск игр">
                        <span class="material-symbols-outlined">search</span>
                    </a>
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

    @stack('modals')

    <x-confirm-dialog />

    <footer class="relative z-10 mx-auto max-w-7xl px-4 py-10 text-center text-xs leading-6 text-slate-600">
        <p>GameList · {{ date('Y') }} · {{ __('app.tagline') }}</p>
        <p>
            Некоторые данные об играх предоставлены
            <a href="https://howlongtobeat.com/" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-500 transition hover:text-violet-300">HowLongToBeat</a>
            и
            <a href="https://rawg.io/" target="_blank" rel="noopener noreferrer" class="font-semibold text-slate-500 transition hover:text-violet-300">RAWG</a>.
        </p>
    </footer>
</body>
</html>
