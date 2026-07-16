@extends('emails._layout')

@section('content')
    <h1 style="margin:0 0 16px;color:#fff;font-size:25px">Пароль успешно изменён</h1>
    <p style="margin:0 0 22px;color:#cbd5e1;font-size:16px;line-height:1.65">
        Пароль аккаунта <strong style="color:#fff">{{ '@'.$recipient->login }}</strong> был изменён через восстановление доступа. Все прежние авторизованные сессии завершены.
    </p>
    <p style="margin:0;color:#fca5a5;font-size:13px;line-height:1.6">
        Если это были не вы, как можно скорее свяжитесь с администратором сайта.
    </p>
@endsection
