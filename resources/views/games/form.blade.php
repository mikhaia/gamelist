@extends('layouts.app')

@php($editing = $game->exists)
@section('title', $editing ? 'Редактировать игру' : __('app.actions.add_game'))

@section('content')
<div class="mx-auto max-w-5xl">
    <a class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-white" href="{{ route('lists.show', $gameList) }}">
        <span class="material-symbols-outlined">arrow_back</span> {{ __('app.actions.back') }}
    </a>

    <div class="grid gap-5 lg:grid-cols-[.9fr_1.1fr]">
        <section class="panel h-fit">
            <span class="eyebrow"><span class="material-symbols-outlined">travel_explore</span> Поиск</span>
            <h1 class="text-2xl font-extrabold">{{ $editing ? 'Редактировать игру' : 'Найти игру' }}</h1>
            <p class="muted mt-2">Поиск поможет заполнить название, обложку и время прохождения. Если сервис недоступен, заполните форму вручную.</p>

            <form method="GET" action="{{ $editing ? route('games.edit', $game) : route('games.create', $gameList) }}" class="mt-5 flex gap-2" data-catalog-search-form>
                <input class="field mt-0" name="q" value="{{ $query }}" placeholder="Название игры" aria-label="Название игры для поиска">
                <button class="button button-secondary shrink-0"><span class="material-symbols-outlined">search</span><span class="hidden sm:inline">{{ __('app.actions.search') }}</span></button>
            </form>

                <div class="mt-4 {{ $query === '' ? 'hidden' : '' }}" data-catalog-search data-query="{{ $query }}" data-cache-url="{{ route('catalog.cached') }}" data-search-url="{{ route('catalog.search') }}">
                    <div class="max-h-[34rem] space-y-2 overflow-y-auto pr-1" data-catalog-results>
                        @include('games._catalog_results', ['results' => $results, 'cached' => true])
                    </div>
                    <div class="mt-3 flex items-center gap-2 rounded-xl border border-violet-400/10 bg-violet-500/5 px-3 py-2.5 text-xs text-violet-200/70 {{ $query === '' ? 'hidden' : '' }}" data-catalog-loading>
                        <span class="material-symbols-outlined animate-spin text-base">progress_activity</span>
                        <span data-catalog-loading-label>{{ $results === [] ? 'Ищем игры во внешнем каталоге…' : 'Показали локальные результаты. Ищем остальные…' }}</span>
                    </div>
                    <div class="mt-3 hidden rounded-2xl border border-white/8 bg-black/15 p-4 text-sm text-slate-500" data-catalog-empty>Совпадений не найдено. Попробуйте другой запрос или добавьте игру вручную.</div>
                    <div class="mt-3 hidden rounded-2xl border border-amber-400/20 bg-amber-500/8 p-4 text-sm leading-6 text-amber-200" data-catalog-error>
                        <strong class="flex items-center gap-2"><span class="material-symbols-outlined">cloud_off</span> Внешний каталог сейчас недоступен</strong>
                        <p class="mt-1 text-xs text-amber-200/60">Локальные результаты уже показаны. Игру также можно добавить вручную.</p>
                    </div>
                </div>
        </section>

        <section class="panel">
            <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('games.update', $game) : route('games.store', $gameList) }}" class="space-y-5" data-game-form>
                @csrf
                @if ($editing) @method('PUT') @endif
                <input type="hidden" name="hltb_id" value="{{ old('hltb_id', $game->hltb_id) }}">
                <input type="hidden" name="catalog_cover_url" value="">
                <input type="hidden" name="source_cover_url" value="{{ old('source_cover_url', $game->source_cover_url) }}">
                <input type="hidden" name="main_story_minutes" value="{{ old('main_story_minutes', $game->main_story_minutes) }}">
                <input type="hidden" name="main_extra_minutes" value="{{ old('main_extra_minutes', $game->main_extra_minutes) }}">
                <input type="hidden" name="completionist_minutes" value="{{ old('completionist_minutes', $game->completionist_minutes) }}">

                <div class="flex items-start gap-4">
                    <div class="grid aspect-[3/4] w-24 shrink-0 place-items-center overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-violet-950 to-cyan-950 sm:w-28">
                        <img data-cover-preview src="{{ $game->cover_url ?: 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=' }}" alt="Предпросмотр обложки" class="h-full w-full object-cover">
                    </div>
                    <div class="min-w-0 flex-1 pt-1">
                        <p class="text-sm font-bold">Данные игры</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">Результат поиска можно выбрать слева — поля заполнятся автоматически.</p>
                    </div>
                </div>

                <div>
                    <label class="label" for="title">Название</label>
                    <input class="field" id="title" name="title" value="{{ old('title', $game->title) }}" required placeholder="Название игры">
                    @error('title') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="status">Статус</label>
                        <select class="field" id="status" name="status">
                            @foreach ($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $game->status?->value ?? 'want_to_play') === $status->value)>{{ $status->label() }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="platform">Платформа</label>
                        <select class="field" id="platform" name="platform">
                            @foreach ($platforms as $platform)<option value="{{ $platform->value }}" @selected(old('platform', $game->platform?->value ?? $gameList->default_platform) === $platform->value)>{{ $platform->label() }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="started_at">Начал играть</label>
                        <input class="field" id="started_at" name="started_at" type="date" value="{{ old('started_at', $game->started_at?->format('Y-m-d')) }}">
                        @error('started_at') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label" for="completed_at">Закончил играть</label>
                        <input class="field" id="completed_at" name="completed_at" type="date" value="{{ old('completed_at', $game->completed_at?->format('Y-m-d')) }}">
                        @error('completed_at') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="rounded-2xl border border-white/8 bg-black/15 p-4">
                    <p class="text-sm font-bold">Своя обложка</p>
                    <p class="mt-1 text-xs text-slate-600">Загрузка файла имеет приоритет над URL и результатом поиска.</p>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="label" for="cover_url">URL изображения</label>
                            <input class="field" id="cover_url" name="cover_url" value="{{ old('cover_url') }}" type="url" placeholder="https://example.com/cover.jpg">
                            @error('cover_url') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label" for="cover">Или загрузить файл</label>
                            <input class="field file:mr-3 file:rounded-lg file:border-0 file:bg-violet-500/15 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-300" id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                            @error('cover') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                @if ($editing)
                    <div>
                        <label class="label" for="game_list_id">Список</label>
                        <select class="field" id="game_list_id" name="game_list_id">
                            @foreach ($gameLists as $list)
                                <option value="{{ $list->id }}" @selected((string) old('game_list_id', $gameList->id) === (string) $list->id)>{{ $list->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500">Игра будет перенесена в выбранный список.</p>
                        @error('game_list_id') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="label" for="notes">Заметки</label>
                    <textarea class="field min-h-24" id="notes" name="notes" placeholder="Необязательно">{{ old('notes', $game->notes) }}</textarea>
                </div>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-between">
                    @if ($editing)
                        <button type="submit" form="delete-game" class="button button-danger" data-confirm="Удалить игру из списка?"><span class="material-symbols-outlined">delete</span> {{ __('app.actions.delete') }}</button>
                    @else<span></span>@endif
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <a href="{{ route('lists.show', $gameList) }}" class="button button-secondary">{{ __('app.actions.cancel') }}</a>
                        <button class="button button-primary"><span class="material-symbols-outlined">save</span> {{ __('app.actions.save') }}</button>
                    </div>
                </div>
            </form>
            @if ($editing)
                <form id="delete-game" method="POST" action="{{ route('games.destroy', $game) }}">@csrf @method('DELETE')</form>
            @endif
        </section>
    </div>
</div>
@endsection
