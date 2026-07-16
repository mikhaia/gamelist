@extends('emails._layout')

@section('content')
    <h1 style="margin:0 0 16px;color:#fff;font-size:25px">У вас новый друг!</h1>
    <p style="margin:0 0 22px;color:#cbd5e1;font-size:16px;line-height:1.65">
        Пользователь <strong style="color:#fff">{{ '@'.$friend->login }}</strong> добавил вас в друзья и хочет следить за вашей игровой историей.
    </p>
    <a href="{{ route('profiles.show', $friend->login) }}" style="display:inline-block;border-radius:12px;background:#8b5cf6;padding:13px 20px;color:#fff;font-size:14px;font-weight:700;text-decoration:none">
        Открыть профиль
    </a>
@endsection
