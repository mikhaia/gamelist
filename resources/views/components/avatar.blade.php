@props(['user', 'size' => 'large'])

@php
    $dimensions = match ($size) {
        'tiny' => 'size-8',
        'small' => 'size-16',
        default => 'size-24 sm:size-28',
    };
    $iconSize = match ($size) {
        'tiny' => 'text-base',
        'small' => 'text-3xl',
        default => 'text-5xl',
    };
    $frame = $user->email
        ? 'from-amber-200 via-yellow-500 to-amber-700 shadow-amber-500/25'
        : 'from-slate-200 via-slate-400 to-slate-600 shadow-slate-400/20';
@endphp

<div {{ $attributes->class("relative shrink-0 rounded-full bg-gradient-to-br p-1 shadow-xl {$frame}") }} title="{{ $user->email ? 'Золотая рамка' : 'Серебряная рамка' }}">
    <div class="{{ $dimensions }} grid place-items-center overflow-hidden rounded-full border-4 border-[#0a0c16] bg-gradient-to-br from-violet-900 to-cyan-950">
        @if ($user->avatar_url)
            <img src="{{ $user->avatar_url }}" alt="Аватар {{ '@'.$user->login }}" class="h-full w-full object-cover">
        @else
            <span class="material-symbols-outlined {{ $iconSize }} text-white/45">person</span>
        @endif
    </div>
</div>
