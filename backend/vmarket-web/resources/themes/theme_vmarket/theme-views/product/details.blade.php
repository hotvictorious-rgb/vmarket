@extends('theme-views.layouts.app')

@php
    $price = $product->unit_price;
    $discount = \App\Utils\Helpers::get_product_discount($product, $price);
    $finalPrice = $price - $discount;
    $shop = $product->seller?->shop;
    $shopName = $shop?->name ?? (getWebConfig(name: 'company_name') ?? 'Victorious MARKET');
    $shopSlug = $shop?->slug ?? '';
    $inStock = $product->isMarketplacePurchasable() && $product->current_stock > 0;
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
      "@@context": "https://schema.org/",
      "@@type": "Product",
      "name": "{{ addslashes($product->name) }}",
      "image": [
        "{{ getStorageImages(path: $mainImage, type: 'product') }}"
      ],
      "description": "{{ addslashes(Str::limit(strip_tags($product->details), 200)) }}",
      "sku": "{{ $product->code ?? ('VM-' . $product->id) }}",
      @if($product->brand)
      "brand": {
        "@@type": "Brand",
        "name": "{{ addslashes($product->brand->name) }}"
      },
      @endif
      "offers": {
        "@@type": "Offer",
        "url": "{{ url()->current() }}",
        "priceCurrency": "NGN",
        "price": "{{ $finalPrice }}",
        "priceValidUntil": "{{ date('Y-12-31') }}",
        "itemCondition": "https://schema.org/NewCondition",
        "availability": "{{ $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}",
        "seller": {
          "@@type": "Organization",
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
            
            @if($product->added_by == 'admin')
                <div class="vm-detail-merchant-pill" style="border: 1.5px solid var(--vm-accent-gold); background: rgba(212,175,55,0.08); padding: 8px 14px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13.5px; font-weight: 700; color: #1B1035;">👑 {{ translate('Sold by') }}: <strong>{{ getInHouseShopConfig(key: 'name') ?? 'Victorious Official Flagship' }}</strong></span>
                    <span class="vm-verified-badge" style="background: var(--vm-accent-gold); color: #1B1035; font-weight: 800; font-size: 10px; padding: 3px 8px; border-radius: 6px;">★ Platform Direct (In-Shop)</span>
                </div>
            @elseif($shop && $shopSlug)
                <a href="{{ route('vendor-shop', $shopSlug) }}" class="vm-detail-merchant-pill" style="border: 1.5px solid var(--vm-border); background: #FFFFFF; padding: 8px 14px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                    <span style="font-size: 13.5px; font-weight: 700; color: #1B1035;">🏪 {{ translate('Sold by') }}: <strong>{{ $shopName }}</strong></span>
                    <span class="vm-verified-badge" style="background: #2E1B4E; color: #FFFFFF; font-size: 10px; padding: 3px 8px; border-radius: 6px;">✓ Verified Merchant</span>
                </a>
            @else
                <div class="vm-detail-merchant-pill" style="border: 1.5px solid var(--vm-accent-gold); background: rgba(212,175,55,0.08); padding: 8px 14px; border-radius: 10px; display: inline-flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13.5px; font-weight: 700; color: #1B1035;">👑 {{ translate('Sold by') }}: <strong>{{ getWebConfig(name: 'company_name') ?? 'Victorious Official Flagship' }}</strong></span>
                    <span class="vm-verified-badge" style="background: var(--vm-accent-gold); color: #1B1035; font-weight: 800; font-size: 10px; padding: 3px 8px; border-radius: 6px;">★ Platform Direct (In-Shop)</span>
                </div>
            @endif

            <h1 class="vm-detail-title">{{ $product->name }}</h1>

            <!-- Ratings & SKU -->
            <div style="display: flex; gap: 16px; align-items: center; font-size: 13px; color: var(--vm-text-muted);">
                <span>⭐ {{ number_format($overallRating[0] ?? 0, 1) }} ({{ $overallRating[1] ?? 0 }} {{ translate('reviews') }})</span>
                <span>•</span>
                <span>{{ translate('SKU') }}: {{ $product->code ?? ('VM-' . $product->id) }}</span>
                <span>•</span>
                @if($inStock)
                    <span style="color: var(--vm-success); font-weight: 700;">● {{ translate('In Stock') }} ({{ $product->current_stock }} {{ translate('units') }})</span>
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
                @if($product->added_by == 'admin')
                    <div class="vm-fulfillment-item">
                        <span class="vm-fulfillment-icon">🏬</span>
                        <div>
                            <strong>{{ translate('Platform In-Shop Pickup (Uyo Central Hub)') }}</strong>
                            <p style="font-size: 12px; color: var(--vm-text-muted); margin-top: 1px;">
                                {{ translate('Managed directly by Victorious MARKET. Inspect and pick up your items physically at Uyo Flagship Hub with zero delivery fee.') }}
                            </p>
                        </div>
                    </div>
                    <div class="vm-fulfillment-item" style="border-top: 1px solid var(--vm-border-light); padding-top: 8px;">
                        <span class="vm-fulfillment-icon">🚚</span>
                        <div>
                            <strong>{{ translate('Direct Platform Delivery') }}</strong>
                            <p style="font-size: 12px; color: var(--vm-text-muted); margin-top: 1px;">
                                {{ translate('Dispatched from Uyo Central Logistics directly to any destination in Akwa Ibom across active delivery lanes.') }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="vm-fulfillment-item">
                        <span class="vm-fulfillment-icon">🏪</span>
                        <div>
                            <strong>{{ translate('Independent Merchant Store') }}: {{ $shopName }}</strong>
                            <p style="font-size: 12px; color: var(--vm-text-muted); margin-top: 1px;">
                                {{ translate('Dispatched from merchant premises in') }} <strong>{{ $shop?->lga?->name ?? ($shop?->address ?? 'Akwa Ibom') }}</strong> {{ translate('across verified logistics lanes.') }}
                            </p>
                        </div>
                    </div>
                    <div class="vm-fulfillment-item" style="border-top: 1px solid var(--vm-border-light); padding-top: 8px;">
                        <span class="vm-fulfillment-icon">🏬</span>
                        <div>
                            <strong>{{ translate('Merchant Counter Pickup Eligible') }}</strong>
                            <p style="font-size: 12px; color: var(--vm-text-muted); margin-top: 1px;">
                                {{ translate('Reserve online and visit') }} <strong>{{ $shopName }}</strong> {{ translate('in') }} {{ $shop?->lga?->name ?? 'store' }} {{ translate('to inspect items at their retail counter.') }}
                            </p>
                        </div>
                    </div>
                @endif

                <div class="vm-fulfillment-item" style="border-top: 1px solid var(--vm-border-light); padding-top: 8px;">
                    <span class="vm-fulfillment-icon">🛡️</span>
                    <div>
                        <strong style="color: #16a34a;">{{ translate('Paystack Escrow Buyer Protection') }}</strong>
                        <p style="font-size: 12px; color: var(--vm-text-muted); margin-top: 1px;">
                            {{ translate('100% Secure. Funds held safely in platform escrow until you inspect, receive, and confirm your order.') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quantity & Actions Form -->
            <form action="{{ route('cart.add') }}" method="POST" class="add-to-cart-details-form addToCartDynamicForm" id="add-to-cart-form">
                @csrf
                <input type="hidden" name="id" value="{{ $product->id }}">
                
                <div style="display: flex; align-items: center; gap: 16px; margin: 12px 0 16px;">
                    <span style="font-size: 14px; font-weight: 600;">{{ translate('Quantity') }}:</span>
                    <div style="display: flex; align-items: center; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); overflow: hidden; background: #FFFFFF;">
                        <button type="button" class="btn-number" data-type="minus" data-field="quantity" id="vmQtyMinus" style="padding: 8px 14px; background: transparent; cursor: pointer; font-weight: 700; font-size: 16px;">-</button>
                        <input type="number" name="quantity" id="vmQtyInput" class="input-number" value="{{ $product->minimum_order_qty ?? 1 }}" min="{{ $product->minimum_order_qty ?? 1 }}" max="{{ $product->current_stock }}" style="width: 50px; text-align: center; font-weight: 700; font-size: 14px;" readonly>
                        <button type="button" class="btn-number" data-type="plus" data-field="quantity" id="vmQtyPlus" style="padding: 8px 14px; background: transparent; cursor: pointer; font-weight: 700; font-size: 16px;">+</button>
                    </div>
                </div>

                <div class="vm-detail-actions" style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="button" class="vm-btn-primary product-add-to-cart-button add-to-cart" data-form=".addToCartDynamicForm" data-update="{{ translate('Update_Cart') }}" data-add="{{ translate('Add_to_Cart') }}" {{ !$inStock ? 'disabled style=opacity:0.5;cursor:not-allowed;' : '' }}>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span>{{ translate('Add to Cart') }}</span>
                    </button>

                    <button type="button" class="vm-btn-gold product-buy-now-button" data-form=".addToCartDynamicForm" data-auth="{{( getWebConfig(name: 'guest_checkout') == 1 || Auth::guard('customer')->check() ? 'true':'false')}}" data-route="{{ route('shop-cart') }}" data-url="{{ route('checkout-details') }}" {{ !$inStock ? 'disabled style=opacity:0.5;cursor:not-allowed;' : '' }}>
                        <span>⚡ {{ translate('Buy Now') }}</span>
                    </button>
                    
                    @if($shop && $shopSlug)
                        <a href="{{ route('vendor-shop', $shopSlug) }}" class="vm-btn-outline" style="padding: 10px 16px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 13.5px; font-weight: 700; color: var(--vm-dark); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <span>🏪 {{ translate('Store') }}</span>
                        </a>
                    @endif
                </div>
            </form>

        </div>
    </div>

    <!-- Product Description & Specifications -->
    <div style="background: var(--vm-surface); border: 1px solid var(--vm-border); border-radius: var(--vm-radius-lg); padding: 32px; margin-bottom: 24px;">
        <h2 style="font-size: 18px; font-weight: 800; color: var(--vm-dark); margin-bottom: 16px; border-bottom: 2px solid var(--vm-primary-light); padding-bottom: 8px;">
            {{ translate('Product Details & Overview') }}
        </h2>
        <div style="font-size: 14.5px; line-height: 1.8; color: var(--vm-text);">
            {!! $product->details !!}
        </div>
    </div>

    <!-- Verified Customer Reviews Section -->
    <div style="background: var(--vm-surface); border: 1px solid var(--vm-border); border-radius: var(--vm-radius-lg); padding: 32px; margin-bottom: 48px;" id="product-reviews-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 2px solid var(--vm-primary-light); padding-bottom: 12px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 20px; font-weight: 800; color: var(--vm-dark); margin: 0;">
                    {{ translate('Customer Reviews & Ratings') }}
                </h2>
                <p style="font-size: 13px; color: var(--vm-text-muted); margin: 4px 0 0 0;">
                    {{ translate('Real feedback from verified purchasers who ordered and received this product.') }}
                </p>
            </div>
            <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(30,10,60,0.05); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; color: var(--vm-primary);">
                <span>🔒 {{ translate('Verified Orders Only') }}</span>
            </div>
        </div>

        @php
            $totalReviewsCount = $overallRating[1] ?? 0;
            $avgRatingVal = (float)($overallRating[0] ?? 0);
        @endphp

        <!-- Ratings Summary Block -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; background: var(--vm-bg); border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 24px; margin-bottom: 32px;">
            <!-- Score Column -->
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; border-right: 1px solid var(--vm-border); padding-right: 16px;">
                <div style="font-size: 48px; font-weight: 900; color: var(--vm-dark); line-height: 1;">
                    {{ number_format($avgRatingVal, 1) }}
                </div>
                <div style="margin: 8px 0; color: #D4AF37; font-size: 18px;">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= round($avgRatingVal))
                            ★
                        @else
                            ☆
                        @endif
                    @endfor
                </div>
                <div style="font-size: 13.5px; color: var(--vm-text-muted); font-weight: 600;">
                    {{ $totalReviewsCount }} {{ translate('verified ratings') }}
                </div>
            </div>

            <!-- Distribution Progress Bars -->
            <div style="display: flex; flex-direction: column; justify-content: center; gap: 8px;">
                @php
                    $starCounts = [
                        5 => $rating[0] ?? 0,
                        4 => $rating[1] ?? 0,
                        3 => $rating[2] ?? 0,
                        2 => $rating[3] ?? 0,
                        1 => $rating[4] ?? 0,
                    ];
                @endphp
                @foreach([5, 4, 3, 2, 1] as $star)
                    @php
                        $cnt = $starCounts[$star] ?? 0;
                        $pct = $totalReviewsCount > 0 ? round(($cnt / $totalReviewsCount) * 100) : 0;
                    @endphp
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 12.5px;">
                        <span style="width: 45px; font-weight: 600; color: var(--vm-text);">{{ $star }} ★</span>
                        <div style="flex: 1; height: 8px; background: #E5E7EB; border-radius: 4px; overflow: hidden;">
                            <div style="width: {{ $pct }}%; height: 100%; background: #D4AF37; border-radius: 4px;"></div>
                        </div>
                        <span style="width: 35px; text-align: right; color: var(--vm-text-muted); font-weight: 600;">{{ $cnt }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Reviews List -->
        @if(isset($productReviews) && $productReviews->count() > 0)
            <div style="display: flex; flex-direction: column; gap: 20px;">
                @foreach($productReviews as $review)
                    @php
                        $customer = $review->customer;
                        $customerName = $customer ? ($customer->f_name . ' ' . $customer->l_name) : translate('Verified Customer');
                        $avatarUrl = $customer?->image_full_url ? getStorageImages(path: $customer->image_full_url, type: 'avatar') : null;
                    @endphp
                    <div style="border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 20px; background: #FFFFFF;">
                        <!-- Review Header -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--vm-primary-light); color: var(--vm-primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; overflow: hidden;">
                                    @if($avatarUrl)
                                        <img src="{{ $avatarUrl }}" alt="{{ $customerName }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        {{ strtoupper(substr($customerName, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <div style="font-size: 14.5px; font-weight: 700; color: var(--vm-dark); display: flex; align-items: center; gap: 6px;">
                                        <span>{{ $customerName }}</span>
                                        <span style="font-size: 11px; background: rgba(22, 163, 74, 0.1); color: #16a34a; font-weight: 700; padding: 2px 7px; border-radius: 4px;">✓ {{ translate('Verified Purchase') }}</span>
                                    </div>
                                    <div style="font-size: 12px; color: var(--vm-text-muted);">
                                        {{ $review->created_at ? $review->created_at->format('M d, Y') : '' }}
                                    </div>
                                </div>
                            </div>
                            <div style="color: #D4AF37; font-size: 16px;">
                                @for($s = 1; $s <= 5; $s++)
                                    @if($s <= $review->rating)
                                        ★
                                    @else
                                        ☆
                                    @endif
                                @endfor
                            </div>
                        </div>

                        <!-- Review Comment -->
                        <div style="font-size: 14px; line-height: 1.6; color: var(--vm-text); margin-bottom: 12px;">
                            {{ $review->comment }}
                        </div>

                        <!-- Photo Attachments (if any) -->
                        @if(!empty($review->attachment_full_url) && count($review->attachment_full_url) > 0)
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
                                @foreach($review->attachment_full_url as $photo)
                                    <a href="{{ $photo['path'] ?? '#' }}" target="_blank" style="display: block; width: 68px; height: 68px; border-radius: 6px; overflow: hidden; border: 1px solid var(--vm-border);">
                                        <img src="{{ $photo['path'] ?? '' }}" alt="Review photo" style="width: 100%; height: 100%; object-fit: cover;">
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <!-- Seller / Store Response (if any) -->
                        @if($review->reply)
                            <div style="background: var(--vm-bg); border-left: 3px solid var(--vm-primary); border-radius: 4px; padding: 12px 16px; margin-top: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                    <strong style="font-size: 13px; color: var(--vm-primary);">
                                        🏪 {{ translate('Store Response') }}
                                    </strong>
                                    <span style="font-size: 11.5px; color: var(--vm-text-muted);">
                                        {{ $review->reply->created_at ? $review->reply->created_at->format('M d, Y') : '' }}
                                    </span>
                                </div>
                                <div style="font-size: 13px; color: var(--vm-text); line-height: 1.5;">
                                    {{ $review->reply->reply_text }}
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                <!-- Pagination -->
                <div style="margin-top: 16px;">
                    {{ $productReviews->fragment('product-reviews-section')->links() }}
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div style="text-align: center; padding: 40px 20px; background: var(--vm-bg); border-radius: var(--vm-radius-md); border: 1px dashed var(--vm-border);">
                <div style="font-size: 36px; margin-bottom: 10px;">⭐</div>
                <h3 style="font-size: 16px; font-weight: 700; color: var(--vm-dark); margin-bottom: 6px;">
                    {{ translate('No reviews yet for this product') }}
                </h3>
                <p style="font-size: 13.5px; color: var(--vm-text-muted); max-width: 480px; margin: 0 auto;">
                    {{ translate('Only verified shoppers who bought and received this item can write a review. Check back after verified orders are fulfilled!') }}
                </p>
            </div>
        @endif
    </div>

</div>
@endsection
