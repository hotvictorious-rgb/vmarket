<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session('direction', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="_token" content="{{ csrf_token() }}">
    
    @php
        $pageMetaTitle = data_get($robotsMetaContentData ?? null, 'meta_title') ?: data_get($robotsMetaContentData ?? null, 'title');
        $favicon = !empty($web_config['fav_icon']['status']) ? $web_config['fav_icon']['path'] : theme_asset('assets/img/vm_icon.jpg');
        $primaryColor = preg_match('/^#[a-fA-F0-9]{6}$/', $web_config['primary_color'] ?? '') ? $web_config['primary_color'] : '#5E17EB';
        $secondaryColor = preg_match('/^#[a-fA-F0-9]{6}$/', $web_config['secondary_color'] ?? '') ? $web_config['secondary_color'] : '#FFD700';
    @endphp
    <title>@if($pageMetaTitle){{ $pageMetaTitle }}@else @yield('title', getWebConfig(name: 'company_name')) @endif</title>
    @include('theme-views.partials._robotsMetaContentData')
    
    <!-- Favicon -->
    <link rel="icon" href="{{ $favicon }}">
    <link rel="apple-touch-icon" href="{{ $favicon }}">
    
    <!-- Bootstrap & Utility Icons CSS -->
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/toastr.css') }}">
    
    <!-- VMarket Master Theme CSS (Loaded after Bootstrap to enforce bespoke design language) -->
    @php
        $vmarketCssPath = base_path('resources/themes/theme_vmarket/public/assets/css/vmarket.css');
        $vmarketCssVersion = file_exists($vmarketCssPath) ? filemtime($vmarketCssPath) : config('app.asset_version', '1.0');
    @endphp
    <link rel="stylesheet" href="{{ theme_asset('assets/css/vmarket.css') }}?v={{ $vmarketCssVersion }}">
    <style>:root { --vm-primary: {{ $primaryColor }}; --vm-gold: {{ $secondaryColor }}; }</style>
    <!-- [AI] VM-STORE-004: intl-tel-input CSS so any initialized country picker renders styled, never as a raw list -->
    <link rel="stylesheet" href="{{ theme_asset('assets/plugins/intl-tel-input/css/intlTelInput.min.css') }}">
    
    @stack('css_or_js')
    @stack('styles')
</head>
<body>

    <!-- Header Navigation -->
    @include('theme-views.layouts.partials._header')

    <!-- Main Content Body -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @include('theme-views.layouts.partials._footer')

    <!-- Mobile Bottom Navigation Bar -->
    @include('theme-views.layouts.partials._mobile-nav')

    <!-- Fly-Out Shopping Bag Drawer (Suppressed on all Cart Review and Checkout pages) -->
    @php
        $currentRouteName = request()->route()?->getName();
        $isCartOrCheckout = in_array($currentRouteName, [
            'shop-cart',
            'checkout-details',
            'checkout-shipping',
            'checkout-payment',
            'checkout-review',
            'checkout-complete',
        ], true) || Request::is('shop-cart*', 'checkout*');
    @endphp
    @if(!$isCartOrCheckout)
        @include('theme-views.layouts.partials._cart-drawer')
    @endif

    <!-- Omnichannel Location & Fulfillment Switcher Modal -->
    @include('theme-views.layouts.partials._location_modal')

    <!-- Global Customer Auth & Action Modals -->
    @if(!auth()->guard('customer')->check())
        @include('theme-views.layouts.partials.modal._register')
        @include('theme-views.layouts.partials.modal._login')
    @endif
    @include('theme-views.layouts.partials.modal._quick-view')
    @include('theme-views.layouts.partials.modal._buy-now')
    @include('theme-views.layouts.partials.modal._initial')

    <!-- Translations & Route References for Client-Side JS -->
    <span class="system-default-country-code" data-value="{{ strtolower(getWebConfig(name: 'country_code') ?? 'ng') }}"></span>
    @include('theme-views.layouts.partials._translate-text-for-js')
    @include('theme-views.layouts.partials._route-for-js')
    @include('theme-views.layouts.main-script')

    <!-- Core Interactive Scripts -->
    <script src="{{ theme_asset('assets/js/custom.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/vmarket.js') }}" defer></script>

    {!! Toastr::message() !!}

    <script>
        function route_alert(route, message) {
            function getSafeDestination(value) {
                try {
                    const target = new URL(value, window.location.origin);
                    if (target.origin !== window.location.origin) {
                        return null;
                    }
                    if (!['http:', 'https:'].includes(target.protocol)) {
                        return null;
                    }
                    return target.href;
                } catch (e) {
                    return null;
                }
            }

            const destination = getSafeDestination(route);
            if (!destination) {
                console.error('Invalid confirmation destination.');
                return;
            }

            const text = String(message ?? '');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: @json(translate('Are you sure?')),
                    text: text,
                    icon: 'warning',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: '#6B7280',
                    confirmButtonColor: '#5E17EB',
                    cancelButtonText: @json(translate('No')),
                    confirmButtonText: @json(translate('Yes')),
                    reverseButtons: true
                }).then(function (result) {
                    if (result && (result.isConfirmed || result.value)) {
                        window.location.assign(destination);
                    }
                });
            } else if (window.confirm(text)) {
                window.location.assign(destination);
            }
        }
    </script>
    
    @stack('script')
    @stack('scripts')
</body>
</html>
