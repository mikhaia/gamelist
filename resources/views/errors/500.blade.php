<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#070913">
    <title>Ошибка сервера · GameList</title>
    <meta name="description" content="GameList столкнулся с временной ошибкой. Попробуйте обновить страницу.">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preload" href="/fonts/material-symbols-outlined.woff2?v=20260723-2" as="font" type="font/woff2" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url('/fonts/material-symbols-outlined.woff2?v=20260723-2') format('woff2');
        }

        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        html { min-width: 320px; background: #070913; }
        body {
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            color: #f8fafc;
            font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;
            background:
                radial-gradient(circle at 52% -12%, rgba(124, 58, 237, .26), transparent 36rem),
                radial-gradient(circle at 95% 78%, rgba(8, 145, 178, .1), transparent 28rem),
                linear-gradient(180deg, #070913 0%, #090b16 50%, #060810 100%);
        }
        body::before {
            position: fixed;
            inset: 0;
            pointer-events: none;
            content: '';
            opacity: .17;
            background-image:
                linear-gradient(rgba(255, 255, 255, .028) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .028) 1px, transparent 1px);
            background-size: 42px 42px;
            mask-image: linear-gradient(to bottom, black, transparent 84%);
        }
        .ambient {
            position: fixed;
            width: 25rem;
            height: 25rem;
            border-radius: 999px;
            filter: blur(115px);
            opacity: .18;
            pointer-events: none;
        }
        .ambient-one { top: 10%; left: -15rem; background: #7c3aed; }
        .ambient-two { right: -14rem; bottom: 0; background: #0891b2; }
        .material-symbols-outlined {
            display: inline-block;
            width: 1em;
            min-width: 1em;
            overflow: hidden;
            font-family: 'Material Symbols Outlined';
            font-feature-settings: 'liga';
            font-size: 1.25rem;
            font-style: normal;
            font-weight: 400;
            line-height: 1;
            letter-spacing: normal;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
        }
        .page {
            position: relative;
            z-index: 1;
            display: flex;
            width: min(1160px, calc(100% - 32px));
            min-height: 100vh;
            margin: 0 auto;
            flex-direction: column;
            justify-content: center;
            padding: 28px 0 40px;
        }
        .brand {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 10px;
            margin-bottom: 26px;
            color: #f8fafc;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -.025em;
            text-decoration: none;
        }
        .brand-mark {
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border: 1px solid rgba(167, 139, 250, .32);
            border-radius: 13px;
            color: #c4b5fd;
            background: rgba(139, 92, 246, .15);
            box-shadow: 0 12px 32px rgba(76, 29, 149, .2);
        }
        .brand-accent { color: #a78bfa; }
        .panel {
            position: relative;
            display: grid;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 32px;
            background: rgba(255, 255, 255, .052);
            box-shadow: 0 32px 90px rgba(0, 0, 0, .35);
            backdrop-filter: blur(22px);
        }
        .panel::before {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(115deg, rgba(139, 92, 246, .08), transparent 38%, rgba(34, 211, 238, .04));
            content: '';
        }
        .copy {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: clamp(30px, 6vw, 72px);
        }
        .eyebrow {
            display: inline-flex;
            width: fit-content;
            align-items: center;
            gap: 8px;
            margin-bottom: 19px;
            padding: 7px 12px;
            border: 1px solid rgba(251, 191, 36, .2);
            border-radius: 999px;
            color: #fde68a;
            background: rgba(245, 158, 11, .08);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .15em;
            text-transform: uppercase;
        }
        .eyebrow .material-symbols-outlined { font-size: 16px; }
        h1 {
            max-width: 690px;
            margin: 0;
            font-size: clamp(39px, 6vw, 68px);
            line-height: 1.02;
            letter-spacing: -.055em;
        }
        h1 span {
            color: transparent;
            background: linear-gradient(90deg, #c4b5fd, #67e8f9);
            background-clip: text;
            -webkit-background-clip: text;
        }
        .lead {
            max-width: 640px;
            margin: 22px 0 0;
            color: #94a3b8;
            font-size: clamp(15px, 2vw, 17px);
            line-height: 1.75;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 11px;
            margin-top: 29px;
        }
        .button {
            display: inline-flex;
            min-height: 47px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 18px;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 14px;
            color: #e2e8f0;
            background: rgba(255, 255, 255, .06);
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            transition: transform .2s ease, border-color .2s ease, background .2s ease;
        }
        .button:hover { transform: translateY(-2px); border-color: rgba(255, 255, 255, .2); background: rgba(255, 255, 255, .1); }
        .button-primary {
            border-color: rgba(167, 139, 250, .32);
            color: white;
            background: #8b5cf6;
            box-shadow: 0 14px 32px rgba(76, 29, 149, .3);
        }
        .button-primary:hover { background: #a78bfa; }
        .status-card {
            display: flex;
            max-width: 560px;
            align-items: flex-start;
            gap: 12px;
            margin-top: 29px;
            padding-top: 22px;
            border-top: 1px solid rgba(255, 255, 255, .08);
        }
        .status-icon {
            display: grid;
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid rgba(34, 211, 238, .18);
            border-radius: 12px;
            color: #67e8f9;
            background: rgba(8, 145, 178, .09);
        }
        .status-copy strong { display: block; color: #cbd5e1; font-size: 12px; }
        .status-copy span { display: block; margin-top: 4px; color: #64748b; font-size: 11px; line-height: 1.6; }
        .visual {
            position: relative;
            z-index: 1;
            display: grid;
            min-height: 330px;
            place-items: center;
            overflow: hidden;
            border-top: 1px solid rgba(255, 255, 255, .08);
            background:
                linear-gradient(rgba(255, 255, 255, .025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .025) 1px, transparent 1px),
                rgba(0, 0, 0, .13);
            background-size: 30px 30px;
        }
        .visual::before {
            position: absolute;
            top: -30%;
            left: 0;
            width: 100%;
            height: 28%;
            background: linear-gradient(to bottom, transparent, rgba(103, 232, 249, .06), transparent);
            content: '';
            animation: scan 5s linear infinite;
        }
        .error-code {
            position: absolute;
            color: rgba(255, 255, 255, .045);
            font-size: clamp(130px, 35vw, 220px);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.08em;
            user-select: none;
        }
        .error-code::before,
        .error-code::after {
            position: absolute;
            inset: 0;
            overflow: hidden;
            content: attr(data-text);
        }
        .error-code::before {
            color: rgba(167, 139, 250, .12);
            transform: translateX(3px);
            clip-path: inset(18% 0 58% 0);
            animation: glitch 4.2s steps(1) infinite;
        }
        .error-code::after {
            color: rgba(103, 232, 249, .1);
            transform: translateX(-3px);
            clip-path: inset(65% 0 12% 0);
            animation: glitch 4.2s steps(1) .16s infinite reverse;
        }
        .orbit {
            position: absolute;
            width: 250px;
            height: 250px;
            border: 1px solid rgba(167, 139, 250, .16);
            border-radius: 999px;
            animation: orbit 13s linear infinite;
        }
        .orbit::after {
            position: absolute;
            top: 20px;
            left: 42px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #a78bfa;
            box-shadow: 0 0 18px rgba(167, 139, 250, .8);
            content: '';
        }
        .orbit-two {
            width: 310px;
            height: 310px;
            border-color: rgba(34, 211, 238, .09);
            animation-duration: 19s;
            animation-direction: reverse;
        }
        .orbit-two::after { top: auto; bottom: 42px; left: auto; right: 34px; background: #22d3ee; box-shadow: 0 0 18px rgba(34, 211, 238, .7); }
        .core {
            position: relative;
            display: grid;
            width: 118px;
            height: 118px;
            place-items: center;
            border: 1px solid rgba(167, 139, 250, .28);
            border-radius: 36px;
            color: #ddd6fe;
            background: linear-gradient(145deg, rgba(139, 92, 246, .28), rgba(8, 145, 178, .12));
            box-shadow: 0 28px 80px rgba(76, 29, 149, .32), inset 0 1px rgba(255, 255, 255, .08);
            animation: float 3.6s ease-in-out infinite;
        }
        .core > .material-symbols-outlined { font-size: 53px; }
        .warning {
            position: absolute;
            top: -9px;
            right: -9px;
            display: grid;
            width: 35px;
            height: 35px;
            place-items: center;
            border: 5px solid #10111d;
            border-radius: 50%;
            color: #fde68a;
            background: #d97706;
            box-shadow: 0 8px 20px rgba(120, 53, 15, .45);
        }
        .warning .material-symbols-outlined { font-size: 16px; }
        .signal {
            position: absolute;
            bottom: 26px;
            left: 50%;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 999px;
            color: #94a3b8;
            background: rgba(7, 9, 19, .72);
            box-shadow: 0 12px 28px rgba(0, 0, 0, .24);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            transform: translateX(-50%);
            backdrop-filter: blur(12px);
        }
        .signal-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 0 0 5px rgba(245, 158, 11, .08);
            animation: blink 1.6s ease-in-out infinite;
        }
        .footnote { margin: 18px 0 0; color: #475569; font-size: 10px; text-align: center; }

        @keyframes scan { to { transform: translateY(560%); } }
        @keyframes orbit { to { transform: rotate(360deg); } }
        @keyframes float { 50% { transform: translateY(-10px) rotate(2deg); } }
        @keyframes blink { 50% { opacity: .38; } }
        @keyframes glitch {
            0%, 88%, 100% { transform: translateX(0); }
            89% { transform: translateX(7px); }
            91% { transform: translateX(-5px); }
            93% { transform: translateX(2px); }
        }
        @media (min-width: 900px) {
            .panel { grid-template-columns: minmax(0, 1.25fr) minmax(330px, .75fr); }
            .visual { min-height: 590px; border-top: 0; border-left: 1px solid rgba(255, 255, 255, .08); }
            .error-code { font-size: 190px; }
        }
        @media (max-width: 560px) {
            .page { width: min(100% - 20px, 1160px); padding-top: 18px; }
            .brand { margin-left: 8px; margin-bottom: 18px; }
            .panel { border-radius: 25px; }
            .copy { padding: 27px 22px 30px; }
            .actions { flex-direction: column; }
            .button { width: 100%; }
            .visual { min-height: 280px; }
            .orbit { width: 210px; height: 210px; }
            .orbit-two { width: 255px; height: 255px; }
            .core { width: 100px; height: 100px; border-radius: 30px; }
            .core > .material-symbols-outlined { font-size: 45px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
<body>
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <main class="page">
        <a href="{{ url('/') }}" class="brand" aria-label="GameList — на главную">
            <span class="brand-mark"><span class="material-symbols-outlined">stadia_controller</span></span>
            <span>Game<span class="brand-accent">List</span></span>
        </a>

        <section class="panel" aria-labelledby="error-title">
            <div class="copy">
                <span class="eyebrow"><span class="material-symbols-outlined">warning</span> Ошибка сервера · 500</span>
                <h1 id="error-title">Сервер пропустил ход.<br><span>Попробуем ещё раз?</span></h1>
                <p class="lead">Что-то пошло не по плану, но проблема уже записана в журнал. Обновите страницу — чаще всего следующая попытка проходит успешно.</p>

                <div class="actions">
                    <a href="" class="button button-primary"><span class="material-symbols-outlined">refresh</span> Попробовать снова</a>
                    <a href="{{ url('/') }}" class="button"><span class="material-symbols-outlined">home</span> На главную</a>
                </div>

                <div class="status-card">
                    <span class="status-icon"><span class="material-symbols-outlined">support_agent</span></span>
                    <span class="status-copy">
                        <strong>Ошибка повторяется?</strong>
                        <span>Подождите минуту и обновите страницу. Технические подробности намеренно скрыты.</span>
                    </span>
                </div>
            </div>

            <div class="visual" aria-hidden="true">
                <span class="error-code" data-text="500">500</span>
                <span class="orbit"></span>
                <span class="orbit orbit-two"></span>
                <span class="core">
                    <span class="material-symbols-outlined">sports_esports</span>
                    <span class="warning"><span class="material-symbols-outlined">priority_high</span></span>
                </span>
                <span class="signal"><span class="signal-dot"></span> Переподключаемся</span>
            </div>
        </section>

        <p class="footnote">Код 500 · Внутренняя ошибка сервера</p>
    </main>
</body>
</html>
