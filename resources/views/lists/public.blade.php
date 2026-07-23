@extends('layouts.app')

@section('title', $gameList->name)

@section('content')
<div class="relative mb-7 overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-violet-950/45 to-cyan-950/30 p-5 text-left shadow-2xl shadow-black/20 sm:p-8">
    @if ($gameList->cover_url)
        <img src="{{ $gameList->cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-90">
        <div class="absolute inset-0 bg-gradient-to-r from-[#080a14]/90 via-[#080a14]/55 to-[#080a14]/15"></div>
    @endif
    <div class="relative">
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <span class="eyebrow mb-0">
                <span class="material-symbols-outlined">public</span>
                Публичный список
                <a href="{{ route('profiles.show', $gameList->user->login) }}" class="text-white transition hover:text-violet-200">{{ '@'.$gameList->user->login }}</a>
            </span>
            <x-friend-button :user="$gameList->user" :is-friend="$isFriend" compact />
        </div>
        <h1 class="page-title max-w-3xl">{{ $gameList->name }}</h1>
        @if ($gameList->description)<p class="muted mt-3 max-w-2xl text-slate-300">{{ $gameList->description }}</p>@endif
        <p class="mt-4 text-xs font-semibold text-slate-400">{{ $selectedStatuses === [] ? $totalGames : $gameList->games->count().' из '.$totalGames }} игр · Обновлён {{ $gameList->updated_at->translatedFormat('j F Y') }}</p>
    </div>
</div>

@include('lists._filters', ['publicView' => true])

@include('lists._games', ['readonly' => true])
@endsection
