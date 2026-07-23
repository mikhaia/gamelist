@extends('layouts.app')

@php($editing = $gameList->exists)
@php($availableStatusValues = (array) old('available_statuses', $gameList->availableStatusValues()))
@section('title', $editing ? 'Редактировать список' : __('app.actions.create_list'))

@section('content')
<div class="mx-auto max-w-2xl">
    <a class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-white" href="{{ $editing ? route('lists.show', $gameList) : route('lists.index') }}">
        <span class="material-symbols-outlined">arrow_back</span> Назад
    </a>
    <div class="panel">
        <span class="eyebrow"><span class="material-symbols-outlined">playlist_add</span> Настройки списка</span>
        <h1 class="text-2xl font-extrabold">{{ $editing ? 'Редактировать список' : 'Новый список' }}</h1>

        <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('lists.update', $gameList) : route('lists.store') }}" class="mt-7 space-y-5">
            @csrf
            @if ($editing) @method('PUT') @endif
            <div>
                <label class="label" for="name">Название</label>
                <input class="field" id="name" name="name" value="{{ old('name', $gameList->name) }}" required autofocus placeholder="Например: В очереди на Switch">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="slug">Адрес публичной ссылки</label>
                <div class="mt-2 flex items-center overflow-hidden rounded-xl border border-white/10 bg-black/20 focus-within:border-violet-400/60">
                    <span class="hidden border-r border-white/10 px-4 text-xs text-slate-600 sm:block">{{ auth()->user()->login }}/</span>
                    <input class="w-full bg-transparent px-4 py-3 text-sm outline-none" id="slug" name="slug" value="{{ old('slug', $gameList->slug) }}" placeholder="switch-backlog">
                </div>
                <p class="mt-2 text-xs text-slate-600">Только латинские строчные буквы, цифры и дефис. Если оставить пустым, адрес создастся автоматически.</p>
                @error('slug') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="description">Описание</label>
                <textarea class="field min-h-28 resize-y" id="description" name="description" placeholder="О чём этот список?">{{ old('description', $gameList->description) }}</textarea>
                @error('description') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div class="rounded-2xl border border-white/8 bg-black/15 p-4">
                <div class="flex items-start gap-4">
                    <div class="grid h-20 w-28 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-gradient-to-br from-violet-950 to-cyan-950">
                        @if ($gameList->cover_url)
                            <img src="{{ $gameList->cover_url }}" alt="Текущая обложка списка" class="h-full w-full object-cover">
                        @else
                            <span class="material-symbols-outlined text-3xl text-white/20">image</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-slate-200">Обложка списка</p>
                        <p class="mt-1 text-xs leading-5 text-slate-600">Она станет затемнённым фоном карточки и страницы списка. Загруженный файл имеет приоритет над URL.</p>
                    </div>
                </div>
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
            <div>
                <label class="label" for="default_platform">Платформа по умолчанию</label>
                <select class="field" id="default_platform" name="default_platform">
                    @foreach ($platforms as $platform)
                        <option value="{{ $platform->value }}" @selected(old('default_platform', $gameList->default_platform ?: 'nintendo_switch') === $platform->value)>{{ $platform->label() }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset class="rounded-2xl border border-white/8 bg-black/15 p-4">
                <legend class="px-1 text-sm font-bold text-slate-200">Доступные статусы</legend>
                <p class="mt-1 text-xs leading-5 text-slate-500">Выберите статусы, которые можно назначать играм в этом списке. Они также станут колонками режима «Доска».</p>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($statuses as $status)
                        <label class="relative cursor-pointer">
                            <input
                                type="checkbox"
                                name="available_statuses[]"
                                value="{{ $status->value }}"
                                class="peer sr-only"
                                @checked(in_array($status->value, $availableStatusValues, true))
                            >
                            <span class="flex items-center gap-3 rounded-xl border border-white/8 bg-black/20 px-3 py-3 pr-10 text-sm font-semibold text-slate-500 transition hover:text-white peer-checked:border-violet-400/40 peer-checked:bg-violet-500/15 peer-checked:text-violet-200">
                                <span class="material-symbols-outlined text-lg">{{ $status->icon() }}</span>
                                {{ $status->label() }}
                            </span>
                            <span class="material-symbols-outlined pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-base text-violet-300 opacity-0 transition peer-checked:opacity-100">check_circle</span>
                        </label>
                    @endforeach
                </div>
                @error('available_statuses') <p class="field-error mt-3">{{ $message }}</p> @enderror
                @error('available_statuses.*') <p class="field-error mt-3">{{ $message }}</p> @enderror
            </fieldset>
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-white/8 bg-black/15 p-4">
                <input class="mt-1 size-4 accent-violet-500" type="checkbox" name="is_public" value="1" @checked(old('is_public', $gameList->exists ? $gameList->is_public : true))>
                <span><strong class="block text-sm">Публичный список</strong><span class="mt-1 block text-xs leading-5 text-slate-500">Любой человек со ссылкой сможет посмотреть список, но не сможет его изменить.</span></span>
            </label>
            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-between">
                @if ($editing)
                    <button type="submit" form="delete-list" class="button button-danger" data-confirm="Удалить список вместе со всеми играми? Это действие нельзя отменить." data-confirm-title="Удалить список?" data-confirm-label="Удалить список"><span class="material-symbols-outlined">delete</span> Удалить список</button>
                @else<span></span>@endif
                <div class="flex flex-col-reverse gap-3 sm:flex-row">
                    <a class="button button-secondary" href="{{ $editing ? route('lists.show', $gameList) : route('lists.index') }}">{{ __('app.actions.cancel') }}</a>
                    <button class="button button-primary"><span class="material-symbols-outlined">save</span> {{ __('app.actions.save') }}</button>
                </div>
            </div>
        </form>
        @if ($editing)
            <form id="delete-list" method="POST" action="{{ route('lists.destroy', $gameList) }}">@csrf @method('DELETE')</form>
        @endif
    </div>
</div>
@endsection
