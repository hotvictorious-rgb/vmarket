@extends('theme-views.layouts.app')

@section('title', ($pageTitleContent ?? ($pageTitle ?? translate('All Products'))) . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 20px 16px 56px;">

    @php
        $activeCity = session('customer_city', 'Abak');
        $activeLgaId = session('customer_lga_id', 39);
        $currentSort = request('sort_by', 'latest');
    @endphp

    <!-- LGA Deliverability & Escrow Protection Strip -->
    <div style="background: linear-gradient(135deg, rgba(94, 23, 235, 0.06), rgba(255, 215, 0, 0.08)); border: 1.5px solid rgba(94, 23, 235, 0.15); border-radius: var(--vm-radius-md); padding: 14px 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--vm-primary); color: #FFFFFF; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; box-shadow: 0 4px 10px rgba(94, 23, 235, 0.25);">
                📍
            </div>
            <div>
                <div style="font-size: 14px; font-weight: 800; color: var(--vm-dark); display: flex; align-items: center; gap: 8px;">
                    <span>{{ translate('Delivering to') }}: <span style="color: var(--vm-primary);">{{ $activeCity }}, Akwa Ibom</span></span>
                    <button type="button" onclick="document.getElementById('vmHeaderLocationBtn')?.click() || document.getElementById('vmMobileLocationBtn')?.click()" style="background: none; border: none; color: var(--vm-primary); font-size: 12.5px; font-weight: 700; text-decoration: underline; cursor: pointer; padding: 0;">
                        {{ translate('Change LGA') }} ▾
                    </button>
                </div>
                <div style="font-size: 12px; color: var(--vm-text-muted); margin-top: 2px;">
                    {{ translate('All catalog products below are verified for delivery or physical in-shop inspection in your area.') }}
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <span style="display: inline-flex; align-items: center; gap: 6px; background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-full); padding: 5px 12px; font-size: 12px; font-weight: 700; color: #16a34a;">
                <span style="font-size: 14px;">🛡️</span> {{ translate('Paystack Escrow Protected') }}
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px; background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-full); padding: 5px 12px; font-size: 12px; font-weight: 700; color: var(--vm-primary);">
                <span style="font-size: 14px;">⚡</span> {{ translate('Same-Day LGA Dispatch') }}
            </span>
        </div>
    </div>

    <!-- Category Filter Pills Strip -->
    @if(isset($categories) && count($categories) > 0)
        <div style="margin-bottom: 24px;">
            <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 8px; -webkit-overflow-scrolling: touch; scrollbar-width: thin;">
                <a href="{{ route('products') }}" 
                   style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: var(--vm-radius-full); font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; transition: var(--vm-transition); {{ !request('category_id') && !request()->route('slug') ? 'background: var(--vm-primary); color: #FFFFFF; box-shadow: 0 4px 12px rgba(94, 23, 235, 0.25);' : 'background: #FFFFFF; color: var(--vm-dark); border: 1px solid var(--vm-border);' }}">
                    <span>🛍️</span>
                    <span>{{ translate('All Categories') }}</span>
                </a>
                @foreach($categories->take(10) as $cat)
                    @php
                        $isActive = request('category_id') == $cat->id || (request()->route('slug') == $cat->slug);
                    @endphp
                    <a href="{{ route('category-products', $cat->slug) }}" 
                       style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: var(--vm-radius-full); font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; transition: var(--vm-transition); {{ $isActive ? 'background: var(--vm-primary); color: #FFFFFF; box-shadow: 0 4px 12px rgba(94, 23, 235, 0.25);' : 'background: #FFFFFF; color: var(--vm-dark); border: 1px solid var(--vm-border);' }}">
                        <span>{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Controls Bar: Title, Count, Sort, Search -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1.5px solid var(--vm-border-light);">
        <div>
            <h1 style="font-size: 20px; font-weight: 800; color: var(--vm-dark); margin: 0; line-height: 1.3;">
                {{ $pageTitleContent ?? ($pageTitle ?? translate('Catalog Products')) }}
            </h1>
            <div style="font-size: 13px; color: var(--vm-text-muted); margin-top: 3px;">
                <strong>{{ $products->total() }}</strong> {{ translate('authentic products available') }}
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <!-- Sort Form -->
            <form action="{{ url()->current() }}" method="GET" id="vmSortForm" style="display: flex; align-items: center; gap: 8px;">
                @if(request('name'))
                    <input type="hidden" name="name" value="{{ request('name') }}">
                @endif
                <label for="vmSortSelect" style="font-size: 13px; font-weight: 700; color: var(--vm-text-muted); margin: 0; white-space: nowrap;">
                    {{ translate('Sort By') }}:
                </label>
                <select name="sort_by" id="vmSortSelect" onchange="document.getElementById('vmSortForm').submit()" style="padding: 7px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 13px; font-weight: 600; color: var(--vm-dark); background: #FFFFFF; cursor: pointer;">
                    <option value="latest" {{ $currentSort == 'latest' ? 'selected' : '' }}>{{ translate('Latest Arrivals') }}</option>
                    <option value="low-high" {{ $currentSort == 'low-high' ? 'selected' : '' }}>{{ translate('Price: Low to High') }}</option>
                    <option value="high-low" {{ $currentSort == 'high-low' ? 'selected' : '' }}>{{ translate('Price: High to Low') }}</option>
                    <option value="a-z" {{ $currentSort == 'a-z' ? 'selected' : '' }}>{{ translate('Alphabetical: A-Z') }}</option>
                </select>
            </form>

            <!-- Search Filter -->
            <form action="{{ url()->current() }}" method="GET" style="display: flex; gap: 6px;">
                <input type="text" 
                       name="name" 
                       value="{{ request('name') }}" 
                       placeholder="{{ translate('Filter catalog...') }}" 
                       style="background: #FFFFFF; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-full); padding: 7px 14px; font-size: 13px; width: 180px;">
                <button type="submit" class="vm-search-btn" style="padding: 7px 14px; border-radius: var(--vm-radius-full);">
                    {{ translate('Search') }}
                </button>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    @if($products->count() > 0)
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px;">
            @foreach($products as $product)
                @php
                    $price = $product->unit_price;
                    $discount = \App\Utils\Helpers::get_product_discount($product, $price);
                    $finalPrice = $price - $discount;
                    $shop = $product->seller?->shop;
                    $shopName = $shop?->name ?? (getWebConfig(name: 'company_name') ?? 'Victorious Flagship');
                    $shopSlug = $shop?->slug ?? '';
                    $inStock = $product->isMarketplacePurchasable() && $product->current_stock > 0;
                @endphp
                <div style="background: var(--vm-surface); border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-lg); overflow: hidden; display: flex; flex-direction: column; transition: all 0.25s ease; position: relative;"
                     onmouseover="this.style.borderColor='var(--vm-primary)'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(94, 23, 235, 0.12)'"
                     onmouseout="this.style.borderColor='var(--vm-border)'; this.style.transform='none'; this.style.boxShadow='none'">
                    
                    <!-- Top Merchant & Deliverability Strip -->
                    <div style="padding: 10px 14px 6px; display: flex; justify-content: space-between; align-items: center; font-size: 11.5px; background: rgba(0,0,0,0.015); border-bottom: 1px solid var(--vm-border-light);">
                        @if($shop && $shopSlug)
                            <a href="{{ route('vendor-shop', $shopSlug) }}" style="display: inline-flex; align-items: center; gap: 4px; color: var(--vm-text-muted); font-weight: 700; text-decoration: none;">
                                <span>🏪</span>
                                <span>{{ Str::limit($shopName, 18) }}</span>
                                <span style="color: #16a34a; font-size: 10px;">✓</span>
                            </a>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 4px; color: var(--vm-primary); font-weight: 700;">
                                <span>👑</span>
                                <span>{{ translate('Official Store') }}</span>
                            </span>
                        @endif

                        <span style="font-size: 11px; font-weight: 700; color: #16a34a; background: rgba(22, 163, 74, 0.08); padding: 2px 7px; border-radius: var(--vm-radius-full);">
                            🚚 LGA Delivery
                        </span>
                    </div>

                    <!-- Product Image -->
                    <a href="{{ route('product', $product->slug) }}" style="position: relative; display: block; aspect-ratio: 1 / 1; background: #FFFFFF; overflow: hidden; padding: 12px;">
                        <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}" 
                             alt="{{ $product->name }}" 
                             style="width: 100%; height: 100%; object-fit: contain; transition: transform 0.3s ease;"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform='none'"
                             loading="lazy">

                        @if($discount > 0)
                            <span style="position: absolute; top: 12px; left: 12px; background: #ef4444; color: #FFFFFF; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: var(--vm-radius-full); box-shadow: 0 2px 6px rgba(239, 68, 68, 0.3);">
                                @if ($product->discount_type == 'percent')
                                    -{{ round($product->discount) }}% OFF
                                @else
                                    -{{ webCurrencyConverter($discount) }}
                                @endif
                            </span>
                        @endif

                        @if($shop && $shop->pickup_enabled)
                            <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(94, 23, 235, 0.9); color: #FFFFFF; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: var(--vm-radius-full);">
                                🏪 Pickup Available
                            </span>
                        @endif
                    </a>

                    <!-- Product Info Body -->
                    <div style="padding: 14px; display: flex; flex-direction: column; flex-grow: 1; justify-content: space-between; gap: 10px;">
                        <div>
                            <!-- Star Rating -->
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--vm-text-muted); margin-bottom: 6px;">
                                <span style="color: #f59e0b; font-weight: 700;">★ 5.0</span>
                                <span>•</span>
                                <span>{{ $product->reviews_count ?? 1 }} {{ translate('verified') }}</span>
                            </div>

                            <!-- Title -->
                            <a href="{{ route('product', $product->slug) }}" 
                               style="font-size: 14px; font-weight: 700; color: var(--vm-dark); text-decoration: none; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; height: 39px;"
                               title="{{ $product->name }}">
                                {{ $product->name }}
                            </a>
                        </div>

                        <!-- Price & Actions Row -->
                        <div>
                            <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 12px;">
                                <span style="font-size: 17px; font-weight: 800; color: var(--vm-primary);">
                                    {{ webCurrencyConverter($finalPrice) }}
                                </span>
                                @if($discount > 0)
                                    <span style="font-size: 12.5px; color: var(--vm-text-muted); text-decoration: line-through;">
                                        {{ webCurrencyConverter($price) }}
                                    </span>
                                @endif
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <a href="{{ route('product', $product->slug) }}" 
                                   style="padding: 8px 10px; font-size: 12px; font-weight: 700; color: var(--vm-dark); background: #FFFFFF; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); text-decoration: none; text-align: center; transition: var(--vm-transition); display: flex; align-items: center; justify-content: center;"
                                   onmouseover="this.style.borderColor='var(--vm-primary)'; this.style.color='var(--vm-primary)'"
                                   onmouseout="this.style.borderColor='var(--vm-border)'; this.style.color='var(--vm-dark)'">
                                    {{ translate('Details') }}
                                </a>

                                <a href="{{ route('product', $product->slug) }}" 
                                   class="vm-btn-primary" 
                                   style="padding: 8px 10px; font-size: 12px; font-weight: 700; border-radius: var(--vm-radius-md); text-align: center; display: flex; align-items: center; justify-content: center; gap: 4px; box-shadow: none;">
                                    <span>⚡</span>
                                    <span>{{ translate('Buy Now') }}</span>
                                </a>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div style="margin-top: 36px; display: flex; justify-content: center;">
            {{ $products->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div style="text-align: center; padding: 64px 20px; background: #FFFFFF; border-radius: var(--vm-radius-lg); border: 1.5px solid var(--vm-border); box-shadow: var(--vm-shadow-sm);">
            <div style="font-size: 48px; margin-bottom: 12px;">🛍️</div>
            <h3 style="font-size: 18px; font-weight: 800; color: var(--vm-dark); margin-bottom: 6px;">
                {{ translate('No products found matching your filter') }}
            </h3>
            <p style="font-size: 13.5px; color: var(--vm-text-muted); max-width: 440px; margin: 0 auto 20px;">
                {{ translate('Try searching for different keywords or explore other categories available across Akwa Ibom.') }}
            </p>
            <a href="{{ route('products') }}" class="vm-btn-primary" style="display: inline-flex; width: auto; padding: 10px 24px;">
                {{ translate('Clear Filters & View All') }}
            </a>
        </div>
    @endif

</div>
@endsection
