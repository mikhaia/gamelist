@extends('layouts.app')

@section('title', 'Поиск игр')

@section('content')
<div
    data-catalog-browser
    data-results-url="{{ $gameList ? route('catalog.results', $gameList) : route('search.results') }}"
    data-fresh-url="{{ route('catalog.search') }}"
    data-rawg-url="{{ route('catalog.rawg-search') }}"
    data-query="{{ $query }}"
    data-genre="{{ $filters['genre'] }}"
    data-genre-name="{{ $filters['genreName'] }}"
    data-platform="{{ $filters['platform'] }}"
    data-platform-name="{{ $filters['platformName'] }}"
    data-next-page="{{ $games->hasMorePages() ? $games->currentPage() + 1 : '' }}"
>
    @if ($gameList)
        <a class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-white" href="{{ route('lists.show', $gameList) }}">
            <span class="material-symbols-outlined">arrow_back</span> {{ __('app.actions.back') }}
        </a>
    @endif

    <div class="mb-7 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <span class="eyebrow"><span class="material-symbols-outlined">travel_explore</span> Быстрое добавление</span>
            <h1 class="page-title">Поиск игр</h1>
            @if ($gameList)
                <p class="muted mt-2 max-w-2xl">Нажимайте <span class="material-symbols-outlined text-base text-violet-300">add</span>, чтобы добавлять игры в «{{ $gameList->name }}» без перезагрузки страницы.</p>
            @elseif (auth()->check())
                <p class="muted mt-2 max-w-2xl">Нажмите <span class="material-symbols-outlined text-base text-violet-300">add</span> у нужной игры и выберите список, в который хотите её добавить.</p>
            @else
                <p class="muted mt-2 max-w-2xl">Ищите игры, открывайте подробную информацию и добавляйте их в свои списки после входа.</p>
            @endif
        </div>
        @if ($gameList)
            <span class="status-chip shrink-0"><span class="material-symbols-outlined text-sm">playlist_add</span>Список: {{ $gameList->name }}</span>
        @endif
    </div>

    <div class="panel mb-6">
        <form method="GET" action="{{ $gameList ? route('catalog.index', $gameList) : route('search.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(16rem,1fr)_minmax(11rem,14rem)_minmax(11rem,14rem)_auto]" data-catalog-browser-form>
            <div class="relative min-w-0 sm:col-span-2 lg:col-span-1">
                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-600">search</span>
                <input class="field mt-0 pl-12" name="q" value="{{ $query }}" placeholder="Найдите игру по названию" autocomplete="off" aria-label="Поиск игр" data-catalog-browser-input>
            </div>
            <div class="relative min-w-0">
                <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-1/2 z-10 -translate-y-1/2 text-base text-violet-300">category</span>
                <select class="field mt-0 appearance-none pl-10 pr-9" name="genre" aria-label="Жанр" data-catalog-browser-genre>
                    <option value="">Все жанры</option>
                    @if ($filters['genre'] && ! collect($filterOptions['genres'])->contains('value', $filters['genre']))
                        <option value="{{ $filters['genre'] }}" selected>{{ $filters['genreName'] }}</option>
                    @endif
                    @foreach ($filterOptions['genres'] as $option)
                        <option value="{{ $option['value'] }}" @selected($filters['genre'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-base text-slate-600">expand_more</span>
            </div>
            <div class="relative min-w-0">
                <span class="material-symbols-outlined pointer-events-none absolute left-3.5 top-1/2 z-10 -translate-y-1/2 text-base text-cyan-300">devices</span>
                <select class="field mt-0 appearance-none pl-10 pr-9" name="platform" aria-label="Платформа" data-catalog-browser-platform>
                    <option value="">Все платформы</option>
                    @if ($filters['platform'] && ! collect($filterOptions['platforms'])->contains('value', $filters['platform']))
                        <option value="{{ $filters['platform'] }}" selected>{{ $filters['platformName'] }}</option>
                    @endif
                    @foreach ($filterOptions['platforms'] as $option)
                        <option value="{{ $option['value'] }}" @selected($filters['platform'] === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-base text-slate-600">expand_more</span>
            </div>
            <button class="button button-primary shrink-0 sm:col-span-2 lg:col-span-1"><span class="material-symbols-outlined">search</span> Найти игры</button>
        </form>
        <div class="mt-4 flex justify-end text-xs text-slate-500">
            <span class="{{ $query === '' && ! $filters['genre'] && ! $filters['platform'] ? 'hidden' : 'flex' }} items-center gap-2 text-violet-300/70" data-catalog-browser-loading>
                <span class="material-symbols-outlined animate-spin text-base">progress_activity</span><span data-catalog-browser-loading-label>Ищем игры…</span>
            </span>
        </div>
        <div class="mt-3 hidden rounded-xl border border-amber-400/20 bg-amber-500/8 px-3 py-2.5 text-xs text-amber-200" data-catalog-browser-error>
            Не удалось обновить результаты поиска. Можно продолжить добавлять уже показанные игры.
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-5" data-catalog-browser-results aria-live="polite">
        @include('catalog._cards')
    </div>

    <div class="panel {{ $games->isEmpty() ? 'flex' : 'hidden' }} min-h-56 flex-col items-center justify-center text-center" data-catalog-browser-empty>
        <span class="material-symbols-outlined text-5xl text-violet-400/40">search_off</span>
        <h2 class="mt-4 text-lg font-extrabold">Ничего не найдено</h2>
        <p class="muted mt-2 max-w-lg">Попробуйте изменить запрос или ввести полное название игры.</p>
    </div>

    <div class="mt-7 flex justify-center">
        <button type="button" class="button button-secondary {{ $games->hasMorePages() ? '' : 'hidden' }}" data-catalog-browser-more>
            <span class="material-symbols-outlined">expand_more</span><span data-catalog-browser-more-label>Показать ещё 20</span>
        </button>
    </div>

    @auth
        @if (! $gameList)
            <div class="fixed inset-0 z-[60] hidden items-center justify-center p-4" data-catalog-list-dialog>
                <button type="button" class="absolute inset-0 cursor-pointer bg-black/75 backdrop-blur-sm" aria-label="Закрыть выбор списка" data-catalog-list-close></button>
                <section class="glass relative z-10 w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-[#0b0e1a] shadow-2xl shadow-black/60" role="dialog" aria-modal="true" aria-labelledby="catalog-list-dialog-title">
                    <header class="flex items-start gap-3 border-b border-white/10 p-5">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-violet-400/20 bg-violet-500/10 text-violet-300">
                            <span class="material-symbols-outlined">playlist_add</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 id="catalog-list-dialog-title" class="font-extrabold text-white">Добавить в список</h2>
                            <p class="mt-1 truncate text-xs text-slate-500" data-catalog-list-game-title></p>
                        </div>
                        <button type="button" class="grid size-9 cursor-pointer place-items-center rounded-xl text-slate-500 transition hover:bg-white/8 hover:text-white" aria-label="Закрыть" data-catalog-list-close>
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </header>

                    @if ($userLists->isEmpty())
                        <div class="p-6 text-center">
                            <span class="material-symbols-outlined text-4xl text-violet-300/30">playlist_add</span>
                            <p class="muted mt-3">Сначала создайте игровой список.</p>
                            <a href="{{ route('lists.create') }}" class="button button-primary button-sm mt-4"><span class="material-symbols-outlined">add</span> Создать список</a>
                        </div>
                    @else
                        <div class="max-h-[60vh] space-y-2 overflow-y-auto overscroll-contain p-3" data-catalog-list-options>
                            @foreach ($userLists as $list)
                                <button type="button" class="flex w-full cursor-pointer items-center gap-3 rounded-2xl border border-white/8 bg-white/[.025] p-3 text-left transition hover:border-violet-400/25 hover:bg-violet-500/8" data-catalog-list-option data-add-url-template="{{ route('catalog.add', [$list, 'CATALOG_GAME_ID']) }}">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-violet-500/10 text-violet-300">
                                        <span class="material-symbols-outlined" data-catalog-list-option-icon>add</span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-extrabold text-white">{{ $list->name }}</span>
                                        <span class="mt-0.5 block text-[10px] font-semibold text-slate-500">{{ __('app.platforms.'.$list->default_platform) }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        @endif
    @endauth
</div>
@endsection
