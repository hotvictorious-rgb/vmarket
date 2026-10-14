@php
    use App\Utils\CartManager;
@endphp
<div class="col-lg-4">
    <div class="vm-summary-card sticky-top-80">
        @php
            $systemTaxConfig = getTaxModuleSystemTypesConfig();
            $current_url = request()->segment(count(request()->segments()));
            $product_price_total = 0;
            $totalTax = \App\Utils\CartManager::getCartListTaxAmount();
            $total_discount_on_product = 0;
            $cart = CartManager::getCartListQuery(type: 'checked');
            $cartAll = CartManager::getCartListQuery();
            $cart_group_ids = CartManager::get_cart_group_ids();
            $checkedGroupIds = CartManager::get_cart_group_ids(type: 'checked');
            $cartShippings = \App\Models\CartShipping::whereIn('cart_group_id', $checkedGroupIds)->get();
            $shippingTotal = 0.0;
            $hasPickup = false;
            foreach ($cartShippings as $cs) {
                $m = \App\Models\ShippingMethod::find($cs->shipping_method_id);
                $isCsPickup = in_array((int)$cs->shipping_method_id, [3, 9]);
                if ($m && (str_contains(strtolower($m->title), 'pickup') || str_contains(strtolower($m->title), 'pick up') || str_contains(strtolower($m->title), 'in-store') || (float)$m->cost == 0)) {
                    $isCsPickup = true;
                }
                if ($isCsPickup) {
                    $hasPickup = true;
                } else {
                    $shippingTotal += (float)$cs->shipping_cost;
                }
            }
            if ($cart->count() > 0) {
                foreach ($cart as $key => $cartItem) {
                    $product_price_total += $cartItem['price'] * $cartItem['quantity'];
                    $total_discount_on_product += $cartItem['discount'] * $cartItem['quantity'];
                }
            }
            $taxAmount = ($systemTaxConfig['SystemTaxVat']['is_active'] && !$systemTaxConfig['is_included']) ? ($totalTax['item_tax'] ?? 0) : 0;
            $grandTotal = ($product_price_total - $total_discount_on_product) + $shippingTotal + $taxAmount;
        @endphp

        <div class="vm-summary-title">
            <span>{{ translate('Order Summary') }}</span>
            <span class="badge bg-light text-dark border fs-12 fw-semibold">
                {{ $cart->count() }} {{ translate('items') }}
            </span>
        </div>

        @if ($cartAll->count() > 0 && $cart->count() == 0)
            <div class="alert alert-warning py-2 px-3 fs-12 rounded-3 mb-2">
                <i class="bi bi-info-circle me-1"></i> {{ translate('Please check items to proceed to checkout') }}
            </div>
        @endif

        <div class="vm-summary-line">
            <span>{{ translate('Merchandise Subtotal') }}</span>
            <span class="vm-summary-line-value">{{ webCurrencyConverter($product_price_total) }}</span>
        </div>

        @if($total_discount_on_product > 0)
            <div class="vm-summary-line">
                <span class="text-success">{{ translate('Store Discounts') }}</span>
                <span class="vm-summary-line-value text-success">-{{ webCurrencyConverter($total_discount_on_product) }}</span>
            </div>
        @endif

        <div class="vm-summary-line">
            <span class="d-flex align-items-center gap-1">
                <i class="bi bi-truck text-primary"></i> {{ translate('Shipping / Pickup') }}
            </span>
            <span class="vm-summary-line-value {{ $shippingTotal == 0 ? 'text-success' : '' }}"
                  id="summary-shipping-cost"
                  data-original-html="{{ $shippingTotal > 0 ? webCurrencyConverter($shippingTotal) : translate('FREE') }}">
                {{ $shippingTotal > 0 ? webCurrencyConverter($shippingTotal) : translate('FREE (₦0.00)') }}
            </span>
        </div>

        @if($systemTaxConfig['SystemTaxVat']['is_active'] && !$systemTaxConfig['is_included'])
            <div class="vm-summary-line">
                <span>{{ translate('Estimated VAT') }}</span>
                <span class="vm-summary-line-value">{{ webCurrencyConverter(amount: $totalTax['item_tax']) }}</span>
            </div>
        @endif

        <div class="vm-summary-total-line">
            <div>
                <div class="vm-summary-total-label">{{ translate('Grand Total') }}</div>
                @if($systemTaxConfig['SystemTaxVat']['is_active'] && $systemTaxConfig['is_included'])
                    <span class="fs-11 text-muted">({{ translate('Tax Included') }})</span>
                @endif
            </div>
            <div class="vm-summary-total-amount"
                 id="summary-grand-total"
                 data-original-html="{{ webCurrencyConverter($grandTotal) }}">
                {{ webCurrencyConverter($grandTotal) }}
            </div>
        </div>

        <div id="summary-pickup-due-notice" class="alert alert-info py-2 px-3 mt-3 mb-0 rounded-3 {{ $hasPickup ? '' : 'd-none' }}" style="background-color: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.25); color: #065F46;">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fs-12"><strong>{{ translate('Due at Store Counter') }}:</strong></span>
                <strong class="fs-13">{{ webCurrencyConverter($product_price_total - $total_discount_on_product + $taxAmount) }}</strong>
            </div>
        </div>

        {{-- Escrow Trust Reassurance Card --}}
        <div class="vm-escrow-card">
            <i class="bi bi-shield-lock-fill" style="color: #FFD700;"></i>
            <div>
                <strong class="text-dark d-block fs-12 mb-1">
                    <span style="color: #5E17EB;">{{ translate('Victorious') }}</span> {{ translate('Escrow & OTP Lock') }}
                </strong>
                <span class="fs-11 text-muted line-height-1">
                    {{ translate('100% Secure. Funds held in platform escrow and disbursed only after physical inspection or handover OTP release.') }}
                </span>
            </div>
        </div>

        <div class="d-flex flex-column gap-2 mt-2">
            @if (str_contains(request()->url(), 'checkout-payment'))
                <label class="custom-control custom-checkbox mb-2 d-flex user-select-none cursor-pointer">
                    <input type="checkbox" class="custom-control-input payment-input-checkbox">
                    <span class="custom-control-label fs-12 ms-2">
                        <span>{{ translate('I agree to the') }}</span>
                        <a class="text-primary fw-bold text-decoration-none" target="_blank"
                           href="{{ route('business-page.view', ['slug' => 'terms-and-conditions']) }}">
                            {{ translate('terms_and_condition') }}
                        </a>
                        @foreach($web_config['business_pages']->where('default_status', 1) as $businessPage)
                            @if($businessPage['slug'] == 'privacy-policy' || $businessPage['slug'] == 'refund-policy')
                                , <a class="text-primary fw-bold text-decoration-none" target="_blank"
                                   href="{{ route('business-page.view', ['slug' => $businessPage['slug']]) }}">
                                    {{ translate(str_replace('-', '_', $businessPage['slug'])) }}
                                </a>
                            @endif
                        @endforeach
                    </span>
                </label>
            @endif

            @if (str_contains(request()->url(), 'checkout-payment'))
                <button class="vm-checkout-cta-btn custom-disabled" id="proceed-to-payment-action"
                        data-goto-checkout="{{ route('checkout-details') }}"
                        data-route="{{ route('checkout-payment') }}" data-type="{{ 'checkout-payment' }}"
                        {{ isset($isProductNullStatus) && $isProductNullStatus == 1 ? 'disabled' : '' }}
                        type="button">
                    <i class="bi bi-lock-fill"></i> {{ translate('Complete Escrow Payment') }}
                </button>
            @else
                <button class="vm-checkout-cta-btn {{ $cart->count() <= 0 ? 'custom-disabled' : '' }}"
                        id="proceed-to-next-action"
                        data-goto-checkout="{{ route('customer.choose-shipping-address-other') }}"
                        data-checkout-payment="{{ route('checkout-payment') }}"
                        {{ isset($isProductNullStatus) && $isProductNullStatus == 1 ? 'disabled' : '' }}
                        type="button">
                    {{ translate('Proceed to Checkout') }} <i class="bi bi-arrow-right"></i>
                </button>
            @endif

            <a href="{{ route('home') }}" class="btn btn-link text-muted text-center fs-13 text-decoration-none py-1">
                <i class="bi bi-arrow-left me-1"></i> {{ translate('Continue Shopping') }}
            </a>
        </div>
    </div>
</div>
