@php
    $cartCount = \App\Utils\CartManager::get_cart()->count();
    $customer = auth('customer')->user();
@endphp

<div class="vm-mobile-nav">
    <a href="{{ route('home') }}" class="vm-mobile-nav-item {{ Request::is('/') ? 'active' : '' }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        <span>{{ translate('Home') }}</span>
    </a>
    
    <a href="{{ route('categories') }}" class="vm-mobile-nav-item {{ Request::is('categories*') ? 'active' : '' }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        <span>{{ translate('Categories') }}</span>
    </a>
    
    <a href="{{ route('vendors') }}" class="vm-mobile-nav-item {{ Request::is('vendors*') || Request::is('shop*') ? 'active' : '' }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><path d="M9 22V12h6v10"></path></svg>
        <span>{{ translate('Stores') }}</span>
    </a>
    
    <a href="{{ route('shop-cart') }}" class="vm-mobile-nav-item {{ Request::is('shop-cart*') ? 'active' : '' }}">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
        <span>{{ translate('Cart') }}</span>
        @if($cartCount > 0)
            <span class="vm-action-badge" style="top: -2px; right: 8px;">{{ $cartCount }}</span>
        @endif
    </a>
    
    @if($customer)
        <a href="{{ route('user-profile') }}" class="vm-mobile-nav-item {{ Request::is('user-profile*') ? 'active' : '' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <span>{{ translate('Account') }}</span>
        </a>
    @else
        <a href="{{ route('customer.auth.login') }}" class="vm-mobile-nav-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            <span>{{ translate('Sign In') }}</span>
        </a>
    @endif
</div>
