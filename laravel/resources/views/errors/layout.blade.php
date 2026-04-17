<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16.png">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Instrument Sans", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0f172a;
            background: linear-gradient(180deg, #f7faf9 0%, #edf4f2 100%);
            display: grid;
            place-items: center;
        }

        .shell {
            width: min(100%, 420px);
            padding: 24px;
        }

        .panel {
            padding: 40px 32px;
            border: 1px solid #d9e5e1;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .code {
            font-size: clamp(3rem, 10vw, 5rem);
            font-weight: 700;
            line-height: 0.9;
            letter-spacing: -0.08em;
            color: #0f766e;
        }

        h1 {
            margin: 16px 0 8px;
            font-size: 1.75rem;
            line-height: 1.1;
        }

        .message {
            margin: 0;
            color: #52606d;
            font-size: 0.98rem;
            line-height: 1.6;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 24px;
        }

        .button {
            appearance: none;
            border-radius: 999px;
            padding: 12px 18px;
            border: 1px solid transparent;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 160ms ease, border-color 160ms ease, transform 160ms ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            background: #0f766e;
            color: white;
        }

        .button-secondary {
            background: #fff;
            color: #0f172a;
            border-color: #d9e5e1;
        }

        @media (max-width: 480px) {
            .shell {
                padding: 16px;
            }

            .panel {
                padding: 32px 20px;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="panel">
            <div class="code">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p class="message">@yield('message')</p>

            <div class="actions">
                <a class="button button-primary" href="{{ url('/') }}">Bosh sahifa</a>
                <button class="button button-secondary" type="button" onclick="window.history.back()">Orqaga</button>
            </div>
        </section>
    </main>
</body>
</html>
