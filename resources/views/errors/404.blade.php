<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#070913">
    <title>Страница не найдена · GameList</title>
    <meta name="description" content="Такой страницы в GameList нет. Вернитесь на главную или найдите игру в каталоге.">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preload" href="/fonts/material-symbols-outlined.woff2?v=20260724-2" as="font" type="font/woff2" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url('/fonts/material-symbols-outlined.woff2?v=20260724-2') format('woff2');
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
            text-transform: none;
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
            border: 1px solid rgba(103, 232, 249, .2);
            border-radius: 999px;
            color: #a5f3fc;
            background: rgba(8, 145, 178, .08);
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
            inset: 16% 8%;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(103, 232, 249, .07), transparent 62%);
            content: '';
            animation: pulse 4s ease-in-out infinite;
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
            color: rgba(167, 139, 250, .1);
            transform: translateX(3px);
            clip-path: inset(18% 0 58% 0);
            animation: glitch 5.4s steps(1) infinite;
        }
        .error-code::after {
            color: rgba(103, 232, 249, .11);
            transform: translateX(-3px);
            clip-path: inset(65% 0 12% 0);
            animation: glitch 5.4s steps(1) .16s infinite reverse;
        }
        .radar-ring {
            position: absolute;
            width: 220px;
            height: 220px;
            border: 1px dashed rgba(167, 139, 250, .17);
            border-radius: 999px;
            animation: orbit 18s linear infinite;
        }
        .radar-ring::before,
        .radar-ring::after {
            position: absolute;
            border-radius: 999px;
            content: '';
        }
        .radar-ring::before { inset: 36px; border: 1px solid rgba(34, 211, 238, .1); }
        .radar-ring::after {
            top: 13px;
            left: 53px;
            width: 8px;
            height: 8px;
            background: #67e8f9;
            box-shadow: 0 0 20px rgba(103, 232, 249, .75);
        }
        .radar-ring-two {
            width: 300px;
            height: 300px;
            border-style: solid;
            border-color: rgba(34, 211, 238, .08);
            animation-duration: 25s;
            animation-direction: reverse;
        }
        .radar-ring-two::before { inset: 68px; border-color: rgba(167, 139, 250, .1); }
        .radar-ring-two::after { top: auto; right: 35px; bottom: 58px; left: auto; background: #a78bfa; box-shadow: 0 0 20px rgba(167, 139, 250, .7); }
        .route-line {
            position: absolute;
            width: 124px;
            height: 1px;
            opacity: .55;
            background: repeating-linear-gradient(90deg, rgba(103, 232, 249, .38) 0 7px, transparent 7px 13px);
            transform-origin: left center;
        }
        .route-one { margin: -76px 0 0 -186px; transform: rotate(28deg); }
        .route-two { margin: 95px 0 0 45px; transform: rotate(-31deg); }
        .route-node {
            position: absolute;
            width: 10px;
            height: 10px;
            border: 2px solid #070913;
            border-radius: 50%;
            background: #67e8f9;
            box-shadow: 0 0 0 5px rgba(34, 211, 238, .08), 0 0 18px rgba(34, 211, 238, .6);
            animation: blink 2.4s ease-in-out infinite;
        }
        .node-one { margin: -136px 0 0 -296px; }
        .node-two { margin: 156px 0 0 278px; animation-delay: .7s; }
        .core {
            position: relative;
            display: grid;
            width: 118px;
            height: 118px;
            place-items: center;
            border: 1px solid rgba(103, 232, 249, .25);
            border-radius: 36px;
            color: #cffafe;
            background: linear-gradient(145deg, rgba(8, 145, 178, .24), rgba(124, 58, 237, .16));
            box-shadow: 0 28px 80px rgba(8, 145, 178, .16), inset 0 1px rgba(255, 255, 255, .08);
            animation: float 3.6s ease-in-out infinite;
        }
        .core > .material-symbols-outlined { font-size: 53px; }
        .missing {
            position: absolute;
            top: -9px;
            right: -9px;
            display: grid;
            width: 35px;
            height: 35px;
            place-items: center;
            border: 5px solid #10111d;
            border-radius: 50%;
            color: #ede9fe;
            background: #7c3aed;
            box-shadow: 0 8px 20px rgba(76, 29, 149, .42);
        }
        .missing .material-symbols-outlined { font-size: 16px; }
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
            background: #22d3ee;
            box-shadow: 0 0 0 5px rgba(34, 211, 238, .08);
            animation: blink 1.6s ease-in-out infinite;
        }
        .footnote { margin: 18px 0 0; color: #475569; font-size: 10px; text-align: center; }

        @keyframes orbit { to { transform: rotate(360deg); } }
        @keyframes float { 50% { transform: translateY(-10px) rotate(-2deg); } }
        @keyframes pulse { 50% { opacity: .45; transform: scale(1.08); } }
        @keyframes blink { 50% { opacity: .38; } }
        @keyframes glitch {
            0%, 91%, 100% { transform: translateX(0); }
            92% { transform: translateX(6px); }
            94% { transform: translateX(-4px); }
            96% { transform: translateX(2px); }
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
            .radar-ring { width: 190px; height: 190px; }
            .radar-ring-two { width: 245px; height: 245px; }
            .core { width: 100px; height: 100px; border-radius: 30px; }
            .core > .material-symbols-outlined { font-size: 45px; }
            .route-one { margin-left: -150px; }
            .route-two { margin-left: 28px; }
            .node-one { margin-left: -248px; }
            .node-two { margin-left: 230px; }
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
                <span class="eyebrow"><span class="material-symbols-outlined">search_off</span> Маршрут не найден · 404</span>
                <h1 id="error-title">Эта страница вышла из игры.<br><span>Найдём другой путь?</span></h1>
                <p class="lead">Возможно, ссылка устарела или в адресе есть ошибка. Вернитесь на главную либо загляните в каталог — там точно найдётся что-нибудь интересное.</p>

                <div class="actions">
                    <a href="{{ url('/') }}" class="button button-primary"><span class="material-symbols-outlined">home</span> На главную</a>
                    <a href="{{ url('/search') }}" class="button"><span class="material-symbols-outlined">search</span> Найти игру</a>
                </div>

                <div class="status-card">
                    <span class="status-icon"><span class="material-symbols-outlined">travel_explore</span></span>
                    <span class="status-copy">
                        <strong>Пришли по старой ссылке?</strong>
                        <span>Проверьте адрес или начните новый маршрут с главной страницы GameList.</span>
                    </span>
                </div>
            </div>

            <div class="visual" aria-hidden="true">
                <span class="error-code" data-text="404">404</span>
                <span class="route-line route-one"></span>
                <span class="route-line route-two"></span>
                <span class="route-node node-one"></span>
                <span class="route-node node-two"></span>
                <span class="radar-ring"></span>
                <span class="radar-ring radar-ring-two"></span>
                <span class="core">
                    <span class="material-symbols-outlined">travel_explore</span>
                    <span class="missing"><span class="material-symbols-outlined">search_off</span></span>
                </span>
                <span class="signal"><span class="signal-dot"></span> Ищем новый маршрут</span>
            </div>
        </section>

        <p class="footnote">Код 404 · Страница не найдена</p>
    </main>
</body>
</html>
