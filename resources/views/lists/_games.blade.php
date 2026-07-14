@php($readonly = $readonly ?? false)

@if ($gameList->games->isEmpty())
    <div class="panel flex min-h-64 flex-col items-center justify-center text-center">
        <span class="material-symbols-outlined text-6xl text-violet-400/40">videogame_asset_off</span>
        <h2 class="mt-4 text-lg font-extrabold">Список пока пуст</h2>
        <p class="muted mt-2">Игры появятся здесь после добавления или импорта.</p>
        @unless ($readonly)
            <a href="{{ route('games.create', $gameList) }}" class="button button-primary mt-5"><span class="material-symbols-outlined">add</span> {{ __('app.actions.add_game') }}</a>
        @endunless
    </div>
@elseif ($gameList->display_mode === 'compact')
    <div class="glass overflow-hidden rounded-3xl">
        @foreach ($gameList->games as $game)
            <article class="flex items-center gap-3 border-b border-white/7 p-3 last:border-b-0 sm:gap-4 sm:p-4">
                <div class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-gradient-to-br from-violet-900/70 to-cyan-950/60 sm:size-16">
                    @if ($game->cover_url)
                        <img src="{{ $game->cover_url }}" alt="Обложка {{ $game->title }}" class="h-full w-full object-cover" loading="lazy">
                    @else
                        <span class="material-symbols-outlined text-2xl text-white/25">sports_esports</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-sm font-extrabold sm:text-base">{{ $game->title }}</h3>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                        <span>{{ $game->platform->label() }}</span>
                        @if ($game->main_story_minutes)<span>· {{ $game->formattedTime($game->main_story_minutes) }}</span>@endif
                    </div>
                </div>
                @if ($readonly)
                    <span class="status-chip hidden sm:inline-flex"><span class="material-symbols-outlined text-sm">{{ $game->status->icon() }}</span>{{ $game->status->label() }}</span>
                @else
                    <form method="POST" action="{{ route('games.status', $game) }}" class="hidden sm:block">
                        @csrf @method('PATCH')
                        <select name="status" class="rounded-xl border border-white/10 bg-[#111422] px-3 py-2 text-xs font-semibold text-slate-300" onchange="this.form.submit()">
                            @foreach (\App\Enums\GameStatus::cases() as $status)<option value="{{ $status->value }}" @selected($game->status === $status)>{{ $status->label() }}</option>@endforeach
                        </select>
                    </form>
                    <a href="{{ route('games.edit', $game) }}" class="icon-button" aria-label="Редактировать {{ $game->title }}"><span class="material-symbols-outlined">edit</span></a>
                @endif
            </article>
        @endforeach
    </div>
@else
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-5">
        @foreach ($gameList->games as $game)
            <article class="glass group overflow-hidden rounded-2xl transition duration-300 hover:-translate-y-1 hover:border-white/20 sm:rounded-3xl">
                <div class="relative aspect-[3/4] overflow-hidden bg-gradient-to-br from-violet-950 via-slate-900 to-cyan-950">
                    @if ($game->cover_url)
                        <img src="{{ $game->cover_url }}" alt="Обложка {{ $game->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy">
                    @else
                        <div class="grid h-full place-items-center"><span class="material-symbols-outlined text-5xl text-white/15">sports_esports</span></div>
                    @endif
                    <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#0b0d18] to-transparent"></div>
                    <span class="absolute right-2.5 top-2.5 grid size-8 place-items-center rounded-xl border border-white/10 bg-black/60 text-violet-200 backdrop-blur-lg" title="{{ $game->status->label() }}">
                        <span class="material-symbols-outlined text-lg">{{ $game->status->icon() }}</span>
                    </span>
                </div>
                <div class="p-3.5 sm:p-4">
                    <h3 class="line-clamp-2 min-h-10 text-sm font-extrabold leading-5 sm:text-base">{{ $game->title }}</h3>
                    <p class="mt-2 truncate text-[11px] font-semibold text-slate-500">{{ $game->platform->label() }}</p>
                    @if ($game->main_story_minutes)
                        <p class="mt-2 flex items-center gap-1.5 text-[11px] text-slate-400"><span class="material-symbols-outlined text-sm">schedule</span>{{ $game->formattedTime($game->main_story_minutes) }}</p>
                    @endif
                    @if ($readonly)
                        <span class="status-chip mt-3 max-w-full"><span class="material-symbols-outlined text-sm">{{ $game->status->icon() }}</span><span class="truncate">{{ $game->status->label() }}</span></span>
                    @else
                        <div class="mt-3 flex items-center gap-2 border-t border-white/7 pt-3">
                            <form method="POST" action="{{ route('games.status', $game) }}" class="min-w-0 flex-1">
                                @csrf @method('PATCH')
                                <select name="status" class="w-full truncate rounded-lg border border-white/8 bg-[#111422] px-2 py-1.5 text-[11px] font-semibold text-slate-300" onchange="this.form.submit()">
                                    @foreach (\App\Enums\GameStatus::cases() as $status)<option value="{{ $status->value }}" @selected($game->status === $status)>{{ $status->label() }}</option>@endforeach
                                </select>
                            </form>
                            <a href="{{ route('games.edit', $game) }}" class="grid size-8 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-white/8 hover:text-white" aria-label="Редактировать {{ $game->title }}"><span class="material-symbols-outlined text-base">edit</span></a>
                        </div>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endif
