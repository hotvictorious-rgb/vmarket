@php($couponAmount = session()->has('coupon_discount') ? session('coupon_discount') : 0)
@php($totalAmount = $order['order_amount'] - $couponAmount)
@php($isPhysicalProduct = $order->details()->whereHas('product', fn ($q) => $q->where('product_type', 'physical'))->exists())
<div class="modal fade z-1049 order-choose-payment-method-modal" id="choosePaymentMethodModal-{{ $order['id'] }}"
     tabindex="-1"
     aria-labelledby="choosePaymentMethodModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div id="global-loader" class="global-loader d-none">
                <span class="loader"></span>
            </div>
            <div class="cash-on-delivery-section">
                <form action="{{ route('customer.customer-order-edit-pay-amount') }}" method="POST"
                      class="needs-validation px-4" id="cash_on_delivery_form">
                    @csrf
                    <div class="modal-header border-0 p-2 d-block">
                        <div class="d-flex justify-content-end">
                            <button type="button"
                                    class="close-custom-btn btn d-center border-0 text-muted fs-12 p-1 w-30 h-30 lh-1 rounded-pill"
                                    data-bs-dismiss="modal" aria-label="Close">
                                <i class="fi fi-sr-cross d-flex"></i>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div>
                            <div class="text-center mb-3">
                                <h4 class="fs-18 fw-bold text-center mb-2">{{ translate('Choose_Payment_Method') }}</h4>
                                <h6 class="text-muted mb-2">{{ translate('Due Bill') }}</h6>
                                <h3 class="fs-22">{{ webCurrencyConverter(amount: $order?->edit_due_amount ?? 0 ) }}</h3>
                            </div>

                            <div>
                                <ul class="option-select-btn d-grid flex-wrap gap-3">

                                    @if(getWebConfig(name: 'digital_payment')['status'])
                                        <li>
                                            <label id="digital-payment-btn" class="w-100">
                                            <span
                                                class="payment-method payment-method_parent position-relative z-10 d-flex align-items-center gap-3 pay-via-digital">
                                                <img width="30"
                                                     src="{{ theme_asset('assets/img/icons/degital-payment.png') }}"
                                                     class="dark-support" alt="">
                                                <span class="fs-16">{{ translate('Digital Payment') }}</span>
                                            </span>
                                            </label>
                                        </li>
                                        @foreach ($paymentGatewayList as $payment_gateway)
                                            @php($additionalData = $payment_gateway['additional_data'] != null ? json_decode($payment_gateway['additional_data']) : [])
                                                <?php
                                                $gatewayImgPath = dynamicAsset(path: 'public/assets/back-end/img/modal/payment-methods/' . $payment_gateway->key_name . '.png');
                                                if ($additionalData != null && $additionalData?->gateway_image && file_exists(base_path('storage/app/public/payment_modules/gateway_image/' . $additionalData->gateway_image))) {
                                                    $gatewayImgPath = $additionalData->gateway_image ? dynamicStorage(path: 'storage/app/public/payment_modules/gateway_image/' . $additionalData->gateway_image) : $gatewayImgPath;
                                                }
                                                ?>
                                            <li id="digital-info-section" class="digital-info-section fade-slide">
                                                <label
                                                    class="bg-white w-100 h-100 d-block position-relative cursor-pointer"
                                                    for="{{ $payment_gateway->key_name }}">
                                                    <input type="hidden" name="external_redirect_link"
                                                           value="{{ route('web-payment-success') }}">
                                                    <input type="hidden" name="payment_platform"
                                                           value="web">
                                                    <input type="hidden" value="{{ $order['id'] }}" name="order_id">
                                                    <input type="radio" id="{{ $payment_gateway->key_name }}"
                                                           name="payment_method"
                                                           class="payment-radio"
                                                           value="{{ $payment_gateway->key_name }}" data-keep>
                                                    <div
                                                        class="payment-method next-btn-enable d-flex align-items-center gap-3 digital-payment-card overflow-hidden w-100"
                                                        type="button">
                                                        <img width="100" class="dark-support" alt=""
                                                             src="{{ $gatewayImgPath }}">
                                                    </div>
                                                </label>
                                            </li>
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex justify-content-center align-items-center gap-3 w-100">
                            <button type="button" data-bs-dismiss="modal"
                                    class="btn btn--reset fw-semibold w-100">{{ translate('Cancel') }}</button>
                            <button type="submit"
                                    class="btn btn-primary fw-semibold w-100 payment-proceed-btn">{{ translate('Proceed_To_Pay') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
