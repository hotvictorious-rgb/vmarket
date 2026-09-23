@extends('theme-views.layouts.app')

@section('title', getWebConfig(name: 'company_name') . ' | ' . translate('Leading Omnichannel Marketplace in Akwa Ibom'))

@section('content')
<div class="vm-container">

    <!-- 1. Hero Banner Slider (Admin-Controlled Banners Only) -->
    @if(isset($bannerTypeMainBanner) && count($bannerTypeMainBanner) > 0)
        <div class="vm-hero-section">
            <div class="vm-hero-slider">
                @foreach($bannerTypeMainBanner as $idx => $banner)
                    @php
                        $bannerPhoto = is_array($banner) ? ($banner['photo_full_url'] ?? ['path' => '']) : ($banner->photo_full_url ?? ['path' => '']);
                        $bannerUrl = is_array($banner) ? ($banner['url'] ?? '#') : ($banner->url ?? '#');
                    @endphp
                    <a href="{{ $bannerUrl }}" class="vm-hero-slide" style="{{ $idx > 0 ? 'display: none;' : '' }}">
                        <img src="{{ getStorageImages(path: $bannerPhoto, type: 'banner') }}" 
                             alt="{{ getWebConfig(name: 'company_name') }} Promotion" 
                             {{ $idx === 0 ? 'fetchpriority="high"' : 'loading="lazy"' }}>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 2. Value Proposition Strip -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 32px; background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 18px 24px; box-shadow: var(--vm-shadow-xs);">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: var(--vm-radius-full); background: var(--vm-primary-light); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">🛡️</div>
            <div>
                <strong style="font-size: 13.5px; color: var(--vm-dark); display: block;">{{ translate('100% Authentic Items') }}</strong>
                <span style="font-size: 12px; color: var(--vm-text-muted);">{{ translate('Verified local merchants') }}</span>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: var(--vm-radius-full); background: var(--vm-gold-light); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">🏪</div>
            <div>
                <strong style="font-size: 13.5px; color: var(--vm-dark); display: block;">{{ translate('In-Shop Inspection') }}</strong>
                <span style="font-size: 12px; color: var(--vm-text-muted);">{{ translate('Inspect physically before payment') }}</span>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: var(--vm-radius-full); background: var(--vm-primary-light); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">⚡</div>
            <div>
                <strong style="font-size: 13.5px; color: var(--vm-dark); display: block;">{{ translate('Directional LGA Delivery') }}</strong>
                <span style="font-size: 12px; color: var(--vm-text-muted);">{{ translate('Uyo, Eket & nationwide logistics') }}</span>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: var(--vm-radius-full); background: #ECFDF5; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">💳</div>
            <div>
                <strong style="font-size: 13.5px; color: var(--vm-dark); display: block;">{{ translate('Paystack Escrow') }}</strong>
                <span style="font-size: 12px; color: var(--vm-text-muted);">{{ translate('Cards, transfers & cashbacks') }}</span>
            </div>
        </div>
    </div>

    <!-- 3. Featured Categories -->
    @if(isset($categories) && count($categories) > 0)
        <div style="margin-bottom: 32px;">
            <div class="vm-section-header">
                <h2 class="vm-section-title">{{ translate('Browse Popular Categories') }}</h2>
                <a href="{{ route('categories') }}" class="vm-section-link">{{ translate('View All') }} →</a>
            </div>
            <div class="vm-categories-grid">
                @foreach($categories->take(10) as $cat)
                    <a href="{{ route('category-products', $cat->slug) }}" class="vm-category-card">
                        <img src="{{ getStorageImages(path: $cat->icon_full_url, type: 'category') }}" 
                             alt="{{ $cat->name }}" 
                             class="vm-category-icon" 
                             loading="lazy">
                        <span class="vm-category-name">{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 4. Featured Products Showcase -->
    @if(isset($featuredProductsList) && count($featuredProductsList) > 0)
        <div style="margin-bottom: 36px;">
            <div class="vm-section-header">
                <h2 class="vm-section-title">{{ translate('Featured Products') }}</h2>
                <a href="{{ route('products') }}" class="vm-section-link">{{ translate('Explore More') }} →</a>
            </div>
            <div class="vm-products-grid">
                @foreach($featuredProductsList->take(10) as $product)
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
        </div>
    @endif

    <!-- 5. Top Verified Merchants Strip -->
    @if(isset($topVendorsList) && count($topVendorsList) > 0)
        <div style="margin-bottom: 40px; background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-lg); padding: 24px; box-shadow: var(--vm-shadow-xs);">
            <div class="vm-section-header">
                <div>
                    <h2 class="vm-section-title">{{ translate('Top Verified Merchants') }}</h2>
                    <p style="font-size: 13px; color: var(--vm-text-muted); margin-top: 2px;">
                        {{ translate('Official stores offering physical counter inspection and rapid dispatch.') }}
                    </p>
                </div>
                <a href="{{ route('vendors') }}" class="vm-section-link">{{ translate('All Stores') }} →</a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px;">
                @foreach($topVendorsList->take(6) as $vendorItem)
                    @php
                        $shop = $vendorItem->shop;
                        if (!$shop) continue;
                    @endphp
                    <a href="{{ route('vendor-shop', $shop->slug) }}" 
                       style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: var(--vm-radius-md); border: 1px solid var(--vm-border-light); background: var(--vm-bg); transition: var(--vm-transition);"
                       onmouseover="this.style.borderColor='var(--vm-primary)'; this.style.background='#FFFFFF'; this.style.boxShadow='var(--vm-shadow-hover)'"
                       onmouseout="this.style.borderColor='var(--vm-border-light)'; this.style.background='var(--vm-bg)'; this.style.boxShadow='none'">
                        <img src="{{ getStorageImages(path: $shop->image_full_url, type: 'shop') }}" 
                             alt="{{ $shop->name }}" 
                             style="width: 48px; height: 48px; border-radius: var(--vm-radius-md); object-fit: cover; flex-shrink: 0; border: 1px solid var(--vm-border);">
                        <div>
                            <strong style="font-size: 13.5px; color: var(--vm-dark); display: block; line-height: 1.25;">
                                {{ Str::limit($shop->name, 16) }}
                            </strong>
                            <span style="font-size: 11.5px; color: var(--vm-text-muted);">
                                📍 {{ $shop->deliveryCity?->name ?? 'Uyo' }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 6. Admin Promotional Footer Banner (Admin-Controlled Only) -->
    @if(isset($bannerTypeFooterBanner) && count($bannerTypeFooterBanner) > 0)
        <div style="margin-bottom: 36px; border-radius: var(--vm-radius-lg); overflow: hidden; box-shadow: var(--vm-shadow-sm);">
            @php
                $footerBanner = $bannerTypeFooterBanner[0];
                $fPhoto = is_array($footerBanner) ? ($footerBanner['photo_full_url'] ?? ['path' => '']) : ($footerBanner->photo_full_url ?? ['path' => '']);
                $fUrl = is_array($footerBanner) ? ($footerBanner['url'] ?? '#') : ($footerBanner->url ?? '#');
            @endphp
            <a href="{{ $fUrl }}" style="display: block; width: 100%;">
                <img src="{{ getStorageImages(path: $fPhoto, type: 'banner') }}" 
                     alt="{{ getWebConfig(name: 'company_name') }} Offer" 
                     style="width: 100%; max-height: 240px; object-fit: cover;" 
                     loading="lazy">
            </a>
        </div>
    @endif

    <!-- 7. Latest Arrivals Grid -->
    @if(isset($latestProductsList) && count($latestProductsList) > 0)
        <div style="margin-bottom: 40px;">
            <div class="vm-section-header">
                <h2 class="vm-section-title">{{ translate('Latest Products in Market') }}</h2>
                <a href="{{ route('products') }}" class="vm-section-link">{{ translate('View All') }} →</a>
            </div>
            <div class="vm-products-grid">
                @foreach($latestProductsList->take(10) as $product)
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
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
@endsection
