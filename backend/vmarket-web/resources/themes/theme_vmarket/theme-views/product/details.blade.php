@extends('theme-views.layouts.app')

@php
    $price = $product->unit_price;
    $discount = \App\Utils\Helpers::get_product_discount($product, $price);
    $finalPrice = $price - $discount;
    $shop = $product->seller?->shop;
    $shopName = $shop?->name ?? (getWebConfig(name: 'company_name') ?? 'Victorious MARKET');
    $shopSlug = $shop?->slug ?? '';
    $inStock = $product->current_stock > 0;
    $images = $product->images_full_url ?? [];
    $mainImage = $product->thumbnail_full_url ?? ['path' => ''];
@endphp

@section('title', $product->name . ' | ' . getWebConfig(name: 'company_name'))

@push('css_or_js')
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($product->details), 160) }}">
    <meta property="og:image" content="{{ getStorageImages(path: $mainImage, type: 'product') }}">
    <meta property="og:price:amount" content="{{ $finalPrice }}">
    <meta property="og:price:currency" content="NGN">

    <!-- Server-Rendered Google Product Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org/",
      "@type": "Product",
      "name": "{{ addslashes($product->name) }}",
      "image": [
        "{{ getStorageImages(path: $mainImage, type: 'product') }}"
      ],
      "description": "{{ addslashes(Str::limit(strip_tags($product->details), 200)) }}",
      "sku": "{{ $product->code ?? ('VM-' . $product->id) }}",
      @if($product->brand)
      "brand": {
        "@type": "Brand",
        "name": "{{ addslashes($product->brand->name) }}"
      },
      @endif
      "offers": {
        "@type": "Offer",
        "url": "{{ url()->current() }}",
        "priceCurrency": "NGN",
        "price": "{{ $finalPrice }}",
        "priceValidUntil": "{{ date('Y-12-31') }}",
        "itemCondition": "https://schema.org/NewCondition",
        "availability": "{{ $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
        "seller": {
          "@type": "Organization",
          "name": "{{ addslashes($shopName) }}"
        }
      }
    }
    </script>
@endpush

