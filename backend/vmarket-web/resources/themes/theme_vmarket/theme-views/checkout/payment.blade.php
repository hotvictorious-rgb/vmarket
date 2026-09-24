@php use App\Utils\Helpers; @endphp
@extends('theme-views.layouts.app')

@section('title', translate('Payment_Details') . ' | ' . $web_config['company_name'] . ' ' . translate('ecommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <h4 class="text-center mb-3 text-capitalize">{{ translate('payment_details') }}</h4>
            <div class="row payment-method-list-page">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-body  px-sm-4">
                            <div class="d-flex justify-content-center mb-30">
                                <ul class="cart-step-list">
                                    <li class="done cursor-pointer get-view-by-onclick" data-link="{{ route('shop-cart') }}">
                                        <span><i class="bi bi-check2"></i></span> {{ translate('cart') }}
                                    </li>
                                    <li class="done cursor-pointer get-view-by-onclick text-capitalize"
                                        data-link="{{ route('checkout-details') }}">
                                        <span><i class="bi bi-check2"></i></span> {{ translate('shipping_details') }}
                                    </li>
                                    <li class="current"><span><i class="bi bi-check2"></i></span> {{ translate('payment') }}
                                    </li>
                                </ul>
                            </div>

                            @if (!$activeMinimumMethods)
                                <div class="d-flex justify-content-center py-5 align-items-center">
                                    <div class="text-center">
                                        <img src="{{ theme_asset(path: 'assets/img/not_found.png') }}" alt=""
                                             class="mb-4" width="70">
                                        <h5 class="fs-14 text-muted">
                                            {{ translate('payment_methods_are_not_available_at_this_time.') }}</h5>
                                    </div>
                                </div>
                            @else
                                <h5 class="mb-4 text-capitalize">{{ translate('payment_information') }}</h5>

                                <div class="mb-30">
                                    <ul class="option-select-btn d-grid flex-wrap gap-3">
                                        @if ($digital_payment['status'] == 1 && count($payment_gateways_list) > 0)
                                            <li>
                                                    <label id="digital-payment-btn" class="w-100">
                                                        <span
                                                            class="payment-method payment-method_parent d-flex align-items-center gap-3">
                                                            <img width="30"
                                                                 src="{{ theme_asset('assets/img/icons/degital-payment.png') }}"
                                                                 class="dark-support" alt="">
                                                            <span class="fs-16">{{ translate('Digital_Payment') }}</span>
                                                        </span>
                                                    </label>
                                                </li>

                                                @foreach ($payment_gateways_list as $payment_gateway)
                                                    @php($additionalData = $payment_gateway['additional_data'] != null ? json_decode($payment_gateway['additional_data']) : [])
                                                        <?php
                                                        $gatewayImgPath = dynamicAsset(path: 'public/assets/back-end/img/modal/payment-methods/' . $payment_gateway->key_name . '.png');
                                                        if ($additionalData != null && $additionalData?->gateway_image && file_exists(base_path('storage/app/public/payment_modules/gateway_image/' . $additionalData->gateway_image))) {
                                                            $gatewayImgPath = $additionalData->gateway_image ? dynamicStorage(path: 'storage/app/public/payment_modules/gateway_image/' . $additionalData->gateway_image) : $gatewayImgPath;
                                                        }
                                                        ?>

                                                    <li>
                                                        <form method="post"
                                                              class="digital-payment d--none payment-method-form checkout-payment-{{ $payment_gateway->key_name }}"
                                                              action="{{ route('customer.web-payment-request') }}">
                                                            @csrf
                                                            <input type="text" hidden name="user_id"
                                                                   value="{{ auth('customer')->check() ? auth('customer')->id() : session('guest_id') }}">
                                                            <input type="text" hidden name="customer_id"
                                                                   value="{{ auth('customer')->check() ? auth('customer')->id() : session('guest_id') }}">
                                                            <input type="radio" hidden name="payment_method"
                                                                   value="{{ $payment_gateway->key_name }}"
                                                                   data-form=".checkout-payment-{{ $payment_gateway->key_name }}">
                                                            <input type="text" hidden name="payment_platform"
                                                                   value="web">
                                                            @if ($payment_gateway->mode == 'live' && isset($payment_gateway->live_values['callback_url']))
                                                                <input type="text" hidden name="callback"
                                                                       value="{{ $payment_gateway->live_values['callback_url'] }}">
                                                            @elseif ($payment_gateway->mode == 'test' && isset($payment_gateway->test_values['callback_url']))
                                                                <input type="text" hidden name="callback"
                                                                       value="{{ $payment_gateway->test_values['callback_url'] }}">
                                                            @else
                                                                <input type="text" hidden name="callback"
                                                                       value="">
                                                            @endif
                                                            <input type="text" hidden name="external_redirect_link"
                                                                   value="{{ route('web-payment-success') }}">

                                                            <label class="w-100">
                                                                @php($additional_data = $payment_gateway['additional_data'] != null ? json_decode($payment_gateway['additional_data']) : [])
                                                                <button
                                                                    class="payment-method next-btn-enable d-flex align-items-center gap-3 digital-payment-card overflow-hidden w-100"
                                                                    type="submit">
                                                                    @if (!empty($gatewayImgPath))
                                                                        <img width="100" class="dark-support"
                                                                             alt=""
                                                                             src="{{ $gatewayImgPath }}">
                                                                    @else
                                                                        <h4>{{ ucwords(str_replace('_', ' ', $payment_gateway->key_name ?? '')) }}
                                                                        </h4>
                                                                    @endif
                                                                </button>
                                                            </label>
                                                        </form>
                                                    </li>
                                                @endforeach
                                            @endif
                                        @endif
                                    </ul>



                                </div>
                            @endif

                        </div>
                    </div>
                </div>
                @include('theme-views.partials._order-summery')
            </div>
        </div>
    </main>
@endsection

@push('script')
    <script src="{{ theme_asset('assets/js/payment-page.js') }}"></script>
@endpush
