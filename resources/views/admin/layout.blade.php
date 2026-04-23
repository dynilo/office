<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} | {{ config('app.name') }}</title>
    <style>
        :root {
            --ink: #14213d;
            --ink-soft: #33415c;
            --paper: #f7f4ea;
            --panel: #fffdf7;
            --line: #d7cfbf;
            --accent: #c46a2d;
            --accent-soft: #f1d7c4;
            --ok: #2f6b4f;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(196, 106, 45, 0.12), transparent 24rem),
                linear-gradient(180deg, #f8f5ed 0%, #efe7d8 100%);
        }

        .shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 18rem 1fr;
        }

        .sidebar {
            padding: 2rem 1.5rem;
            border-right: 1px solid rgba(20, 33, 61, 0.1);
            background: rgba(255, 253, 247, 0.86);
            backdrop-filter: blur(8px);
        }

        .brand {
            margin-bottom: 2rem;
        }

        .eyebrow {
            margin: 0 0 0.35rem;
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent);
        }

        .brand h1 {
            margin: 0;
            font-size: 1.85rem;
            line-height: 1;
            font-weight: 600;
        }

        .brand p {
            margin: 0.75rem 0 0;
            color: var(--ink-soft);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .nav {
            display: grid;
            gap: 0.65rem;
        }

        .nav a {
            display: block;
            padding: 0.85rem 1rem;
            border: 1px solid transparent;
            border-radius: 1rem;
            color: var(--ink);
            text-decoration: none;
            background: transparent;
            transition: transform 140ms ease, border-color 140ms ease, background 140ms ease;
        }

        .nav a:hover {
            transform: translateX(3px);
            border-color: rgba(196, 106, 45, 0.25);
            background: rgba(255, 255, 255, 0.6);
        }

        .nav a.is-active {
            background: var(--accent-soft);
            border-color: rgba(196, 106, 45, 0.35);
        }

        .nav strong {
            display: block;
            font-size: 1rem;
        }

        .nav span {
            display: block;
            margin-top: 0.25rem;
            color: var(--ink-soft);
            font-size: 0.85rem;
        }

        .content {
            padding: 2rem;
        }

        .hero {
            display: grid;
            gap: 1rem;
            padding: 2rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.5rem;
            background: linear-gradient(140deg, rgba(255,255,255,0.88), rgba(248,240,228,0.9));
            box-shadow: 0 20px 60px rgba(20, 33, 61, 0.08);
        }

        .hero h2 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.25rem);
            line-height: 0.95;
            font-weight: 600;
        }

        .hero p {
            margin: 0;
            max-width: 42rem;
            color: var(--ink-soft);
            font-size: 1rem;
            line-height: 1.65;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .card {
            padding: 1.25rem;
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 1.25rem;
            background: rgba(255, 253, 247, 0.75);
        }

        .card h3 {
            margin: 0 0 0.5rem;
            font-size: 1.1rem;
        }

        .card p, .card code {
            margin: 0;
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .card code {
            display: inline-block;
            margin-top: 0.35rem;
            padding: 0.2rem 0.4rem;
            border-radius: 0.45rem;
            background: rgba(20, 33, 61, 0.05);
            color: var(--ink);
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: rgba(47, 107, 79, 0.12);
            color: var(--ok);
            font-size: 0.85rem;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { border-right: 0; border-bottom: 1px solid rgba(20, 33, 61, 0.1); }
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <p class="eyebrow">AI Office OS</p>
                <h1>Admin Shell</h1>
                <p>Operational frontend foundation for agents, tasks, executions, and audit visibility.</p>
            </div>

            <nav class="nav" aria-label="Admin navigation">
                @foreach ($navigation as $item)
                    <a href="{{ $item['href'] }}" class="{{ $page === $item['key'] ? 'is-active' : '' }}">
                        <strong>{{ $item['label'] }}</strong>
                        <span>Ready for API-backed admin screens.</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <main class="content">
            @yield('content')
        </main>
    </div>

    <script>
        window.OfficeAdmin = @json($bootstrap);
    </script>
</body>
</html>
