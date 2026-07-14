@extends('layouts.app')

@php($editing = $gameList->exists)
@section('title', $editing ? 'Редактировать список' : __('app.actions.create_list'))

@section('content')
<div class="mx-auto max-w-2xl">
    <a class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-white" href="{{ $editing ? route('lists.show', $gameList) : route('lists.index') }}">
        <span class="material-symbols-outlined">arrow_back</span> Назад
    </a>
    <div class="panel">
        <span class="eyebrow"><span class="material-symbols-outlined">playlist_add</span> Настройки списка</span>
        <h1 class="text-2xl font-extrabold">{{ $editing ? 'Редактировать список' : 'Новый список' }}</h1>

        <form method="POST" action="{{ $editing ? route('lists.update', $gameList) : route('lists.store') }}" class="mt-7 space-y-5">
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
            <div>
                <label class="label" for="default_platform">Платформа по умолчанию</label>
                <select class="field" id="default_platform" name="default_platform">
                    @foreach ($platforms as $platform)
                        <option value="{{ $platform->value }}" @selected(old('default_platform', $gameList->default_platform ?: 'nintendo_switch') === $platform->value)>{{ $platform->label() }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-white/8 bg-black/15 p-4">
                <input class="mt-1 size-4 accent-violet-500" type="checkbox" name="is_public" value="1" @checked(old('is_public', $gameList->exists ? $gameList->is_public : true))>
                <span><strong class="block text-sm">Публичный список</strong><span class="mt-1 block text-xs leading-5 text-slate-500">Любой человек со ссылкой сможет посмотреть список, но не сможет его изменить.</span></span>
            </label>
            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                <a class="button button-secondary" href="{{ $editing ? route('lists.show', $gameList) : route('lists.index') }}">{{ __('app.actions.cancel') }}</a>
                <button class="button button-primary"><span class="material-symbols-outlined">save</span> {{ __('app.actions.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
