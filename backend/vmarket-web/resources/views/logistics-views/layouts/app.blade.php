<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ Session::get('direction') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>@yield('title') | {{ getWebConfig(name: 'company_name') ?? 'Victorious Market' }} {{ translate('Logistics_Portal') }}</title>
    <link rel="shortcut icon" href="{{ dynamicStorage(path: 'storage/app/public/company/'.getWebConfig(name: 'company_fav_icon')) }}">

    {{-- Fonts & Icons --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/uicons-solid-rounded.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/toastr.css') }}">
    
    <style>
        :root {
            --vm-primary: #5f1376;
            --vm-primary-hover: #480d5b;
            --vm-gold: #e5a93c;
            --vm-dark: #1e1e2d;
            --vm-light: #f8f9fa;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6fb;
            color: #2b3445;
        }
        .navbar-top {
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
            height: 70px;
        }
        .portal-sidebar {
            width: 260px;
            min-height: calc(100vh - 70px);
            background: #ffffff;
            border-right: 1px solid #e9ecef;
        }
        .portal-sidebar .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
        }
        .portal-sidebar .nav-link:hover,
        .portal-sidebar .nav-link.active {
            background-color: rgba(95, 19, 118, 0.08);
            color: var(--vm-primary);
            font-weight: 600;
        }
        .portal-sidebar .nav-link.active i {
            color: var(--vm-primary);
        }
        .badge-soft-success { background: #e8f9ef; color: #00ba43; }
        .badge-soft-warning { background: #fff8eb; color: #ff9800; }
        .badge-soft-info { background: #e7f5ff; color: #007bff; }
        .badge-soft-danger { background: #feebeb; color: #ff334b; }
        .btn-primary {
            background-color: var(--vm-primary);
            border-color: var(--vm-primary);
        }
        .btn-primary:hover {
            background-color: var(--vm-primary-hover);
            border-color: var(--vm-primary-hover);
        }
        .portal-brand {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--vm-primary);
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
    </style>
    @stack('css')
</head>
<body>
    {{-- Top Navigation --}}
    <nav class="navbar navbar-top sticky-top px-4">
        <div class="d-flex align-items-center justify-content-between w-100">
            <a href="{{ route('logistics.dashboard') }}" class="portal-brand">
                <i class="fi fi-sr-truck-side text-warning fs-22"></i>
                <span>Victorious <span style="color: var(--vm-gold);">LOGISTICS</span></span>
            </a>

            @if(Auth::guard('logistics')->check())
                @php($company = Auth::guard('logistics')->user())
                <div class="d-flex align-items-center gap-3">
                    <div class="d-none d-md-block text-end">
                        <div class="fw-bold">{{ $company->name }}</div>
                        <div class="fs-12 text-muted">{{ translate('Wallet_Balance:') }} <span class="text-success fw-bold">₦{{ number_format($company->wallet->current_balance ?? 0, 2) }}</span></div>
                    </div>
                    <a href="{{ route('logistics.auth.logout') }}" class="btn btn-outline-danger btn-sm px-3">
                        <i class="fi fi-sr-exit"></i> {{ translate('Logout') }}
                    </a>
                </div>
            @endif
        </div>
    </nav>

    <div class="d-flex">
        {{-- Sidebar --}}
        @if(Auth::guard('logistics')->check())
        <aside class="portal-sidebar p-2 d-none d-md-block">
            <div class="nav flex-column">
                <a href="{{ route('logistics.dashboard') }}" class="nav-link {{ Request::is('logistics/dashboard') || Request::is('logistics') ? 'active' : '' }}">
                    <i class="fi fi-sr-apps"></i> {{ translate('Dashboard') }}
                </a>
                <a href="{{ route('logistics.riders.index') }}" class="nav-link {{ Request::is('logistics/riders*') ? 'active' : '' }}">
                    <i class="fi fi-sr-motorcycle"></i> {{ translate('Fleet_&_Riders') }}
                </a>
                <a href="{{ route('logistics.orders.index') }}" class="nav-link {{ Request::is('logistics/orders*') ? 'active' : '' }}">
                    <i class="fi fi-sr-box"></i> {{ translate('Orders_&_Waybills') }}
                </a>
                <a href="{{ route('logistics.wallet.index') }}" class="nav-link {{ Request::is('logistics/wallet*') ? 'active' : '' }}">
                    <i class="fi fi-sr-wallet"></i> {{ translate('Wallet_&_Payouts') }}
                </a>
            </div>
        </aside>
        @endif

        {{-- Main Page Content --}}
        <main class="flex-grow-1 p-4 overflow-auto">
            @yield('content')
        </main>
    </div>

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/jquery.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/toastr.js') }}"></script>
    {!! ToastMagic::message() !!}
    @stack('script')
</body>
</html>
