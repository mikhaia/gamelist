@extends('layouts.app')

@section('title', 'Новый пароль')

@section('content')
<div class="mx-auto max-w-md py-8 sm:py-14">
    <div class="panel">
        <div class="brand-mark mb-6"><span class="material-symbols-outlined">lock</span></div>
        <h1 class="text-2xl font-extrabold">Введите код</h1>
        <p class="muted mt-2">Код отправлен на <span class="font-semibold text-slate-200">{{ $email }}</span>. Он действует 10 минут.</p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">
            @csrf
            <div>
                <label class="label" for="code">Шестизначный код</label>
                <input class="field text-center font-mono text-xl tracking-[.35em]" id="code" name="code" value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required autofocus placeholder="000000">
                @error('code') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">Новый пароль</label>
                <input class="field" id="password" name="password" type="password" autocomplete="new-password" required placeholder="Минимум 8 символов">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Повторите пароль</label>
                <input class="field" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </div>
            <button class="button button-primary w-full"><span class="material-symbols-outlined">save</span> Изменить пароль</button>
        </form>

        <form method="POST" action="{{ route('password.email') }}" class="mt-4 text-center">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button class="cursor-pointer text-sm font-bold text-violet-300 transition hover:text-violet-200">Отправить новый код</button>
        </form>
    </div>
</div>
@endsection
