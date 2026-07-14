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

@if ($lists->isEmpty())
    <div class="panel flex min-h-80 flex-col items-center justify-center text-center">
        <span class="material-symbols-outlined text-6xl text-violet-400/50">playlist_add</span>
        <h2 class="mt-5 text-xl font-extrabold">Пока нет ни одного списка</h2>
        <p class="muted mt-2 max-w-md">Создайте первый список — например, «В очереди на Switch» или «Лучшее за год».</p>
        <a href="{{ route('lists.create') }}" class="button button-primary mt-6">Создать список</a>
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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
                <p class="relative mt-2 line-clamp-2 min-h-12 text-sm leading-6 text-slate-400">{{ $list->description ?: 'Игровая коллекция без описания.' }}</p>
                <div class="relative mt-5 flex items-center justify-between border-t border-white/10 pt-4 text-xs font-semibold text-slate-400">
                    <span>{{ $list->games_count }} игр</span>
                    <span class="flex items-center gap-1 text-slate-400">Открыть <span class="material-symbols-outlined text-sm transition group-hover:translate-x-1">arrow_forward</span></span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
