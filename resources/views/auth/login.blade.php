@extends('layouts.app')

@section('title', __('app.nav.login'))

@section('content')
<div class="mx-auto max-w-md py-8 sm:py-14">
    <div class="panel">
        <div class="brand-mark mb-6"><span class="material-symbols-outlined">login</span></div>
        <h1 class="text-2xl font-extrabold">С возвращением</h1>
        <p class="muted mt-2">Войдите, чтобы продолжить свою игровую историю.</p>

        <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
            @csrf
            <div>
                <label class="label" for="login">Логин или email</label>
                <input class="field" id="login" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus placeholder="your_login или player@example.com">
                @error('login') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <div class="flex items-center justify-between gap-3">
                    <label class="label" for="password">Пароль</label>
                    <a href="{{ route('password.request') }}" class="text-xs font-bold text-violet-300 transition hover:text-violet-200">Забыли пароль?</a>
                </div>
                <input class="field" id="password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex cursor-pointer items-center gap-3 text-sm text-slate-400">
                <input type="checkbox" name="remember" value="1" class="size-4 accent-violet-500"> Запомнить меня
            </label>
            <button class="button button-primary w-full"><span class="material-symbols-outlined">login</span> Войти</button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-500">Нет аккаунта? <a class="font-bold text-violet-300 hover:text-violet-200" href="{{ route('register') }}">Зарегистрироваться</a></p>
    </div>
</div>
@endsection
