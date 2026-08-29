<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Logistics Command') — Victorious MARKET Delivery</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        /* [AI] Delivery Module Layout — Brand: #5E17EB (Primary Purple), #FFD700 (Gold), #FFFFFF (White) */
        :root {
            --delivery-primary: #5E17EB;
            --delivery-gold: #FFD700;
            --delivery-dark: #1a1a2e;
            --delivery-sidebar-width: 250px;
        }
        body { font-family: 'Inter', sans-serif; background: #f4f6fb; color: #2b3445; }
        .delivery-sidebar {
            width: var(--delivery-sidebar-width);
            min-height: 100vh;
            background: linear-gradient(160deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            position: fixed; left: 0; top: 0; z-index: 1000;
            display: flex; flex-direction: column;
        }
        .delivery-sidebar .brand {
            padding: 1.5rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .delivery-sidebar .brand-logo {
            font-size: 1.1rem; font-weight: 700; color: #fff;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .delivery-sidebar .brand-logo .badge-fleet {
            font-size: 0.65rem; background: var(--delivery-gold); color: #1a1a2e;
            padding: 2px 8px; border-radius: 20px; font-weight: 800; text-transform: uppercase;
        }
        .delivery-sidebar nav { flex: 1; padding: 1rem 0; }
        .delivery-sidebar .nav-link {
            color: rgba(255,255,255,0.7); padding: 0.75rem 1.25rem;
            border-radius: 0; font-size: 0.875rem; display: flex; align-items: center; gap: 0.85rem;
            transition: all 0.2s; border-left: 3px solid transparent; font-weight: 500;
        }
        .delivery-sidebar .nav-link:hover, .delivery-sidebar .nav-link.active {
            color: #fff; background: rgba(94,23,235,0.3);
            border-left-color: var(--delivery-primary);
        }
        .delivery-sidebar .nav-link i { width: 20px; text-align: center; font-size: 1rem; }
        .delivery-main { margin-left: var(--delivery-sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }
        .delivery-topbar {
            background: #fff; padding: 0.85rem 1.75rem;
            box-shadow: 0 1px 0 rgba(0,0,0,0.06);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 999;
        }
        .delivery-topbar .hub-badge {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.85rem; font-weight: 600; color: #333;
        }
        .delivery-content { padding: 1.75rem; flex: 1; }
        .glass-card {
            background: #fff; border-radius: 14px; padding: 1.5rem;
            border: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 2px 12px rgba(0,0,0,0.04);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .glass-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.07); }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .btn-brand-primary {
            background: var(--delivery-primary); color: #fff; border: none;
            font-weight: 600; padding: 0.5rem 1.25rem; border-radius: 8px;
            transition: all 0.2s;
        }
        .btn-brand-primary:hover { background: #4a0ec4; color: #fff; }
        .badge-status {
            font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 20px;
        }
        .table-custom th {
            font-weight: 600; font-size: 0.8rem; text-transform: uppercase;
            letter-spacing: 0.5px; color: #777; background: #fafbfc;
            border-bottom: 2px solid #f0f0f4; padding: 0.85rem 1rem;
        }
        .table-custom td { padding: 0.95rem 1rem; vertical-align: middle; font-size: 0.875rem; }
    </style>
    @stack('css')
</head>
<body>

<!-- Sidebar -->
<aside class="delivery-sidebar">
    <div class="brand">
        <div class="brand-logo">
            <i class="fa-solid fa-truck-fast text-warning"></i>
            <span>Vmarket Logistics</span>
            <span class="badge-fleet">Fleet Hub</span>
        </div>
    </div>
    <nav>
        <a href="{{ route('delivery.dashboard') }}" class="nav-link {{ Request::is('delivery') || Request::is('delivery/dashboard*') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Live Command</span>
        </a>
        <a href="{{ route('delivery.shipments.index') }}" class="nav-link {{ Request::is('delivery/shipments*') ? 'active' : '' }}">
            <i class="fa-solid fa-boxes-packing"></i>
            <span>Shipments & Batches</span>
        </a>
        <a href="{{ route('delivery.hubs.index') }}" class="nav-link {{ Request::is('delivery/hubs*') ? 'active' : '' }}">
            <i class="fa-solid fa-warehouse"></i>
            <span>Logistics Hubs</span>
        </a>
        <a href="{{ route('delivery.routes.index') }}" class="nav-link {{ Request::is('delivery/routes*') ? 'active' : '' }}">
            <i class="fa-solid fa-route"></i>
            <span>Corridor Routes & Pricing</span>
        </a>
        <a href="{{ route('delivery.fleet.index') }}" class="nav-link {{ Request::is('delivery/fleet*') ? 'active' : '' }}">
            <i class="fa-solid fa-motorcycle"></i>
            <span>Fleet & 3PL Partners</span>
        </a>
        <a href="{{ route('delivery.finance.index') }}" class="nav-link {{ Request::is('delivery/finance*') ? 'active' : '' }}">
            <i class="fa-solid fa-money-bill-transfer"></i>
            <span>Cash-in-Hand & Audit</span>
        </a>
    </nav>
    <div class="p-3 border-top border-secondary border-opacity-25">
        <a href="{{ route('admin.dashboard.index') }}" class="btn btn-outline-light btn-sm w-100 py-2 fs-12">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Vmarket Admin
        </a>
    </div>
</aside>

<!-- Main Area -->
<div class="delivery-main">
    <!-- Topbar -->
    <header class="delivery-topbar">
        <div class="d-flex align-items-center gap-3">
            <span class="hub-badge">
                <i class="fa-solid fa-location-dot text-danger"></i>
                <span>Regional Logistics Command: <strong>Akwa Ibom & Nigeria</strong></span>
            </span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 border border-success border-opacity-25 rounded-pill">
                <i class="fa-solid fa-circle-check me-1"></i> Live Corridor Dispatch Active
            </span>
            <div class="dropdown">
                <button class="btn btn-light btn-sm dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-user-shield me-1 text-primary"></i> {{ auth('admin')->user()->name ?? 'Logistics Director' }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="{{ route('admin.dashboard.index') }}"><i class="fa-solid fa-house me-2"></i> Admin Panel</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="{{ route('admin.auth.logout') }}"><i class="fa-solid fa-right-from-bracket me-2"></i> Sign Out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="delivery-content">
        @yield('content')
    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
{!! Toastr::message() !!}
@stack('js')
</body>
</html>
