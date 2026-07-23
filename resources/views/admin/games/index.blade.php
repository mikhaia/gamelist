@extends('layouts.admin')

@section('title', 'Игры')

@section('content')
<form method="GET" action="{{ route('admin.games.index') }}" class="admin-panel mb-5 grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-[minmax(15rem,1fr)_13rem_13rem_13rem_auto]">
    <div class="relative min-w-0">
        <span class="material-symbols-outlined pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-lg text-slate-400">search</span>
        <input class="admin-field pl-11" name="q" value="{{ $query }}" placeholder="Поиск по названию" aria-label="Поиск игр">
    </div>
    <select class="admin-field" name="genre" aria-label="Жанр">
        <option value="">Все жанры</option>
        @foreach ($filterOptions['genres'] as $option)
            <option value="{{ $option['value'] }}" @selected($genre === $option['value'])>{{ $option['label'] }}</option>
        @endforeach
    </select>
    <select class="admin-field" name="age_rating" aria-label="Возрастной рейтинг">
        <option value="">Любой возраст</option>
        @foreach ($ageRatings as $rating)
            <option value="{{ $rating }}" @selected($ageRating === $rating)>{{ $rating }}</option>
        @endforeach
    </select>
    <select class="admin-field" name="platform" aria-label="Платформа">
        <option value="">Все платформы</option>
        @foreach ($filterOptions['platforms'] as $option)
            <option value="{{ $option['value'] }}" @selected($platform === $option['value'])>{{ $option['label'] }}</option>
        @endforeach
    </select>
    <div class="flex gap-2 md:col-span-2 xl:col-span-1">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        <button class="admin-button flex-1"><span class="material-symbols-outlined">filter_alt</span> Применить</button>
        @if ($query !== '' || $genre || $ageRating || $platform)
            <a href="{{ route('admin.games.index') }}" class="grid size-11 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white/70 text-slate-500 transition hover:bg-white hover:text-slate-900" title="Сбросить фильтры" aria-label="Сбросить фильтры"><span class="material-symbols-outlined">filter_alt_off</span></a>
        @endif
    </div>
</form>

<section class="admin-panel overflow-hidden" aria-label="Таблица игр">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[76rem] border-collapse text-left">
            <thead class="border-b border-slate-200/80 bg-white/45 text-xs font-extrabold uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-4 sm:px-6"><x-admin.sort-link field="title" label="Название" :sort="$sort" :direction="$direction" /></th>
                    <th class="px-5 py-4 text-slate-500 sm:px-6">Жанры</th>
                    <th class="px-5 py-4 text-slate-500 sm:px-6">Возраст</th>
                    <th class="px-5 py-4 text-slate-500 sm:px-6">Платформы</th>
                    <th class="px-5 py-4 sm:px-6"><x-admin.sort-link field="updated_at" label="Обновлена" :sort="$sort" :direction="$direction" default-direction="desc" /></th>
                    <th class="px-5 py-4 sm:px-6"><x-admin.sort-link field="created_at" label="Добавлена" :sort="$sort" :direction="$direction" default-direction="desc" /></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/70">
                @forelse ($games as $game)
                    <tr class="transition hover:bg-white/55" data-admin-game="{{ $game->title }}">
                        <td class="max-w-72 px-5 py-4 sm:px-6">
                            <a href="{{ route('games.show', $game) }}" class="block truncate font-extrabold text-slate-900 transition hover:text-violet-700">{{ $game->title }}</a>
                        </td>
                        <td class="max-w-72 px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse (array_slice($game->genres ?? [], 0, 3) as $item)
                                    <span class="rounded-lg bg-violet-500/8 px-2 py-1 text-[10px] font-bold text-violet-800">{{ $item }}</span>
                                @empty
                                    <span class="text-sm text-slate-400">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-5 py-4 sm:px-6">
                            <span class="rounded-lg bg-amber-500/10 px-2 py-1 text-xs font-extrabold text-amber-800">{{ $game->ageRatingLabel() ?? $game->age_rating ?? '—' }}</span>
                        </td>
                        <td class="max-w-80 px-5 py-4 sm:px-6">
                            <p class="line-clamp-2 text-xs leading-5 text-slate-600">{{ implode(', ', $game->platforms ?? []) ?: '—' }}</p>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-500 sm:px-6">{{ $game->updated_at->format('d.m.Y H:i') }}</td>
                        <td class="px-5 py-4 text-sm font-semibold text-slate-600 sm:px-6">{{ $game->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-slate-400">Игры не найдены.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if ($games->hasPages())
    <div class="mt-5">{{ $games->links() }}</div>
@endif
@endsection
