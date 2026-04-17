<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') | {{ config('app.name', 'UniVision') }}</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <style>
        :root {
            color-scheme: light dark;
            --bg: #f3f8fa;
            --bg-deep: #dbf0ef;
            --panel: rgba(255, 255, 255, 0.82);
            --panel-border: rgba(19, 78, 74, 0.14);
            --text: #0f172a;
            --muted: #486270;
            --primary: #0f766e;
            --primary-strong: #115e59;
            --accent: #d8f3f0;
            --danger: #991b1b;
            --shadow: 0 28px 70px rgba(15, 23, 42, 0.12);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #07141d;
                --bg-deep: #0d2b33;
                --panel: rgba(8, 20, 29, 0.78);
                --panel-border: rgba(94, 234, 212, 0.14);
                --text: #e6f6f5;
                --muted: #9db7bc;
                --primary: #54d3c2;
                --primary-strong: #8df4e7;
                --accent: rgba(84, 211, 194, 0.08);
                --danger: #fca5a5;
                --shadow: 0 32px 90px rgba(0, 0, 0, 0.42);
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(20, 184, 166, 0.16), transparent 28%),
                radial-gradient(circle at bottom right, rgba(14, 116, 144, 0.18), transparent 30%),
                linear-gradient(135deg, var(--bg), var(--bg-deep));
            display: grid;
            place-items: center;
            overflow: hidden;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
        }

        body::before {
            background-image:
                linear-gradient(rgba(15, 118, 110, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15, 118, 110, 0.06) 1px, transparent 1px);
            background-size: 36px 36px;
            mask-image: radial-gradient(circle at center, black 38%, transparent 82%);
        }

        body::after {
            inset: auto;
            width: 34rem;
            height: 34rem;
            right: -8rem;
            top: -8rem;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(84, 211, 194, 0.24), transparent 68%);
            filter: blur(10px);
        }

        .shell {
            width: min(100%, 1080px);
            padding: 2rem;
        }

        .panel {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(260px, 0.85fr);
            gap: 2rem;
            padding: 2rem;
            border: 1px solid var(--panel-border);
            border-radius: 28px;
            background: var(--panel);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow);
        }

        .panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(84, 211, 194, 0.08), transparent 34%, rgba(14, 116, 144, 0.08));
            pointer-events: none;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.55rem 0.8rem;
            border-radius: 999px;
            background: var(--accent);
            color: var(--primary-strong);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .badge-dot {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            background: currentColor;
            box-shadow: 0 0 0 0 rgba(84, 211, 194, 0.6);
            animation: pulse 2.2s infinite;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            margin-bottom: 1.4rem;
        }

        .brand img {
            width: 2.75rem;
            height: 2.75rem;
            object-fit: contain;
        }

        .brand-copy {
            display: grid;
            gap: 0.15rem;
        }

        .brand-title {
            font-size: 1rem;
            font-weight: 700;
        }

        .brand-subtitle {
            font-size: 0.82rem;
            color: var(--muted);
        }

        .code {
            margin: 1rem 0 0;
            font-size: clamp(4.25rem, 11vw, 8rem);
            font-weight: 800;
            line-height: 0.9;
            letter-spacing: -0.08em;
            color: var(--primary-strong);
            text-shadow: 0 10px 30px rgba(15, 118, 110, 0.18);
        }

        h1 {
            margin: 1rem 0 0.75rem;
            font-size: clamp(1.8rem, 3vw, 3rem);
            line-height: 1.02;
            letter-spacing: -0.04em;
        }

        .message {
            margin: 0;
            max-width: 38rem;
            font-size: 1rem;
            line-height: 1.7;
            color: var(--muted);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
            margin-top: 2rem;
        }

        .button {
            appearance: none;
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 0.95rem 1.25rem;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: transform 180ms ease, background-color 180ms ease, border-color 180ms ease, color 180ms ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            background: var(--primary);
            color: white;
        }

        .button-secondary {
            border-color: var(--panel-border);
            background: transparent;
            color: var(--text);
        }

        .sidebar {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 1rem;
            align-content: start;
        }

        .card {
            padding: 1.2rem 1.15rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.36);
            border: 1px solid var(--panel-border);
        }

        @media (prefers-color-scheme: dark) {
            .card {
                background: rgba(4, 14, 22, 0.42);
            }
        }

        .card-label {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 0.55rem;
        }

        .card-value {
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.45;
        }

        .card-value strong {
            color: var(--primary-strong);
        }

        .hint {
            color: var(--muted);
            font-size: 0.94rem;
            line-height: 1.6;
        }

        .footer {
            margin-top: 1.1rem;
            font-size: 0.85rem;
            color: var(--muted);
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(84, 211, 194, 0.55); }
            70% { box-shadow: 0 0 0 12px rgba(84, 211, 194, 0); }
            100% { box-shadow: 0 0 0 0 rgba(84, 211, 194, 0); }
        }

        @media (max-width: 900px) {
            .panel {
                grid-template-columns: 1fr;
            }

            .shell {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="panel">
            <div style="position:relative; z-index:1;">
                <div class="brand">
                    <img src="/images/univision_logo_transparent.png" alt="UniVision">
                    <div class="brand-copy">
                        <div class="brand-title">{{ config('app.name', 'UniVision') }}</div>
                        <div class="brand-subtitle">Namangan davlat universiteti monitoring tizimi</div>
                    </div>
                </div>

                <div class="badge">
                    <span class="badge-dot"></span>
                    Tizim holati
                </div>

                <div class="code">@yield('code')</div>
                <h1>@yield('title')</h1>
                <p class="message">@yield('message')</p>

                <div class="actions">
                    <a class="button button-primary" href="{{ url('/') }}">Bosh sahifaga qaytish</a>
                    <button class="button button-secondary" type="button" onclick="window.history.back()">Oldingi sahifa</button>
                </div>

                <div class="footer">
                    Agar muammo davom etsa, tizim administratori bilan bog'laning.
                </div>
            </div>

            <aside class="sidebar">
                <div class="card">
                    <div class="card-label">Holat tafsiloti</div>
                    <div class="card-value"><strong>@yield('code')</strong> - @yield('short')</div>
                </div>
                <div class="card">
                    <div class="card-label">Tavsiya</div>
                    <div class="hint">@yield('hint')</div>
                </div>
                <div class="card">
                    <div class="card-label">Manzil</div>
                    <div class="hint">{{ request()->path() }}</div>
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
