@extends('layouts.app')

@section('title', $gameList->name)

@section('content')
<div class="mb-8 text-center sm:mb-10">
    <span class="eyebrow"><span class="material-symbols-outlined">public</span> Публичный список {{ '@'.$gameList->user->login }}</span>
    <h1 class="page-title mx-auto max-w-3xl">{{ $gameList->name }}</h1>
    @if ($gameList->description)<p class="muted mx-auto mt-3 max-w-2xl">{{ $gameList->description }}</p>@endif
    <p class="mt-4 text-xs font-semibold text-slate-600">{{ $gameList->games->count() }} игр · Обновлён {{ $gameList->updated_at->translatedFormat('j F Y') }}</p>
</div>

@include('lists._games', ['readonly' => true])
@endsection
