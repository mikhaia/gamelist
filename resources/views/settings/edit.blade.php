@extends('layouts.app')

@section('title', 'Настройки')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <span class="eyebrow"><span class="material-symbols-outlined">settings</span> Аккаунт</span>
        <h1 class="page-title">Настройки</h1>
        <p class="muted mt-2">Управление аватаром и безопасностью аккаунта {{ '@'.auth()->user()->login }}.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <section class="panel">
            <h2 class="text-lg font-extrabold">Аватар</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Изображение будет оптимизировано и сохранено локально.</p>
            <form method="POST" enctype="multipart/form-data" action="{{ route('settings.avatar') }}" class="mt-5 space-y-4">
                @csrf @method('PATCH')
                <div class="mx-auto grid size-28 place-items-center overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-violet-900/50 to-cyan-950/50">
                    @if (auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="Аватар" class="h-full w-full object-cover">
                    @else
                        <span class="material-symbols-outlined text-5xl text-white/25">person</span>
                    @endif
                </div>
                <div>
                    <label class="label" for="avatar">Новое изображение</label>
                    <input class="field file:mr-3 file:rounded-lg file:border-0 file:bg-violet-500/15 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-300" id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required>
                    @error('avatar') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button class="button button-primary w-full"><span class="material-symbols-outlined">account_circle</span> Обновить аватар</button>
            </form>
        </section>

        <section class="panel">
            <h2 class="text-lg font-extrabold">Сменить пароль</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Для подтверждения понадобится текущий пароль.</p>
            <form method="POST" action="{{ route('settings.password') }}" class="mt-5 space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="label" for="current_password">Текущий пароль</label>
                    <input class="field" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="password">Новый пароль</label>
                    <input class="field" id="password" name="password" type="password" autocomplete="new-password" required>
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label" for="password_confirmation">Повторите пароль</label>
                    <input class="field" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <button class="button button-primary w-full"><span class="material-symbols-outlined">password</span> Сохранить пароль</button>
            </form>
        </section>
    </div>
</div>
@endsection
