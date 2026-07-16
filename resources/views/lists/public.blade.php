@extends('layouts.app')

@section('title', $gameList->name)

@section('content')
<div class="relative mb-8 overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-violet-950/45 to-cyan-950/30 px-5 py-10 text-center shadow-2xl shadow-black/20 sm:mb-10 sm:px-8 sm:py-14">
    @if ($gameList->cover_url)
        <img src="{{ $gameList->cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-90">
        <div class="absolute inset-0 bg-[#080a14]/35"></div>
    @endif
    <div class="relative">
        <div class="mb-3 flex flex-wrap items-center justify-center gap-2">
            <span class="eyebrow mb-0">
                <span class="material-symbols-outlined">public</span>
                Публичный список
                <a href="{{ route('profiles.show', $gameList->user->login) }}" class="text-white transition hover:text-violet-200">{{ '@'.$gameList->user->login }}</a>
            </span>
            <x-friend-button :user="$gameList->user" :is-friend="$isFriend" compact />
        </div>
        <h1 class="page-title mx-auto max-w-3xl">{{ $gameList->name }}</h1>
        @if ($gameList->description)<p class="muted mx-auto mt-3 max-w-2xl text-slate-300">{{ $gameList->description }}</p>@endif
        <p class="mt-4 text-xs font-semibold text-slate-400">{{ $selectedStatuses === [] ? $totalGames : $gameList->games->count().' из '.$totalGames }} игр · Обновлён {{ $gameList->updated_at->translatedFormat('j F Y') }}</p>
    </div>
</div>

@include('lists._filters', ['publicView' => true])

@include('lists._games', ['readonly' => true])
@endsection
