@extends('layouts.app')

@section('title', 'Достижения @'.$profile->login)

@section('content')
<div class="mx-auto max-w-6xl">
    <a href="{{ route('profiles.show', $profile->login) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-white"><span class="material-symbols-outlined">arrow_back</span> К профилю {{ '@'.$profile->login }}</a>

    <section class="mt-5 panel overflow-hidden">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="eyebrow"><span class="material-symbols-outlined">workspace_premium</span> Достижения</span>
                <h1 class="text-3xl font-extrabold tracking-tight text-white">{{ '@'.$profile->login }}</h1>
                <p class="mt-2 text-sm text-slate-400">{{ $earned->count() }} из {{ $earned->count() + $locked->count() }} достижений разблокировано.</p>
            </div>
            <span class="grid size-16 place-items-center rounded-3xl bg-amber-500/10 text-amber-300 ring-1 ring-amber-400/20"><span class="material-symbols-outlined text-3xl">workspace_premium</span></span>
        </div>
    </section>

    @if ($earned->isNotEmpty())
        <section class="mt-8" aria-labelledby="earned-achievements">
            <h2 id="earned-achievements" class="text-lg font-extrabold text-white">Получены</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($earned as $item)
                    @php($achievement = $item['achievement'])
                    @php($award = $item['record'])
                    <article class="flex gap-3 rounded-2xl border border-white/10 bg-white/[.045] p-4 shadow-lg shadow-black/10">
                        <span class="grid size-12 shrink-0 place-items-center rounded-2xl ring-1 {{ $achievement->colorClasses() }}"><span class="material-symbols-outlined text-2xl">{{ $achievement->icon() }}</span></span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-extrabold text-white">{{ $achievement->title() }}</h3>
                            <p class="mt-1 text-xs leading-5 text-slate-400">{{ $achievement->description() }}</p>
                            <p class="mt-2 text-[11px] font-semibold text-slate-500">Получено {{ $award->awarded_at->format('d.m.Y') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-10" aria-labelledby="locked-achievements">
        <h2 id="locked-achievements" class="text-lg font-extrabold text-slate-300">Ещё впереди</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($locked as $item)
                @php($achievement = $item['achievement'])
                <article class="flex gap-3 rounded-2xl border border-white/7 bg-black/15 p-4 grayscale">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-slate-500/10 text-slate-500 ring-1 ring-slate-400/10"><span class="material-symbols-outlined text-2xl">{{ $achievement->icon() }}</span></span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-extrabold text-slate-400">{{ $achievement->title() }}</h3>
                        <p class="mt-1 text-xs leading-5 text-slate-600">{{ $achievement->description() }}</p>
                        <p class="mt-2 text-[11px] font-semibold text-slate-500">Цель: {{ $achievement->requirement() }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection
