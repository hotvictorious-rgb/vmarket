@php
    use App\Utils\CartManager;
@endphp
<div class="col-lg-4">
    <div class="card text-dark sticky-top-80">
        <div class="card-body px-sm-4 d-flex flex-column gap-3">
            @php($systemTaxConfig = getTaxModuleSystemTypesConfig())
            @php($current_url = request()->segment(count(request()->segments())))
            @php($product_price_total = 0)
            @php($totalTax = \App\Utils\CartManager::getCartListTaxAmount())
            @php($total_discount_on_product = 0)
            @php($cart = CartManager::getCartListQuery(type: 'checked'))
            @php($cartAll = CartManager::getCartListQuery())
            @php($cart_group_ids = CartManager::get_cart_group_ids())
            {{-- VM-CUST-003: canonical summary — no legacy shipping/coupon/referral. Delivery fees via fulfillment downstream. --}}
            @if ($cart->count() > 0)
                @foreach ($cart as $key => $cartItem)
                    @php($product_price_total += $cartItem['price'] * $cartItem['quantity'])
                    @php($total_discount_on_product += $cartItem['discount'] * $cartItem['quantity'])
                @endforeach
            @endif

            @if ($cartAll->count() > 0 && $cart->count() == 0)
                <span>{{ translate('Please_checked_items_before_proceeding_to_checkout') }}</span>
            @elseif($cartAll->count() == 0)
                <span>{{ translate('empty_cart') }}</span>
            @endif

            <h4 class="text-capitalize mb-0">{{ translate('order_summary') }}</h4>
            {{-- VM-CUST-003: legacy coupon input removed. Victorious Points only. --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-16">
                <div class="opacity-75 text-capitalize">{{ translate('item_price') }}</div>
                <div class="fw-semibold">{{ webCurrencyConverter($product_price_total) }}</div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-16">
                <div class="opacity-75 text-capitalize">{{ translate('product_discount') }}</div>
                <div class="fw-semibold">{{ webCurrencyConverter($total_discount_on_product) }}</div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-16">
                <div class="opacity-75 text-capitalize">{{ translate('sub_total') }}</div>
                <div class="fw-semibold">{{ webCurrencyConverter($product_price_total - $total_discount_on_product) }}</div>
            </div>

            @php($estimatedCashbackRate = (float) (getWebConfig(name: 'loyalty_point_earn_rate_percent') ?: 5.0))
            @php($estimatedCashback = ($product_price_total - $total_discount_on_product) * $estimatedCashbackRate / 100)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-16">
                <div class="opacity-75 text-capitalize">{{ translate('estimated_victorious_points_cashback') }} ({{ rtrim(rtrim(number_format($estimatedCashbackRate, 2), '0'), '.') }}%)</div>
                <div class="fw-semibold text-success">+{{ webCurrencyConverter($estimatedCashback) }}</div>
            </div>

            @php($totalAmount = $product_price_total + $totalTax['item_tax'] - $total_discount_on_product)

            @if($systemTaxConfig['SystemTaxVat']['is_active'] && !$systemTaxConfig['is_included'])
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-16">
                    <div class="opacity-75 text-capitalize">{{ translate('estimated_tax') }}</div>
                    <div class="fw-semibold">{{ webCurrencyConverter(amount: $totalTax['item_tax']) }}</div>
                </div>
            @endif

            <hr class="m-0" />

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 fs-16">
                <h5>
                    {{ translate('total') }}
                    @if($systemTaxConfig['SystemTaxVat']['is_active'] && $systemTaxConfig['is_included'])
                        <span class="fs-12 fw-semibold">({{ translate('Tax_:_Inc.') }})</span>
                    @endif
                </h5>
                <h4 class="text-primary">{{ webCurrencyConverter($totalAmount) }}</h4>
            </div>
            <div class="fs-12 text-muted">{{ translate('delivery_fees_calculated_at_checkout') }}</div>

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
