@extends('layouts.app')

@section('title', 'Импорт игр')

@section('content')
<div class="mx-auto max-w-4xl">
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
                    <p class="mt-1 text-xs text-slate-500">Найдено строк: {{ count($items) }}. Дубликаты отключены.</p>
                </div>
                <div class="flex gap-2 text-xs">
                    <span class="status-chip"><span class="size-2 rounded-full bg-emerald-400"></span> Новая</span>
                    <span class="status-chip"><span class="size-2 rounded-full bg-amber-400"></span> Дубликат</span>
                </div>
            </div>

            <div class="mt-5 max-h-96 overflow-y-auto rounded-2xl border border-white/8">
                @forelse ($items as $item)
                    @php($duplicate = $item['duplicate_in_input'] || $item['duplicate_existing'])
                    <label class="flex items-center gap-3 border-b border-white/7 p-3 last:border-b-0 {{ $duplicate ? 'bg-amber-500/[.035]' : 'hover:bg-white/[.025]' }}">
                        <input type="checkbox" name="titles[]" value="{{ $item['title'] }}" class="size-4 accent-violet-500" @checked(!$duplicate) @disabled($duplicate)>
                        <span class="min-w-0 flex-1 truncate text-sm font-semibold {{ $duplicate ? 'text-slate-500' : 'text-slate-200' }}">{{ $item['title'] }}</span>
                        @if ($duplicate)
                            <span class="shrink-0 text-[10px] font-bold uppercase tracking-wider text-amber-400/70">{{ $item['duplicate_existing'] ? 'Уже в списке' : 'Повтор строки' }}</span>
                        @else
                            <span class="material-symbols-outlined text-base text-emerald-400">add_circle</span>
                        @endif
                    </label>
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
            @error('titles') <p class="field-error mt-3">Выберите хотя бы одну новую игру.</p> @enderror
            <div class="mt-5 flex justify-end">
                <button class="button button-primary" @disabled(collect($items)->every(fn ($item) => $item['duplicate_in_input'] || $item['duplicate_existing']))>
                    <span class="material-symbols-outlined">download_done</span> {{ __('app.actions.import_selected') }}
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
