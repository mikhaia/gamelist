@php
    $games = [
        [
            'title' => 'Persona 5 Royal',
            'hours' => '≈ 101 час',
            'note' => 'Один учебный год, несколько дворцов и очень много карри.',
            'mark' => 'P5R',
            'accent' => '#ef4444',
            'accentSoft' => 'rgba(239, 68, 68, .16)',
        ],
        [
            'title' => 'Baldur’s Gate 3',
            'hours' => '≈ 68 часов',
            'note' => 'Можно спасти мир. Или потратить вечер на создание персонажа.',
            'mark' => 'BG3',
            'accent' => '#f59e0b',
            'accentSoft' => 'rgba(245, 158, 11, .16)',
        ],
        [
            'title' => 'Elden Ring',
            'hours' => '≈ 60 часов',
            'note' => 'Междуземье большое — обновление точно успеет установиться.',
            'mark' => 'ER',
            'accent' => '#eab308',
            'accentSoft' => 'rgba(234, 179, 8, .14)',
        ],
        [
            'title' => 'The Witcher 3',
            'hours' => '≈ 52 часа',
            'note' => 'Пара контрактов, партия в гвинт — и мы уже снова онлайн.',
            'mark' => 'W3',
            'accent' => '#22d3ee',
            'accentSoft' => 'rgba(34, 211, 238, .14)',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#070913">
    <title>Скоро вернёмся · GameList</title>
    <meta name="description" content="GameList обновляется и скоро вернётся.">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preload" href="/fonts/material-symbols-outlined.woff2?v=20260722-1" as="font" type="font/woff2" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Material Symbols Outlined';
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url('/fonts/material-symbols-outlined.woff2?v=20260722-1') format('woff2');
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
                radial-gradient(circle at 50% -12%, rgba(124, 58, 237, .24), transparent 35rem),
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
            mask-image: linear-gradient(to bottom, black, transparent 80%);
        }
        .ambient {
            position: fixed;
            width: 24rem;
            height: 24rem;
            border-radius: 999px;
            filter: blur(110px);
            opacity: .18;
            pointer-events: none;
        }
        .ambient-one { top: 20%; left: -14rem; background: #7c3aed; }
        .ambient-two { right: -13rem; bottom: 3%; background: #0891b2; }
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
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 28px 0 40px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -.025em;
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
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 32px;
            background: rgba(255, 255, 255, .052);
            box-shadow: 0 32px 90px rgba(0, 0, 0, .35);
            backdrop-filter: blur(22px);
        }
        .hero {
            display: grid;
            align-items: center;
            gap: 30px;
            padding: clamp(28px, 5vw, 64px);
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            margin-bottom: 18px;
            padding: 7px 12px;
            border: 1px solid rgba(167, 139, 250, .22);
            border-radius: 999px;
            color: #c4b5fd;
            background: rgba(139, 92, 246, .1);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .15em;
            text-transform: uppercase;
        }
        h1 {
            max-width: 770px;
            margin: 0;
            font-size: clamp(36px, 6vw, 68px);
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
            max-width: 690px;
            margin: 22px 0 0;
            color: #94a3b8;
            font-size: clamp(15px, 2vw, 18px);
            line-height: 1.75;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px;
            margin-top: 28px;
        }
        .retry {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 18px;
            border: 1px solid rgba(167, 139, 250, .3);
            border-radius: 14px;
            color: white;
            background: #8b5cf6;
            box-shadow: 0 14px 32px rgba(76, 29, 149, .28);
            font: inherit;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            transition: transform .2s ease, background .2s ease;
        }
        .retry:hover { transform: translateY(-2px); background: #a78bfa; }
        .hint { color: #64748b; font-size: 12px; line-height: 1.6; }
        .deploy-visual {
            position: relative;
            display: none;
            width: 220px;
            height: 220px;
            place-items: center;
            justify-self: end;
        }
        .deploy-ring {
            position: absolute;
            inset: 0;
            border: 1px solid rgba(167, 139, 250, .2);
            border-radius: 999px;
            animation: pulse 2.4s ease-out infinite;
        }
        .deploy-ring:nth-child(2) { inset: 24px; animation-delay: .55s; }
        .deploy-core {
            display: grid;
            width: 112px;
            height: 112px;
            place-items: center;
            border: 1px solid rgba(103, 232, 249, .22);
            border-radius: 34px;
            color: #c4b5fd;
            background: linear-gradient(145deg, rgba(139, 92, 246, .22), rgba(8, 145, 178, .12));
            box-shadow: 0 24px 70px rgba(76, 29, 149, .3);
            transform: rotate(-5deg);
        }
        .deploy-core .material-symbols-outlined { font-size: 48px; }
        .recommendations {
            padding: 0 clamp(20px, 4vw, 48px) clamp(24px, 4vw, 46px);
        }
        .recommendations-header {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
            padding-top: 26px;
            border-top: 1px solid rgba(255, 255, 255, .08);
        }
        .recommendations h2 { margin: 0; font-size: clamp(20px, 3vw, 27px); letter-spacing: -.025em; }
        .recommendations-header p { margin: 6px 0 0; color: #64748b; font-size: 13px; }
        .status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            border: 1px solid rgba(52, 211, 153, .2);
            border-radius: 999px;
            color: #a7f3d0;
            background: rgba(16, 185, 129, .08);
            font-size: 11px;
            font-weight: 700;
        }
        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #34d399;
            box-shadow: 0 0 0 5px rgba(52, 211, 153, .08);
            animation: blink 1.5s ease-in-out infinite;
        }
        .games { display: grid; gap: 12px; }
        .game-card {
            position: relative;
            min-width: 0;
            overflow: hidden;
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 22px;
            background: rgba(0, 0, 0, .18);
        }
        .game-card::after {
            position: absolute;
            top: -60px;
            right: -60px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--accent-soft);
            filter: blur(24px);
            content: '';
        }
        .poster {
            position: relative;
            z-index: 1;
            display: grid;
            height: 112px;
            margin-bottom: 15px;
            place-items: center;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 16px;
            color: rgba(255, 255, 255, .93);
            background:
                linear-gradient(135deg, var(--accent-soft), transparent 70%),
                rgba(255, 255, 255, .035);
        }
        .poster::before {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(110deg, transparent 35%, rgba(255, 255, 255, .06), transparent 65%);
            content: '';
            transform: translateX(-100%);
            animation: sheen 4.6s ease-in-out infinite;
        }
        .game-mark { font-size: 27px; font-weight: 800; letter-spacing: -.04em; text-shadow: 0 8px 28px var(--accent); }
        .game-meta { position: relative; z-index: 1; }
        .game-title { margin: 0; overflow: hidden; font-size: 15px; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
        .hours {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
            color: var(--accent);
            font-size: 11px;
            font-weight: 800;
        }
        .hours .material-symbols-outlined { font-size: 14px; }
        .game-note { min-height: 55px; margin: 10px 0 0; color: #64748b; font-size: 11px; line-height: 1.65; }
        .footnote { margin: 18px 0 0; color: #475569; font-size: 10px; text-align: center; }

        @keyframes pulse {
            0% { opacity: .65; transform: scale(.82); }
            75%, 100% { opacity: 0; transform: scale(1.08); }
        }
        @keyframes blink { 50% { opacity: .45; } }
        @keyframes sheen {
            0%, 60% { transform: translateX(-100%); }
            88%, 100% { transform: translateX(100%); }
        }
        @media (min-width: 620px) { .games { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 920px) {
            .hero { grid-template-columns: minmax(0, 1fr) 220px; }
            .deploy-visual { display: grid; }
        }
        @media (min-width: 1040px) { .games { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
<body>
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <main class="page">
        <div class="brand" aria-label="GameList">
            <span class="brand-mark"><span class="material-symbols-outlined">stadia_controller</span></span>
            <span>Game<span class="brand-accent">List</span></span>
        </div>

        <section class="panel">
            <div class="hero">
                <div>
                    <span class="eyebrow"><span class="material-symbols-outlined">rocket_launch</span> Технический перерыв · 503</span>
                    <h1>Мы деплоимся.<br><span>Скоро вернёмся.</span></h1>
                    <p class="lead">Пока мы выдаём серверу новый уровень и полируем сохранения, можно начать что-нибудь совсем коротенькое. Например…</p>
                    <div class="actions">
                        <a href="" class="retry"><span class="material-symbols-outlined">progress_activity</span> Проверить ещё раз</a>
                        <span class="hint">Обычно это занимает меньше одной игровой заставки.</span>
                    </div>
                </div>

                <div class="deploy-visual" aria-hidden="true">
                    <span class="deploy-ring"></span>
                    <span class="deploy-ring"></span>
                    <span class="deploy-core"><span class="material-symbols-outlined">sports_esports</span></span>
                </div>
            </div>

            <div class="recommendations">
                <div class="recommendations-header">
                    <div>
                        <h2>Во что можно пока поиграть</h2>
                        <p>На случай, если «пять минут» внезапно покажутся слишком долгими.</p>
                    </div>
                    <span class="status"><span class="status-dot"></span> Обновление устанавливается</span>
                </div>

                <div class="games">
                    @foreach ($games as $game)
                        <article class="game-card" style="--accent: {{ $game['accent'] }}; --accent-soft: {{ $game['accentSoft'] }};">
                            <div class="poster" aria-hidden="true"><span class="game-mark">{{ $game['mark'] }}</span></div>
                            <div class="game-meta">
                                <h3 class="game-title">{{ $game['title'] }}</h3>
                                <span class="hours"><span class="material-symbols-outlined">schedule</span> {{ $game['hours'] }} на сюжет</span>
                                <p class="game-note">{{ $game['note'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <p class="footnote">Время прохождения указано приблизительно. Релиз обновления, к счастью, займёт меньше.</p>
            </div>
        </section>
    </main>
</body>
</html>
