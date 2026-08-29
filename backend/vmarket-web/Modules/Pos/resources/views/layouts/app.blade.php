<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS') — Victorious MARKET</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* [AI] POS Module Layout — Brand: #5E17EB (Primary Purple), #FFD700 (Gold), #FFFFFF (White) */
        :root {
            --pos-primary: #5E17EB;
            --pos-gold: #FFD700;
            --pos-dark: #1a1a2e;
            --pos-sidebar-width: 240px;
        }
        body { font-family: 'Inter', sans-serif; background: #f4f6fb; }
        .pos-sidebar {
            width: var(--pos-sidebar-width);
            min-height: 100vh;
            background: linear-gradient(160deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            position: fixed; left: 0; top: 0; z-index: 1000;
            display: flex; flex-direction: column;
        }
        .pos-sidebar .brand {
            padding: 1.5rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .pos-sidebar .brand-logo {
            font-size: 1.1rem; font-weight: 700; color: #fff;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .pos-sidebar .brand-logo .badge-free {
            font-size: 0.6rem; background: var(--pos-gold); color: #1a1a2e;
            padding: 2px 6px; border-radius: 4px; font-weight: 700;
        }
        .pos-sidebar nav { flex: 1; padding: 1rem 0; }
        .pos-sidebar .nav-link {
            color: rgba(255,255,255,0.65); padding: 0.65rem 1.2rem;
            border-radius: 0; font-size: 0.875rem; display: flex; align-items: center; gap: 0.75rem;
            transition: all 0.2s; border-left: 3px solid transparent;
        }
        .pos-sidebar .nav-link:hover, .pos-sidebar .nav-link.active {
            color: #fff; background: rgba(94,23,235,0.25);
            border-left-color: var(--pos-primary);
        }
        .pos-sidebar .nav-link i { width: 18px; text-align: center; }
        .pos-main { margin-left: var(--pos-sidebar-width); min-height: 100vh; }
        .pos-topbar {
            background: #fff; padding: 0.75rem 1.5rem;
            box-shadow: 0 1px 0 rgba(0,0,0,0.06);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 999;
        }
        .pos-topbar .seller-badge {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.85rem; color: #555;
        }
        .pos-topbar .marketplace-badge {
            font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 20px;
        }
        .pos-topbar .marketplace-badge.pos-only {
            background: rgba(94,23,235,0.1); color: var(--pos-primary);
        }
        .pos-topbar .marketplace-badge.approved {
            background: rgba(25,135,84,0.1); color: #198754;
        }
        .pos-content { padding: 1.5rem; }
        .stat-card {
            background: #fff; border-radius: 12px; padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .stat-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .pos-sidebar { transform: translateX(-100%); }
            .pos-main { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- [AI] POS Sidebar Navigation --}}
<div class="pos-sidebar">
    <div class="brand">
        <div class="brand-logo">
            <i class="fas fa-store" style="color: var(--pos-gold);"></i>
            <span>Vmarket <strong>POS</strong></span>
            @php $seller = auth('seller')->user(); @endphp
            {{-- [AI] FREE badge shown when seller is pending/not yet approved on marketplace --}}
            @if($seller && $seller->status !== 'approved')
                <span class="badge-free">FREE</span>
            @endif
        </div>
        @if($seller)
            <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-top: 4px; truncate: ellipsis;">
                {{ $seller->f_name }} {{ $seller->l_name }}
            </div>
        @endif
    </div>
    <nav>
        <a href="{{ route('pos.dashboard') }}" class="nav-link {{ request()->routeIs('pos.dashboard') ? 'active' : '' }}">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}">
            <i class="fas fa-cash-register"></i> POS Terminal
        </a>
        <a href="{{ route('pos.products.index') }}" class="nav-link {{ request()->routeIs('pos.products.*') ? 'active' : '' }}">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="{{ route('pos.stock.index') }}" class="nav-link {{ request()->routeIs('pos.stock.*') ? 'active' : '' }}">
            <i class="fas fa-warehouse"></i> Stock
        </a>
        <a href="{{ route('pos.transactions.index') }}" class="nav-link {{ request()->routeIs('pos.transactions.*') ? 'active' : '' }}">
            <i class="fas fa-receipt"></i> Transactions
        </a>
        <a href="{{ route('pos.debts.index') }}" class="nav-link {{ request()->routeIs('pos.debts.*') ? 'active' : '' }}">
            <i class="fas fa-hand-holding-usd"></i> Debt Ledger
        </a>
        <a href="{{ route('pos.reports.index') }}" class="nav-link {{ request()->routeIs('pos.reports.*') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i> Reports
        </a>
        <a href="{{ route('pos.warehouses.index') }}" class="nav-link {{ request()->routeIs('pos.warehouses.*') ? 'active' : '' }}">
            <i class="fas fa-code-branch"></i> Branches
        </a>
        <div style="padding: 0.75rem 1.2rem; margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08);">
            {{-- [AI] Marketplace return button: visible ONLY to sellers with status='approved' --}}
            @if($seller && $seller->status === 'approved')
                <a href="{{ url('/vendor') }}" class="nav-link" style="border-left-color: var(--pos-gold);">
                    <i class="fas fa-arrow-left" style="color: var(--pos-gold);"></i>
                    <span style="color: var(--pos-gold);">🔙 Back to Merchant Panel</span>
                </a>
            @else
                {{-- [AI] Unverified / pending sellers see locked marketplace pill --}}
                <div class="nav-link" style="opacity: 0.5; cursor: not-allowed; font-size: 0.75rem;">
                    <i class="fas fa-lock"></i> Marketplace Pending KYC
                </div>
            @endif
        </div>
    </nav>
</div>

{{-- [AI] Main Content Area --}}
<div class="pos-main">
    <div class="pos-topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-light d-md-none" onclick="document.querySelector('.pos-sidebar').style.transform='translateX(0)'">
                <i class="fas fa-bars"></i>
            </button>
            <span class="text-muted" style="font-size: 0.85rem;">@yield('breadcrumb', 'POS')</span>
        </div>
        <div class="seller-badge">
            @if($seller)
                {{-- [AI] Seller status badge: uses sellers.status + sellers.pos_status --}}
                @if($seller->status === 'approved')
                    <span class="marketplace-badge approved"><i class="fas fa-check-circle"></i> Verified Merchant</span>
                @elseif($seller->status === 'pending')
                    <span class="marketplace-badge" style="background: rgba(255,193,7,0.1); color: #856404;">
                        <i class="fas fa-clock"></i> Pending Approval
                    </span>
                @elseif($seller->status === 'denied')
                    <span class="marketplace-badge" style="background: rgba(220,53,69,0.1); color: #dc3545;">
                        <i class="fas fa-times-circle"></i> Denied
                    </span>
                @endif
                <span class="text-muted">|</span>
            @endif
            <form action="{{ route('seller.auth.logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size: 0.75rem;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger m-3">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="pos-content">
        @yield('content')
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
