@props(['label', 'value', 'icon', 'tone' => 'violet', 'stat' => null, 'layout' => 'default', 'percentage' => null, 'percentageLabel' => null])

@php
    $tones = [
        'violet' => 'bg-violet-500/10 text-violet-700 ring-violet-500/15',
        'cyan' => 'bg-cyan-500/10 text-cyan-700 ring-cyan-500/15',
        'emerald' => 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/15',
        'amber' => 'bg-amber-500/10 text-amber-700 ring-amber-500/15',
    ];
@endphp

<article class="admin-panel group p-5 transition hover:-translate-y-0.5 hover:bg-white/80" @if($stat) data-admin-stat="{{ $stat }}" data-admin-value="{{ $value }}" @endif>
    @if ($layout === 'stacked')
        <p class="w-full text-3xl font-extrabold tracking-tight text-slate-950">{{ $value }}</p>
        @if ($percentage !== null)
            <div class="mt-3 flex items-center gap-3">
                <span class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-200/80" role="progressbar" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $label }}: {{ $percentageLabel }}">
                    <span class="block h-full rounded-full bg-gradient-to-r from-violet-600 to-cyan-500" style="width: {{ max(0, min(100, (float) $percentage)) }}%"></span>
                </span>
                <span class="shrink-0 text-xs font-extrabold text-slate-700">{{ $percentageLabel }}</span>
            </div>
        @endif
        <div class="mt-5 flex items-end justify-between gap-4">
            <p class="text-xs font-bold leading-5 text-slate-500">{{ $label }}</p>
            <span class="grid size-11 shrink-0 place-items-center rounded-2xl ring-1 {{ $tones[$tone] ?? $tones['violet'] }}">
                <span class="material-symbols-outlined">{{ $icon }}</span>
            </span>
        </div>
    @else
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold leading-5 text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $value }}</p>
            </div>
            <span class="grid size-11 shrink-0 place-items-center rounded-2xl ring-1 {{ $tones[$tone] ?? $tones['violet'] }}">
                <span class="material-symbols-outlined">{{ $icon }}</span>
            </span>
        </div>
    @endif
</article>
