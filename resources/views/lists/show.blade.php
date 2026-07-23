@extends('layouts.app')

@section('title', $gameList->name)

@section('content')
<div class="relative mb-7 overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-violet-950/45 to-cyan-950/30 p-5 shadow-2xl shadow-black/20 sm:p-8">
    @if ($gameList->cover_url)
        <img src="{{ $gameList->cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-90">
        <div class="absolute inset-0 bg-gradient-to-r from-[#080a14]/90 via-[#080a14]/55 to-[#080a14]/15"></div>
    @endif
    <div class="relative">
    <a class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-400 hover:text-white" href="{{ route('lists.index') }}"><span class="material-symbols-outlined">arrow_back</span> Все списки</a>
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span class="status-chip"><span class="material-symbols-outlined text-sm">{{ $gameList->is_public ? 'public' : 'lock' }}</span>{{ $gameList->is_public ? 'Публичный список' : 'Личный список' }}</span>
                <span class="status-chip" data-list-game-count data-total-games="{{ $totalGames }}" data-filtered="{{ $selectedStatuses === [] ? 'false' : 'true' }}">{{ $selectedStatuses === [] ? $totalGames : $gameList->games->count().' из '.$totalGames }} игр</span>
            </div>
            <div class="flex min-w-0 items-center gap-3" data-list-heading>
                <h1 class="page-title min-w-0 break-words">{{ $gameList->name }}</h1>
                <a href="{{ route('lists.edit', $gameList) }}" class="icon-button shrink-0 border border-white/10 bg-white/5" title="{{ __('app.actions.edit') }}" aria-label="{{ __('app.actions.edit') }}" data-list-settings><span class="material-symbols-outlined">settings</span></a>
            </div>
            @if ($gameList->description)<p class="muted mt-3 max-w-3xl">{{ $gameList->description }}</p>@endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('games.create', $gameList) }}" class="button border-white/40 bg-white/85 text-slate-950 shadow-lg shadow-black/20 backdrop-blur-xl hover:-translate-y-0.5 hover:border-white/70 hover:bg-white/95" data-list-add-game><span class="material-symbols-outlined">add</span> {{ __('app.actions.add_game') }}</a>
            <a href="{{ route('catalog.index', $gameList) }}" class="button button-secondary"><span class="material-symbols-outlined">travel_explore</span> {{ __('app.actions.catalog') }}</a>
            <a href="{{ route('imports.create', $gameList) }}" class="button button-secondary"><span class="material-symbols-outlined">playlist_add</span> {{ __('app.actions.import') }}</a>
        </div>
    </div>
    </div>
</div>

<div class="mb-6 flex flex-col gap-3 rounded-2xl border border-white/8 bg-white/[.035] p-3 sm:flex-row sm:items-center sm:justify-between">
    @if ($gameList->is_public)
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ $gameList->public_path }}" target="_blank" rel="noopener noreferrer" class="grid size-9 shrink-0 place-items-center rounded-xl bg-cyan-500/10 text-cyan-300 transition hover:bg-cyan-500/20 hover:text-cyan-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-400" title="Открыть публичный список" aria-label="Открыть публичный список" data-public-list-link><span class="material-symbols-outlined">link</span></a>
            <span class="min-w-0 truncate text-xs text-slate-500">{{ $gameList->public_path }}</span>
        </div>
        <button type="button" class="button button-secondary button-sm shrink-0" data-copy="{{ $gameList->public_path }}" data-copied="{{ __('app.messages.copied') }}">
            <span class="material-symbols-outlined">content_copy</span><span data-copy-label>{{ __('app.actions.copy') }}</span>
        </button>
    @else
        <p class="flex items-center gap-2 text-xs text-slate-500"><span class="material-symbols-outlined">lock</span> Публичная ссылка отключена в настройках списка.</p>
    @endif
    <form method="POST" action="{{ route('lists.display', $gameList) }}" class="flex rounded-xl border border-white/8 bg-black/20 p-1">
        @csrf @method('PATCH')
        @foreach (['cards' => ['grid_view', __('app.actions.cards')], 'compact' => ['view_agenda', __('app.actions.compact')], 'board' => ['view_kanban', __('app.actions.board')]] as $mode => $meta)
            <button name="display_mode" value="{{ $mode }}" class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold {{ $gameList->display_mode === $mode ? 'bg-white/10 text-white' : 'text-slate-600 hover:text-slate-300' }}">
                <span class="material-symbols-outlined text-sm">{{ $meta[0] }}</span><span class="hidden sm:inline">{{ $meta[1] }}</span>
            </button>
        @endforeach
    </form>
</div>

@include('lists._filters', ['publicView' => false])

@include('lists._games', ['readonly' => false])
@endsection
