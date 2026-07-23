@props(['field', 'label', 'sort', 'direction', 'defaultDirection' => 'asc'])

@php
    $active = $sort === $field;
    $nextDirection = $active && $direction === 'asc' ? 'desc' : ($active ? 'asc' : $defaultDirection);
    $query = array_merge(request()->except('page'), ['sort' => $field, 'direction' => $nextDirection]);
    $url = request()->url().'?'.http_build_query($query);
@endphp

<a href="{{ $url }}" class="inline-flex items-center gap-1.5 transition hover:text-violet-700 {{ $active ? 'text-violet-700' : 'text-slate-500' }}">
    <span>{{ $label }}</span>
    @if ($active)
        <span aria-hidden="true">{{ $direction === 'asc' ? '↑' : '↓' }}</span>
    @else
        <span class="material-symbols-outlined text-sm opacity-50">sort</span>
    @endif
</a>
