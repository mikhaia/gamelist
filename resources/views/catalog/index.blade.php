@extends('layouts.app')

@section('title', 'Каталог игр')

@section('content')
<div
    data-catalog-browser
    data-results-url="{{ route('catalog.results', $gameList) }}"
    data-fresh-url="{{ route('catalog.search') }}"
    data-query="{{ $query }}"
    data-next-page="{{ $games->hasMorePages() ? $games->currentPage() + 1 : '' }}"
>
    <a class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-white" href="{{ route('lists.show', $gameList) }}">
        <span class="material-symbols-outlined">arrow_back</span> {{ __('app.actions.back') }}
    </a>

    <div class="mb-7 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <span class="eyebrow"><span class="material-symbols-outlined">travel_explore</span> Быстрое добавление</span>
            <h1 class="page-title">Каталог игр</h1>
            <p class="muted mt-2 max-w-2xl">Нажимайте <span class="material-symbols-outlined text-base text-violet-300">add</span>, чтобы добавлять игры в «{{ $gameList->name }}» без перезагрузки страницы.</p>
        </div>
        <span class="status-chip shrink-0"><span class="material-symbols-outlined text-sm">playlist_add</span>Список: {{ $gameList->name }}</span>
    </div>

    <div class="panel mb-6">
        <form method="GET" action="{{ route('catalog.index', $gameList) }}" class="flex flex-col gap-3 sm:flex-row" data-catalog-browser-form>
            <div class="relative min-w-0 flex-1">
                <span class="material-symbols-outlined pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-600">search</span>
                <input class="field mt-0 pl-12" name="q" value="{{ $query }}" placeholder="Найдите игру по названию" autocomplete="off" aria-label="Поиск игр" data-catalog-browser-input>
            </div>
            <button class="button button-primary shrink-0"><span class="material-symbols-outlined">search</span> Найти игры</button>
        </form>
        <div class="mt-4 flex justify-end text-xs text-slate-500">
            <span class="{{ $query === '' ? 'hidden' : 'flex' }} items-center gap-2 text-violet-300/70" data-catalog-browser-loading>
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
</div>
@endsection
