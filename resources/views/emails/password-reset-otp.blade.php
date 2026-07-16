@extends('emails._layout')

@section('content')
    <h1 style="margin:0 0 16px;color:#fff;font-size:25px">Восстановление доступа</h1>
    <p style="margin:0 0 20px;color:#cbd5e1;font-size:16px;line-height:1.65">
        Используйте этот одноразовый код, чтобы установить новый пароль:
    </p>
    <div style="margin:0 0 22px;border:1px solid #4c1d95;border-radius:16px;background:#1e1538;padding:18px;text-align:center;color:#ddd6fe;font-family:monospace;font-size:34px;font-weight:800;letter-spacing:8px">
        {{ $code }}
    </div>
    <p style="margin:0;color:#94a3b8;font-size:13px;line-height:1.6">
        Код действует 10 минут и может быть использован только один раз. Если вы не запрашивали восстановление, просто проигнорируйте письмо.
    </p>
@endsection
