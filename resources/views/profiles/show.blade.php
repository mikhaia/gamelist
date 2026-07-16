@extends('layouts.app')

@section('title', '@'.$profile->login)

@section('content')
<x-profile-card :user="$profile" :stats="$stats" :is-friend="$isFriend" />

<section class="mt-8">
    <div class="mb-4 flex items-end justify-between gap-4">
        <div>
            <span class="eyebrow"><span class="material-symbols-outlined">trophy</span> Избранное</span>
            <h2 class="text-2xl font-extrabold">Любимые игры</h2>
        </div>
    </div>

    @if ($favoriteGames->isNotEmpty())
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($favoriteGames as $game)
                @php
                    $gamePageUrl = $game->catalog_game_id ? route('games.show', $game->catalog_game_id) : null;
                @endphp
                @if ($gamePageUrl)
                    <a href="{{ $gamePageUrl }}" class="glass group relative min-h-52 overflow-hidden rounded-3xl p-5 transition duration-300 hover:-translate-y-1 hover:border-violet-300/30 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-400" aria-label="Открыть страницу игры {{ $game->title }}" data-favorite-game-card>
                @else
                    <article class="glass relative min-h-52 overflow-hidden rounded-3xl p-5" data-favorite-game-card>
                @endif
                    @if ($game->cover_url)
                        <img src="{{ $game->cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-65 transition duration-500 group-hover:scale-[1.03]">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#090b16] via-[#090b16]/70 to-black/15"></div>
                    @endif
                    <div class="relative flex h-full min-h-42 flex-col justify-end">
                        <span class="status-chip w-fit">{{ $game->status->label() }}</span>
                        <h3 class="mt-3 text-lg font-extrabold text-white transition group-hover:text-violet-200">{{ $game->title }}</h3>
                        <p class="mt-1 text-xs text-slate-400">{{ $game->platform->label() }}</p>
                    </div>
                @if ($gamePageUrl)
                    </a>
                @else
                    </article>
                @endif
            @endforeach
        </div>
    @else
        <div class="panel py-10 text-center">
            <span class="material-symbols-outlined text-4xl text-amber-300/40">trophy</span>
            <p class="muted mt-3">{{ $isOwner ? 'Выберите до трёх игр, которые особенно вам нравятся.' : 'Пользователь пока не выбрал любимые игры.' }}</p>
        </div>
    @endif

    @if ($isOwner)
        <form method="POST" action="{{ route('profile.favorites.update') }}" class="panel relative z-30 mt-4" data-favorite-picker>
            @csrf @method('PATCH')
            <div class="hidden" data-favorite-game-source aria-hidden="true">
                @foreach ($availableGames as $game)
                    <span data-favorite-game data-value="{{ $game->id }}" data-title="{{ $game->title }}" data-list="{{ $game->gameList->name }}"></span>
                @endforeach
            </div>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                @for ($slot = 0; $slot < 3; $slot++)
                    @php
                        $selectedGameId = (string) old("game_ids.{$slot}", $favoriteGames->get($slot)?->id);
                        $selectedGame = $availableGames->first(fn ($game) => (string) $game->id === $selectedGameId);
                    @endphp
                    <div class="relative flex-1" data-favorite-combobox>
                        <label class="label" for="favorite_game_search_{{ $slot }}">Любимая игра {{ $slot + 1 }}</label>
                        <input type="hidden" name="game_ids[]" value="{{ $selectedGame?->id }}" data-favorite-value>
                        <div class="relative">
                            <span class="material-symbols-outlined pointer-events-none absolute top-1/2 left-4 z-10 mt-1 -translate-y-1/2 text-lg text-slate-500">search</span>
                            <input
                                class="field pr-11 pl-11"
                                id="favorite_game_search_{{ $slot }}"
                                type="search"
                                value="{{ $selectedGame?->title }}"
                                autocomplete="off"
                                placeholder="Начните вводить название…"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="favorite_game_results_{{ $slot }}"
                                data-favorite-input
                            >
                            <button type="button" class="absolute top-1/2 right-2 mt-1 {{ $selectedGame ? 'grid' : 'hidden' }} size-8 -translate-y-1/2 cursor-pointer place-items-center rounded-lg text-slate-500 transition hover:bg-white/8 hover:text-white" data-favorite-clear aria-label="Очистить выбор" title="Очистить выбор">
                                <span class="material-symbols-outlined text-base">close</span>
                            </button>
                        </div>
                        <div id="favorite_game_results_{{ $slot }}" class="absolute right-0 left-0 z-50 mt-2 hidden max-h-72 overflow-y-auto overscroll-contain rounded-2xl border border-white/10 bg-[#0b0e1a] p-1.5 shadow-2xl shadow-black/50" role="listbox" data-favorite-results></div>
                    </div>
                @endfor
                <button class="button button-primary shrink-0"><span class="material-symbols-outlined">save</span> Сохранить</button>
            </div>
            @error('game_ids') <p class="field-error">{{ $message }}</p> @enderror
            @error('game_ids.*') <p class="field-error">{{ $message }}</p> @enderror
        </form>
    @endif
</section>

<section class="mt-10">
    <div class="mb-5">
        <span class="eyebrow"><span class="material-symbols-outlined">view_list</span> Коллекции</span>
        <h2 class="text-2xl font-extrabold">Публичные списки</h2>
    </div>

    @if ($publicLists->isEmpty())
        <div class="panel py-12 text-center">
            <span class="material-symbols-outlined text-5xl text-violet-300/35">public</span>
            <p class="muted mt-3">Публичных списков пока нет.</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($publicLists as $list)
                <a href="{{ route('public.lists.show', [$profile->login, $list->slug]) }}" class="glass group relative overflow-hidden rounded-3xl p-5 transition duration-300 hover:-translate-y-1 hover:border-violet-400/30">
                    @if ($list->cover_url)
                        <img src="{{ $list->cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-75 transition duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#090b16]/95 via-[#090b16]/65 to-black/15"></div>
                    @endif
                    <h3 class="relative text-xl font-extrabold text-white">{{ $list->name }}</h3>
                    <p class="relative mt-2 line-clamp-2 min-h-12 text-sm leading-6 text-slate-300">{{ $list->description }}</p>
                    <div class="relative mt-5 flex items-center justify-between border-t border-white/10 pt-4 text-xs font-semibold text-slate-300">
                        <span>{{ $list->games_count }} {{ trans_choice('app.counts.games', $list->games_count) }}</span>
                        <span class="flex items-center gap-1">Открыть <span class="material-symbols-outlined text-sm">arrow_forward</span></span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</section>
@endsection
