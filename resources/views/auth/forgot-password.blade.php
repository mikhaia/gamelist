@extends('layouts.app')

@section('title', 'Восстановление доступа')

@section('content')
<div class="mx-auto max-w-md py-8 sm:py-14">
    <div class="panel">
        <div class="brand-mark mb-6"><span class="material-symbols-outlined">password</span></div>
        <h1 class="text-2xl font-extrabold">Забыли пароль?</h1>
        <p class="muted mt-2">Укажите email аккаунта — мы отправим шестизначный код для восстановления доступа.</p>

        <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input class="field" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" required autofocus placeholder="player@example.com">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button class="button button-primary w-full"><span class="material-symbols-outlined">password</span> Получить код</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-500"><a class="font-bold text-violet-300 hover:text-violet-200" href="{{ route('login') }}">Вернуться ко входу</a></p>
    </div>
</div>
@endsection
