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

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-deep:   #1c1814;
            --bg-mid:    #2a2318;
            --bg-light:  #342c1f;
            --sand:      #c8a97e;
            --sand-l:    #e0c99a;
            --sand-xl:   #f0dfc0;
            --sand-d:    #9a7a50;
            --charcoal:  #3d3530;
            --card-bg:   rgba(250, 246, 238, 0.97);
            --white:     #ffffff;
            --text:      #1e1a16;
            --muted:     #7a6f64;
            --border:    #e8dfd0;
            --input-bg:  #f5f0e8;
            --error:     #c0392b;
            --success-bg:#f0fdf4;
            --success-tx:#166534;
            --success-bd:#bbf7d0;
        }

        html, body {
            height: 100%;
            font-family: 'DM Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow: hidden;
        }

        /* ── FULL BLEED BACKGROUND ───────────────────── */
        .scene {
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse 70% 60% at 15% 20%,  rgba(200,169,126,0.12) 0%, transparent 55%),
                radial-gradient(ellipse 55% 50% at 85% 75%,  rgba(200,169,126,0.08) 0%, transparent 50%),
                radial-gradient(ellipse 80% 70% at 50% 50%,  rgba(42,35,24,0.4) 0%, transparent 70%),
                linear-gradient(145deg, var(--bg-deep) 0%, var(--bg-mid) 40%, var(--bg-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Grain texture overlay */
        .scene::before {
            content: '';
            position: absolute; inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            opacity: 0.35;
            pointer-events: none;
        }

        /* Ambient light orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(80px);
        }
        .orb-1 {
            width: 500px; height: 500px;
            top: -120px; left: -100px;
            background: radial-gradient(circle, rgba(200,169,126,0.14) 0%, transparent 70%);
        }
        .orb-2 {
            width: 420px; height: 420px;
            bottom: -100px; right: -80px;
            background: radial-gradient(circle, rgba(200,169,126,0.10) 0%, transparent 70%);
        }
        .orb-3 {
            width: 300px; height: 300px;
            top: 40%; left: 55%;
            background: radial-gradient(circle, rgba(160,130,90,0.06) 0%, transparent 70%);
        }

        /* ── AMBIENT GRADEBOOK SVG (background) ─────── */
        .bg-gradebook {
            position: absolute;
            right: -60px;
            top: 50%;
            transform: translateY(-50%);
            width: 560px;
            height: 480px;
            opacity: 0.055;
            pointer-events: none;
            animation: slowDrift 12s ease-in-out infinite;
        }

        .bg-gradebook-left {
            position: absolute;
            left: -80px;
            top: 50%;
            transform: translateY(-50%) scaleX(-1);
            width: 360px;
            height: 320px;
            opacity: 0.03;
            pointer-events: none;
            animation: slowDrift 16s ease-in-out infinite reverse;
        }

        @keyframes slowDrift {
            0%, 100% { transform: translateY(-50%) translateX(0); }
            50%       { transform: translateY(-52%) translateX(8px); }
        }

        /* Thin decorative lines */
        .line-deco {
            position: absolute;
            pointer-events: none;
            opacity: 0.06;
        }
        .line-h {
            width: 100%; height: 1px;
            background: linear-gradient(90deg, transparent, var(--sand), transparent);
        }
        .line-h-1 { top: 28%; }
        .line-h-2 { top: 72%; }
        .line-v {
            height: 100%; width: 1px;
            background: linear-gradient(180deg, transparent, var(--sand), transparent);
            top: 0;
        }
        .line-v-1 { left: 20%; }
        .line-v-2 { right: 20%; }

        /* Corner ornaments */
        .corner {
            position: absolute;
            width: 60px; height: 60px;
            opacity: 0.08;
            pointer-events: none;
        }
        .corner svg { width: 100%; height: 100%; }
        .corner-tl { top: 1.5rem; left: 1.5rem; }
        .corner-br { bottom: 1.5rem; right: 1.5rem; transform: rotate(180deg); }

        /* ── LAYOUT WRAPPER ──────────────────────────── */
        .layout {
            position: relative; z-index: 10;
            display: flex;
            align-items: center;
            gap: 4rem;
            padding: 2rem;
            width: 100%;
            max-width: 960px;
        }

        /* ── BRAND SIDE ──────────────────────────────── */
        .brand-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            animation: fadeLeft 0.6s cubic-bezier(0.22,1,0.36,1) both;
        }

        @keyframes fadeLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .brand-pill {
            display: inline-flex; align-items: center; gap: 0.5rem;
            background: rgba(200,169,126,0.12);
            border: 1px solid rgba(200,169,126,0.2);
            border-radius: 100px;
            padding: 0.35rem 0.9rem;
            margin-bottom: 1.75rem;
        }
        .brand-pill-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--sand);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%,100% { opacity: 1; transform: scale(1); }
            50%      { opacity: 0.5; transform: scale(0.8); }
        }
        .brand-pill span {
            font-size: 0.7rem; font-weight: 500;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--sand-l);
        }

        .brand-title {
            font-family: 'Fraunces', serif;
            font-size: 3.4rem;
            font-weight: 700;
            line-height: 1.05;
            color: var(--sand-xl);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .brand-title em {
            font-style: italic;
            font-weight: 300;
            color: var(--sand);
        }

        .brand-desc {
            font-size: 0.9rem;
            line-height: 1.75;
            color: rgba(255,255,255,0.35);
            max-width: 300px;
            margin-bottom: 2.25rem;
        }

        /* Feature pills */
        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }
        .feature-item {
            display: flex; align-items: center; gap: 0.65rem;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
        }
        .feature-item i {
            width: 22px; height: 22px;
            background: rgba(200,169,126,0.1);
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.65rem;
            color: var(--sand);
            flex-shrink: 0;
        }

        /* ── FORM CARD ───────────────────────────────── */
        .form-card {
            width: 380px;
            flex-shrink: 0;
            background: var(--card-bg);
            border-radius: 24px;
            padding: 2.5rem 2.25rem 2rem;
            box-shadow:
                0 0 0 1px rgba(200,169,126,0.12),
                0 4px 16px rgba(0,0,0,0.25),
                0 20px 60px rgba(0,0,0,0.35),
                0 50px 100px rgba(0,0,0,0.2);
            animation: riseIn 0.55s cubic-bezier(0.22,1,0.36,1) 0.1s both;
            position: relative;
            overflow: hidden;
        }

        @keyframes riseIn {
            from { opacity: 0; transform: translateY(24px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0)    scale(1); }
        }

        /* Card top accent */
        .card-accent {
            position: absolute;
            top: 0; left: 1.5rem; right: 1.5rem;
            height: 2.5px;
            background: linear-gradient(90deg, transparent, var(--sand), var(--sand-l), var(--sand), transparent);
            border-radius: 0 0 3px 3px;
        }

        /* Card inner watermark */
        .card-watermark {
            position: absolute;
            bottom: -20px; right: -20px;
            width: 120px; height: 120px;
            opacity: 0.04;
            pointer-events: none;
        }

        /* Header */
        .form-header { margin-bottom: 1.85rem; }

        .form-eyebrow {
            font-size: 0.68rem; font-weight: 600;
            letter-spacing: 0.15em; text-transform: uppercase;
            color: var(--sand-d);
            margin-bottom: 0.6rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .form-eyebrow::before {
            content: '';
            display: inline-block;
            width: 14px; height: 2px;
            background: var(--sand-d); border-radius: 2px;
        }

        .form-title {
            font-family: 'Fraunces', serif;
            font-size: 1.85rem; font-weight: 700;
            color: var(--text);
            line-height: 1.15;
            margin-bottom: 0.4rem;
            letter-spacing: -0.01em;
        }

        .form-sub {
            font-size: 0.825rem;
            color: var(--muted);
        }

        /* Fields */
        .field { margin-bottom: 1.15rem; }
        .field-label {
            display: block;
            font-size: 0.72rem; font-weight: 600;
            letter-spacing: 0.07em; text-transform: uppercase;
            color: var(--charcoal);
            margin-bottom: 0.42rem;
        }
        .field-wrap { position: relative; }

        .field-ico {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%);
            color: #b8a898; font-size: 0.78rem;
            pointer-events: none; transition: color 0.2s;
        }

        .field-input {
            width: 100%;
            padding: 0.72rem 1rem 0.72rem 2.4rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            color: var(--text);
            background: var(--input-bg);
            border: 1.5px solid var(--border);
            border-radius: 11px;
            outline: none;
            transition: all 0.2s;
        }
        .field-input::placeholder { color: #c0b5a8; }
        .field-input:focus {
            background: var(--white);
            border-color: var(--sand-d);
            box-shadow: 0 0 0 3px rgba(154,122,80,0.12);
        }
        .field-wrap:focus-within .field-ico { color: var(--sand-d); }

        .eye-btn {
            position: absolute; right: 10px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #b8a898; font-size: 0.78rem;
            padding: 4px 5px; border-radius: 5px;
            transition: color 0.2s, background 0.2s;
        }
        .eye-btn:hover { color: var(--charcoal); background: rgba(0,0,0,0.05); }

        .field-err {
            display: flex; align-items: center; gap: 0.3rem;
            font-size: 0.73rem; font-weight: 500;
            color: var(--error); margin-top: 0.38rem;
        }

        .alert-ok {
            display: flex; align-items: center; gap: 0.45rem;
            background: var(--success-bg); color: var(--success-tx);
            border: 1px solid var(--success-bd);
            border-radius: 9px; padding: 0.55rem 0.85rem;
            font-size: 0.78rem; font-weight: 500;
            margin-bottom: 1.35rem;
        }

        /* Row */
        .form-row {
            display: flex; align-items: center;
            justify-content: space-between;
            margin: 1.3rem 0 1.55rem;
        }
        .check-label {
            display: inline-flex; align-items: center; gap: 0.42rem;
            font-size: 0.8rem; color: var(--muted); cursor: pointer;
        }
        .check-label input[type="checkbox"] {
            width: 13px; height: 13px;
            accent-color: var(--sand-d); cursor: pointer;
        }
        .link-forgot {
            font-size: 0.8rem; font-weight: 500;
            color: var(--sand-d); text-decoration: none;
            transition: color 0.2s;
        }
        .link-forgot:hover { color: var(--charcoal); }

        /* Submit */
        .btn-submit {
            width: 100%;
            padding: 0.85rem 1.5rem;
            display: flex; align-items: center; justify-content: center; gap: 0.6rem;
            background: linear-gradient(135deg, var(--charcoal) 0%, #2a2318 100%);
            color: var(--sand-xl);
            border: none; border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem; font-weight: 600;
            letter-spacing: 0.02em; cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(28,24,20,0.35);
            position: relative; overflow: hidden;
        }
        .btn-submit::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(200,169,126,0.12) 0%, transparent 60%);
            pointer-events: none;
        }
        .btn-submit:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 8px 24px rgba(28,24,20,0.45);
        }
        .btn-submit:active { transform: translateY(0); }

        .btn-ico {
            width: 25px; height: 25px;
            background: rgba(200,169,126,0.15);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem;
        }

        /* Card foot */
        .card-foot {
            margin-top: 1.4rem;
            padding-top: 1.2rem;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 0.7rem; color: #b8a898;
            display: flex; align-items: center; justify-content: center; gap: 0.38rem;
        }

        /* ── RESPONSIVE ─────────────────────────────── */
        @media (max-width: 820px) {
            html, body { overflow: auto; }
            .scene { min-height: 100vh; height: auto; align-items: flex-start; padding: 2rem 1rem 3rem; }
            .layout { flex-direction: column; align-items: center; gap: 2rem; max-width: 420px; }
            .brand-side { align-items: center; text-align: center; }
            .brand-desc { text-align: center; }
            .feature-list { align-items: center; }
            .form-card { width: 100%; }
            .bg-gradebook, .bg-gradebook-left { display: none; }
            .brand-title { font-size: 2.4rem; }
        }
    </style>
