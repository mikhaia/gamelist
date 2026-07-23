@extends('layouts.admin')

@section('title', 'Ошибки')

@section('content')
@php
    $levelStyles = [
        'warning' => 'bg-amber-500/10 text-amber-800 ring-amber-500/20',
        'error' => 'bg-red-500/10 text-red-800 ring-red-500/20',
        'critical' => 'bg-rose-600 text-white ring-rose-700/20',
        'alert' => 'bg-fuchsia-700 text-white ring-fuchsia-800/20',
        'emergency' => 'bg-slate-950 text-white ring-slate-950/20',
    ];
@endphp

<section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Статистика ошибок">
    @foreach ([
        ['key' => 'total', 'label' => 'В выборке', 'icon' => 'bug_report', 'accent' => 'text-violet-700 bg-violet-500/10'],
        ['key' => 'today', 'label' => 'Сегодня', 'icon' => 'today', 'accent' => 'text-red-700 bg-red-500/10'],
        ['key' => 'seven_days', 'label' => 'За 7 дней', 'icon' => 'date_range', 'accent' => 'text-cyan-800 bg-cyan-500/10'],
        ['key' => 'critical', 'label' => 'Критических', 'icon' => 'emergency_home', 'accent' => 'text-fuchsia-800 bg-fuchsia-500/10'],
        ['key' => 'files', 'label' => 'Лог-файлов', 'icon' => 'description', 'accent' => 'text-slate-700 bg-slate-500/10'],
    ] as $card)
        <article class="admin-panel flex items-center gap-4 p-4" data-admin-error-stat="{{ $card['key'] }}" data-admin-value="{{ $stats[$card['key']] }}">
            <span class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $card['accent'] }}"><span class="material-symbols-outlined">{{ $card['icon'] }}</span></span>
            <span>
                <strong class="block text-2xl font-extrabold tracking-tight text-slate-950">{{ number_format($stats[$card['key']], 0, ',', ' ') }}</strong>
                <span class="mt-0.5 block text-[11px] font-bold text-slate-500">{{ $card['label'] }}</span>
            </span>
        </article>
    @endforeach
</section>

