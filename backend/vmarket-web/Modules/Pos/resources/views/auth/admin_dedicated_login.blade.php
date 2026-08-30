<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Terminal — Victorious MARKET POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #090d16;
            --card-bg: #0f172a;
            --border: #1e293b;
            --gold: #f59e0b;
            --primary: #6366f1;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --danger: #ef4444;
            --success: #10b981;
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
                radial-gradient(at 0% 0%, rgba(245, 158, 11, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.12) 0px, transparent 50%);
        }

        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
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
            background: linear-gradient(90deg, #f59e0b, #eab308, #6366f1);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #b45309, #f59e0b);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 20px rgba(245, 158, 11, 0.35);
        }

        .brand-header h1 {
            font-size: 1.45rem;
            font-weight: 800;
            color: #f8fafc;
            letter-spacing: -0.02em;
        }

        .admin-pill {
            display: inline-block;
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
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
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
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
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
            background: rgba(15, 23, 42, 0.9);
        }

        .btn-submit {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #090d16;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
            margin-top: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(245, 158, 11, 0.5);
        }

        .btn-submit:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-header">
            <div class="brand-icon">👑</div>
            <h1>Super Admin Terminal</h1>
            <div><span class="admin-pill">Victorious Master Command</span></div>
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

        <form method="POST" action="{{ route('admin.dedicated.login.post', $slug) }}">
            @csrf

            <div class="form-group">
                <label for="email">Master Administrator Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="admin@admin.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Master Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">
                ⚡ Unlock Command Terminal
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: 0.75rem; color: #64748b;">
            Powered by <strong style="color: #f59e0b;">Victorious MARKET</strong> your Trusted Online Market
        </div>
    </div>

</body>
</html>
