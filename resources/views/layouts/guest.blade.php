<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Class Record System') }} — ESSU Guiuan</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.brand')

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html { min-height: 100%; background: #062306; }
        body { margin: 0; min-height: 100vh; }
        body { font-family: 'DM Sans', system-ui, sans-serif; color: #1f2937; -webkit-font-smoothing: antialiased;
               background: radial-gradient(ellipse at 50% 0%, #0f4f0f 0%, #0b3d0b 45%, #062306 100%) fixed; }

        .auth { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 40px 20px; }
        .auth-card { width: 100%; max-width: 460px; overflow: hidden; background: #f8f4ea; border-radius: 20px; box-shadow: 0 24px 60px rgba(0,0,0,.35), 0 2px 6px rgba(0,0,0,.15); }
        .auth-card.wide { max-width: 620px; }

        /* Letterhead */
        .letterhead { padding: 22px 32px 14px; text-align: center; background: #ffffff; border-bottom: 2px solid #d4af37; }
        .letterhead img { display: block; width: 100%; max-width: 300px; height: auto; margin: 0 auto; }
        .letterhead-sub { margin-top: 8px; font-size: 11px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: #4b5563; }
        .auth-body { padding: 30px 36px 26px; }

        .form-header { margin-bottom: 24px; }
        .form-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-size: 11.5px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; color: #8a6d12; }
        .form-eyebrow-line { width: 18px; height: 2px; background: #d4af37; }
        .form-title { margin: 8px 0 0; font-family: 'Fraunces', serif; font-size: 32px; font-weight: 700; line-height: 1.1; color: #0b3d0b; }
        .form-sub { margin: 6px 0 0; font-size: 14px; line-height: 1.55; color: #57534e; }

        .form-section { margin: 0 0 8px; font-size: 11.5px; font-weight: 600; letter-spacing: .1em; text-transform: uppercase; color: #8a6d12; }
        .form-grid { display: grid; gap: 0 14px; }
        .form-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .form-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .form-divider { height: 1px; margin: 6px 0 18px; background: #e7dfcc; border: 0; }

        .field { margin-bottom: 16px; }
        .field-label { display: block; margin-bottom: 7px; font-size: 11.5px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #44403c; }
        .field-label .opt { font-weight: 500; letter-spacing: 0; text-transform: none; color: #a8a29e; }
        .field-wrap { position: relative; }
        .field-ico { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #a8861a; pointer-events: none; }
        .field-input { width: 100%; height: 48px; padding: 0 14px 0 42px; font: inherit; font-size: 14.5px; color: #1c1917; background: #ffffff; border: 1px solid #ddd3bd; border-radius: 12px; outline: none; transition: border-color .15s, box-shadow .15s; }
        .field-input::placeholder { color: #a8a29e; }
        .field-input:focus { border-color: #a8861a; box-shadow: 0 0 0 3px rgba(212,175,55,.25); }
        .field-input.has-eye { padding-right: 46px; }
        .eye-btn { position: absolute; right: 7px; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; color: #a8a29e; background: none; border: 0; border-radius: 8px; cursor: pointer; }
        .eye-btn:hover { color: #0b3d0b; background: #f3ecd8; }
        .field-err { display: flex; align-items: center; gap: 6px; margin-top: 6px; font-size: 12.5px; color: #b91c1c; }
        .field.has-error .field-input { border-color: #ef4444; }

        .form-row { display: flex; align-items: center; justify-content: space-between; margin: 4px 0 22px; }
        .check-label { display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; color: #44403c; cursor: pointer; }
        .check-label input { width: 16px; height: 16px; accent-color: #0a5b05; }
        .link-forgot { font-size: 13.5px; font-weight: 600; color: #8a6d12; text-decoration: none; }
        .link-forgot:hover { color: #0b3d0b; text-decoration: underline; }

        .btn-submit { position: relative; display: flex; align-items: center; justify-content: center; width: 100%; height: 50px; font: inherit; font-size: 15px; font-weight: 600; color: #0b3d0b; background: linear-gradient(135deg, #d4af37, #a8861a); border: 0; border-radius: 12px; cursor: pointer; box-shadow: 0 8px 18px rgba(168,134,26,.35); transition: filter .15s, box-shadow .15s; }
        .btn-submit:hover { filter: brightness(1.05); box-shadow: 0 10px 22px rgba(168,134,26,.45); }
        .btn-submit:focus-visible { outline: 3px solid rgba(10,91,5,.45); outline-offset: 2px; }
        .btn-label { display: inline-flex; align-items: center; gap: 10px; }
        .btn-spinner { display: none; }
        .spinner-ring { width: 18px; height: 18px; border: 2px solid rgba(11,61,11,.3); border-top-color: #0b3d0b; border-radius: 50%; animation: spin .7s linear infinite; }
        .btn-submit.loading { pointer-events: none; opacity: .85; }
        .btn-submit.loading .btn-label { display: none; }
        .btn-submit.loading .btn-spinner { display: inline-flex; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .form-alt { margin-top: 18px; font-size: 13.5px; text-align: center; color: #57534e; }
        .alert-ok { display: flex; align-items: center; gap: 8px; margin-bottom: 18px; padding: 12px 14px; font-size: 13.5px; color: #166534; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; }
        .card-foot { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 24px; padding-top: 16px; font-size: 12px; color: #78716c; border-top: 1px solid #e7dfcc; }
        .card-foot i { color: #a8861a; }
        .page-foot { margin-top: 18px; font-size: 12px; text-align: center; color: rgba(251,243,208,.6); }

        /* Breeze pages (forgot / reset / verify / confirm password) */
        .auth-body label { color: #44403c; }
        .auth-body input[type=email], .auth-body input[type=password], .auth-body input[type=text] { border-radius: 10px; }

        @media (max-width: 560px) {
            .auth { padding: 20px 12px; }
            .letterhead { padding: 18px 20px 12px; }
            .auth-body { padding: 24px 20px 22px; }
            .form-grid.cols-2, .form-grid.cols-3 { grid-template-columns: 1fr; }
            .form-title { font-size: 28px; }
        }
    </style>
</head>
<body>
<main class="auth">
    <div>
        <div class="auth-card {{ $attributes->has('wide') ? 'wide' : '' }}">
            <div class="letterhead">
                <img src="{{ asset('images/rog/essu-banner.png') }}" alt="Eastern Samar State University — Excellence, Accountability, Service">
                <div class="letterhead-sub">Guiuan Campus · Faculty Class Record System</div>
            </div>
            <div class="auth-body">
                {{ $slot }}
            </div>
        </div>
        <p class="page-foot">&copy; {{ date('Y') }} Eastern Samar State University – Guiuan Campus</p>
    </div>
</main>

@stack('scripts')
</body>
</html>
