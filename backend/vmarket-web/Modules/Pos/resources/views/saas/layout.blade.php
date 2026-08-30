<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SaaS Master Admin') – VMarket POS Cloud</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #090d16;
            --sidebar-bg: #0f172a;
            --card-bg: #1e293b;
            --border: #334155;
            --text: #f8fafc;
            --text-muted: #94a3b8;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar Navigation */
        .saas-sidebar {
            width: 270px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 50;
        }

        .saas-header {
            padding: 1.5rem 1.5rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .saas-logo {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        .saas-menu {
            flex: 1;
            padding: 1.25rem 0.75rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .menu-cat {
            font-size: 0.68rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.75rem 0.75rem 0.25rem;
        }

        .saas-nav {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .saas-nav:hover {
            background: rgba(51, 65, 85, 0.6);
            color: var(--text);
        }

        .saas-nav.active {
            background: rgba(99, 102, 241, 0.2);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.4);
        }

        .saas-content {
            margin-left: 270px;
            flex: 1;
            padding: 2rem 2.5rem 4rem;
            max-width: 1400px;
        }

        /* Metrics & Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #a855f7);
        }

        .kpi-card h4 {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kpi-card .val {
            font-size: 1.8rem;
            font-weight: 900;
            color: #fff;
            margin-top: 0.35rem;
        }

        .panel-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.75rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            font-size: 0.88rem;
            font-weight: 700;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-warning { background: var(--warning); color: #000; }
        .btn-danger { background: var(--danger); color: #fff; }

        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 0.85rem 1rem; font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid var(--border); background: rgba(15, 23, 42, 0.4); }
        td { padding: 1rem; border-bottom: 1px solid var(--border); font-size: 0.88rem; vertical-align: middle; }
        tr:hover td { background: rgba(51, 65, 85, 0.3); }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-pro { background: rgba(99, 102, 241, 0.2); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.4); }
        .badge-free { background: rgba(100, 116, 139, 0.2); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.3); }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .alert-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    </style>
    @stack('styles')
</head>
<body>

    <!-- SaaS Master Sidebar -->
    <aside class="saas-sidebar">
        <div class="saas-header">
            <div class="saas-logo">⚡</div>
            <div>
                <h1 style="font-size: 1.15rem; font-weight: 900; letter-spacing: -0.02em; color: #fff;">VMarket Cloud</h1>
                <p style="font-size: 0.72rem; color: #818cf8; font-weight: 700;">Master SaaS Platform</p>
            </div>
        </div>

        <nav class="saas-menu">
            <div class="menu-cat">Executive Radar</div>
            <a href="{{ route('saas.dashboard') }}" class="saas-nav {{ request()->routeIs('saas.dashboard') ? 'active' : '' }}">
                <span>📊</span> <span>Master Overview</span>
            </a>
            <a href="{{ route('saas.activity') }}" class="saas-nav {{ request()->routeIs('saas.activity') ? 'active' : '' }}">
                <span>📡</span> <span>Live Platform Pulse</span>
            </a>

            <div class="menu-cat">Tenants & Businesses</div>
            <a href="{{ route('saas.tenants') }}" class="saas-nav {{ request()->routeIs('saas.tenants') ? 'active' : '' }}">
                <span>🏢</span> <span>Companies & Shops</span>
            </a>
            <a href="{{ route('saas.invoices') }}" class="saas-nav {{ request()->routeIs('saas.invoices') ? 'active' : '' }}">
                <span>💳</span> <span>Paystack Billing Ledger</span>
            </a>

            <div class="menu-cat">Platform Configuration</div>
            <a href="{{ route('saas.settings') }}" class="saas-nav {{ request()->routeIs('saas.settings') ? 'active' : '' }}">
                <span>⚙️</span> <span>SaaS & Pricing Config</span>
            </a>

            <div class="menu-cat">POS Store Navigation</div>
            <a href="{{ route('dashboard') }}" class="saas-nav" style="color: #38bdf8;">
                <span>🏪</span> <span>Go to Store POS →</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="saas-content">
        @if(session('success'))
            <div class="alert alert-success">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">❌ {{ session('error') }}</div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
