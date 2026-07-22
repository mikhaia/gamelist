@extends('layouts.app')

@section('title', 'Настройки')

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-8">
        <span class="eyebrow"><span class="material-symbols-outlined">settings</span> Аккаунт</span>
        <h1 class="page-title">Настройки</h1>
        <p class="muted mt-2">Управление email, аватаром и безопасностью аккаунта {{ '@'.auth()->user()->login }}.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <section class="panel md:col-span-2">
            <h2 class="text-lg font-extrabold">Обложка профиля</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Отображается фоном на публичной плашке профиля и в разделе друзей.</p>
            <form method="POST" enctype="multipart/form-data" action="{{ route('settings.profile-cover') }}" class="mt-5 space-y-4">
                @csrf @method('PATCH')
                <div class="relative h-40 overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-violet-950 to-cyan-950">
                    @if (auth()->user()->profile_cover_url)
                        <img src="{{ auth()->user()->profile_cover_url }}" alt="Обложка профиля" class="h-full w-full object-cover">
                        <div class="absolute inset-0 bg-[#090b16]/25"></div>
                    @endif
                    <div class="absolute inset-0 grid place-items-center"><x-avatar :user="auth()->user()" size="small" /></div>
                </div>
                <div>
                    <label class="label" for="profile_cover">Новое изображение</label>
                    <input class="field file:mr-3 file:rounded-lg file:border-0 file:bg-violet-500/15 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-300" id="profile_cover" name="profile_cover" type="file" accept="image/jpeg,image/png,image/webp,image/gif" required>
                    <p class="mt-2 text-[11px] leading-5 text-slate-500">Для Retina-экранов изображение сохраняется шириной до 2432 пикселей.</p>
                    @error('profile_cover') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button class="button button-primary"><span class="material-symbols-outlined">image</span> Обновить обложку профиля</button>
            </form>
        </section>

        <section class="panel">
            <h2 class="text-lg font-extrabold">Аватар</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Выберите фотографию и расположите лицо внутри круглой области на отдельной странице.</p>
            <div class="mt-5 flex flex-col items-center gap-4">
                <x-avatar :user="auth()->user()" />
                <a class="button button-primary w-full" href="{{ route('settings.avatar.edit') }}"><span class="material-symbols-outlined">crop</span> Настроить аватар</a>
            </div>
        </section>

        <section class="panel">
            <h2 class="text-lg font-extrabold">Email</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Необязательный адрес, который можно использовать вместо логина при входе. Он также даёт Gold-статус профиля и позволяет восстановить доступ с помощью одноразового кода, если вы забудете пароль.</p>
            <form method="POST" action="{{ route('settings.email') }}" class="mt-5 space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="label" for="email">Email</label>
                    <input class="field" id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" autocomplete="email" inputmode="email" placeholder="player@example.com">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button class="button button-primary w-full"><span class="material-symbols-outlined">save</span> Сохранить email</button>
            </form>
        </section>

        <section class="panel md:col-span-2">
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
