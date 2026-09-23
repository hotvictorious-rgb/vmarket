@php
    $companyName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
    $companyWebLogo = getWebConfig(name: 'company_web_logo');
    $cartCount = \App\Utils\CartManager::get_cart()->count();
    $customer = auth('customer')->user();
@endphp

<!-- Top Bar -->
<div class="vm-top-bar">
    <div class="vm-container vm-top-bar-inner">
        <div>
            <span>👑 {{ translate('Welcome to Victorious MARKET — Seamless Shopping, Swift Logistics') }}</span>
        </div>
        <div style="display: flex; gap: 16px;">
            <a href="{{ route('vendors') }}">{{ translate('Verified Merchants') }}</a>
            <span>•</span>
            <a href="{{ route('contacts') }}">{{ translate('Help & Support') }}</a>
        </div>
    </div>
</div>

<!-- Main Sticky Header -->
<header class="vm-header">
    <div class="vm-container vm-header-inner">
        <!-- Brand Wordmark & Logo -->
        <a href="{{ route('home') }}" class="vm-brand">
            @if($companyWebLogo)
                <img src="{{ getStorageImages(path: $companyWebLogo, type: 'logo') }}" alt="{{ $companyName }}" class="vm-brand-logo">
            @endif
            <span class="vm-brand-wordmark">Victorious <span>MARKET</span></span>
        </a>

        <!-- Global Search Bar -->
        <form action="{{ route('products') }}" method="GET" class="vm-search-form">
            <div class="vm-search-input-wrap">
                <input type="text" name="name" class="vm-search-input" placeholder="{{ translate('Search for products, categories, or items...') }}" value="{{ request('name') }}" autocomplete="off" required>
                <button type="submit" class="vm-search-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <span>{{ translate('Search') }}</span>
                </button>
            </div>
        </form>

        <!-- Actions -->
        <div class="vm-header-actions">
            @if($customer)
                <a href="{{ route('user-profile') }}" class="vm-action-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>{{ Str::limit($customer->f_name, 10) }}</span>
                </a>
            @else
                <a href="{{ route('customer.auth.login') }}" class="vm-action-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                    <span>{{ translate('Sign In') }}</span>
                </a>
            @endif

            <a href="{{ route('shop-cart') }}" class="vm-action-btn" title="{{ translate('Cart') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                <span class="vm-action-badge">{{ $cartCount }}</span>
            </a>
        </div>
    </div>
</header>

<!-- Desktop Navigation Bar -->
<nav class="vm-nav-bar">
    <div class="vm-container">
        <ul class="vm-nav-list">
            <li><a href="{{ route('home') }}" class="vm-nav-link {{ Request::is('/') ? 'active' : '' }}">{{ translate('Home') }}</a></li>
            <li><a href="{{ route('products') }}" class="vm-nav-link {{ Request::is('products*') ? 'active' : '' }}">{{ translate('All Products') }}</a></li>
            <li><a href="{{ route('categories') }}" class="vm-nav-link {{ Request::is('categories*') ? 'active' : '' }}">{{ translate('Categories') }}</a></li>
            <li><a href="{{ route('vendors') }}" class="vm-nav-link {{ Request::is('vendors*') ? 'active' : '' }}">{{ translate('Verified Merchants') }}</a></li>
            <li><a href="{{ route('contacts') }}" class="vm-nav-link {{ Request::is('contacts*') ? 'active' : '' }}">{{ translate('Contact & Location') }}</a></li>
        </ul>
    </div>
</nav>
