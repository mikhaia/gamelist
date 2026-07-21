@extends('layouts.app')

@section('title', 'Импорт игр')

@section('content')
<div class="mx-auto max-w-6xl">
    <a class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-white" href="{{ route('lists.show', $gameList) }}">
        <span class="material-symbols-outlined">arrow_back</span> {{ __('app.actions.back') }}
    </a>
    <div class="panel">
        <span class="eyebrow"><span class="material-symbols-outlined">playlist_add</span> Массовое добавление</span>
        <h1 class="text-2xl font-extrabold">Импорт игр</h1>
        <p class="muted mt-2">Вставьте список: каждая непустая строка станет игрой. Маркеры Markdown, чекбоксы и нумерация будут удалены автоматически.</p>

        <form method="POST" action="{{ route('imports.preview', $gameList) }}" class="mt-6">
            @csrf
            <label class="label" for="games_text">Список игр</label>
            <textarea class="field min-h-64 font-mono text-sm leading-7" id="games_text" name="games_text" required placeholder="- [ ] Metroid Prime 4&#10;- [x] The Legend of Zelda: Tears of the Kingdom&#10;3. Hades II">{{ old('games_text', $gamesText) }}</textarea>
            @error('games_text') <p class="field-error">{{ $message }}</p> @enderror
            <div class="mt-4 flex justify-end">
                <button class="button button-primary"><span class="material-symbols-outlined">preview</span> {{ __('app.actions.preview') }}</button>
            </div>
        </form>
    </div>

    @if (is_array($items))
        <form method="POST" action="{{ route('imports.store', $gameList) }}" class="panel mt-5">
            @csrf
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-extrabold">Предварительный просмотр</h2>
                    <p class="mt-1 text-xs text-slate-500">Найдено строк: {{ count($items) }}. Выберите игры и при необходимости свяжите их с каталогом.</p>
                </div>
                <div class="flex gap-2 text-xs">
                    <span class="status-chip"><span class="size-2 rounded-full bg-emerald-400"></span> Новая</span>
                    <span class="status-chip"><span class="size-2 rounded-full bg-amber-400"></span> Дубликат</span>
                </div>
            </div>

            <div class="mt-5 space-y-3 pr-1">
                @forelse ($items as $index => $item)
                    @php($duplicate = $item['duplicate_in_input'] || $item['duplicate_existing'])
                    @php($exactSuggestion = collect($item['catalog_suggestions'])->firstWhere('normalized_title', $item['normalized_title']))
                    <article class="rounded-2xl border p-4 {{ $duplicate ? 'border-amber-400/15 bg-amber-500/[.035]' : 'border-white/8 bg-black/10' }}" data-import-item>
                        <input type="hidden" name="items[{{ $index }}][title]" value="{{ $item['title'] }}">
                        <div class="flex items-center gap-3">
                            <input id="import_item_{{ $index }}" type="checkbox" name="items[{{ $index }}][selected]" value="1" class="size-4 shrink-0 accent-violet-500" @checked(!$duplicate) @disabled($duplicate)>
                            <label for="import_item_{{ $index }}" class="min-w-0 flex-1 truncate text-sm font-extrabold {{ $duplicate ? 'text-slate-500' : 'text-slate-200' }}">{{ $item['title'] }}</label>
                            @if ($duplicate)
                                <span class="shrink-0 text-[10px] font-bold uppercase tracking-wider text-amber-400/70">{{ $item['duplicate_existing'] ? 'Уже в списке' : 'Повтор строки' }}</span>
                            @else
                                <span class="material-symbols-outlined text-base text-emerald-400">add_circle</span>
                            @endif
                        </div>

                        @if (! $duplicate)
                            <fieldset class="mt-4 border-t border-white/7 pt-3">
                                <legend class="px-1 text-[10px] font-extrabold uppercase tracking-[.12em] text-slate-500">Связать с игрой из каталога</legend>
                                <div class="mt-3 flex gap-3 overflow-x-auto overscroll-x-contain pb-2" data-import-catalog-suggestions>
                                    <label class="w-28 shrink-0 cursor-pointer">
                                        <input type="radio" name="items[{{ $index }}][catalog_game_id]" value="" class="peer sr-only" @checked(! $exactSuggestion)>
                                        <span class="block overflow-hidden rounded-2xl border border-white/8 bg-white/[.025] transition hover:border-white/20 peer-checked:border-violet-400/60 peer-checked:bg-violet-500/10">
                                            <span class="grid aspect-[3/4] place-items-center bg-gradient-to-br from-violet-950 to-cyan-950"><span class="material-symbols-outlined text-3xl text-white/25">edit_note</span></span>
                                            <span class="line-clamp-2 min-h-12 p-2 text-center text-[11px] font-bold leading-4 text-slate-300">Оставить название</span>
                                        </span>
                                    </label>

                                    @foreach ($item['catalog_suggestions'] as $suggestion)
                                        <label class="w-28 shrink-0 cursor-pointer">
                                            <input type="radio" name="items[{{ $index }}][catalog_game_id]" value="{{ $suggestion['id'] }}" class="peer sr-only" @checked(($exactSuggestion['id'] ?? null) === $suggestion['id'])>
                                            <span class="block overflow-hidden rounded-2xl border border-white/8 bg-white/[.025] transition hover:border-white/20 peer-checked:border-violet-400/60 peer-checked:bg-violet-500/10">
                                                <span class="grid aspect-[3/4] place-items-center overflow-hidden bg-gradient-to-br from-violet-950 to-cyan-950">
                                                    @if ($suggestion['cover_url'])
                                                        <img src="{{ $suggestion['cover_url'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                                    @else
                                                        <span class="material-symbols-outlined text-3xl text-white/20">sports_esports</span>
                                                    @endif
                                                </span>
                                                <span class="line-clamp-2 min-h-12 p-2 text-center text-[11px] font-bold leading-4 text-slate-200">{{ $suggestion['title'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @if ($item['catalog_suggestions'] === [])
                                    <p class="mt-2 text-xs text-slate-600">Похожих игр в каталоге не найдено — будет сохранено исходное название.</p>
                                @endif
                            </fieldset>
                        @endif
                    </article>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">Не найдено ни одной игры.</p>
                @endforelse
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="status">Статус для новых игр</label>
                    <select class="field" id="status" name="status">
                        @foreach ($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="platform">Платформа</label>
                    <select class="field" id="platform" name="platform">
                        @foreach ($platforms as $platform)<option value="{{ $platform->value }}" @selected($gameList->default_platform === $platform->value)>{{ $platform->label() }}</option>@endforeach
                    </select>
                </div>
            </div>
            @error('items') <p class="field-error mt-3">{{ $message }}</p> @enderror
            @error('items.*.catalog_game_id') <p class="field-error mt-3">{{ $message }}</p> @enderror
            <div class="mt-5 flex justify-end">
                <button class="button button-primary" @disabled(collect($items)->every(fn ($item) => $item['duplicate_in_input'] || $item['duplicate_existing']))>
                    <span class="material-symbols-outlined">download_done</span> {{ __('app.actions.import_selected') }}
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
