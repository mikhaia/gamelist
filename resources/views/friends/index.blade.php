@extends('layouts.app')

@section('title', __('app.nav.friends'))

@section('content')
<div class="mb-8">
    <span class="eyebrow"><span class="material-symbols-outlined">groups</span> Сообщество</span>
    <h1 class="page-title">Мои друзья</h1>
    <p class="muted mt-2">Следите за новыми списками и игровыми успехами интересных вам людей.</p>
</div>

<section>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-extrabold">Мои друзья</h2>
        @if ($friends->isNotEmpty())<span class="status-chip">{{ $friends->count() }}</span>@endif
    </div>

    @if ($friends->isEmpty())
        <div class="panel py-10 text-center">
            <span class="material-symbols-outlined text-5xl text-violet-300/35">person_add</span>
            <p class="muted mt-3">Откройте публичный профиль или список другого пользователя и добавьте его в друзья.</p>
        </div>
    @else
        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ($friends as $friend)
                <x-profile-card :user="$friend" :stats="$statsByUser[$friend->id]" :is-friend="true" compact />
            @endforeach
        </div>
    @endif
</section>

@if ($incoming->isNotEmpty())
    <section class="mt-10">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-extrabold">Со мной хотят дружить</h2>
                <p class="muted mt-1">Пользователи, которые добавили вас, но пока не добавлены вами.</p>
            </div>
            <span class="status-chip">{{ $incoming->count() }}</span>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ($incoming as $person)
                <x-profile-card :user="$person" :stats="$statsByUser[$person->id]" :is-friend="false" compact />
            @endforeach
        </div>
    </section>
@endif
@endsection
