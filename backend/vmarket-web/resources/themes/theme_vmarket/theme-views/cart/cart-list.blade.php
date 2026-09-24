@extends('theme-views.layouts.app')

@section('title', translate('Shopping Cart') . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
    <div class="main-content d-flex flex-column gap-3 py-4 mb-5" id="cart-summary">
        @include(VIEW_FILE_NAMES['products_cart_details_partials'])
    </div>

    <span id="get-cart-select-cart-items" data-route="{{ route('cart.select-cart-items') }}"></span>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/cart-list-page.js') }}"></script>
@endpush
