@extends('theme-views.layouts.app')

@section('title', ($pageTitle ?? translate('All Products')) . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 24px 16px 48px;">
    
    <!-- Section Header & Filter Form -->
    <div class="vm-section-header">
        <div>
            <h1 class="vm-section-title">{{ $pageTitle ?? translate('Catalog Products') }} ({{ $products->total() }})</h1>
            <p style="font-size: 13px; color: var(--vm-text-muted); margin-top: 4px;">
                {{ translate('Authentic marketplace inventory with verified merchant escrow and swift delivery.') }}
            </p>
        </div>

        <form action="{{ url()->current() }}" method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="name" value="{{ request('name') }}" placeholder="{{ translate('Filter products...') }}" class="vm-search-input" style="background: #FFFFFF; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-full); padding: 6px 14px; font-size: 13px; width: 220px;">
            <button type="submit" class="vm-search-btn" style="padding: 6px 16px;">{{ translate('Search') }}</button>
        </form>
    </div>

    <!-- Products Grid -->
    @if($products->count() > 0)
        <div class="vm-products-grid">
            @foreach($products as $product)
                @php
                    $price = $product->unit_price;
                    $discount = \App\Utils\Helpers::get_product_discount($product, $price);
                    $finalPrice = $price - $discount;
                    $shop = $product->seller?->shop;
                @endphp
                <div class="vm-product-card">
                    <a href="{{ route('product', $product->slug) }}" class="vm-product-img-wrap">
                        <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}" 
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
                        @if($shop)
                            <a href="{{ route('vendor-shop', $shop->slug) }}" class="vm-product-shop">
                                <span>🏪</span>
                                <span>{{ Str::limit($shop->name, 18) }}</span>
                            </a>
                        @endif
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

        <div style="margin-top: 32px; display: flex; justify-content: center;">
            {{ $products->links() }}
        </div>
    @else
        <div style="text-align: center; padding: 60px 20px; background: #FFFFFF; border-radius: var(--vm-radius-md); border: 1px solid var(--vm-border);">
            <p style="font-size: 16px; font-weight: 600; color: var(--vm-text-muted);">
                {{ translate('No products found matching your criteria.') }}
            </p>
            <a href="{{ route('products') }}" class="vm-btn-primary" style="display: inline-flex; width: auto; margin-top: 16px; padding: 10px 20px;">
                {{ translate('Browse All Products') }}
            </a>
        </div>
    @endif

</div>
@endsection
