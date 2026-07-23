@extends('layouts.app')

@section('title', 'Импорт из Steam')

@section('content')
<div class="mx-auto flex min-h-[65vh] max-w-2xl items-center justify-center" data-steam-import-screen>
    <section class="glass relative w-full overflow-hidden rounded-3xl border-[#66c0f4]/20 p-6 text-center shadow-2xl shadow-black/30 sm:p-10" aria-labelledby="steam-import-title">
        <img src="{{ asset('images/steam/list-cover.webp') }}" alt="" class="pointer-events-none absolute inset-0 h-full w-full object-cover opacity-25">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-[#101722]/80 via-[#090d16]/90 to-[#070913]"></div>

        <div class="relative">
            <span class="mx-auto grid size-20 place-items-center rounded-3xl border border-[#66c0f4]/20 bg-[#1b2838]/75 text-[#66c0f4] shadow-xl shadow-black/25 backdrop-blur-xl">
                <span class="material-symbols-outlined animate-spin text-4xl" aria-hidden="true">progress_activity</span>
            </span>

            <p class="mt-6 text-xs font-extrabold uppercase tracking-[0.24em] text-[#66c0f4]">Steam</p>
            <h1 id="steam-import-title" class="mt-2 text-2xl font-extrabold tracking-tight sm:text-3xl">Создаём список игр</h1>
            <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-slate-400">
                Получаем библиотеку, сверяем игровое время и достижения. Для большой коллекции это может занять немного больше времени.
            </p>

            <div class="mx-auto mt-8 max-w-md">
                <div class="relative h-2 overflow-hidden rounded-full bg-white/8" role="progressbar" aria-label="Импорт библиотеки Steam" aria-valuetext="Импорт выполняется">
                    <span class="steam-import-progress absolute inset-y-0 left-0 rounded-full bg-gradient-to-r from-[#66c0f4] via-cyan-300 to-[#66c0f4] shadow-[0_0_18px_rgba(102,192,244,0.45)]"></span>
                </div>
                <p class="mt-3 text-xs font-semibold text-slate-500">Не закрывайте страницу — после завершения список откроется автоматически.</p>
            </div>

            <div class="mx-auto mt-8 grid max-w-lg gap-2 text-left text-xs text-slate-400 sm:grid-cols-3">
                <span class="flex items-center gap-2 rounded-xl border border-white/8 bg-black/20 px-3 py-2.5"><span class="material-symbols-outlined text-base text-[#66c0f4]">library_books</span>Библиотека</span>
                <span class="flex items-center gap-2 rounded-xl border border-white/8 bg-black/20 px-3 py-2.5"><span class="material-symbols-outlined text-base text-[#66c0f4]">schedule</span>Игровое время</span>
                <span class="flex items-center gap-2 rounded-xl border border-white/8 bg-black/20 px-3 py-2.5"><span class="material-symbols-outlined text-base text-[#66c0f4]">workspace_premium</span>Достижения</span>
            </div>

            <form method="POST" action="{{ route('lists.steam.import') }}" data-steam-import-form>
                @csrf
                <noscript>
                    <button type="submit" class="button mt-8 border-[#66c0f4]/30 bg-[#1b2838] text-[#9bd7f7]">Начать импорт</button>
                </noscript>
            </form>
        </div>
    </section>
</div>

<style>
    @keyframes steam-import-progress {
        0% { transform: translateX(-110%); }
        50% { transform: translateX(125%); }
        100% { transform: translateX(250%); }
    }

    .steam-import-progress {
        width: 42%;
        animation: steam-import-progress 1.8s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .steam-import-progress {
            width: 100%;
            animation: none;
            opacity: .65;
        }
    }
</style>

<script>
    window.addEventListener('load', () => {
        window.setTimeout(() => document.querySelector('[data-steam-import-form]')?.requestSubmit(), 250);
    }, { once: true });
</script>
@endsection
