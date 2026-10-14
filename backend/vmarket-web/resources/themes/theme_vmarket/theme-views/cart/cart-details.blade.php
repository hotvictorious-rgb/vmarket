@php
    use App\Models\Cart;
    use App\Utils\OrderManager;
    use App\Utils\ProductManager;
    use App\Utils\CartManager;
    use function App\Utils\get_shop_name;
    // [AI] VM-CUST-003: Canonical cart — delivery fees resolved downstream via FulfillmentAvailabilityService + DeliveryLane.
    $cart = CartManager::getCartListQuery()->groupBy('cart_group_id');
@endphp

<div class="container py-3">
    {{-- 3-Step Checkout Flow Tracker --}}
    <div class="d-flex justify-content-center mb-4">
        <ul class="cart-step-list">
            <li class="current cursor-pointer get-view-by-onclick" data-link="{{ route('shop-cart') }}">
                <span><i class="bi bi-cart3"></i></span> {{ translate('1. Cart Review') }}
            </li>
            <li class="cursor-pointer text-capitalize" data-link="{{ route('checkout-details') }}">
                <span><i class="bi bi-geo-alt"></i></span> {{ translate('2. Shipping & Inspection') }}
            </li>
            <li>
                <span><i class="bi bi-shield-check"></i></span> {{ translate('3. Escrow Payment') }}
            </li>
        </ul>
    </div>

    @if($cart->count() == 0)
        {{-- Empty Cart State --}}
        <div class="vm-merchant-card py-5 px-4 text-center">
            <div class="d-flex flex-column align-items-center justify-content-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center"
                     style="width: 88px; height: 88px; background: rgba(94, 23, 235, 0.08); color: #5E17EB;">
                    <i class="bi bi-bag-x" style="font-size: 40px;"></i>
                </div>
                <div>
                    <h4 class="fw-bold m-0" style="color: #1B1035;">{{ translate('Your Shopping Cart is Empty') }}</h4>
                    <p class="text-muted fs-14 mt-2 mb-0" style="max-width: 460px; margin: 0 auto;">
                        {{ translate('Explore verified merchants across Akwa Ibom with guaranteed physical in-shop counter inspection and swift directional logistics.') }}
                    </p>
                </div>
                <a href="{{ route('products') }}" class="vm-checkout-cta-btn mt-3" style="max-width: 240px;">
                    <i class="bi bi-shop me-1"></i> {{ translate('Explore Catalog') }}
                </a>
            </div>
        </div>
    @else
        <form action="javascript:" id="cart-form">
            <div class="row gy-4">
                {{-- Left Column: Merchant Groups & Cart Items --}}
                <div class="col-lg-8">
                    @foreach($cart as $group_key => $group)
                        @php
                            $physical_product = false;
                            foreach ($group as $row) {
                                if ($row->product_type == 'physical' && $row->is_checked) {
                                    $physical_product = true;
                                }
                            }
                            $firstItem = $group[0];
                            $verify_status = OrderManager::verifyCartListMinimumOrderAmount(request(), $group_key);

                            $cartShipping = \App\Models\CartShipping::where('cart_group_id', $group_key)->first();
                            $activeMethodId = $cartShipping?->shipping_method_id ?? 2;
                            $isPickupSelected = in_array((int)$activeMethodId, [3, 9]);
                            $currentShippingFee = $isPickupSelected ? 0.00 : ($cartShipping?->shipping_cost ?? 600.00);

                            if ($firstItem->seller_is == 'admin') {
                                $originLgaId = (int) (\App\Models\Shop::where('seller_id', 0)->first()?->lga_id ?: 69);
                                $shopName = getInHouseShopConfig(key: 'name');
                                $shopSlug = getInHouseShopConfig(key: 'slug');
                            } else {
                                $originLgaId = (int) (\App\Models\Shop::where('seller_id', $firstItem->seller_id)->first()?->lga_id ?: 69);
                                $shopName = get_shop_name($firstItem['seller_id']);
                                $shopSlug = $firstItem->seller->shop['slug'] ?? '';
                            }
                            $destLgaId = (int) session('customer_lga_id', 69);
                            $doorstepLaneFee = \App\Models\DeliveryLane::getDeliveryFee($originLgaId, $destLgaId) ?? 600.00;
                        @endphp

                        <div class="vm-merchant-card cart_information" id="cart-group-{{ $group_key }}">
                            {{-- Merchant Header --}}
                            <div class="vm-merchant-header">
                                <div class="vm-merchant-title-wrap">
                                    <input type="checkbox"
                                           class="shop-head-check shop-head-check-desktop shop-head-check-mobile vm-cart-check"
                                           title="{{ translate('Select all items from this merchant') }}">
                                    @if($firstItem->seller_is == 'admin')
                                        <a href="{{ route('vendor-shop', ['slug' => $shopSlug]) }}" class="text-decoration-none">
                                            <h5 class="vm-merchant-name">
                                                <span>👑</span> {{ $shopName }}
                                            </h5>
                                        </a>
                                        <span class="vm-merchant-badge vm-merchant-badge-flagship">
                                            <i class="bi bi-shield-check"></i> {{ translate('Official Flagship • Platform Managed') }}
                                        </span>
                                    @else
                                        <a href="{{ route('vendor-shop', ['slug' => $shopSlug]) }}" class="text-decoration-none">
                                            <h5 class="vm-merchant-name">
                                                <span>🏪</span> {{ $shopName ?: translate('Verified Merchant') }}
                                            </h5>
                                        </a>
                                        <span class="vm-merchant-badge vm-merchant-badge-vendor">
                                            <i class="bi bi-patch-check-fill"></i> {{ translate('Verified Merchant • Uyo Regional Hub') }}
                                        </span>
                                    @endif

                                    @if ($verify_status['minimum_order_amount'] > $verify_status['amount'])
                                        <span class="badge bg-danger-subtle text-danger fs-11"
                                              data-bs-toggle="tooltip"
                                              data-bs-title="{{ translate('minimum_Order_Amount') }} {{ webCurrencyConverter($verify_status['minimum_order_amount']) }} {{ translate('for') }} {{ $shopName }}">
                                            <i class="bi bi-exclamation-circle me-1"></i> {{ translate('Min. Order') }}: {{ webCurrencyConverter($verify_status['minimum_order_amount']) }}
                                        </span>
                                    @endif
                                </div>

                                <div class="fs-12 text-muted d-flex align-items-center gap-1">
                                    <i class="bi bi-geo-alt text-primary"></i>
                                    <span>{{ session('customer_city', 'Uyo') }}</span>
                                    <a href="javascript:" data-bs-toggle="modal" data-bs-target="#locationModal" class="text-primary fw-semibold text-decoration-none ms-1">
                                        {{ translate('Change') }}
                                    </a>
                                </div>
                            </div>

                            {{-- Fulfillment Selector (Doorstep vs In-Store Pickup) --}}
                            <div class="vm-fulfillment-section">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <span class="fs-12 fw-bold text-uppercase" style="color: #475569; letter-spacing: 0.5px;">
                                        <i class="bi bi-truck me-1 text-primary"></i> {{ translate('Fulfillment Method for this Store') }}:
                                    </span>
                                    <span class="fs-12">
                                        <span class="text-muted">{{ translate('Selected Fee') }}:</span>
                                        <strong class="fs-13 fw-bold {{ $isPickupSelected ? 'text-success' : 'text-primary' }}">
                                            {{ $isPickupSelected ? translate('FREE (₦0.00)') : webCurrencyConverter($doorstepLaneFee) }}
                                        </strong>
                                    </span>
                                </div>

                                <div class="vm-fulfillment-grid">
                                    {{-- Option 1: Doorstep Delivery --}}
                                    <div class="vm-fulfillment-card set-shipping-id {{ !$isPickupSelected ? 'active-doorstep' : '' }}"
                                         data-id="2"
                                         data-cart-group="{{ $group_key }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="vm-fulfillment-radio">
                                                @if(!$isPickupSelected)
                                                    <div class="vm-fulfillment-radio-dot"></div>
                                                @endif
                                            </div>
                                            <div class="vm-fulfillment-info">
                                                <h6 class="vm-fulfillment-title">🚚 {{ translate('Doorstep Delivery') }}</h6>
                                                <div class="vm-fulfillment-subtitle">
                                                    {{ translate('Express Courier') }} • 1-2 {{ translate('hours') }}
                                                </div>
                                            </div>
                                        </div>
                                        <span class="vm-fulfillment-badge" style="background: rgba(94, 23, 235, 0.1); color: #5E17EB;">
                                            {{ webCurrencyConverter($doorstepLaneFee) }}
                                        </span>
                                    </div>

                                    {{-- Option 2: In-Store Counter Inspection & Pickup --}}
                                    <div class="vm-fulfillment-card set-shipping-id {{ $isPickupSelected ? 'active-pickup' : '' }}"
                                         data-id="3"
                                         data-cart-group="{{ $group_key }}">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="vm-fulfillment-radio">
                                                @if($isPickupSelected)
                                                    <div class="vm-fulfillment-radio-dot"></div>
                                                @endif
                                            </div>
                                            <div class="vm-fulfillment-info">
                                                <h6 class="vm-fulfillment-title">🏬 {{ translate('In-Store Counter Pickup') }}</h6>
                                                <div class="vm-fulfillment-subtitle">
                                                    {{ $firstItem->seller_is == 'admin' ? translate('Uyo Flagship Counter') : translate('Merchant Physical Counter') }} • {{ translate('Inspect before payment') }}
                                                </div>
                                            </div>
                                        </div>
                                        <span class="vm-fulfillment-badge" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
                                            {{ translate('FREE (₦0.00)') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Item Rows List --}}
                            <div class="vm-cart-items-wrapper">
                                @foreach($group as $cart_key => $cartItem)
                                    @php
                                        $product = $cartItem->allProducts ?: $cartItem;
                                        $getProductCurrentStock = $product->current_stock;
                                        if (!empty($product->variation)) {
                                            foreach (json_decode($product->variation, true) as $productVariantSingle) {
                                                if ($productVariantSingle['type'] == $cartItem->variant) {
                                                    $getProductCurrentStock = $productVariantSingle['qty'];
                                                }
                                            }
                                        }

                                        $checkProductStatus = $cartItem->allProducts?->status ?? 0;
                                        if ($cartItem->seller_is == 'admin' && (checkVendorAbility(type: 'inhouse', status: 'temporary_close') || checkVendorAbility(type: 'inhouse', status: 'vacation_status'))) {
                                            $checkProductStatus = 0;
                                        } elseif ($cartItem->seller_is == 'seller' && (checkVendorAbility(type: 'vendor', status: 'temporary_close', vendor: $cartItem->allProducts->seller->shop ?? null) || checkVendorAbility(type: 'vendor', status: 'vacation_status', vendor: $cartItem->allProducts->seller->shop ?? null))) {
                                            $checkProductStatus = 0;
                                        }

                                        $isAvailable = ($checkProductStatus == 1 && $product->product_type == 'physical' ? ($getProductCurrentStock >= $cartItem['quantity']) : true);
                                    @endphp

                                    <div class="vm-cart-item" id="cart-item-{{ $cartItem['id'] }}">
                                        {{-- Checkbox --}}
                                        <div>
                                            <input type="checkbox"
                                                   class="shop-item-check shop-item-check-desktop shop-item-check-mobile vm-cart-check"
                                                   value="{{ $cartItem['id'] }}"
                                                   {{ $cartItem['is_checked'] ? 'checked' : '' }}
                                                   title="{{ translate('Select item') }}">
                                        </div>

                                        {{-- Product Thumbnail --}}
                                        <div class="vm-cart-thumb">
                                            <img alt="{{ $cartItem['name'] }}"
                                                 src="{{ getStorageImages(path: $cartItem->product->thumbnail_full_url ?? null, type: 'product') }}"
                                                 class="{{ !$isAvailable ? 'opacity-50' : '' }}">
                                            @if(!$isAvailable)
                                                <span class="position-absolute top-50 start-50 translate-middle badge bg-dark text-white fs-10 px-1 py-1">
                                                    {{ translate('Out of Stock') }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Product Details --}}
                                        <div class="vm-cart-info">
                                            <h6 class="vm-cart-title">
                                                <a href="{{ $checkProductStatus ? route('product', $cartItem['slug']) : 'javascript:' }}">
                                                    {{ $cartItem['name'] }}
                                                </a>
                                            </h6>

                                            @if(!empty($cartItem['variant']))
                                                <span class="vm-cart-variant-pill">
                                                    <i class="bi bi-tag me-1"></i> {{ $cartItem['variant'] }}
                                                </span>
                                            @endif

                                            <div class="vm-cart-unit-price">
                                                {{ translate('Unit') }}: <strong class="text-dark">{{ webCurrencyConverter($cartItem['price']) }}</strong>
                                                <span class="mx-1 text-muted">•</span>
                                                <span>{{ translate('Qty') }}: <strong class="text-primary">{{ $cartItem['quantity'] }}</strong></span>
                                            </div>

                                            <div class="vm-cart-guarantee-tag">
                                                <i class="bi bi-shield-check"></i>
                                                <span>{{ $isPickupSelected ? translate('Physical Counter Inspection Guarantee') : translate('Directional LGA Dispatch Guarantee') }}</span>
                                            </div>
                                        </div>

                                        {{-- Quantity Stepper --}}
                                        <div class="d-none d-md-block">
                                            @if ($checkProductStatus == 1)
                                                <div class="vm-stepper">
                                                    <button type="button"
                                                            class="vm-stepper-btn update-cart-quantity-list-cart-data update-cart-quantity-list-mobile-cart-data {{ ($cartItem['quantity'] <= ($product->minimum_order_qty ?? 1)) ? 'is-delete' : '' }}"
                                                            data-cart="{{ $cartItem['id'] }}"
                                                            data-value="-1"
                                                            data-min-order="{{ $product->minimum_order_qty ?? 1 }}"
                                                            data-action="{{ ($cartItem['quantity'] <= ($product->minimum_order_qty ?? 1)) ? 'delete' : 'minus' }}"
                                                            data-prevent="true"
                                                            title="{{ ($cartItem['quantity'] <= ($product->minimum_order_qty ?? 1)) ? translate('Remove from cart') : translate('Decrease quantity') }}">
                                                        <i class="bi {{ ($cartItem['quantity'] <= ($product->minimum_order_qty ?? 1)) ? 'bi-trash3' : 'bi-dash' }}"></i>
                                                    </button>

                                                    <input type="text"
                                                           class="vm-stepper-input update-cart-quantity-list-cart-data-input update-cart-quantity-list-mobile-cart-data-input"
                                                           id="cartQuantityWeb{{ $cartItem['id'] }}"
                                                           value="{{ $cartItem['quantity'] }}"
                                                           data-cart="{{ $cartItem['id'] }}"
                                                           data-value="0"
                                                           data-min-order="{{ $product->minimum_order_qty ?? 1 }}"
                                                           data-min="{{ $product->minimum_order_qty ?? 1 }}"
                                                           data-current-stock="{{ $getProductCurrentStock }}"
                                                           name="quantity">
                                                    <input type="hidden" id="cartQuantityMobile{{ $cartItem['id'] }}" value="{{ $cartItem['quantity'] }}">

                                                    <button type="button"
                                                            class="vm-stepper-btn update-cart-quantity-list-cart-data update-cart-quantity-list-mobile-cart-data"
                                                            data-cart="{{ $cartItem['id'] }}"
                                                            data-value="1"
                                                            data-min-order="{{ $product->minimum_order_qty ?? 1 }}"
                                                            data-action="plus"
                                                            data-prevent="true"
                                                            title="{{ translate('Increase quantity') }}">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                            @else
                                                <button type="button"
                                                        class="vm-stepper-btn is-delete update-cart-quantity-list-cart-data"
                                                        data-cart="{{ $cartItem['id'] }}"
                                                        data-value="-1"
                                                        data-action="delete"
                                                        data-prevent="true">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                                <input type="hidden" id="cartQuantityWeb{{ $cartItem['id'] }}" value="{{ $cartItem['quantity'] }}">
                                                <input type="hidden" id="cartQuantityMobile{{ $cartItem['id'] }}" value="{{ $cartItem['quantity'] }}">
                                            @endif
                                        </div>

                                        {{-- Item Total & Discount --}}
                                        <div class="vm-cart-total-col d-none d-md-block">
                                            <div class="vm-cart-item-total">
                                                {{ webCurrencyConverter(($cartItem['price'] - $cartItem['discount']) * $cartItem['quantity']) }}
                                            </div>
                                            @if($cartItem['discount'] > 0)
                                                <span class="vm-cart-discount-pill">
                                                    -{{ webCurrencyConverter($cartItem['discount'] * $cartItem['quantity']) }}
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Delete Button (Desktop) --}}
                                        <div class="d-none d-md-block">
                                            <button type="button"
                                                    class="vm-cart-del-btn"
                                                    onclick="removeProductFromCartList({{ $cartItem['id'] }})"
                                                    title="{{ translate('Remove from cart') }}">
                                                <i class="bi bi-trash3 fs-15"></i>
                                            </button>
                                        </div>

                                        {{-- Mobile Controls Row (< 768px) --}}
                                        <div class="vm-cart-controls-mobile d-md-none">
                                            <div class="vm-stepper">
                                                <button type="button"
                                                        class="vm-stepper-btn update-cart-quantity-list-cart-data update-cart-quantity-list-mobile-cart-data {{ ($cartItem['quantity'] <= ($product->minimum_order_qty ?? 1)) ? 'is-delete' : '' }}"
                                                        data-cart="{{ $cartItem['id'] }}"
                                                        data-value="-1"
                                                        data-min-order="{{ $product->minimum_order_qty ?? 1 }}"
                                                        data-action="{{ ($cartItem['quantity'] <= ($product->minimum_order_qty ?? 1)) ? 'delete' : 'minus' }}"
                                                        data-prevent="true">
                                                    <i class="bi {{ ($cartItem['quantity'] <= ($product->minimum_order_qty ?? 1)) ? 'bi-trash3' : 'bi-dash' }}"></i>
                                                </button>

                                                <input type="text"
                                                       class="vm-stepper-input update-cart-quantity-list-cart-data-input update-cart-quantity-list-mobile-cart-data-input"
                                                       id="cartQuantityMobile{{ $cartItem['id'] }}"
                                                       value="{{ $cartItem['quantity'] }}"
                                                       data-cart="{{ $cartItem['id'] }}"
                                                       data-value="0"
                                                       data-min-order="{{ $product->minimum_order_qty ?? 1 }}"
                                                       data-min="{{ $product->minimum_order_qty ?? 1 }}"
                                                       data-current-stock="{{ $getProductCurrentStock }}"
                                                       aria-label="{{ translate('Quantity') }}">

                                                <button type="button"
                                                        class="vm-stepper-btn update-cart-quantity-list-cart-data update-cart-quantity-list-mobile-cart-data"
                                                        data-cart="{{ $cartItem['id'] }}"
                                                        data-value="1"
                                                        data-min-order="{{ $product->minimum_order_qty ?? 1 }}"
                                                        data-action="plus"
                                                        data-prevent="true">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>

                                            <div class="d-flex align-items-center gap-3">
                                                <div class="text-end">
                                                    <div class="vm-cart-item-total">
                                                        {{ webCurrencyConverter(($cartItem['price'] - $cartItem['discount']) * $cartItem['quantity']) }}
                                                    </div>
                                                    @if($cartItem['discount'] > 0)
                                                        <span class="vm-cart-discount-pill">
                                                            -{{ webCurrencyConverter($cartItem['discount'] * $cartItem['quantity']) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <button type="button"
                                                        class="vm-cart-del-btn"
                                                        onclick="removeProductFromCartList({{ $cartItem['id'] }})"
                                                        title="{{ translate('Remove') }}">
                                                    <i class="bi bi-trash3 fs-15"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Free Delivery Threshold Banner (if configured) --}}
                            @php($free_delivery_status = OrderManager::getFreeDeliveryOrderAmountArray($group[0]->cart_group_id))
                            @if ($free_delivery_status['status'])
                                <div class="px-4 py-3 bg-light border-top">
                                    <div class="d-flex align-items-center justify-content-between mb-2 fs-12">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-gift-fill text-warning fs-14"></i>
                                            @if ($free_delivery_status['amount_need'] <= 0)
                                                <span class="fw-bold text-success">{{ translate('You qualified for FREE Directional Delivery!') }}</span>
                                            @else
                                                <span>{{ translate('Add') }} <strong class="text-primary">{{ webCurrencyConverter($free_delivery_status['amount_need']) }}</strong> {{ translate('more for FREE delivery') }}</span>
                                            @endif
                                        </div>
                                        <span class="fw-bold text-muted">{{ (int)$free_delivery_status['percentage'] }}%</span>
                                    </div>
                                    <div class="progress" style="height: 6px; border-radius: 999px;">
                                        <div class="progress-bar {{ $free_delivery_status['percentage'] == 100 ? 'bg-success' : 'bg-primary' }}"
                                             style="width: {{ $free_delivery_status['percentage'] }}%;"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Order Note Card --}}
                    <div class="vm-merchant-card p-3 mb-0">
                        <label for="order_note" class="form-label fw-bold fs-13 mb-2" style="color: #1B1035;">
                            <i class="bi bi-chat-left-text me-1 text-primary"></i> {{ translate('Order Instructions / Notes') }}
                            <span class="text-muted fw-normal fs-12">({{ translate('optional') }})</span>
                        </label>
                        <textarea class="form-control"
                                  rows="2"
                                  id="order_note"
                                  name="order_note"
                                  placeholder="{{ translate('e.g. Please call before arrival or inspect seal carefully at counter...') }}"
                                  style="border-radius: 10px; font-size: 13.5px; border-color: #E2E8F0;">{{ session('order_note') }}</textarea>
                    </div>
                </div>

                {{-- Right Column: Order Summary Card --}}
                @include('theme-views.partials._order-summery')
            </div>
        </form>
    @endif
</div>

@push('script')
    <script src="{{ theme_asset('assets/js/cart.js') }}"></script>
@endpush
