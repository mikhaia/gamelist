@extends('layouts.app')

@section('title', __('app.nav.register'))

@section('content')
<div class="mx-auto max-w-md py-8 sm:py-14">
    <div class="panel">
        <div class="brand-mark mb-6"><span class="material-symbols-outlined">person_add</span></div>
        <h1 class="text-2xl font-extrabold">Создать аккаунт</h1>
        <p class="muted mt-2">Логин станет частью публичных ссылок, а email можно использовать для входа.</p>

        <x-steam-button href="{{ route('steam.redirect') }}" class="mt-7" label="Регистрация через" aria-label="Зарегистрироваться через Steam" data-steam-register />

        <div class="my-6 flex items-center gap-3 text-[10px] font-extrabold uppercase tracking-[.18em] text-slate-600">
            <span class="h-px flex-1 bg-white/10"></span>
            или заполните форму
            <span class="h-px flex-1 bg-white/10"></span>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf
            <div>
                <label class="label" for="login">Логин</label>
                <input class="field" id="login" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus placeholder="your_login">
                <p class="mt-2 text-xs text-slate-600">Латинские буквы, цифры и _. От 3 до 32 символов.</p>
                @error('login') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="email">Email <span class="font-normal text-slate-600">· необязательно</span></label>
                <input class="field" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" placeholder="player@example.com">
                <p class="mt-2 text-xs text-slate-600">Email должен быть уникальным. Его можно добавить или изменить позже.</p>
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">Пароль</label>
                <input class="field" id="password" name="password" type="password" autocomplete="new-password" required placeholder="Минимум 8 символов">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Повторите пароль</label>
                <input class="field" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="••••••••">
            </div>
            <button class="button button-primary w-full"><span class="material-symbols-outlined">rocket_launch</span> Создать аккаунт</button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-500">Уже есть аккаунт? <a class="font-bold text-violet-300 hover:text-violet-200" href="{{ route('login') }}">Войти</a></p>
    </div>
</div>
@endsection
