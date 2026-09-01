<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $systemSettings->businessName ?? config('app.name', 'SmartPOS')) – Inventory & POS</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #5E17EB;
            --primary-dark: #4A0EC7;
            --secondary: #FFD700;
            --accent-gold: #FFD700;
            --success: #16a34a;
            --warning: #FFD700;
            --danger: #dc2626;
            --bg: #0b0f19;
            --sidebar-bg: #111827;
            --card-bg: #1f2937;
            --border: #374151;
            --text: #ffffff;
            --text-muted: #9ca3af;
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
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 50;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
        }

        .brand-text h1 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #f9fafb;
        }

        .brand-text p {
            font-size: 0.75rem;
            color: #9ca3af;
        }

        .sidebar-menu {
            flex: 1;
            padding: 1rem 0.75rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .menu-category {
            font-size: 0.7rem;
            font-weight: 800;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.75rem 0.75rem 0.25rem;
        }

        .nav-item {
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

        .nav-item:hover {
            background: rgba(55, 65, 81, 0.6);
            color: var(--text);
        }

        .nav-item.active {
            background: rgba(37, 99, 235, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(37, 99, 235, 0.4);
        }

        .nav-item.pos-btn {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.3);
            margin: 0.5rem 0;
        }
        .nav-item.pos-btn:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .nav-item.auditor-btn {
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, 0.2);
        }
        .nav-item.auditor-btn.active {
            background: rgba(220, 38, 38, 0.2);
            border-color: rgba(220, 38, 38, 0.5);
        }

        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Main Content Wrapper */
        .main-wrapper {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: rgba(17, 24, 39, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
            gap: 1rem;
        }

        /* Enterprise Topbar Elements */
        .topbar-hub-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.95rem;
            font-size: 0.82rem;
            font-weight: 800;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .topbar-hub-admin {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.25), rgba(124, 58, 237, 0.35));
            border: 1px solid rgba(167, 139, 250, 0.5);
            color: #c4b5fd;
            box-shadow: 0 2px 10px rgba(124, 58, 237, 0.2);
        }
        .topbar-hub-admin:hover {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.45);
            transform: translateY(-1px);
        }
        .topbar-hub-vendor {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.25), rgba(13, 148, 136, 0.35));
            border: 1px solid rgba(52, 211, 153, 0.5);
            color: #6ee7b7;
            box-shadow: 0 2px 10px rgba(13, 148, 136, 0.2);
        }
        .topbar-hub-vendor:hover {
            background: linear-gradient(135deg, #059669, #0d9488);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.45);
            transform: translateY(-1px);
        }
        .live-clock-pill {
            background: rgba(31, 41, 55, 0.7);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.4rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: #93c5fd;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            white-space: nowrap;
        }
        .live-clock-pill #headerTime {
            color: #4ade80;
            font-family: monospace;
            font-size: 0.88rem;
        }
        .clock-divider {
            color: #4b5563;
        }
        .topbar-tool-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 700;
            background: rgba(31, 41, 55, 0.85);
            border: 1px solid #4b5563;
            border-radius: 12px;
            color: #e5e7eb;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .topbar-tool-btn:hover {
            background: rgba(55, 65, 81, 0.95);
            border-color: #6b7280;
            color: #ffffff;
        }
        .topbar-vdivider {
            width: 1px;
            height: 26px;
            background: var(--border);
        }
        .user-profile-group {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            background: rgba(31, 41, 55, 0.65);
            border: 1px solid var(--border);
            padding: 0.35rem 0.45rem 0.35rem 0.75rem;
            border-radius: 14px;
            backdrop-filter: blur(8px);
        }
        .user-avatar-chip {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
            flex-shrink: 0;
        }
        .user-meta {
            display: flex;
            flex-direction: column;
            text-align: left;
            padding-right: 0.35rem;
        }
        .user-name {
            font-size: 0.82rem;
            font-weight: 700;
            color: #f9fafb;
            line-height: 1.2;
            max-width: 140px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-role-badge {
            font-size: 0.65rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .topbar-logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.85rem;
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.35);
            border-radius: 10px;
            color: #fca5a5;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .topbar-logout-btn:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #ef4444;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
            transform: translateY(-1px);
        }

        .container {
            width: 100%;
            max-width: 1360px;
            margin: 0 auto;
            padding: 2rem;
            flex: 1;
        }

        /* Online Badge */
        .online-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.65rem;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 99px;
            font-size: 0.75rem;
            color: #4ade80;
            font-weight: 700;
        }

        .online-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.2); }
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success { background: rgba(22, 163, 74, 0.2); border: 1px solid rgba(22, 163, 74, 0.4); color: #86efac; }
        .alert-warning { background: rgba(217, 119, 6, 0.2); border: 1px solid rgba(217, 119, 6, 0.4); color: #fde047; }
        .alert-danger  { background: rgba(220, 38, 38, 0.2); border: 1px solid rgba(220, 38, 38, 0.4); color: #fca5a5; }

        /* General UI components */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            font-family: inherit;
        }

        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
        .btn-success { background: var(--success); color: #fff; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3); }
        .btn-warning { background: var(--warning); color: #fff; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3); }
        .btn-danger  { background: var(--danger);  color: #fff; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3); }
        .btn-secondary { background: var(--card-bg); color: var(--text-muted); border: 1px solid var(--border); }
        .btn-lg { padding: 1rem 1.75rem; font-size: 1.05rem; border-radius: 14px; }
        .btn-block { width: 100%; }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.65rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .badge-success { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
        .badge-warning { background: rgba(217,119,6,0.15); color: #fde047; border: 1px solid rgba(217,119,6,0.3); }
        .badge-danger  { background: rgba(220,38,38,0.15); color: #f87171; border: 1px solid rgba(220,38,38,0.3); }
        .badge-info    { background: rgba(59,130,246,0.15); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); }

        /* Modal */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 100;
        }
        .modal {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            width: 100%;
            max-width: 580px;
            padding: 2rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
            max-height: 90vh;
            overflow-y: auto;
        }

        /* Form elements */
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em; }
        input, select, textarea {
            width: 100%; padding: 0.85rem 1rem;
            background: rgba(11, 15, 25, 0.7);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text);
            font-size: 1rem;
            font-family: inherit;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
        }

        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; }
        .grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; }

        @media (max-width: 1024px) {
            .sidebar { width: 75px; }
            .sidebar-header .brand-text, .menu-category, .nav-item span, .sidebar-footer { display: none; }
            .sidebar-header { justify-content: center; padding: 1rem; }
            .nav-item { justify-content: center; padding: 0.75rem; }
            .main-wrapper { margin-left: 75px; }
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        @php
            $displayStoreName = null;
            $shopId = session('shop_id');
            $sellerId = session('seller_id');
            if ($shopId) {
                try {
                    $shopRecord = \Illuminate\Support\Facades\DB::connection('vmarket')->table('shops')->where('id', $shopId)->first();
                    if ($shopRecord) $displayStoreName = $shopRecord->name;
                } catch (\Throwable $e) {}
            } elseif ($sellerId) {
                try {
                    $shopRecord = \Illuminate\Support\Facades\DB::connection('vmarket')->table('shops')->where('seller_id', $sellerId)->first();
                    if ($shopRecord) $displayStoreName = $shopRecord->name;
                } catch (\Throwable $e) {}
            }
            if (!$displayStoreName && auth()->check()) {
                $userEmail = auth()->user()->email ?? '';
                try {
                    $sellerRecord = \Illuminate\Support\Facades\DB::connection('vmarket')->table('sellers')->whereRaw('LOWER(email) = ?', [strtolower($userEmail)])->first();
                    if ($sellerRecord) {
                        $shopRecord = \Illuminate\Support\Facades\DB::connection('vmarket')->table('shops')->where('seller_id', $sellerRecord->id)->first();
                        if ($shopRecord) $displayStoreName = $shopRecord->name;
                    }
                } catch (\Throwable $e) {}
            }
            if (!$displayStoreName) {
                $displayStoreName = $systemSettings->businessName ?? config('app.name', 'Victorious POS');
            }
        @endphp

        <div class="sidebar-header">
            <div class="brand-icon">🏪</div>
            <div class="brand-text">
                <h1 title="{{ $displayStoreName }}">{{ \Illuminate\Support\Str::limit($displayStoreName, 20) }}</h1>
                <p>{{ session('user_role') === 'staff' ? 'Cashier Register' : 'Store POS & Stock' }}</p>
            </div>
        </div>

        @php
            $isSuperAdmin = (session('is_super_admin') === true) || Auth::guard('admin')->check();
            $userRole = session('user_role');
            $sellerId = session('seller_id');
            $sellerStatus = session('seller_status', 'pending');
            $isStoreOwner = $isSuperAdmin || in_array($userRole, ['verified_merchant', 'unverified_merchant', 'admin']) || (Auth::guard('seller')->check() && !session('is_vendor_employee'));
            $currentRole = $isStoreOwner ? 'admin' : ($userRole ?? 'staff');
            $isSuperAdminEmployee = ($userRole === 'super_admin_employee');
            $isVerifiedMerchant = ($userRole === 'verified_merchant') || (!$isSuperAdmin && $sellerId && $sellerStatus === 'approved' && !in_array($userRole, ['verified_merchant_employee', 'unverified_merchant_employee', 'staff']));
            $isUnverifiedMerchant = ($userRole === 'unverified_merchant') || (!$isSuperAdmin && $sellerId && $sellerStatus !== 'approved' && !in_array($userRole, ['verified_merchant_employee', 'unverified_merchant_employee', 'staff']));
            $isVerifiedMerchantEmployee = ($userRole === 'verified_merchant_employee') || ($userRole === 'staff' && $sellerStatus === 'approved');
            $isUnverifiedMerchantEmployee = ($userRole === 'unverified_merchant_employee') || ($userRole === 'staff' && $sellerStatus !== 'approved');
        @endphp

        <nav class="sidebar-menu">
            <div class="menu-category">Main Operations</div>
            <a href="{{ route('pos.dashboard') }}" class="nav-item {{ request()->routeIs('pos.dashboard*') ? 'active' : '' }}">
                <span>🏠</span> <span>{{ $currentRole === 'cashier' ? 'My Shift Summary' : ($currentRole === 'storekeeper' ? 'Stock Hub' : 'Dashboard') }}</span>
            </a>

            @if(in_array($currentRole, ['admin', 'manager', 'sales_stock', 'cashier']))
                <!-- Big POS Button -->
                <a href="{{ route('pos.index') }}" class="nav-item pos-btn {{ request()->routeIs('pos.index') ? 'active' : '' }}">
                    <span>💰</span> <span>Sell Goods (POS)</span>
                </a>
            @endif

            <div class="menu-category">Inventory & Stock</div>
            <a href="{{ route('pos.products.index') }}" class="nav-item {{ request()->routeIs('pos.products.*') ? 'active' : '' }}">
                <span>🛍️</span> <span>Products Catalog</span>
            </a>

            @if(in_array($currentRole, ['admin', 'manager', 'sales_stock', 'storekeeper', 'viewer']))
                <a href="{{ route('pos.stock.index') }}" class="nav-item {{ request()->routeIs('pos.stock.index') ? 'active' : '' }}">
                    <span>📦</span> <span>Stock In / Out</span>
                </a>
                <a href="{{ route('pos.stock.transfers') }}" class="nav-item {{ request()->routeIs('pos.stock.transfers') ? 'active' : '' }}">
                    <span>🚚</span> <span>Shop Transfers</span>
                </a>
                <a href="{{ route('pos.stock.unsupplied') }}" class="nav-item {{ request()->routeIs('pos.stock.unsupplied') ? 'active' : '' }}">
                    <span>⏳</span> <span>Pickup Orders</span>
                </a>
                <a href="{{ route('pos.stock.adjustments') }}" class="nav-item {{ request()->routeIs('pos.stock.adjustments') ? 'active' : '' }}">
                    <span>📉</span> <span>Damaged Goods</span>
                </a>
            @endif

            <div class="menu-category">Ledgers & History</div>
            <a href="{{ route('pos.transactions.index') }}" class="nav-item {{ request()->routeIs('pos.transactions.*') ? 'active' : '' }}">
                <span>📜</span> <span>{{ $currentRole === 'cashier' ? 'My Sales History' : 'History & Ledgers' }}</span>
            </a>

            @if(in_array($currentRole, ['admin', 'manager', 'sales_stock', 'cashier']))
                <a href="{{ route('pos.returns') }}" class="nav-item {{ request()->routeIs('pos.returns*') ? 'active' : '' }}">
                    <span>🔄</span> <span>Returns & Refunds</span>
                </a>
            @endif

            @if(in_array($currentRole, ['admin', 'manager', 'sales_stock', 'viewer']))
                <a href="{{ route('pos.debts.index') }}" class="nav-item {{ request()->routeIs('pos.debts.*') ? 'active' : '' }}">
                    <span>💳</span> <span>Customer Debts</span>
                </a>
            @endif

            @if(in_array($currentRole, ['admin', 'viewer']))
                <a href="{{ route('pos.wholesale.index') }}" class="nav-item {{ request()->routeIs('pos.wholesale.*') ? 'active' : '' }}">
                    <span>📦</span> <span>Wholesale Hub</span>
                </a>
            @endif

            @if(in_array($currentRole, ['admin', 'manager', 'viewer']))
                <div class="menu-category">Management & Reports</div>
                @if(in_array($currentRole, ['admin', 'viewer']))
                    <a href="{{ route('pos.auditor.index') }}" class="nav-item auditor-btn {{ request()->routeIs('pos.auditor.*') ? 'active' : '' }}">
                        <span>🚨</span> <span>Auditor Control Hub</span>
                    </a>
                @endif
                <a href="{{ route('pos.reports.index') }}" class="nav-item {{ request()->routeIs('pos.reports.*') ? 'active' : '' }}">
                    <span>📊</span> <span>Reports & AI Exports</span>
                </a>
                @if(in_array($currentRole, ['admin', 'viewer']))
                    <a href="{{ route('pos.users.index') }}" class="nav-item {{ request()->routeIs('pos.users.*') ? 'active' : '' }}">
                        <span>👥</span> <span>Workers & Roles</span>
                    </a>
                @endif
                @if($currentRole === 'admin')
                    <a href="{{ route('pos.settings.index') }}" class="nav-item {{ request()->routeIs('pos.settings.*') ? 'active' : '' }}">
                        <span>⚙️</span> <span>System Settings</span>
                    </a>
                    <a href="{{ route('pos.subscription.index') }}" class="nav-item {{ request()->routeIs('pos.subscription.*') ? 'active' : '' }}" style="color: #a78bfa;">
                        <span>⭐</span> <span>Plan & Subscription</span>
                    </a>
                    @if($isSuperAdmin)
                        <a href="{{ route('pos.saas.dashboard') }}" class="nav-item {{ request()->routeIs('pos.saas.*') ? 'active' : '' }}" style="color: #f472b6; border: 1px dashed rgba(244,114,182,0.4); border-radius: 10px; margin-top: 0.25rem;">
                            <span>👑</span> <span>SaaS Master Control</span>
                        </a>
                    @endif
                @endif
            @endif

            <div class="menu-category">Support & Help</div>
            <a href="{{ route('pos.help.index') }}" class="nav-item {{ request()->routeIs('pos.help.*') ? 'active' : '' }}" style="color: #93c5fd;">
                <span>📖</span> <span>User Guide & FAQs</span>
            </a>
        </nav>

        <div class="sidebar-footer" style="flex-direction: column; gap: 0.4rem; text-align: center; padding: 1rem 0.75rem;">
            <div style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                <div class="online-badge">
                    <span class="online-dot"></span> Online
                </div>
                <div style="font-size: 0.75rem; color: #6b7280;">v1.2.0</div>
            </div>
            <div style="font-size: 0.68rem; color: #94a3b8; line-height: 1.3; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 0.4rem;">
                Powered by <strong style="color: #60a5fa;">Victorious MARKET</strong><br><span style="color: #64748b;">your Trusted Online Market</span>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <header class="topbar">
            <!-- Left: Ecosystem Hub Switcher & Live Clock -->
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                @if($isSuperAdmin)
                    <a href="{{ route('pos.sso.return') }}" class="topbar-hub-btn topbar-hub-admin" title="Return to Victorious MARKET Super Admin Command Center">
                        <span>🔙</span> <span>Back to Vmarket Admin</span>
                    </a>
                @elseif($isVerifiedMerchant)
                    <a href="{{ route('pos.sso.return') }}" class="topbar-hub-btn topbar-hub-vendor" title="Return to Victorious MARKET Merchant Web Dashboard">
                        <span>🔙</span> <span>Back to Merchant Panel</span>
                    </a>
                @elseif($isUnverifiedMerchant)
                    <div style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 12px; padding: 0.4rem 0.85rem; font-size: 0.82rem; font-weight: 800; color: #fbbf24; display: inline-flex; align-items: center; gap: 0.4rem;" title="Free In-Store POS active. Online Marketplace listing unlocks upon Super Admin KYC verification.">
                        <span>🏪</span> <span>Free In-Store POS (Pending KYC)</span>
                    </div>
                @endif

                <div id="liveClockWidget" class="live-clock-pill">
                    <span>📅</span> <span id="headerDate">--</span>
                    <span class="clock-divider">|</span>
                    <span>⏰</span> <span id="headerTime">--:--:--</span>
                </div>

                @if($currentRole === 'viewer')
                    <div style="background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.4); border-radius: 12px; padding: 0.4rem 0.85rem; font-size: 0.82rem; font-weight: 800; color: #facc15; display: inline-flex; align-items: center; gap: 0.4rem;">
                        <span>👑</span> <span>Executive Observer</span>
                    </div>
                @endif
            </div>

            <!-- Right: Calculator, User Profile Card & Aligned Logout -->
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: nowrap;">
                <button type="button" class="topbar-tool-btn" onclick="toggleCalculator()" title="Quick POS Calculator">
                    <span>🧮</span> <span>Calculator</span>
                </button>

                <div class="topbar-vdivider"></div>

                <!-- User Profile & Aligned Logout Button -->
                <div class="user-profile-group">
                    <div class="user-avatar-chip">
                        {{ strtoupper(substr(auth()->user()->name ?? session('user_name', 'U'), 0, 1)) }}
                    </div>
                    <div class="user-meta">
                        <div class="user-name">{{ auth()->user()->name ?? session('user_name', 'Auditor / Lead') }}</div>
                        <div class="user-role-badge">
                            @if($isSuperAdmin)
                                <span style="color: #c4b5fd;">Super Admin</span>
                            @elseif($isSuperAdminEmployee)
                                <span style="color: #a5b4fc;">Super Admin Staff</span>
                            @elseif($isVerifiedMerchant)
                                <span style="color: #6ee7b7;">Verified Merchant</span>
                            @elseif($isUnverifiedMerchant)
                                <span style="color: #fde047;">Merchant (Free POS)</span>
                            @elseif($isVerifiedMerchantEmployee)
                                <span style="color: #93c5fd;">Cashier</span>
                            @elseif($isUnverifiedMerchantEmployee)
                                <span style="color: #cbd5e1;">Cashier (Free Store)</span>
                            @else
                                {{ ucfirst($currentRole) }}
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('logout') }}" class="topbar-logout-btn" title="Sign out of system">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        <span>Log Out</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="container">
            @if(session('success'))
                <div class="alert alert-success">
                    <span>✓</span> {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning">
                    <span>⚠️</span> {{ session('warning') }}
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger">
                    <span>❌</span> {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Universal Action Confirmation Modal (What Will Happen) -->
    <div id="modalGlobalConfirm" class="modal-backdrop" style="display: none; z-index: 9999;">
        <div class="modal" id="globalConfirmCard" style="max-width: 480px; padding: 1.75rem; background: #0f172a; border: 2px solid #3b82f6; border-radius: 20px; box-shadow: 0 25px 60px rgba(0,0,0,0.7); animation: modalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
            <div style="text-align: center; margin-bottom: 1.25rem;">
                <div id="globalConfirmIcon" style="font-size: 2.75rem; margin-bottom: 0.35rem; line-height: 1;">⚡</div>
                <h3 id="globalConfirmTitle" style="font-size: 1.25rem; font-weight: 800; color: #f8fafc;">Confirm Action</h3>
                <p id="globalConfirmSubtitle" style="font-size: 0.82rem; color: #94a3b8; margin-top: 0.25rem;">Review what will happen before proceeding:</p>
            </div>

            <div id="globalConfirmBody" style="background: rgba(15,23,42,0.85); border: 1px solid var(--border); border-radius: 14px; padding: 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.6rem;">
            </div>

            <div id="globalConfirmImpactWrap" style="margin-bottom: 1.25rem; display: none;">
                <div id="globalConfirmImpact" style="font-weight: 700; padding: 0.65rem 0.85rem; border-radius: 10px; font-size: 0.82rem;"></div>
            </div>

            <div style="display: flex; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" style="flex: 1; padding: 0.75rem; font-weight: 700;" onclick="closeGlobalConfirm()">
                    ✕ Cancel / Edit
                </button>
                <button type="button" id="globalConfirmProceedBtn" class="btn btn-success" style="flex: 1.3; padding: 0.75rem; font-weight: 800;" onclick="executeGlobalConfirm()">
                    ✅ Yes, Proceed
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Header Calculator Modal -->
    <div id="modalCalculator" class="modal-backdrop" style="display: none;">
        <div class="modal" style="max-width: 360px; padding: 1.5rem; background: #111827; border: 2px solid #374151;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #f9fafb;">🧮 POS Calculator</h3>
                <button type="button" onclick="toggleCalculator()" style="background: none; border: none; color: #9ca3af; font-size: 1.25rem; cursor: pointer;">✕</button>
            </div>

            <!-- Display -->
            <div id="calcDisplay" style="background: #030712; border: 1px solid #374151; border-radius: 12px; padding: 1rem; font-size: 1.8rem; font-weight: 800; text-align: right; color: #4ade80; overflow-x: auto; margin-bottom: 1rem; min-height: 60px; font-family: monospace;">
                0
            </div>

            <!-- Keypad Grid -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #dc2626; color: #fff;" onclick="calcClear()">C</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('(')">(</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput(')')">)</button>
                <button type="button" class="btn btn-primary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('/')">÷</button>

                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('7')">7</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('8')">8</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('9')">9</button>
                <button type="button" class="btn btn-primary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('*')">×</button>

                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('4')">4</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('5')">5</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('6')">6</button>
                <button type="button" class="btn btn-primary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('-')">−</button>

                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('1')">1</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('2')">2</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('3')">3</button>
                <button type="button" class="btn btn-primary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('+')">+</button>

                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('0')">0</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('00')">00</button>
                <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('.')">.</button>
                <button type="button" class="btn btn-success" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcEquals()">=</button>
            </div>
        </div>
    </div>

    <!-- Global Action Blocked / Business Rule Constraint Reason Modal -->
    <div id="modalActionBlocked" class="modal-backdrop" style="display: none; z-index: 1200;">
        <div class="modal" style="max-width: 480px; padding: 1.75rem; background: #0f172a; border: 2px solid #ef4444; border-radius: 20px; box-shadow: 0 25px 70px rgba(239,68,68,0.25);">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(239,68,68,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0;">
                    ⛔
                </div>
                <div>
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: #f87171; margin-bottom: 0.15rem;" id="actionBlockedTitle">Action Blocked</h3>
                    <span style="font-size: 0.78rem; color: #94a3b8;" id="actionBlockedSubtitle">Business Rule & Constraint Validation Failed</span>
                </div>
                <button type="button" onclick="closeActionBlockedModal()" style="margin-left: auto; background: none; border: none; color: #94a3b8; font-size: 1.25rem; cursor: pointer;">✕</button>
            </div>

            <p style="font-size: 0.84rem; color: #cbd5e1; margin-bottom: 0.75rem;">
                This request cannot be submitted because the following rules were not met:
            </p>

            <div id="actionBlockedReasonsList" style="background: rgba(15,23,42,0.85); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; max-height: 250px; overflow-y: auto;">
                <!-- Dynamically populated reason rows -->
            </div>

            <button type="button" class="btn btn-primary btn-block" style="font-weight: 800; padding: 0.75rem; border-radius: 10px; background: #ef4444; border-color: #dc2626; box-shadow: 0 4px 15px rgba(239,68,68,0.35);" onclick="closeActionBlockedModal()">
                🔧 Fix Requirements & Continue
            </button>
        </div>
    </div>

    <!-- Live Clock & Calculator Scripts -->
    <script>
    // 1. Live Clock Engine
    function updateClock() {
        const now = new Date();
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const dayName = days[now.getDay()];
        const day = String(now.getDate()).padStart(2, '0');
        const month = months[now.getMonth()];
        const year = now.getFullYear();

        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // 0 should be 12
        const strHours = String(hours).padStart(2, '0');

        const dateEl = document.getElementById('headerDate');
        const timeEl = document.getElementById('headerTime');

        if (dateEl) dateEl.textContent = `${dayName}, ${day} ${month} ${year}`;
        if (timeEl) timeEl.textContent = `${strHours}:${minutes}:${seconds} ${ampm}`;
    }

    setInterval(updateClock, 1000);
    updateClock();

    // 2. Interactive Calculator Engine
    let calcExpression = '';

    function toggleCalculator() {
        const modal = document.getElementById('modalCalculator');
        modal.style.display = (modal.style.display === 'none' || modal.style.display === '') ? 'flex' : 'none';
    }

    function calcInput(val) {
        if (calcExpression === '0' && val !== '.') calcExpression = '';
        calcExpression += val;
        document.getElementById('calcDisplay').textContent = calcExpression;
    }

    function calcClear() {
        calcExpression = '';
        document.getElementById('calcDisplay').textContent = '0';
    }

    function calcEquals() {
        try {
            // Safe evaluation of arithmetic only
            const sanitized = calcExpression.replace(/[^0-9+\-*/().]/g, '');
            if (!sanitized) return;
            const result = Function('"use strict";return (' + sanitized + ')')();
            calcExpression = String(result);
            document.getElementById('calcDisplay').textContent = Number(result).toLocaleString('en-US', { maximumFractionDigits: 4 });
        } catch (e) {
            document.getElementById('calcDisplay').textContent = 'Error';
            calcExpression = '';
        }
    }

    // 3. Universal Action Confirmation Modal Engine
    let pendingConfirmAction = null;

    function showConfirmPopup({
        icon = '⚡',
        title = 'Confirm Action',
        subtitle = 'Review what will happen before proceeding:',
        items = [], // Array of { label: '...', value: '...', color: '...', size: '...' }
        message = '',
        impact = null, // { text: '...', type: 'success'|'warning'|'danger'|'info' }
        confirmText = '✅ Yes, Proceed',
        confirmClass = 'btn-success',
        borderColor = '#3b82f6',
        onConfirm = null,
        form = null
    }) {
        document.getElementById('globalConfirmIcon').textContent = icon;
        document.getElementById('globalConfirmTitle').textContent = title;
        document.getElementById('globalConfirmSubtitle').textContent = subtitle;

        const card = document.getElementById('globalConfirmCard');
        if (card) card.style.borderColor = borderColor;

        const bodyEl = document.getElementById('globalConfirmBody');
        bodyEl.innerHTML = '';

        if (items && items.length > 0) {
            items.forEach(item => {
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.justifyContent = 'space-between';
                row.style.alignItems = 'center';
                row.style.borderBottom = '1px dashed #334155';
                row.style.paddingBottom = '0.45rem';

                const labelSpan = document.createElement('span');
                labelSpan.style.color = '#94a3b8';
                labelSpan.textContent = item.label + ':';

                const valSpan = document.createElement('strong');
                valSpan.textContent = item.value;
                valSpan.style.color = item.color || '#f8fafc';
                if (item.size) valSpan.style.fontSize = item.size;

                row.appendChild(labelSpan);
                row.appendChild(valSpan);
                bodyEl.appendChild(row);
            });
        } else if (message) {
            const p = document.createElement('div');
            p.style.color = '#cbd5e1';
            p.style.lineHeight = '1.5';
            p.innerHTML = message;
            bodyEl.appendChild(p);
        }

        const impactWrap = document.getElementById('globalConfirmImpactWrap');
        const impactEl = document.getElementById('globalConfirmImpact');
        if (impact && impact.text) {
            impactWrap.style.display = 'block';
            impactEl.textContent = impact.text;
            if (impact.type === 'danger') {
                impactEl.style.background = 'rgba(220,38,38,0.15)';
                impactEl.style.color = '#f87171';
                impactEl.style.border = '1px solid #ef4444';
            } else if (impact.type === 'warning') {
                impactEl.style.background = 'rgba(245,158,11,0.15)';
                impactEl.style.color = '#fbbf24';
                impactEl.style.border = '1px solid #f59e0b';
            } else if (impact.type === 'info') {
                impactEl.style.background = 'rgba(59,130,246,0.15)';
                impactEl.style.color = '#60a5fa';
                impactEl.style.border = '1px solid #3b82f6';
            } else {
                impactEl.style.background = 'rgba(34,197,94,0.15)';
                impactEl.style.color = '#4ade80';
                impactEl.style.border = '1px solid #22c55e';
            }
        } else {
            impactWrap.style.display = 'none';
        }

        const proceedBtn = document.getElementById('globalConfirmProceedBtn');
        proceedBtn.textContent = confirmText;
        proceedBtn.className = 'btn ' + confirmClass;

        pendingConfirmAction = () => {
            if (typeof onConfirm === 'function') {
                onConfirm();
            } else if (form) {
                if (typeof form.submit === 'function') {
                    form.submit();
                } else {
                    HTMLFormElement.prototype.submit.call(form);
                }
            }
        };

        document.getElementById('modalGlobalConfirm').style.display = 'flex';
    }

    function closeGlobalConfirm() {
        document.getElementById('modalGlobalConfirm').style.display = 'none';
        pendingConfirmAction = null;
    }

    function executeGlobalConfirm() {
        const act = pendingConfirmAction;
        closeGlobalConfirm();
        if (act) act();
    }

    // 4. Universal Action Blocked & Business Rule Interceptor Engine
    let actionBlockedFocusTarget = null;

    function showActionBlockedModal({
        title = 'Action Blocked',
        subtitle = 'Business Rule & Constraint Validation Failed',
        errors = [], // Array of { title: '...', desc: '...', focus: 'elementId' }
        focus = null
    }) {
        const titleEl = document.getElementById('actionBlockedTitle');
        const subEl = document.getElementById('actionBlockedSubtitle');
        if (titleEl) titleEl.textContent = title;
        if (subEl) subEl.textContent = subtitle;

        const listEl = document.getElementById('actionBlockedReasonsList');
        if (listEl) {
            listEl.innerHTML = '';
            actionBlockedFocusTarget = focus || (errors.length > 0 ? errors[0].focus : null);

            errors.forEach(err => {
                const item = document.createElement('div');
                item.style.display = 'flex';
                item.style.alignItems = 'flex-start';
                item.style.gap = '0.65rem';
                item.innerHTML = `
                    <span style="color: #ef4444; font-size: 1.1rem; line-height: 1.2;">⚠️</span>
                    <div style="flex: 1;">
                        <strong style="color: #f8fafc; font-size: 0.88rem; display: block;">${err.title || 'Validation Error'}</strong>
                        <div style="font-size: 0.8rem; color: #cbd5e1; margin-top: 0.15rem; line-height: 1.35;">${err.desc || err}</div>
                    </div>
                `;
                listEl.appendChild(item);
            });
        }

        const modal = document.getElementById('modalActionBlocked');
        if (modal) modal.style.display = 'flex';
    }

    function closeActionBlockedModal() {
        const modal = document.getElementById('modalActionBlocked');
        if (modal) modal.style.display = 'none';

        if (actionBlockedFocusTarget) {
            const target = typeof actionBlockedFocusTarget === 'string' ? document.getElementById(actionBlockedFocusTarget) : actionBlockedFocusTarget;
            if (target && typeof target.focus === 'function') {
                target.focus();
                if (typeof target.select === 'function') target.select();
            }
            actionBlockedFocusTarget = null;
        }
    }

    function openModal(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'flex';
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }

    // Global click listener to close modals when clicking on the backdrop
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('modal-backdrop')) {
            e.target.style.display = 'none';
            if (e.target.id === 'modalGlobalConfirm') {
                pendingConfirmAction = null;
            }
        }
    });

    // Global keyboard listener (Escape to close all modals)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop').forEach(modal => {
                if (modal.style.display !== 'none') {
                    modal.style.display = 'none';
                }
            });
            pendingConfirmAction = null;
        }
    });
    </script>

    @stack('scripts')
</body>
</html>