<section class="admin-panel mt-5 p-4 sm:p-5">
    <form method="GET" action="{{ route('admin.errors.index') }}" class="grid gap-3 lg:grid-cols-[minmax(16rem,1fr)_14rem_auto_auto]">
        <label>
            <span class="sr-only">Поиск по ошибкам</span>
            <span class="relative block">
                <span class="material-symbols-outlined pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-lg text-slate-400">search</span>
                <input type="search" name="q" value="{{ $search }}" class="admin-field pl-10" placeholder="Сообщение, класс или фрагмент stack trace">
            </span>
        </label>
        <label>
            <span class="sr-only">Уровень ошибки</span>
            <select name="level" class="admin-field">
                <option value="">Все уровни</option>
                @foreach ($levels as $value => $label)
                    <option value="{{ $value }}" @selected($selectedLevel === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="admin-button cursor-pointer"><span class="material-symbols-outlined text-lg">filter_alt</span> Применить</button>
        <a href="{{ route('admin.errors.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl px-3 text-sm font-bold text-slate-500 transition hover:bg-white/70 hover:text-slate-900">Сбросить</a>
    </form>

    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-200/80 pt-4 text-[11px] font-semibold text-slate-500">
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-emerald-600">visibility</span> Только чтение</span>
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-violet-600">shield_lock</span> Секретные значения маскируются</span>
        <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-cyan-700">refresh</span> Данные обновляются при загрузке страницы</span>
    </div>
</section>

@if ($truncated)
    <div class="mt-5 flex items-start gap-3 rounded-2xl border border-amber-300/70 bg-amber-50/80 px-4 py-3 text-sm text-amber-900 shadow-sm">
        <span class="material-symbols-outlined mt-0.5 shrink-0">info</span>
        <p><strong class="font-extrabold">Показана последняя часть логов.</strong> Старые записи ограничены настройками просмотрщика, чтобы большая история не перегружала память сервера.</p>
    </div>
@endif

<div class="mt-5 grid gap-5 2xl:grid-cols-[minmax(0,1fr)_20rem]">
    <section class="space-y-3" aria-label="Записи об ошибках">
        @forelse ($entries as $entry)
            <article class="admin-panel overflow-hidden" data-admin-error-entry="{{ $entry['id'] }}" data-error-level="{{ $entry['level'] }}">
                <div class="p-4 sm:p-5">
                    <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold text-slate-500">
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 uppercase tracking-wider ring-1 {{ $levelStyles[$entry['level']] }}">{{ $entry['level_label'] }}</span>
                        <time datetime="{{ str_replace(' ', 'T', $entry['datetime']) }}" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-slate-600"><span class="material-symbols-outlined text-sm">schedule</span>{{ $entry['datetime'] }}</time>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">{{ $entry['environment'] }}</span>
                        <span class="ml-auto inline-flex min-w-0 items-center gap-1.5 font-mono text-slate-400"><span class="material-symbols-outlined text-sm">description</span><span class="truncate">{{ $entry['file'] }}</span></span>
                    </div>

                    <h2 class="mt-3 break-words text-sm font-extrabold leading-6 text-slate-950 sm:text-base">{{ $entry['message'] }}</h2>
                </div>

                <details class="group border-t border-slate-200/80">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-xs font-extrabold text-slate-600 transition hover:bg-white/65 hover:text-violet-800 sm:px-5">
                        <span class="inline-flex items-center gap-2"><span class="material-symbols-outlined text-lg">code</span> Контекст и stack trace</span>
                        <span class="material-symbols-outlined text-lg transition group-open:rotate-180">expand_more</span>
                    </summary>
                    <div class="border-t border-slate-200/80 bg-slate-950 p-4 sm:p-5">
                        <pre class="max-h-[34rem] overflow-auto whitespace-pre-wrap break-words font-mono text-[11px] leading-5 text-slate-300">{{ $entry['details'] }}</pre>
                    </div>
                </details>
            </article>
        @empty
            <div class="admin-panel px-6 py-16 text-center">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-emerald-500/10 text-emerald-700"><span class="material-symbols-outlined text-3xl">check_circle</span></span>
                <h2 class="mt-4 text-lg font-extrabold text-slate-950">Ошибок не найдено</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Лог пуст, недоступен для чтения или ни одна запись не соответствует выбранным фильтрам.</p>
            </div>
        @endforelse

        @if ($entries->hasPages())
            <div class="pt-2">{{ $entries->links() }}</div>
        @endif
    </section>

    <aside class="admin-panel h-fit overflow-hidden 2xl:sticky 2xl:top-24" aria-labelledby="log-files-heading">
        <div class="border-b border-slate-200/80 px-4 py-4">
            <p class="admin-kicker">Источник</p>
            <h2 id="log-files-heading" class="mt-1 text-sm font-extrabold text-slate-950">Laravel log files</h2>
        </div>
        <div class="divide-y divide-slate-200/70">
            @forelse ($files as $file)
                <div class="px-4 py-3">
                    <span class="block truncate font-mono text-xs font-extrabold text-slate-700" title="{{ $file['name'] }}">{{ $file['name'] }}</span>
                    <span class="mt-1 flex items-center justify-between gap-3 text-[10px] font-semibold text-slate-400">
                        <span>{{ $file['size_formatted'] }}</span>
                        <span>{{ $file['updated_at']?->format('d.m.Y H:i') ?? '—' }}</span>
                    </span>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-xs leading-5 text-slate-400">Файлы вида <span class="font-mono">laravel*.log</span> не найдены.</p>
            @endforelse
        </div>
    </aside>
</div>
@endsection
