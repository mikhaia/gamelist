@props(['title', 'subtitle', 'series', 'icon', 'tone' => 'violet', 'chart'])

@php
    $maximum = max(1, collect($series)->max('count'));
    $total = collect($series)->sum('count');
    $tones = [
        'violet' => [
            'icon' => 'bg-violet-500/10 text-violet-700 ring-violet-500/15',
            'bar' => 'from-violet-600 to-fuchsia-400',
            'shadow' => 'shadow-violet-500/20',
        ],
        'cyan' => [
            'icon' => 'bg-cyan-500/10 text-cyan-700 ring-cyan-500/15',
            'bar' => 'from-cyan-600 to-emerald-400',
            'shadow' => 'shadow-cyan-500/20',
        ],
    ];
    $colors = $tones[$tone] ?? $tones['violet'];
@endphp

<section class="admin-panel p-5 sm:p-6" data-admin-chart="{{ $chart }}">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="font-extrabold text-slate-950">{{ $title }}</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $subtitle }}</p>
        </div>
        <span class="grid size-11 shrink-0 place-items-center rounded-2xl ring-1 {{ $colors['icon'] }}"><span class="material-symbols-outlined">{{ $icon }}</span></span>
    </div>

    <div class="mt-6 grid h-48 grid-cols-7 gap-2 sm:gap-3" aria-label="{{ $title }} за семь дней">
        @foreach ($series as $day)
            @php($height = $day['count'] > 0 ? max(8, round(($day['count'] / $maximum) * 100, 2)) : 0)
            <div class="flex min-w-0 flex-col items-center" data-chart-date="{{ $day['date'] }}" data-chart-count="{{ $day['count'] }}">
                <span class="mb-2 text-xs font-extrabold text-slate-700">{{ $day['count'] }}</span>
                <div class="flex min-h-0 w-full flex-1 items-end justify-center px-1.5 pt-2">
                    <span class="block w-full max-w-8 rounded-[5px] bg-gradient-to-t {{ $colors['bar'] }} shadow-lg {{ $colors['shadow'] }} transition-all duration-500" style="height: {{ $height }}%"></span>
                </div>
                <span class="mt-2 text-[10px] font-extrabold uppercase text-slate-500">{{ $day['weekday'] }}</span>
                <span class="mt-0.5 text-[9px] font-semibold text-slate-400">{{ $day['label'] }}</span>
            </div>
        @endforeach
    </div>

    <div class="mt-4 flex items-center justify-between border-t border-slate-200/70 pt-4 text-xs">
        <span class="font-semibold text-slate-500">Всего за период</span>
        <span class="rounded-lg bg-slate-950 px-2.5 py-1.5 font-extrabold text-white">{{ number_format($total, 0, ',', ' ') }}</span>
    </div>
</section>
