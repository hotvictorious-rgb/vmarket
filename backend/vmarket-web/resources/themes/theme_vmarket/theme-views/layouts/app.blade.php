<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session('direction', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', getWebConfig(name: 'company_name') . ' | ' . translate('Leading Omnichannel Marketplace in Akwa Ibom'))</title>
    
    <!-- Favicon -->
    @php($companyFavIcon = getWebConfig(name: 'company_fav_icon'))
    <link rel="icon" type="image/x-icon" href="{{ getStorageImages(path: $companyFavIcon, type: 'logo') }}">
    
    <!-- Preconnect Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- VMarket Theme CSS -->
    <link rel="stylesheet" href="{{ theme_asset('assets/css/vmarket.css') }}">
    
    @stack('css_or_js')
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

    <!-- VMarket Vanilla JS -->
    <script src="{{ theme_asset('assets/js/vmarket.js') }}" defer></script>
    
    @stack('script')
</body>
</html>
