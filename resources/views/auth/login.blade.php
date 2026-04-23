<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | {{ config('app.name') }}</title>
    <style>
        :root {
            --ink: #14213d;
            --ink-soft: #33415c;
            --paper: #f7f4ea;
            --panel: #fffdf7;
            --accent: #c46a2d;
            --accent-soft: #f1d7c4;
            --danger: #8b2d22;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(196, 106, 45, 0.16), transparent 26rem),
                linear-gradient(180deg, #f8f5ed 0%, #efe7d8 100%);
        }

        .login-panel {
            width: min(100%, 28rem);
            padding: 2rem;
            border: 1px solid rgba(20, 33, 61, 0.1);
            border-radius: 1.5rem;
            background: rgba(255, 253, 247, 0.9);
            box-shadow: 0 24px 70px rgba(20, 33, 61, 0.1);
        }

        .eyebrow {
            margin: 0 0 0.4rem;
            color: var(--accent);
            font-size: 0.72rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: 2rem;
            line-height: 1;
        }

        p {
            color: var(--ink-soft);
            line-height: 1.6;
        }

        form {
            display: grid;
            gap: 1rem;
            margin-top: 1.25rem;
        }

        label {
            display: grid;
            gap: 0.35rem;
            color: var(--ink-soft);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem 0.9rem;
            border: 1px solid rgba(20, 33, 61, 0.16);
            border-radius: 0.9rem;
            color: var(--ink);
            background: rgba(255, 255, 255, 0.78);
            font: inherit;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--ink-soft);
            font-size: 0.95rem;
            letter-spacing: 0;
            text-transform: none;
        }

        button {
            min-height: 2.8rem;
            padding: 0.8rem 1rem;
            border: 1px solid rgba(196, 106, 45, 0.36);
            border-radius: 999px;
            color: #fffdf7;
            background: var(--accent);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .errors {
            margin: 1rem 0 0;
            padding: 0.75rem 0.9rem;
            border-radius: 0.9rem;
            color: var(--danger);
            background: rgba(139, 45, 34, 0.12);
        }

        .errors ul {
            margin: 0;
            padding-left: 1.1rem;
        }
    </style>
</head>
<body>
    <main class="login-panel">
        <p class="eyebrow">AI Office OS</p>
        <h1>Admin login</h1>
        <p>Sign in to access operational visibility and administrative workflows.</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <label>
                Email
                <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            </label>

            <label>
                Password
                <input name="password" type="password" autocomplete="current-password" required>
            </label>

            <label class="remember">
                <input name="remember" type="checkbox" value="1">
                Remember this session
            </label>

            <button type="submit">Log in</button>
        </form>
    </main>
</body>
</html>
