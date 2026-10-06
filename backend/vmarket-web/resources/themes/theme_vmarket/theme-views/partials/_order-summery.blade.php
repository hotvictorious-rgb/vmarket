@php
    use App\Utils\CartManager;
@endphp
<div class="col-lg-4">
    <div class="card text-dark sticky-top-80">
        <div class="card-body px-sm-4 d-flex flex-column gap-3">
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
                $shippingTotal = (float)(\App\Models\CartShipping::whereIn('cart_group_id', $checkedGroupIds)->sum('shipping_cost') ?? 0);
                if ($cart->count() > 0) {
                    foreach ($cart as $key => $cartItem) {
                        $product_price_total += $cartItem['price'] * $cartItem['quantity'];
                        $total_discount_on_product += $cartItem['discount'] * $cartItem['quantity'];
                    }
                }
            @endphp

            @if ($cartAll->count() > 0 && $cart->count() == 0)
                <span>{{ translate('Please_checked_items_before_proceeding_to_checkout') }}</span>
            @elseif($cartAll->count() == 0)
                <span>{{ translate('empty_cart') }}</span>
            @endif

            <h4 class="text-capitalize mb-0 fw-bold" style="color: #1B1035;">{{ translate('order_summary') }}</h4>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-15">
                <div class="text-muted text-capitalize">{{ translate('item_price') }}</div>
                <div class="fw-semibold text-dark">{{ webCurrencyConverter($product_price_total) }}</div>
            </div>

            @if($total_discount_on_product > 0)
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-15">
                    <div class="text-success text-capitalize">{{ translate('product_discount') }}</div>
                    <div class="fw-semibold text-success">-{{ webCurrencyConverter($total_discount_on_product) }}</div>
                </div>
            @endif

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-15">
                <div class="text-muted text-capitalize">{{ translate('items_subtotal') }}</div>
                <div class="fw-semibold text-dark">{{ webCurrencyConverter($product_price_total - $total_discount_on_product) }}</div>
            </div>

            @php
                $checkedGroupIds = CartManager::get_cart_group_ids(type: 'checked');
                $shippingTotal = \App\Models\CartShipping::whereIn('cart_group_id', $checkedGroupIds)->sum('shipping_cost');
                $taxAmount = ($systemTaxConfig['SystemTaxVat']['is_active'] && !$systemTaxConfig['is_included']) ? ($totalTax['item_tax'] ?? 0) : 0;
                $grandTotal = ($product_price_total - $total_discount_on_product) + $shippingTotal + $taxAmount;
            @endphp

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-15">
                <div class="text-muted text-capitalize d-flex align-items-center gap-1">
                    <i class="bi bi-truck text-primary"></i> {{ translate('Shipping / Delivery') }}
                </div>
                <div class="fw-semibold {{ $shippingTotal == 0 ? 'text-success' : 'text-dark' }}"
                     id="summary-shipping-cost"
                     data-original-html="{{ $shippingTotal > 0 ? webCurrencyConverter($shippingTotal) : translate('FREE') }}">
                    {{ $shippingTotal > 0 ? webCurrencyConverter($shippingTotal) : translate('FREE') }}
                </div>
            </div>

            @if($systemTaxConfig['SystemTaxVat']['is_active'] && !$systemTaxConfig['is_included'])
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-15">
                    <div class="text-muted text-capitalize">{{ translate('estimated_tax') }}</div>
                    <div class="fw-semibold text-dark">{{ webCurrencyConverter(amount: $totalTax['item_tax']) }}</div>
                </div>
            @endif

            <hr class="my-2" style="border-color: #E2E8F0;" />

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-16 py-1">
                <div>
                    <h5 class="m-0 fw-bold" style="color: #1B1035;">{{ translate('total') }}</h5>
                    @if($systemTaxConfig['SystemTaxVat']['is_active'] && $systemTaxConfig['is_included'])
                        <span class="fs-11 text-muted">({{ translate('Tax Included') }})</span>
                    @endif
                </div>
                <h4 class="fw-bold m-0" style="color: #2E1B4E;"
                    id="summary-grand-total"
                    data-original-html="{{ webCurrencyConverter($grandTotal) }}">{{ webCurrencyConverter($grandTotal) }}</h4>
            </div>

            <div id="summary-pickup-due-notice" class="alert alert-info py-2 px-3 mb-0 rounded-3 d-none" style="background-color: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.25); color: #065F46;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fs-12"><strong>{{ translate('due_at_store_inspection') ?? 'Due at Store Inspection' }}:</strong></span>
                    <strong class="fs-13">{{ webCurrencyConverter($product_price_total - $total_discount_on_product + $taxAmount) }}</strong>
                </div>
            </div>

            <div class="p-2 rounded border" style="background: rgba(46,27,78,0.03); border-color: rgba(46,27,78,0.1) !important;">
                <div class="d-flex align-items-center gap-2 fs-12 text-muted">
                    <i class="bi bi-shield-lock-fill fs-18" style="color: #B8860B;"></i>
                    <div>
                        <strong class="text-dark d-block">100% Escrow Protected</strong>
                        <span class="fs-11">Funds released strictly upon verified delivery or counter handover.</span>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                @if (str_contains(request()->url(), 'checkout-payment'))
                    <label class="custom-control custom-checkbox mb-3 d-flex user-select-none cursor-pointer">
                        <input type="checkbox" class="custom-control-input payment-input-checkbox">
                        <span class="custom-control-label">
                    <span>{{ translate('i_agree_to_Your') }}</span>
                    <a class="font-size-sm text--primary fw-bold d-inline" target="_blank"
                       href="{{ route('business-page.view', ['slug' => 'terms-and-conditions']) }}">
                        {{ translate('terms_and_condition') }}
                    </a>
                    @foreach($web_config['business_pages']->where('default_status', 1) as $businessPage)
                                @if($businessPage['slug'] == 'privacy-policy' || $businessPage['slug'] == 'refund-policy')
                                    <a class="font-size-sm text--primary fw-bold d-inline" target="_blank"
                                       href="{{ route('business-page.view', ['slug' => $businessPage['slug']]) }}">
                            , {{ translate(str_replace('-', '_', $businessPage['slug'])) }}
                            </a>
                                @endif
                            @endforeach
                    </span>
                    </label>
                @endif

                <a href="{{ route('home') }}" class="btn-link text-primary text-capitalize user-select-none fw-semibold">
                    <i class="fi fi-rr-angle-double-left fs-10"></i> {{ translate('continue_shopping') }}
                </a>

                @if (str_contains(request()->url(), 'checkout-payment'))
                    <button class="btn btn-primary text-capitalize custom-disabled" id="proceed-to-payment-action"
                            data-goto-checkout="{{ route('checkout-details') }}"
                            data-route="{{ route('checkout-payment') }}" data-type="{{ 'checkout-payment' }}"
                            {{ isset($isProductNullStatus) && $isProductNullStatus == 1 ? 'disabled' : '' }}
                            type="button">
                        {{ translate('proceed_to_payment') }}
                    </button>
                @else
                    <button class="btn btn-primary text-capitalize {{ $cart->count() <= 0 ? 'custom-disabled' : '' }}"
                            id="proceed-to-next-action"
                            data-goto-checkout="{{ route('customer.choose-shipping-address-other') }}"
                            data-checkout-payment="{{ route('checkout-payment') }}"
                            {{ isset($isProductNullStatus) && $isProductNullStatus == 1 ? 'disabled' : '' }}
                            type="button">
                        {{ translate('proceed_to_checkout') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