@section('content')
<div class="vm-container" style="padding-top: 24px;">

    <!-- Breadcrumb -->
    <nav style="display: flex; gap: 8px; font-size: 13px; color: var(--vm-text-muted); margin-bottom: 20px;">
        <a href="{{ route('home') }}">{{ translate('Home') }}</a>
        <span>/</span>
        <a href="{{ route('products') }}">{{ translate('Products') }}</a>
        @if($product->category)
            <span>/</span>
            <a href="{{ route('category-products', $product->category->slug) }}">{{ $product->category->name }}</a>
        @endif
        <span>/</span>
        <span style="color: var(--vm-text); font-weight: 600;">{{ Str::limit($product->name, 28) }}</span>
    </nav>

    <!-- Product Main Detail Card -->
    <div class="vm-product-detail-layout">
        
        <!-- Left: Image Gallery -->
        <div class="vm-detail-gallery">
            <div class="vm-detail-main-img">
                <img id="vmMainDetailImg" 
                     src="{{ getStorageImages(path: $mainImage, type: 'product') }}" 
                     alt="{{ $product->name }}">
            </div>

            @if(count($images) > 0)
                <div class="vm-detail-thumbs">
                    <div class="vm-detail-thumb active" data-full-src="{{ getStorageImages(path: $mainImage, type: 'product') }}">
                        <img src="{{ getStorageImages(path: $mainImage, type: 'product') }}" alt="Thumbnail">
                    </div>
                    @foreach($images as $img)
                        <div class="vm-detail-thumb" data-full-src="{{ getStorageImages(path: $img, type: 'product') }}">
                            <img src="{{ getStorageImages(path: $img, type: 'product') }}" alt="Thumbnail">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Right: Purchase Details & Fulfillment -->
        <div class="vm-detail-info">
            
            @if($shop && $shopSlug)
                <a href="{{ route('vendor-shop', $shopSlug) }}" class="vm-detail-merchant-pill">
                    <span>🏪 {{ translate('Sold by') }}: <strong>{{ $shopName }}</strong></span>
                    <span class="vm-verified-badge" style="font-size: 9px; padding: 1px 5px;">✓ Verified</span>
                </a>
            @endif

            <h1 class="vm-detail-title">{{ $product->name }}</h1>

            <!-- Ratings & SKU -->
            <div style="display: flex; gap: 16px; align-items: center; font-size: 13px; color: var(--vm-text-muted);">
                <span>⭐ {{ number_format($product->reviews->avg('rating') ?? 5.0, 1) }} ({{ $product->reviews->count() }} {{ translate('reviews') }})</span>
                <span>•</span>
                <span>{{ translate('SKU') }}: {{ $product->code ?? ('VM-' . $product->id) }}</span>
                <span>•</span>
                @if($inStock)
                    <span style="color: var(--vm-success); font-weight: 700;">● {{ translate('In Stock') }}</span>
                @else
                    <span style="color: var(--vm-danger); font-weight: 700;">● {{ translate('Out of Stock') }}</span>
                @endif
            </div>

            <!-- Price Box -->
            <div class="vm-detail-price-box">
                <span class="vm-detail-current-price">{{ webCurrencyConverter($finalPrice) }}</span>
                @if($discount > 0)
                    <span class="vm-detail-old-price">{{ webCurrencyConverter($price) }}</span>
                    <span class="vm-discount-badge" style="position: static;">
                        @if ($product->discount_type == 'percent')
                            -{{ round($product->discount) }}% OFF
                        @else
                            -{{ webCurrencyConverter($discount) }} OFF
                        @endif
                    </span>
                @endif
            </div>

            <!-- Fulfillment Badges Card -->
            <div class="vm-fulfillment-card">
                <div class="vm-fulfillment-item">
                    <span class="vm-fulfillment-icon">🚚</span>
                    <div>
                        <strong>{{ translate('Directional LGA Delivery Available') }}</strong>
                        <p style="font-size: 12px; color: var(--vm-text-muted); margin-top: 1px;">
                            {{ translate('Deliverable directly to your door in Uyo, Eket, and nationwide. Exact fee calculated at checkout based on authoritative delivery lanes.') }}
                        </p>
                    </div>
                </div>

                <div class="vm-fulfillment-item" style="border-top: 1px solid var(--vm-border-light); padding-top: 8px;">
                    <span class="vm-fulfillment-icon">🏪</span>
                    <div>
                        <strong>{{ translate('In-Shop Inspection & Pickup Eligible') }}</strong>
                        <p style="font-size: 12px; color: var(--vm-text-muted); margin-top: 1px;">
                            {{ translate('Reserve now for 24 hours. Visit the physical merchant store in') }} {{ $shop?->deliveryCity?->name ?? 'Uyo' }} {{ translate('to inspect items before payment.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quantity & Actions Form -->
            <form action="{{ route('cart.add') }}" method="POST" id="add-to-cart-form">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}">
                
                <div style="display: flex; align-items: center; gap: 16px; margin: 12px 0 16px;">
                    <span style="font-size: 14px; font-weight: 600;">{{ translate('Quantity') }}:</span>
                    <div style="display: flex; align-items: center; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); overflow: hidden; background: #FFFFFF;">
                        <button type="button" id="vmQtyMinus" style="padding: 8px 14px; background: transparent; cursor: pointer; font-weight: 700; font-size: 16px;">-</button>
                        <input type="number" name="quantity" id="vmQtyInput" value="1" min="1" max="{{ $product->current_stock }}" style="width: 44px; text-align: center; font-weight: 700; font-size: 14px;" readonly>
                        <button type="button" id="vmQtyPlus" style="padding: 8px 14px; background: transparent; cursor: pointer; font-weight: 700; font-size: 16px;">+</button>
                    </div>
                </div>

                <div class="vm-detail-actions">
                    <button type="submit" class="vm-btn-primary" {{ !$inStock ? 'disabled style=opacity:0.5;cursor:not-allowed;' : '' }}>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span>{{ translate('Add to Cart') }}</span>
                    </button>
                    
                    @if($shop && $shopSlug)
                        <a href="{{ route('vendor-shop', $shopSlug) }}" class="vm-btn-gold">
                            <span>🏪 {{ translate('View Merchant Store') }}</span>
                        </a>
                    @endif
                </div>
            </form>

        </div>
    </div>

    <!-- Product Description & Specifications -->
    <div style="background: var(--vm-surface); border: 1px solid var(--vm-border); border-radius: var(--vm-radius-lg); padding: 32px; margin-bottom: 48px;">
        <h2 style="font-size: 18px; font-weight: 800; color: var(--vm-dark); margin-bottom: 16px; border-bottom: 2px solid var(--vm-primary-light); padding-bottom: 8px;">
            {{ translate('Product Details & Overview') }}
        </h2>
        <div style="font-size: 14.5px; line-height: 1.8; color: var(--vm-text);">
            {!! $product->details !!}
        </div>
    </div>

</div>
@endsection
