@extends('layouts.app')

@section('title', __('app.tagline'))

@section('content')
<section class="grid min-h-[68vh] items-center gap-10 py-8 lg:grid-cols-[1.08fr_.92fr] lg:py-16">
    <div>
        <span class="eyebrow"><span class="material-symbols-outlined">auto_awesome</span> Личная игровая библиотека</span>
        <h1 class="max-w-3xl text-5xl font-extrabold leading-[1.04] tracking-[-.045em] text-white sm:text-6xl lg:text-7xl">
            Все игры.<br><span class="bg-gradient-to-r from-violet-400 via-fuchsia-300 to-cyan-300 bg-clip-text text-transparent">Твой порядок.</span>
        </h1>
        <p class="mt-6 max-w-xl text-base leading-7 text-slate-400 sm:text-lg">
            Создавай тематические списки, отмечай прогресс и делись коллекциями. Обложки и время прохождения найдутся автоматически — а если нет, всё можно добавить вручную.
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
            @auth
                <a href="{{ route('lists.index') }}" class="button button-primary">
                    <span class="material-symbols-outlined">view_list</span> {{ __('app.nav.lists') }}
                </a>
            @else
                <a href="{{ route('register') }}" class="button button-primary">
                    <span class="material-symbols-outlined">rocket_launch</span> Начать бесплатно
                </a>
                <a href="{{ route('login') }}" class="button button-secondary">{{ __('app.nav.login') }}</a>
            @endauth
        </div>
        <div class="mt-10 flex flex-wrap gap-x-6 gap-y-3 text-xs font-semibold text-slate-500">
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-400">check_circle</span> Несколько списков</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-400">check_circle</span> Импорт из Markdown</span>
            <span class="flex items-center gap-2"><span class="material-symbols-outlined text-emerald-400">check_circle</span> Публичные ссылки</span>
        </div>
    </div>

    <div class="relative mx-auto w-full max-w-xl">
        <div class="absolute -inset-5 rounded-[2rem] bg-gradient-to-br from-violet-600/25 to-cyan-500/10 blur-2xl"></div>
        <div class="glass relative overflow-hidden rounded-[2rem] p-4 sm:p-6">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.15em] text-violet-300">@zerocool</p>
                    <h2 class="mt-1 text-xl font-extrabold">Играю сейчас</h2>
                </div>
                <span class="rounded-full bg-white/7 px-3 py-1.5 text-xs text-slate-400">Steam</span>
            </div>
            @php($featuredStatuses = [__('app.statuses.want_to_play'), __('app.statuses.playing'), __('app.statuses.completed')])
            <div class="grid grid-cols-3 gap-3">
                @foreach ($featuredGames as $featuredGame)
                    <a
                        href="{{ route('games.show', $featuredGame) }}"
                        class="group overflow-hidden rounded-2xl border border-white/10 bg-black/20 transition duration-300 hover:-translate-y-1 hover:border-violet-300/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-400"
                        aria-label="Открыть страницу игры {{ $featuredGame->title }}"
                        data-featured-game
                    >
                        <div class="aspect-[3/4] overflow-hidden bg-gradient-to-br from-violet-950 to-cyan-950">
                            <img
                                src="{{ $featuredGame->cover_url }}"
                                alt="Обложка {{ $featuredGame->title }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                                decoding="async"
                            >
                        </div>
                        <div class="p-3">
                            <p class="truncate text-xs font-bold transition group-hover:text-violet-200" title="{{ $featuredGame->title }}">{{ $featuredGame->title }}</p>
                            <p class="mt-1 truncate text-[10px] text-slate-500">{{ $featuredStatuses[$loop->index] }}</p>
                        </div>
                    </a>
                @endforeach

                @for ($placeholder = $featuredGames->count(); $placeholder < 3; $placeholder++)
                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-black/20">
                        <div class="flex aspect-[3/4] items-center justify-center bg-gradient-to-br from-violet-950 to-cyan-950">
                            <span class="material-symbols-outlined text-4xl text-white/30">sports_esports</span>
                        </div>
                        <div class="p-3">
                            <p class="truncate text-xs font-bold">Новая игра</p>
                            <p class="mt-1 truncate text-[10px] text-slate-500">{{ $featuredStatuses[$placeholder] }}</p>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</section>
@endsection