</head>
<body>
<div class="scene">

    {{-- Ambient orbs --}}
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    {{-- Decorative lines --}}
    <div class="line-deco line-h line-h-1"></div>
    <div class="line-deco line-h line-h-2"></div>
    <div class="line-deco line-v line-v-1"></div>
    <div class="line-deco line-v line-v-2"></div>

    {{-- Corner ornaments --}}
    <div class="corner corner-tl">
        <svg viewBox="0 0 60 60" fill="none">
            <path d="M0 60 L0 0 L60 0" stroke="rgba(200,169,126,1)" stroke-width="1.5" fill="none"/>
            <circle cx="0" cy="0" r="4" fill="rgba(200,169,126,0.6)"/>
        </svg>
    </div>
    <div class="corner corner-br">
        <svg viewBox="0 0 60 60" fill="none">
            <path d="M0 60 L0 0 L60 0" stroke="rgba(200,169,126,1)" stroke-width="1.5" fill="none"/>
            <circle cx="0" cy="0" r="4" fill="rgba(200,169,126,0.6)"/>
        </svg>
    </div>

    {{-- Ambient gradebook SVG right --}}
    <div class="bg-gradebook">
        <svg viewBox="0 0 560 480" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="40" y="20" width="480" height="440" rx="16" fill="white" opacity="0.05"/>
            <rect x="40" y="20" width="480" height="50" rx="8" fill="white" opacity="0.08"/>
            <rect x="40" y="70" width="480" height="22" fill="white" opacity="0.04"/>
            <line x1="40"  y1="92"  x2="520" y2="92"  stroke="white" stroke-width="1"/>
            <line x1="40"  y1="118" x2="520" y2="118" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="144" x2="520" y2="144" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="170" x2="520" y2="170" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="196" x2="520" y2="196" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="222" x2="520" y2="222" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="248" x2="520" y2="248" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="274" x2="520" y2="274" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="300" x2="520" y2="300" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="326" x2="520" y2="326" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="352" x2="520" y2="352" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="378" x2="520" y2="378" stroke="white" stroke-width="0.75"/>
            <line x1="40"  y1="420" x2="520" y2="420" stroke="white" stroke-width="1"/>
            <line x1="160" y1="70"  x2="160" y2="460" stroke="white" stroke-width="0.75"/>
            <line x1="240" y1="70"  x2="240" y2="460" stroke="white" stroke-width="0.75"/>
            <line x1="320" y1="70"  x2="320" y2="460" stroke="white" stroke-width="0.75"/>
            <line x1="400" y1="70"  x2="400" y2="460" stroke="white" stroke-width="0.75"/>
            <line x1="460" y1="70"  x2="460" y2="460" stroke="white" stroke-width="1"/>
            <rect x="60"  y="97"  width="80" height="6" rx="3" fill="white" opacity="0.5"/>
            <rect x="60"  y="123" width="70" height="6" rx="3" fill="white" opacity="0.4"/>
            <rect x="60"  y="149" width="75" height="6" rx="3" fill="white" opacity="0.4"/>
            <rect x="60"  y="175" width="65" height="6" rx="3" fill="white" opacity="0.4"/>
            <rect x="60"  y="201" width="72" height="6" rx="3" fill="white" opacity="0.4"/>
            <rect x="60"  y="227" width="68" height="6" rx="3" fill="white" opacity="0.4"/>
            <rect x="60"  y="253" width="74" height="6" rx="3" fill="white" opacity="0.4"/>
            <rect x="60"  y="279" width="66" height="6" rx="3" fill="white" opacity="0.4"/>
            <rect x="60"  y="305" width="71" height="6" rx="3" fill="white" opacity="0.4"/>
            <rect x="60"  y="331" width="69" height="6" rx="3" fill="white" opacity="0.4"/>
            <rect x="465" y="97"  width="42" height="10" rx="5" fill="white" opacity="0.15"/>
            <rect x="465" y="123" width="42" height="10" rx="5" fill="white" opacity="0.12"/>
            <rect x="465" y="149" width="42" height="10" rx="5" fill="white" opacity="0.12"/>
            <rect x="465" y="175" width="42" height="10" rx="5" fill="white" opacity="0.12"/>
            <rect x="465" y="201" width="42" height="10" rx="5" fill="white" opacity="0.12"/>
        </svg>
    </div>

    {{-- Layout --}}
    <div class="layout">

        {{-- Brand side --}}
        <div class="brand-side">
            <div class="brand-pill">
                <div class="brand-pill-dot"></div>
                <span>Faculty Portal</span>
            </div>

            <h1 class="brand-title">Class<br><em>Record</em><br>System</h1>

            <p class="brand-desc">
                A streamlined grade management platform for faculty — from score entry to final grade locking.
            </p>

            <div class="feature-list">
                <div class="feature-item">
                    <i class="fas fa-table-cells"></i>
                    DepEd-style spreadsheet class record
                </div>
                <div class="feature-item">
                    <i class="fas fa-calculator"></i>
                    Automated GWA computation
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar-check"></i>
                    Attendance tracking per section
                </div>
                <div class="feature-item">
                    <i class="fas fa-lock"></i>
                    Final grade locking &amp; audit trail
                </div>
            </div>
        </div>

        {{-- Form card --}}
        <div class="form-card">
            <div class="card-accent"></div>

            {{-- Card watermark --}}
            <div class="card-watermark">
                <svg viewBox="0 0 120 120" fill="none">
                    <rect x="5" y="5" width="110" height="110" rx="8" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <line x1="5"  y1="30"  x2="115" y2="30"  stroke="currentColor" stroke-width="1"/>
                    <line x1="5"  y1="55"  x2="115" y2="55"  stroke="currentColor" stroke-width="0.75"/>
                    <line x1="5"  y1="80"  x2="115" y2="80"  stroke="currentColor" stroke-width="0.75"/>
                    <line x1="35" y1="5"   x2="35"  y2="115" stroke="currentColor" stroke-width="0.75"/>
                    <line x1="70" y1="5"   x2="70"  y2="115" stroke="currentColor" stroke-width="0.75"/>
                </svg>
            </div>

            {{ $slot }}
        </div>

    </div>
</div>
</body>
</html>
