@extends('layouts.app')

@section('title', __('app.nav.history'))

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-8">
        <span class="eyebrow"><span class="material-symbols-outlined">history</span> Игровая хронология</span>
        <h1 class="page-title">{{ __('app.nav.history') }}</h1>
        <p class="muted mt-3 max-w-2xl">Все даты завершения игр из ваших списков. Самые свежие события находятся наверху.</p>
    </div>

    @if ($gamesByCompletionDate->isEmpty())
        <div class="panel flex min-h-72 flex-col items-center justify-center text-center">
            <span class="material-symbols-outlined text-6xl text-violet-400/40">history</span>
            <h2 class="mt-4 text-lg font-extrabold">История пока пуста</h2>
            <p class="muted mt-2 max-w-lg">Укажите дату завершения у игры — событие автоматически появится здесь.</p>
            <a href="{{ route('lists.index') }}" class="button button-primary mt-5"><span class="material-symbols-outlined">view_list</span> Открыть мои списки</a>
        </div>
    @else
        <div class="relative">
            <div class="absolute bottom-3 left-[1.1875rem] top-3 w-px bg-gradient-to-b from-violet-400/60 via-white/10 to-transparent"></div>

            @foreach ($gamesByCompletionDate as $date => $games)
                <section class="relative pb-9 pl-12 last:pb-0">
                    <span class="absolute left-3 top-1.5 size-4 rounded-full border-4 border-[#090b16] bg-violet-400 shadow-lg shadow-violet-500/30"></span>
                    <time datetime="{{ $date }}" class="block text-xs font-extrabold uppercase tracking-[.12em] text-violet-300">
                        {{ $games->first()->completed_at->translatedFormat('j F Y') }}
                    </time>

                    <div class="mt-3 space-y-3">
                        @foreach ($games as $game)
                            @php($duration = $game->completionDuration())
                            <a href="{{ route('games.edit', $game) }}" class="glass group flex gap-3 overflow-hidden rounded-2xl p-3 transition hover:-translate-y-0.5 hover:border-white/20 sm:gap-4 sm:p-4" data-history-event>
                                <div class="grid h-24 w-18 shrink-0 place-items-center overflow-hidden rounded-xl bg-gradient-to-br from-violet-950 via-slate-900 to-cyan-950 sm:h-28 sm:w-21">
                                    @if ($game->cover_url)
                                        <img src="{{ $game->cover_url }}" alt="Обложка {{ $game->title }}" class="h-full w-full object-cover" loading="lazy">
                                    @else
                                        <span class="material-symbols-outlined text-3xl text-white/20">sports_esports</span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1 py-0.5">
                                    <p class="flex flex-wrap items-center gap-1.5 text-[10px] font-extrabold uppercase tracking-[.11em] text-emerald-300">
                                        <span class="material-symbols-outlined text-sm">trophy</span>
                                        <span>Прошёл игру</span>
                                        @if ($duration)
                                            <span class="normal-case tracking-normal text-emerald-200/80">(за {{ $duration }})</span>
                                        @endif
                                    </p>
                                    <h2 class="mt-1.5 line-clamp-2 text-base font-extrabold leading-6 sm:text-lg">{{ $game->title }}</h2>
                                    <div class="mt-3 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                        <span class="status-chip"><span class="material-symbols-outlined text-xs">view_list</span>{{ $game->gameList->name }}</span>
                                        <span>{{ $game->platform->label() }}</span>
                                    </div>
                                </div>

                                <span class="material-symbols-outlined self-center text-slate-700 transition group-hover:translate-x-0.5 group-hover:text-violet-300">arrow_forward</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>
@endsection
