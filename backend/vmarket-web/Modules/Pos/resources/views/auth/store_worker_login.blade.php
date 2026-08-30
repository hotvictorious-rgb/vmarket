<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shop->name ?? 'Store Cashier' }} — POS Terminal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: #111827;
            --border: #1e293b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --text: #f9fafb;
            --text-muted: #94a3b8;
            --danger: #ef4444;
            --success: #22c55e;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #38bdf8, #22c55e);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #1e40af, #2563eb);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
            overflow: hidden;
        }

        .brand-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .brand-header h1 {
            font-size: 1.45rem;
            font-weight: 800;
            color: #f9fafb;
            letter-spacing: -0.02em;
        }

        .store-tag {
            display: inline-block;
            background: rgba(37, 99, 235, 0.15);
            color: #93c5fd;
            border: 1px solid rgba(37, 99, 235, 0.3);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            margin-top: 0.35rem;
        }

        .store-address {
            font-size: 0.825rem;
            color: var(--text-muted);
            margin-top: 0.4rem;
        }

        .alert {
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.825rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
            background: rgba(15, 23, 42, 0.9);
        }

        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.45);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .terminal-footer {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px dashed rgba(255, 255, 255, 0.1);
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-header">
            <div class="brand-icon">
                @if(!empty($shop->image) && $shop->image !== 'def.png')
                    <img src="{{ $shop->image }}" alt="{{ $shop->name }}">
                @else
                    🏪
                @endif
            </div>
            <h1>{{ $shop->name ?? 'Store Cashier Terminal' }}</h1>
            <div><span class="store-tag">Dedicated Staff Register</span></div>
            @if(!empty($shop->address))
                <p class="store-address">📍 {{ $shop->address }}</p>
            @endif
        </div>

        @if(session('error'))
            <div class="alert alert-danger">
                <span>❌</span> {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <span>❌</span> {{ $errors->first() }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                <span>✓</span> {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('store.worker.login.post', $slug) }}">
            @csrf

            <div class="form-group">
                <label for="email">Staff Work Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="cashier@{{ $slug }}.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Staff Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">
                🛒 Sign In to Store Register
            </button>
        </form>

        <div class="terminal-footer">
            <span style="font-weight: 600; color: #94a3b8;">Powered by <strong style="color: #60a5fa;">Victorious MARKET</strong> your Trusted Online Market</span>
        </div>
    </div>

</body>
</html>
