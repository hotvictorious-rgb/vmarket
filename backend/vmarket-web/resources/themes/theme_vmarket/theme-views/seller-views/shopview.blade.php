@extends('theme-views.layouts.app')

@php
    $shopName = $shopInfoArray['name'] ?? ($shop->name ?? translate('Verified Merchant'));
    $shopCity = $shop->deliveryCity?->name ?? 'Uyo';
    $shopState = $shop->deliveryState?->name ?? 'Akwa Ibom';
    $productCount = $products->total();
    $avgRating = number_format((float) ($shopInfoArray['average_rating'] ?? 5.0), 1);
@endphp

@section('title', $shopName . ' | ' . translate('Official Merchant Catalog') . ' - ' . getWebConfig(name: 'company_name'))

@push('css_or_js')
    <meta property="og:title" content="{{ $shopName }} | {{ translate('Official Merchant Catalog') }}">
    <meta property="og:description" content="{{ translate('Browse authentic products from') }} {{ $shopName }} {{ translate('on Victorious MARKET. Guaranteed in-shop inspection and fast LGA delivery.') }}">
@endpush

@section('content')
<div class="vm-container" style="padding-top: 20px;">

    <!-- Unified Victorious Brand Merchant Header (Zero Vendor Banners, Zero Phone Leaks) -->
    <div class="vm-shop-banner-header">
        <div class="vm-shop-banner-pattern"></div>
        <div class="vm-shop-meta-row">
            <div class="vm-shop-profile">
                <img src="{{ getStorageImages(path: $shopInfoArray['image_full_url'], type: 'shop') }}" 
                     alt="{{ $shopName }}" 
                     class="vm-shop-avatar">
                <div>
                    <h1 class="vm-shop-title">
                        {{ $shopName }}
                        <span class="vm-verified-badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                            {{ translate('Verified Merchant') }}
                        </span>
                    </h1>
                    <div class="vm-shop-location">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <span>{{ $shopCity }}, {{ $shopState }}</span>
                        <span>•</span>
                        <span>⭐ {{ $avgRating }} {{ translate('Rating') }}</span>
                    </div>
                </div>
            </div>

            <!-- Merchant Stats Summary -->
            <div class="vm-shop-stats">
                <div class="vm-shop-stat-item">
                    <div class="vm-shop-stat-val">{{ $productCount }}</div>
                    <div class="vm-shop-stat-lbl">{{ translate('Products') }}</div>
                </div>
                <div class="vm-shop-stat-item">
                    <div class="vm-shop-stat-val">100%</div>
                    <div class="vm-shop-stat-lbl">{{ translate('Authentic') }}</div>
                </div>
                <div class="vm-shop-stat-item">
                    <div class="vm-shop-stat-val">🏪</div>
                    <div class="vm-shop-stat-lbl">{{ translate('In-Shop Pickup') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Search & Filter Row -->
    <div style="margin-bottom: 24px;">
        <div class="vm-section-header">
            <h2 class="vm-section-title">{{ translate('Merchant Catalog') }} ({{ $productCount }})</h2>
            <form action="{{ route('vendor-shop', ['slug' => $shopInfoArray['slug']]) }}" method="GET" style="display: flex; gap: 8px;">
                <input type="text" name="product_name" value="{{ request('product_name') }}" placeholder="{{ translate('Search in store...') }}" class="vm-search-input" style="background: #FFFFFF; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-full); padding: 6px 14px; font-size: 13px; width: 220px;">
                <button type="submit" class="vm-search-btn" style="padding: 6px 16px;">{{ translate('Filter') }}</button>
            </form>
        </div>

        @if($categories && count($categories) > 0)
            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 16px;">
                <a href="{{ route('vendor-shop', ['slug' => $shopInfoArray['slug']]) }}" 
                   style="padding: 6px 14px; border-radius: var(--vm-radius-full); font-size: 12.5px; font-weight: 600; white-space: nowrap; {{ !request('category_id') ? 'background: var(--vm-primary); color: #FFFFFF;' : 'background: #FFFFFF; border: 1px solid var(--vm-border); color: var(--vm-text);' }}">
                    {{ translate('All Items') }}
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('vendor-shop', ['slug' => $shopInfoArray['slug'], 'category_id' => $category['id']]) }}" 
                       style="padding: 6px 14px; border-radius: var(--vm-radius-full); font-size: 12.5px; font-weight: 600; white-space: nowrap; {{ request('category_id') == $category['id'] ? 'background: var(--vm-primary); color: #FFFFFF;' : 'background: #FFFFFF; border: 1px solid var(--vm-border); color: var(--vm-text);' }}">
                        {{ $category['name'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Merchant Products Grid -->
    @if($products->count() > 0)
        <div class="vm-products-grid">
            @foreach($products as $product)
                @php
                    $productImage = $product->thumbnail_full_url ?? ['path' => ''];
                    $price = $product->unit_price;
                    $discount = \App\Utils\Helpers::get_product_discount($product, $price);
                    $finalPrice = $price - $discount;
                @endphp
                <div class="vm-product-card">
                    <a href="{{ route('product', $product->slug) }}" class="vm-product-img-wrap">
                        <img src="{{ getStorageImages(path: $productImage, type: 'product') }}" 
                             alt="{{ $product->name }}" 
                             class="vm-product-img" 
                             loading="lazy">
                        @if($discount > 0)
                            <span class="vm-discount-badge">
                                @if ($product->discount_type == 'percent')
                                    -{{ round($product->discount) }}%
                                @else
                                    -{{ webCurrencyConverter($discount) }}
                                @endif
                            </span>
                        @endif
                    </a>
                    <div class="vm-product-info">
                        <span class="vm-product-shop">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                            {{ $shopName }}
                        </span>
                        <a href="{{ route('product', $product->slug) }}" class="vm-product-name" title="{{ $product->name }}">
                            {{ $product->name }}
                        </a>
                        <div class="vm-product-price-row">
                            <span class="vm-price-current">{{ webCurrencyConverter($finalPrice) }}</span>
                            @if($discount > 0)
                                <span class="vm-price-old">{{ webCurrencyConverter($price) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-bottom: 40px; display: flex; justify-content: center;">
            {{ $products->links() }}
        </div>
    @else
        <div style="text-align: center; padding: 60px 20px; background: #FFFFFF; border-radius: var(--vm-radius-md); border: 1px solid var(--vm-border); margin-bottom: 40px;">
            <p style="font-size: 16px; font-weight: 600; color: var(--vm-text-muted);">
                {{ translate('No products found matching your search in this store.') }}
            </p>
            <a href="{{ route('vendor-shop', ['slug' => $shopInfoArray['slug']]) }}" class="vm-btn-primary" style="display: inline-flex; width: auto; margin-top: 16px; padding: 10px 20px;">
                {{ translate('View All Store Products') }}
            </a>
        </div>
    @endif

</div>
@endsection
