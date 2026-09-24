<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ session('direction', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', getWebConfig(name: 'company_name') . ' | ' . translate('Leading Omnichannel Marketplace in Akwa Ibom'))</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="{{ theme_asset('assets/img/vm_icon.jpg') }}">
    <link rel="apple-touch-icon" href="{{ theme_asset('assets/img/vm_icon.jpg') }}">
    
    <!-- Preconnect Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap & Utility Icons CSS -->
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('assets/css/toastr.css') }}">
    
    <!-- VMarket Master Theme CSS (Loaded after Bootstrap to enforce bespoke design language) -->
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
    @include('theme-views.layouts.partials._translate-text-for-js')
    @include('theme-views.layouts.partials._route-for-js')
    @include('theme-views.layouts.main-script')

    <!-- Core Interactive Scripts -->
    <script src="{{ theme_asset('assets/js/custom.js') }}" defer></script>
    <script src="{{ theme_asset('assets/js/vmarket.js') }}" defer></script>

    {!! Toastr::message() !!}

    <script>
        function route_alert(route, message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: "{{ translate('Are you sure?') }}",
                    text: message,
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonColor: '#6B7280',
                    confirmButtonColor: '#5E17EB',
                    cancelButtonText: "{{ translate('No') }}",
                    confirmButtonText: "{{ translate('Yes') }}",
                    reverseButtons: true
                }).then((result) => {
                    if (result.value) {
                        location.href = route;
                    }
                });
            } else if (confirm(message)) {
                location.href = route;
            }
        }
    </script>
    
    @stack('script')
</body>
</html>

