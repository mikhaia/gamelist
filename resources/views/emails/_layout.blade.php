<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? 'GameList' }}</title>
</head>
<body style="margin:0;background:#070913;color:#e2e8f0;font-family:Arial,sans-serif">
    <div style="padding:32px 16px">
        <div style="max-width:600px;margin:0 auto;overflow:hidden;border:1px solid #25283a;border-radius:24px;background:#101321">
            <div style="padding:24px 28px;border-bottom:1px solid #25283a;background:linear-gradient(135deg,#2e1065,#083344)">
                <div style="font-size:22px;font-weight:800;color:#fff">Game<span style="color:#a78bfa">List</span></div>
            </div>
            <div style="padding:30px 28px">
                @yield('content')
            </div>
            <div style="padding:20px 28px;border-top:1px solid #25283a;color:#64748b;font-size:12px">
                Твоя игровая история — в одном месте
            </div>
        </div>
    </div>
</body>
</html>
