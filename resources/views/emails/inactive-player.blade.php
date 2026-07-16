@extends('emails._layout')

@section('content')
    <h1 style="margin:0 0 16px;color:#fff;font-size:25px">Давно вас не было — как ваши игры?</h1>

    @if ($playingGames->isNotEmpty())
        <p style="margin:0 0 14px;color:#cbd5e1;font-size:16px;line-height:1.65">
            Надеемся, прохождение идёт отлично. В GameList у вас всё ещё отмечены как «Играю»:
        </p>
        <ul style="margin:0 0 22px;padding-left:22px;color:#ddd6fe;font-size:15px;line-height:1.8">
            @foreach ($playingGames->take(10) as $game)
                <li>{{ $game->title }}</li>
            @endforeach
        </ul>
    @else
        <p style="margin:0 0 22px;color:#cbd5e1;font-size:16px;line-height:1.65">
            Во что вы играете сейчас? Загляните в свои списки, выберите следующую игру или отметьте новый прогресс.
        </p>
    @endif

    <a href="{{ route('lists.index') }}" style="display:inline-block;border-radius:12px;background:#8b5cf6;padding:13px 20px;color:#fff;font-size:14px;font-weight:700;text-decoration:none">
        Перейти к моим спискам
    </a>
@endsection
