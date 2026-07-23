@extends('layouts.app')

@section('title', __('app.nav.lists'))

@section('content')
<div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <span class="eyebrow"><span class="material-symbols-outlined">grid_view</span> Коллекции</span>
        <h1 class="page-title">Мои списки</h1>
        <p class="muted mt-2">Собирайте игры по платформе, настроению или очереди прохождения.</p>
    </div>
    <a href="{{ route('lists.create') }}" class="button button-primary shrink-0"><span class="material-symbols-outlined">add</span> {{ __('app.actions.create_list') }}</a>
</div>

@error('steam_import')
    <div class="mb-6 rounded-2xl border border-amber-300/20 bg-amber-300/10 p-4 text-sm text-amber-100" role="alert" data-steam-import-error>
        <div class="flex items-start gap-3">
            <span class="material-symbols-outlined mt-0.5 text-amber-300">privacy_tip</span>
            <div>
                <p class="font-bold">{{ $message }}</p>
                @if (session('steam_privacy_url'))
                    <p class="mt-1 leading-6 text-amber-100/70">
                        Проверьте настройки приватности Steam и убедитесь, что «Детали игр» доступны всем.
                        <a href="{{ session('steam_privacy_url') }}" target="_blank" rel="noopener noreferrer" class="font-bold text-amber-200 underline decoration-amber-200/40 underline-offset-4 hover:text-white">Открыть настройки Steam</a>
                    </p>
                @endif
            </div>
        </div>
    </div>
@enderror

@php($canImportSteam = auth()->user()->steam_id && ! $lists->contains('slug', 'steam'))

@if ($lists->isEmpty() && ! $canImportSteam)
    <div class="panel flex min-h-80 flex-col items-center justify-center text-center">
        <span class="material-symbols-outlined text-6xl text-violet-400/50">playlist_add</span>
        <h2 class="mt-5 text-xl font-extrabold">Пока нет ни одного списка</h2>
        <p class="muted mt-2 max-w-md">Создайте первый список — например, «В очереди на Switch» или «Лучшее за год».</p>
        <a href="{{ route('lists.create') }}" class="button button-primary mt-6">Создать список</a>
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @if ($canImportSteam)
            <form method="POST" action="{{ route('lists.steam.import') }}" class="min-w-0">
                @csrf
                <button type="submit" class="glass group relative flex h-full min-h-64 w-full cursor-pointer flex-col overflow-hidden rounded-3xl p-5 text-left opacity-75 transition duration-300 hover:-translate-y-1 hover:border-[#66c0f4]/35 hover:bg-white/[.075] hover:opacity-100" aria-label="Создать список игр из Steam" data-steam-library-import>
                    <img src="{{ asset('images/steam/list-cover.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-45 transition duration-500 group-hover:scale-105 group-hover:opacity-60">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#090b16]/95 via-[#111827]/65 to-[#1b2838]/25"></div>
                    <div class="relative flex w-full items-start justify-between gap-4">
                        <span class="grid size-12 place-items-center rounded-2xl border border-[#66c0f4]/20 bg-[#1b2838]/70 text-[#66c0f4] backdrop-blur-xl">
                            <span class="material-symbols-outlined text-2xl">add</span>
                        </span>
                        <span class="status-chip"><span class="material-symbols-outlined text-sm">lock</span>Приватный</span>
                    </div>
                    <h2 class="relative mt-auto pt-6 text-xl font-extrabold tracking-tight text-white transition group-hover:text-[#9bd7f7]">Игры из Steam</h2>
                    <p class="relative mt-2 line-clamp-2 min-h-12 text-sm leading-6 text-slate-300/75">Создать список из игр привязанного профиля Steam.</p>
                    <div class="relative mt-5 flex w-full items-center justify-between border-t border-white/10 pt-4 text-xs font-semibold text-slate-300/70">
                        <span>Steam</span>
                        <span class="flex items-center gap-1 text-[#66c0f4]">Создать <span class="material-symbols-outlined text-sm transition group-hover:translate-x-1">arrow_forward</span></span>
                    </div>
                </button>
            </form>
        @endif
        @foreach ($lists as $list)
            <a href="{{ route('lists.show', $list) }}" class="glass group relative overflow-hidden rounded-3xl p-5 transition duration-300 hover:-translate-y-1 hover:border-violet-400/30 hover:bg-white/[.075]">
                @if ($list->cover_url)
                    <img src="{{ $list->cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-80 transition duration-500 group-hover:scale-105 group-hover:opacity-90">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#090b16]/95 via-[#090b16]/55 to-black/10"></div>
                @endif
                <div class="relative flex items-start justify-between gap-4">
                    <span class="grid size-12 place-items-center rounded-2xl bg-gradient-to-br from-violet-500/25 to-cyan-500/10 text-violet-300">
                        <span class="material-symbols-outlined text-2xl">sports_esports</span>
                    </span>
                    <span class="status-chip"><span class="material-symbols-outlined text-sm">{{ $list->is_public ? 'public' : 'lock' }}</span>{{ $list->is_public ? 'Публичный' : 'Личный' }}</span>
                </div>
                <h2 class="relative mt-6 text-xl font-extrabold tracking-tight transition group-hover:text-violet-200">{{ $list->name }}</h2>
                <p class="relative mt-2 line-clamp-2 min-h-12 text-sm leading-6 text-slate-400">{{ $list->description }}</p>
                <div class="relative mt-5 flex items-center justify-between border-t border-white/10 pt-4 text-xs font-semibold text-slate-400">
                    <span>{{ $list->games_count }} игр</span>
                    <span class="flex items-center gap-1 text-slate-400">Открыть <span class="material-symbols-outlined text-sm transition group-hover:translate-x-1">arrow_forward</span></span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
