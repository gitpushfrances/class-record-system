<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Class Record System') }} — Sign In</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,600;0,9..144,700;1,9..144,300&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.12.2/lottie.min.js" defer></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-deep:    #1a1610;
            --bg-mid:     #221d14;
            --bg-light:   #2e2618;
            --gold:       #c8a97e;
            --gold-l:     #e0c99a;
            --gold-xl:    #f0dfc0;
            --gold-d:     #9a7a50;
            --gold-dd:    #7a5e38;
            --charcoal:   #2a2318;
            --card-bg:    rgba(248, 243, 235, 0.98);
            --text:       #1e1a13;
            --muted:      #7a6f62;
            --border:     #e5dbd0;
            --input-bg:   #f4efe6;
            --error:      #b91c1c;
            --success-bg: #f0fdf4;
            --success-tx: #166534;
            --success-bd: #bbf7d0;
        }

        html, body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow: auto;
        }

        /* ── SCENE ── */
        .scene {
            min-height: 100vh;
            background: linear-gradient(145deg, var(--bg-deep) 0%, var(--bg-mid) 50%, var(--bg-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            padding: 2rem 1rem;
        }

        /* Grain overlay */
        .scene::after {
            content: '';
            position: absolute; inset: 0; z-index: 1;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            opacity: 0.04;
            pointer-events: none;
        }

        /* Radial glow blobs */
        .orb {
            position: absolute; border-radius: 50%;
            pointer-events: none; z-index: 0;
            filter: blur(90px);
        }
        .orb-1 {
            width: 600px; height: 600px;
            top: -180px; left: -150px;
            background: radial-gradient(circle, rgba(200,169,126,0.13) 0%, transparent 70%);
            animation: orbFloat 18s ease-in-out infinite;
        }
        .orb-2 {
            width: 500px; height: 500px;
            bottom: -140px; right: -120px;
            background: radial-gradient(circle, rgba(200,169,126,0.09) 0%, transparent 70%);
            animation: orbFloat 22s ease-in-out infinite reverse;
        }
        .orb-3 {
            width: 350px; height: 350px;
            top: 35%; left: 38%;
            background: radial-gradient(circle, rgba(160,125,80,0.06) 0%, transparent 70%);
            animation: orbFloat 14s ease-in-out infinite 4s;
        }
        @keyframes orbFloat {
            0%,100% { transform: translate(0,0); }
            33%      { transform: translate(18px,-12px); }
            66%      { transform: translate(-10px,16px); }
        }

        /* Decorative rule lines */
        .rule {
            position: absolute; pointer-events: none; z-index: 1;
            background: linear-gradient(90deg, transparent, rgba(200,169,126,0.08), transparent);
        }
        .rule-h { width: 100%; height: 1px; left: 0; }
        .rule-h1 { top: 22%; }
        .rule-h2 { bottom: 22%; }

        /* Corner ornaments */
        .corner-orn {
            position: absolute; z-index: 2;
            pointer-events: none; opacity: 0.12;
        }
        .corner-orn svg { display: block; }
        .corner-tl { top: 1.75rem; left: 1.75rem; }
        .corner-br { bottom: 1.75rem; right: 1.75rem; transform: rotate(180deg); }

        /* ── BRAND BADGE (top-left) ── */
        .brand-badge {
            position: fixed; top: 1.75rem; left: 2rem;
            z-index: 20;
            display: flex; align-items: center; gap: 0.75rem;
            animation: fadeDown 0.5s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .badge-icon {
            width: 36px; height: 36px;
            background: rgba(200,169,126,0.12);
            border: 1px solid rgba(200,169,126,0.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: var(--gold);
            font-size: 0.85rem;
        }

        .badge-text { line-height: 1.2; }
        .badge-system {
            font-family: 'Fraunces', serif;
            font-size: 0.95rem; font-weight: 700;
            color: var(--gold-xl);
            letter-spacing: -0.01em;
        }
        .badge-system em {
            font-style: italic; font-weight: 300;
            color: var(--gold);
        }
        .badge-school {
            font-size: 0.62rem; font-weight: 400;
            color: rgba(200,169,126,0.45);
            letter-spacing: 0.04em;
        }

        /* ── LOTTIE (bottom-right ambient) ── */
        .lottie-wrap {
            position: fixed;
            bottom: -40px; right: -40px;
            width: 420px; height: 420px;
            z-index: 2;
            opacity: 0.18;
            pointer-events: none;
            filter: sepia(0.6) hue-rotate(-10deg) brightness(0.9);
            animation: lottieFade 1.2s ease 0.3s both;
        }
        @keyframes lottieFade {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 0.18; transform: translateY(0); }
        }
        #lottie-player { width: 100%; height: 100%; }

        /* Ambient gradebook watermark (left side) */
        .bg-book {
            position: fixed;
            left: -60px; top: 50%;
            transform: translateY(-50%);
            width: 380px; height: 380px;
            z-index: 1;
            opacity: 0.04;
            pointer-events: none;
            animation: bookDrift 20s ease-in-out infinite;
        }
        @keyframes bookDrift {
            0%,100% { transform: translateY(-50%) translateX(0); }
            50%      { transform: translateY(-52%) translateX(10px); }
        }

        /* ── CARD WRAPPER (centered) ── */
        .card-wrap {
            position: relative; z-index: 10;
            animation: cardRise 0.6s cubic-bezier(0.22,1,0.36,1) 0.08s both;
        }
        @keyframes cardRise {
            from { opacity: 0; transform: translateY(28px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        /* ── FORM CARD ── */
        .form-card {
            width: 400px;
            background: var(--card-bg);
            border-radius: 26px;
            padding: 2.75rem 2.5rem 2.25rem;
            position: relative; overflow: hidden;
            box-shadow:
                0 0 0 1px rgba(200,169,126,0.14),
                0 2px 8px  rgba(0,0,0,0.18),
                0 12px 40px rgba(0,0,0,0.30),
                0 40px 80px rgba(0,0,0,0.22),
                inset 0 1px 0 rgba(255,255,255,0.7);
        }

        /* Top gold accent bar */
        .card-top-bar {
            position: absolute; top: 0; left: 2rem; right: 2rem; height: 3px;
            background: linear-gradient(90deg, transparent, var(--gold-d), var(--gold), var(--gold-l), var(--gold), var(--gold-d), transparent);
            border-radius: 0 0 4px 4px;
        }

        /* Subtle inner watermark */
        .card-wm {
            position: absolute; bottom: -16px; right: -16px;
            width: 110px; height: 110px;
            opacity: 0.045; pointer-events: none; color: var(--charcoal);
        }

        /* ── FORM HEADER ── */
        .form-header { margin-bottom: 2rem; }

        .form-eyebrow {
            display: inline-flex; align-items: center; gap: 0.45rem;
            font-size: 0.67rem; font-weight: 600;
            letter-spacing: 0.16em; text-transform: uppercase;
            color: var(--gold-d); margin-bottom: 0.65rem;
        }
        .form-eyebrow-line {
            display: inline-block;
            width: 16px; height: 1.5px;
            background: var(--gold-d); border-radius: 2px;
        }

        .form-title {
            font-family: 'Fraunces', serif;
            font-size: 2rem; font-weight: 700;
            color: var(--text); line-height: 1.1;
            letter-spacing: -0.02em; margin-bottom: 0.35rem;
        }

        .form-sub {
            font-size: 0.82rem; color: var(--muted); line-height: 1.5;
        }

        /* ── FIELDS ── */
        .field { margin-bottom: 1.1rem; }

        .field-label {
            display: block;
            font-size: 0.7rem; font-weight: 600;
            letter-spacing: 0.09em; text-transform: uppercase;
            color: #4a4035; margin-bottom: 0.4rem;
        }

        .field-wrap { position: relative; }

        .field-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem; color: var(--text);
            background: var(--input-bg);
            border: 1.5px solid var(--border);
            border-radius: 12px; outline: none;
            transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
            -webkit-font-smoothing: antialiased;
        }
        .field-input::placeholder { color: #c2b5a5; }
        .field-input:focus {
            background: #fff;
            border-color: var(--gold-d);
            box-shadow: 0 0 0 3.5px rgba(154,122,80,0.13);
        }

        .field-ico {
            position: absolute; left: 0.85rem; top: 50%;
            transform: translateY(-50%);
            font-size: 0.75rem; color: #c0ae98;
            pointer-events: none;
            transition: color 0.2s;
        }
        .field-wrap:focus-within .field-ico { color: var(--gold-d); }

        .eye-btn {
            position: absolute; right: 0.65rem; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #c0ae98; font-size: 0.75rem;
            padding: 5px 6px; border-radius: 6px;
            transition: color 0.2s, background 0.2s;
            line-height: 1;
        }
        .eye-btn:hover { color: var(--text); background: rgba(0,0,0,0.06); }

        .field-err {
            display: flex; align-items: center; gap: 0.3rem;
            font-size: 0.72rem; font-weight: 500;
            color: var(--error); margin-top: 0.35rem;
        }

        .alert-ok {
            display: flex; align-items: center; gap: 0.5rem;
            background: var(--success-bg); color: var(--success-tx);
            border: 1px solid var(--success-bd);
            border-radius: 10px; padding: 0.6rem 0.9rem;
            font-size: 0.78rem; font-weight: 500;
            margin-bottom: 1.4rem;
        }

        /* ── REMEMBER / FORGOT ── */
        .form-row {
            display: flex; align-items: center;
            justify-content: space-between;
            margin: 1.25rem 0 1.6rem;
        }

        .check-label {
            display: inline-flex; align-items: center; gap: 0.4rem;
            font-size: 0.8rem; color: var(--muted); cursor: pointer;
            user-select: none;
        }
        .check-label input[type="checkbox"] {
            width: 14px; height: 14px;
            accent-color: var(--gold-d); cursor: pointer;
            border-radius: 4px;
        }

        .link-forgot {
            font-size: 0.8rem; font-weight: 500;
            color: var(--gold-d); text-decoration: none;
            transition: color 0.18s;
            position: relative;
        }
        .link-forgot::after {
            content: '';
            position: absolute; bottom: -1px; left: 0; right: 0;
            height: 1px; background: var(--gold-d);
            transform: scaleX(0); transform-origin: left;
            transition: transform 0.2s ease;
        }
        .link-forgot:hover { color: var(--charcoal); }
        .link-forgot:hover::after { transform: scaleX(1); }

        /* ── GOLD SUBMIT BUTTON ── */
        .btn-submit {
            width: 100%;
            padding: 0.9rem 1.5rem;
            display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            background: linear-gradient(135deg, #c8a97e 0%, #b8945c 40%, #a07840 100%);
            color: #1e1a13;
            border: none; border-radius: 13px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem; font-weight: 700;
            letter-spacing: 0.01em; cursor: pointer;
            position: relative; overflow: hidden;
            box-shadow:
                0 1px 0 rgba(255,255,255,0.25) inset,
                0 4px 16px rgba(160,120,64,0.35),
                0 1px 3px rgba(0,0,0,0.2);
            transition: transform 0.14s, box-shadow 0.2s, filter 0.2s;
        }

        /* Shimmer sweep on hover */
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 60%; height: 100%;
            background: linear-gradient(105deg, transparent 20%, rgba(255,255,255,0.28) 50%, transparent 80%);
            transition: left 0.45s ease;
            pointer-events: none;
        }
        .btn-submit:hover::before { left: 160%; }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow:
                0 1px 0 rgba(255,255,255,0.25) inset,
                0 8px 28px rgba(160,120,64,0.45),
                0 2px 6px rgba(0,0,0,0.22);
            filter: brightness(1.04);
        }
        .btn-submit:active {
            transform: translateY(0);
            filter: brightness(0.97);
        }

        /* Loading state */
        .btn-submit.loading { pointer-events: none; filter: brightness(0.9); }
        .btn-submit.loading .btn-label { opacity: 0; }
        .btn-submit.loading .btn-spinner { display: flex; }

        .btn-label { display: flex; align-items: center; gap: 0.55rem; }
        .btn-label-ico {
            width: 24px; height: 24px;
            background: rgba(30,26,19,0.15);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.7rem;
        }

        .btn-spinner {
            display: none;
            position: absolute; inset: 0;
            align-items: center; justify-content: center;
        }
        .spinner-ring {
            width: 20px; height: 20px;
            border: 2.5px solid rgba(30,26,19,0.2);
            border-top-color: #1e1a13;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── CARD FOOTER ── */
        .card-foot {
            margin-top: 1.5rem;
            padding-top: 1.2rem;
            border-top: 1px solid rgba(0,0,0,0.07);
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
            font-size: 0.68rem; color: #b0a090;
        }
        .card-foot i { font-size: 0.65rem; color: var(--gold-d); }

        /* ── RESPONSIVE ── */
        @media (max-width: 480px) {
            html, body { overflow: auto; }
            .scene { min-height: 100vh; height: auto; align-items: flex-start; padding: 5rem 1rem 3rem; }
            .form-card { width: 100%; border-radius: 20px; padding: 2.25rem 1.75rem 2rem; }
            .lottie-wrap { width: 260px; height: 260px; bottom: -20px; right: -20px; }
            .bg-book { display: none; }
        }
    </style>
</head>
<body>
<div class="scene">

    {{-- Orbs --}}
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    {{-- Rules --}}
    <div class="rule rule-h rule-h1"></div>
    <div class="rule rule-h rule-h2"></div>

    {{-- Corner ornaments --}}
    <div class="corner-orn corner-tl">
        <svg width="56" height="56" viewBox="0 0 56 56" fill="none">
            <path d="M2 54 L2 2 L54 2" stroke="rgba(200,169,126,1)" stroke-width="1.5" fill="none"/>
            <circle cx="2" cy="2" r="3.5" fill="rgba(200,169,126,0.7)"/>
        </svg>
    </div>
    <div class="corner-orn corner-br">
        <svg width="56" height="56" viewBox="0 0 56 56" fill="none">
            <path d="M2 54 L2 2 L54 2" stroke="rgba(200,169,126,1)" stroke-width="1.5" fill="none"/>
            <circle cx="2" cy="2" r="3.5" fill="rgba(200,169,126,0.7)"/>
        </svg>
    </div>

    {{-- Ambient gradebook left --}}
    <div class="bg-book">
        <svg viewBox="0 0 380 380" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="20" y="20" width="340" height="340" rx="12" fill="white" opacity="0.06"/>
            <rect x="20" y="20" width="340" height="42" rx="8" fill="white" opacity="0.1"/>
            <line x1="20"  y1="62"  x2="360" y2="62"  stroke="white" stroke-width="1"/>
            <line x1="20"  y1="88"  x2="360" y2="88"  stroke="white" stroke-width="0.6"/>
            <line x1="20"  y1="114" x2="360" y2="114" stroke="white" stroke-width="0.6"/>
            <line x1="20"  y1="140" x2="360" y2="140" stroke="white" stroke-width="0.6"/>
            <line x1="20"  y1="166" x2="360" y2="166" stroke="white" stroke-width="0.6"/>
            <line x1="20"  y1="192" x2="360" y2="192" stroke="white" stroke-width="0.6"/>
            <line x1="20"  y1="218" x2="360" y2="218" stroke="white" stroke-width="0.6"/>
            <line x1="20"  y1="244" x2="360" y2="244" stroke="white" stroke-width="0.6"/>
            <line x1="20"  y1="270" x2="360" y2="270" stroke="white" stroke-width="0.6"/>
            <line x1="20"  y1="296" x2="360" y2="296" stroke="white" stroke-width="0.6"/>
            <line x1="20"  y1="340" x2="360" y2="340" stroke="white" stroke-width="1"/>
            <line x1="120" y1="62"  x2="120" y2="360" stroke="white" stroke-width="0.6"/>
            <line x1="200" y1="62"  x2="200" y2="360" stroke="white" stroke-width="0.6"/>
            <line x1="280" y1="62"  x2="280" y2="360" stroke="white" stroke-width="0.6"/>
            <rect x="34"  y="71"  width="64" height="5" rx="2.5" fill="white" opacity="0.5"/>
            <rect x="34"  y="97"  width="54" height="5" rx="2.5" fill="white" opacity="0.4"/>
            <rect x="34"  y="123" width="58" height="5" rx="2.5" fill="white" opacity="0.4"/>
            <rect x="34"  y="149" width="50" height="5" rx="2.5" fill="white" opacity="0.4"/>
            <rect x="34"  y="175" width="56" height="5" rx="2.5" fill="white" opacity="0.4"/>
            <rect x="34"  y="201" width="52" height="5" rx="2.5" fill="white" opacity="0.4"/>
        </svg>
    </div>

    {{-- Brand badge top-left --}}
    <div class="brand-badge">
        <div class="badge-icon">
            <i class="fas fa-book-open"></i>
        </div>
        <div class="badge-text">
            <div class="badge-system">Class <em>Record</em> System</div>
            <div class="badge-school">ESSU Guiuan Campus</div>
        </div>
    </div>

    {{-- Lottie bottom-right --}}
    <div class="lottie-wrap">
        <div id="lottie-player"></div>
    </div>

    {{-- Floating card --}}
    <div class="card-wrap">
        <div class="form-card">
            <div class="card-top-bar"></div>

            {{-- Card watermark --}}
            <svg class="card-wm" viewBox="0 0 110 110" fill="none">
                <rect x="4" y="4" width="102" height="102" rx="8" stroke="currentColor" stroke-width="1.5"/>
                <line x1="4"  y1="30"  x2="106" y2="30"  stroke="currentColor" stroke-width="1"/>
                <line x1="4"  y1="56"  x2="106" y2="56"  stroke="currentColor" stroke-width="0.75"/>
                <line x1="4"  y1="82"  x2="106" y2="82"  stroke="currentColor" stroke-width="0.75"/>
                <line x1="34" y1="4"   x2="34"  y2="106" stroke="currentColor" stroke-width="0.75"/>
                <line x1="68" y1="4"   x2="68"  y2="106" stroke="currentColor" stroke-width="0.75"/>
            </svg>

            {{ $slot }}
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Inline Lottie — animated floating books/graduation caps
    const animData = {
        "v": "5.7.4", "fr": 30, "ip": 0, "op": 90, "w": 400, "h": 400,
        "nm": "Academic", "ddd": 0, "assets": [],
        "layers": [
            {
                "ddd": 0, "ind": 1, "ty": 4, "nm": "Book",
                "sr": 1, "ks": {
                    "o": {"a": 0, "k": 80},
                    "r": {"a": 1, "k": [
                        {"i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]},
                         "t": 0,  "s": [-8]},
                        {"i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]},
                         "t": 45, "s": [8]},
                        {"t": 90, "s": [-8]}
                    ]},
                    "p": {"a": 1, "k": [
                        {"i":{"x":0.5,"y":1},"o":{"x":0.5,"y":0},
                         "t": 0,  "s": [200, 220, 0]},
                        {"i":{"x":0.5,"y":1},"o":{"x":0.5,"y":0},
                         "t": 45, "s": [200, 195, 0]},
                        {"t": 90, "s": [200, 220, 0]}
                    ]},
                    "a": {"a": 0, "k": [0, 0, 0]},
                    "s": {"a": 0, "k": [100, 100, 100]}
                },
                "ao": 0, "shapes": [
                    {
                        "ty": "gr", "nm": "BookBody",
                        "it": [
                            {"ty":"rc","d":1,"s":{"a":0,"k":[90,70]},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":6},"nm":"Rect"},
                            {"ty":"fl","c":{"a":0,"k":[0.784,0.663,0.494,1]},"o":{"a":0,"k":100},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    },
                    {
                        "ty": "gr", "nm": "Spine",
                        "it": [
                            {"ty":"rc","d":1,"s":{"a":0,"k":[10,70]},"p":{"a":0,"k":[-40,0]},"r":{"a":0,"k":3},"nm":"Rect"},
                            {"ty":"fl","c":{"a":0,"k":[0.608,0.478,0.314,1]},"o":{"a":0,"k":100},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    },
                    {
                        "ty": "gr", "nm": "Line1",
                        "it": [
                            {"ty":"rc","d":1,"s":{"a":0,"k":[55,5]},"p":{"a":0,"k":[8,-12]},"r":{"a":0,"k":3},"nm":"Rect"},
                            {"ty":"fl","c":{"a":0,"k":[0.875,0.788,0.627,1]},"o":{"a":0,"k":60},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    },
                    {
                        "ty": "gr", "nm": "Line2",
                        "it": [
                            {"ty":"rc","d":1,"s":{"a":0,"k":[40,5]},"p":{"a":0,"k":[0,2]},"r":{"a":0,"k":3},"nm":"Rect"},
                            {"ty":"fl","c":{"a":0,"k":[0.875,0.788,0.627,1]},"o":{"a":0,"k":60},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    },
                    {
                        "ty": "gr", "nm": "Line3",
                        "it": [
                            {"ty":"rc","d":1,"s":{"a":0,"k":[48,5]},"p":{"a":0,"k":[4,16]},"r":{"a":0,"k":3},"nm":"Rect"},
                            {"ty":"fl","c":{"a":0,"k":[0.875,0.788,0.627,1]},"o":{"a":0,"k":60},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    }
                ],
                "ip": 0, "op": 90, "st": 0, "bm": 0
            },
            {
                "ddd": 0, "ind": 2, "ty": 4, "nm": "Book2",
                "sr": 1, "ks": {
                    "o": {"a": 0, "k": 55},
                    "r": {"a": 1, "k": [
                        {"i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]},
                         "t": 0,  "s": [12]},
                        {"i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]},
                         "t": 45, "s": [-6]},
                        {"t": 90, "s": [12]}
                    ]},
                    "p": {"a": 1, "k": [
                        {"i":{"x":0.5,"y":1},"o":{"x":0.5,"y":0},
                         "t": 0,  "s": [280, 300, 0]},
                        {"i":{"x":0.5,"y":1},"o":{"x":0.5,"y":0},
                         "t": 45, "s": [280, 270, 0]},
                        {"t": 90, "s": [280, 300, 0]}
                    ]},
                    "a": {"a": 0, "k": [0, 0, 0]},
                    "s": {"a": 0, "k": [70, 70, 100]}
                },
                "ao": 0, "shapes": [
                    {
                        "ty": "gr", "nm": "BookBody2",
                        "it": [
                            {"ty":"rc","d":1,"s":{"a":0,"k":[90,70]},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":6},"nm":"Rect"},
                            {"ty":"fl","c":{"a":0,"k":[0.608,0.478,0.314,1]},"o":{"a":0,"k":100},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    },
                    {
                        "ty": "gr", "nm": "Spine2",
                        "it": [
                            {"ty":"rc","d":1,"s":{"a":0,"k":[10,70]},"p":{"a":0,"k":[-40,0]},"r":{"a":0,"k":3},"nm":"Rect"},
                            {"ty":"fl","c":{"a":0,"k":[0.471,0.353,0.196,1]},"o":{"a":0,"k":100},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    }
                ],
                "ip": 0, "op": 90, "st": 15, "bm": 0
            },
            {
                "ddd": 0, "ind": 3, "ty": 4, "nm": "Book3",
                "sr": 1, "ks": {
                    "o": {"a": 0, "k": 40},
                    "r": {"a": 1, "k": [
                        {"i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]},
                         "t": 0,  "s": [-15]},
                        {"i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]},
                         "t": 45, "s": [5]},
                        {"t": 90, "s": [-15]}
                    ]},
                    "p": {"a": 1, "k": [
                        {"i":{"x":0.5,"y":1},"o":{"x":0.5,"y":0},
                         "t": 0,  "s": [130, 310, 0]},
                        {"i":{"x":0.5,"y":1},"o":{"x":0.5,"y":0},
                         "t": 45, "s": [130, 280, 0]},
                        {"t": 90, "s": [130, 310, 0]}
                    ]},
                    "a": {"a": 0, "k": [0, 0, 0]},
                    "s": {"a": 0, "k": [55, 55, 100]}
                },
                "ao": 0, "shapes": [
                    {
                        "ty": "gr", "nm": "BookBody3",
                        "it": [
                            {"ty":"rc","d":1,"s":{"a":0,"k":[90,70]},"p":{"a":0,"k":[0,0]},"r":{"a":0,"k":6},"nm":"Rect"},
                            {"ty":"fl","c":{"a":0,"k":[0.941,0.875,0.753,1]},"o":{"a":0,"k":100},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    },
                    {
                        "ty": "gr", "nm": "Spine3",
                        "it": [
                            {"ty":"rc","d":1,"s":{"a":0,"k":[10,70]},"p":{"a":0,"k":[-40,0]},"r":{"a":0,"k":3},"nm":"Rect"},
                            {"ty":"fl","c":{"a":0,"k":[0.784,0.663,0.494,1]},"o":{"a":0,"k":100},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    }
                ],
                "ip": 0, "op": 90, "st": 30, "bm": 0
            },
            {
                "ddd": 0, "ind": 4, "ty": 4, "nm": "Star1",
                "sr": 1, "ks": {
                    "o": {"a": 1, "k": [
                        {"t": 0,  "s": [0],  "i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]}},
                        {"t": 15, "s": [70], "i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]}},
                        {"t": 45, "s": [70], "i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]}},
                        {"t": 60, "s": [0]}
                    ]},
                    "r": {"a": 1, "k": [
                        {"t": 0,  "s": [0],   "i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]}},
                        {"t": 45, "s": [180], "i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]}},
                        {"t": 90, "s": [360]}
                    ]},
                    "p": {"a": 0, "k": [320, 140, 0]},
                    "a": {"a": 0, "k": [0, 0, 0]},
                    "s": {"a": 0, "k": [100, 100, 100]}
                },
                "ao": 0, "shapes": [
                    {
                        "ty": "gr", "nm": "StarShape",
                        "it": [
                            {"ty":"sr","sy":2,"d":1,
                             "pt":{"a":0,"k":4},
                             "p":{"a":0,"k":[0,0]},
                             "r":{"a":0,"k":0},
                             "or":{"a":0,"k":14},
                             "os":{"a":0,"k":5},
                             "nm":"Star"},
                            {"ty":"fl","c":{"a":0,"k":[0.941,0.875,0.753,1]},"o":{"a":0,"k":100},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    }
                ],
                "ip": 0, "op": 90, "st": 0, "bm": 0
            },
            {
                "ddd": 0, "ind": 5, "ty": 4, "nm": "Star2",
                "sr": 1, "ks": {
                    "o": {"a": 1, "k": [
                        {"t": 20, "s": [0],  "i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]}},
                        {"t": 35, "s": [50], "i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]}},
                        {"t": 65, "s": [50], "i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]}},
                        {"t": 80, "s": [0]}
                    ]},
                    "r": {"a": 1, "k": [
                        {"t": 0,  "s": [45],  "i":{"x":[0.5],"y":[1]},"o":{"x":[0.5],"y":[0]}},
                        {"t": 90, "s": [225]}
                    ]},
                    "p": {"a": 0, "k": [80, 180, 0]},
                    "a": {"a": 0, "k": [0, 0, 0]},
                    "s": {"a": 0, "k": [70, 70, 100]}
                },
                "ao": 0, "shapes": [
                    {
                        "ty": "gr", "nm": "StarShape2",
                        "it": [
                            {"ty":"sr","sy":2,"d":1,
                             "pt":{"a":0,"k":4},
                             "p":{"a":0,"k":[0,0]},
                             "r":{"a":0,"k":0},
                             "or":{"a":0,"k":10},
                             "os":{"a":0,"k":4},
                             "nm":"Star"},
                            {"ty":"fl","c":{"a":0,"k":[0.784,0.663,0.494,1]},"o":{"a":0,"k":100},"nm":"Fill"},
                            {"ty":"tr","p":{"a":0,"k":[0,0]},"a":{"a":0,"k":[0,0]},"s":{"a":0,"k":[100,100]},"r":{"a":0,"k":0},"o":{"a":0,"k":100}}
                        ]
                    }
                ],
                "ip": 0, "op": 90, "st": 20, "bm": 0
            }
        ]
    };

    lottie.loadAnimation({
        container: document.getElementById('lottie-player'),
        renderer: 'svg',
        loop: true,
        autoplay: true,
        animationData: animData
    });
});
</script>
</body>
</html>
