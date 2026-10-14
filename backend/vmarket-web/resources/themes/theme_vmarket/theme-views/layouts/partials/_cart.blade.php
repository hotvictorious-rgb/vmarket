@php
    use App\Models\Product;
    use App\Utils\CartManager;
    use App\Utils\Helpers;
    use function App\Utils\get_shop_name;

    $cart = CartManager::getCartListQuery();
    $cartGrouped = $cart->groupBy('cart_group_id');
    $subTotal = 0;
    $totalDiscount = 0;
    foreach ($cart as $item) {
        $subTotal += ($item['price'] - $item['discount']) * $item['quantity'];
        $totalDiscount += $item['discount'] * $item['quantity'];
    }
@endphp

@if($cart->count() > 0)
    <div class="vm-cart-drawer-items-list">
        @foreach($cartGrouped as $groupId => $groupItems)
            @php
                $firstItem = $groupItems[0];
                if ($firstItem->seller_is == 'admin') {
                    $shopName = getInHouseShopConfig(key: 'name');
                    $shopSlug = getInHouseShopConfig(key: 'slug');
                } else {
                    $shopName = $firstItem->seller?->shop?->name ?: get_shop_name($firstItem['seller_id']);
                    $shopSlug = $firstItem->seller?->shop?->slug ?? '';
                }
            @endphp
            <div class="vm-drawer-merchant-group">
                <div class="vm-drawer-merchant-header">
                    <span class="vm-drawer-merchant-icon">🏪</span>
                    <a href="{{ $shopSlug ? route('vendor-shop', ['slug' => $shopSlug]) : 'javascript:' }}" class="vm-drawer-merchant-name text-decoration-none">
                        {{ $shopName }}
                    </a>
                    <span class="vm-drawer-merchant-verified" title="{{ translate('Verified Merchant • Akwa Ibom') }}">
                        <i class="bi bi-patch-check-fill"></i>
                    </span>
                </div>

                <div class="vm-drawer-items-sublist">
                    @foreach($groupItems as $cartItem)
                        @php
                            $product = $cartItem->product ?: $cartItem->allProducts;
                            $itemPrice = $cartItem['price'];
                            $itemDiscount = $cartItem['discount'];
                            $itemFinalPrice = ($itemPrice - $itemDiscount) * $cartItem['quantity'];
                            $currentStock = $product?->current_stock ?? 100;
                            if (!empty($product?->variation)) {
                                $variations = is_array($product->variation) ? $product->variation : json_decode($product->variation, true);
                                if (!empty($variations)) {
                                    foreach ($variations as $var) {
                                        if (isset($var['type']) && $var['type'] == $cartItem->variant) {
                                            $currentStock = $var['qty'] ?? $currentStock;
                                        }
                                    }
                                }
                            }
                        @endphp
                        <div class="vm-drawer-item-row" id="drawer-cart-item-{{ $cartItem['id'] }}">
                            <div class="vm-drawer-item-thumb" style="width: 60px; height: 60px; min-width: 60px; max-width: 60px; flex-shrink: 0; border-radius: 10px; overflow: hidden; background: #F8FAFC; border: 1px solid #E2E8F0; display: flex; align-items: center; justify-content: center;">
                                <img src="{{ getStorageImages(path: $product?->thumbnail_full_url ?? $cartItem['thumbnail_full_url'], type: 'product') }}"
                                     alt="{{ $cartItem['name'] }}"
                                     style="width: 100%; height: 100%; object-fit: cover;"
                                     class="rounded">
                            </div>
                            <div class="vm-drawer-item-details">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <a href="{{ route('product', $cartItem['slug']) }}" class="vm-drawer-item-title">
                                        {{ Str::limit($cartItem['name'], 46) }}
                                    </a>
                                    <button type="button"
                                            class="vm-drawer-item-del-btn"
                                            onclick="removeDrawerCartItem({{ $cartItem['id'] }})"
                                            title="{{ translate('Remove Item') }}">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>

                                @if(!empty($cartItem['variant']))
                                    <span class="vm-drawer-item-variant">{{ translate('Variant') }}: {{ $cartItem['variant'] }}</span>
                                @endif

                                <div class="d-flex justify-content-between align-items-center mt-2 pt-1">
                                    {{-- Live Stepper --}}
                                    <div class="vm-drawer-stepper">
                                        <button type="button"
                                                class="vm-drawer-step-btn"
                                                onclick="updateDrawerQuantity({{ $cartItem['id'] }}, {{ $cartItem['quantity'] - 1 }})"
                                                title="{{ $cartItem['quantity'] <= ($product?->minimum_order_qty ?? 1) ? translate('Remove') : translate('Decrease') }}">
                                            @if($cartItem['quantity'] <= ($product?->minimum_order_qty ?? 1))
                                                <i class="bi bi-trash3 text-danger fs-11"></i>
                                            @else
                                                <i class="bi bi-dash"></i>
                                            @endif
                                        </button>
                                        <span class="vm-drawer-qty-val">{{ $cartItem['quantity'] }}</span>
                                        <button type="button"
                                                class="vm-drawer-step-btn"
                                                onclick="updateDrawerQuantity({{ $cartItem['id'] }}, {{ $cartItem['quantity'] + 1 }})"
                                                {{ $cartItem['quantity'] >= $currentStock ? 'disabled' : '' }}
                                                title="{{ translate('Increase') }}">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>

                                    {{-- Item Price --}}
                                    <div class="vm-drawer-item-price-wrap text-end">
                                        <div class="vm-drawer-item-price">{{ webCurrencyConverter($itemFinalPrice) }}</div>
                                        @if($itemDiscount > 0)
                                            <del class="vm-drawer-item-old-price">{{ webCurrencyConverter($itemPrice * $cartItem['quantity']) }}</del>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{-- Sticky Bottom Action & Pricing Area --}}
    <div class="vm-cart-drawer-footer">
        <div class="vm-drawer-pricing-lines">
            <div class="vm-drawer-line">
                <span>{{ translate('Merchandise Subtotal') }}</span>
                <strong class="text-dark">{{ webCurrencyConverter($subTotal) }}</strong>
            </div>
            @if($totalDiscount > 0)
                <div class="vm-drawer-line text-success">
                    <span>{{ translate('Store Discounts') }}</span>
                    <strong>-{{ webCurrencyConverter($totalDiscount) }}</strong>
                </div>
            @endif
            <div class="vm-drawer-line">
                <span class="d-flex align-items-center gap-1">
                    <i class="bi bi-shop text-primary"></i> {{ translate('Fulfillment Choice') }}
                </span>
                <span class="badge bg-light text-success border border-success-subtle fs-11 fw-semibold">
                    {{ translate('In-Store Pickup Available (FREE ₦0.00)') }}
                </span>
            </div>
        </div>

        <div class="vm-drawer-total-box">
            <div class="vm-drawer-total-label">
                <span>{{ translate('Total Payable') }}</span>
                <small class="text-muted d-block fs-11">{{ translate('Delivery / Pickup chosen at next step') }}</small>
            </div>
            <div class="vm-drawer-total-amount">
                {{ webCurrencyConverter($subTotal) }}
            </div>
        </div>

        <div class="vm-drawer-actions">
            @if($web_config['guest_checkout_status'] || auth('customer')->check())
                <a href="{{ route('checkout-details') }}" class="vm-drawer-checkout-btn">
                    <span>{{ translate('Proceed to Checkout') }}</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </a>
            @else
                <button type="button" class="vm-drawer-checkout-btn" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <span>{{ translate('Proceed to Checkout') }}</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            @endif

            <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                <a href="{{ route('shop-cart') }}" class="vm-drawer-full-cart-link">
                    <i class="bi bi-cart3 me-1"></i> {{ translate('View Full Cart Review') }}
                </a>
                <button type="button" class="btn btn-link text-muted p-0 fs-12 text-decoration-none vm-cart-drawer-continue-shopping">
                    {{ translate('Continue Shopping') }} →
                </button>
            </div>
        </div>
    </div>
@else
    {{-- Empty Shopping Bag State --}}
    <div class="vm-cart-drawer-empty">
        <div class="vm-cart-drawer-empty-icon">
            <i class="bi bi-bag-x"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2">{{ translate('Your Shopping Bag is Empty') }}</h5>
        <p class="text-muted fs-13 mb-4" style="max-width: 320px; margin: 0 auto;">
            {{ translate('Explore verified merchants across Akwa Ibom with guaranteed physical in-shop counter inspection and swift directional logistics.') }}
        </p>
        <a href="{{ route('products') }}" class="vm-drawer-checkout-btn vm-cart-drawer-continue-shopping" style="max-width: 240px; margin: 0 auto; text-decoration: none;">
            <i class="bi bi-shop me-2"></i> {{ translate('Explore Catalog') }}
        </a>
    </div>
@endif
