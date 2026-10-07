
<?php

use Carbon\Carbon;

$isEligibleForRefundButtonShow = 0;
$refund_day_limit = getWebConfig(name: 'refund_day_limit');
$current = Carbon::now();
foreach ($order->details as $key => $detail) {
    $product = $detail?->productAllStatus ?? json_decode($detail->product_details, true);
    if ($product) {
        $length = $detail?->refund_started_at?->diffInDays($current);
        if ($order->order_type == 'default_type' && $order->order_status == 'delivered') {
            if ($detail->refund_request != 0) {
                $isEligibleForRefundButtonShow++;
            }
            if ($refund_day_limit > 0 && !is_null($length) && $length <= $refund_day_limit && $detail->refund_request == 0) {
                $isEligibleForRefundButtonShow++;
            }
        }
    }
}
?>


<div class="border-bottom d-flex align-items-center justify-content-between flex-wrap gap-3 pb-20 mb-20">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="d-flex align-items-center gap-2">
            <img class="svg svg-dark-support" src="{{theme_asset(path: "/assets/img/icons/home-icon.svg")}}" alt="icon">
            <h6 class="text-capitalize fs-14">{{ $order?->seller?->shop?->name ?? '' }}</h6>
        </div>
        @if($order['order_status']=='failed' || $order['order_status']=='canceled')
            <span class="badge text-danger border-danger-1 text-bg-danger rounded-1 fw-normal fs-12 bg-opacity-10">
            {{ translate($order['order_status']=='failed' ? 'Failed To Deliver' : $order['order_status']) }}
        </span>
        @elseif($order['order_status']=='confirmed' || $order['order_status']=='processing' || $order['order_status']=='delivered')
            <span class="badge text-success border-success-1 text-bg-success rounded-1 fw-normal fs-12 bg-opacity-10">
            {{ translate($order['order_status']=='processing' ? 'packaging' : $order['order_status']) }}
        </span>
        @else
            <span class="badge text-primary border-primary-1 text-bg-primary rounded-1 fw-normal fs-12 bg-opacity-10">
            {{ translate($order['order_status']) }}
        </span>
        @endif

    </div>
    <div class="d-flex align-items-center gap-xl-2 gap-2">
        @if($isEligibleForRefundButtonShow > 0)
            <button class="btn btn-outline-primary px-3 rounded-10 fw-medium" data-bs-toggle="modal"
            data-bs-target="#refund-modal">{{ translate('refund') }}</button>
        @endif
        @if($order->order_status=='delivered' &&  $order->order_type == 'default_type')
            <a href="javascript:" class="btn btn-primary px-3 rounded-10 fw-medium order-again"
               data-action="{{route('cart.order-again')}}"
               data-order-id="{{$order['id']}}">{{ translate('reorder') }}</a>
        @endif
        <a target="_blank" href="{{route('generate-invoice',[$order->id])}}"
           class="btn btn--reset px-3 rounded-10 fw-semibold"
           data-bs-toggle="tooltip"
           data-bs-placement="bottom"
           data-bs-title="{{ translate('download_invoice') }}">
            <i class="fi fi-rr-file-download"></i> {{ translate('Invoice') }}
        </a>
    </div>
</div>
@php
    $isPickup = in_array($order->order_type, ['pickup', 'in_house_pickup'], true) || in_array($order->delivery_type, ['self_pickup'], true);
    $canConfirmPickup = $isPickup && $order->payment_status === 'paid' && !in_array($order->order_status, ['delivered', 'canceled', 'returned', 'failed'], true);
    $canConfirmDelivery = !$isPickup && $order->order_status === 'out_for_delivery';
@endphp
<div>
    <div class="row g-3">

        <!-- Order Edit -->
        @if(($order['payment_method'] == 'cash_on_delivery' || $order?->latestEditHistory?->order_due_payment_method == 'cash_on_delivery') && $order['bring_change_amount'] > 0)
        <div class="col-md-12">
            <div class="__badge soft-primary py-2 fs-14 text-dark rounded w-100">
                {{ translate('Please bring') }}
                <strong> {{ $order['bring_change_amount'] }} {{ $order['bring_change_amount_currency'] ?? '' }}</strong> {{ translate('in change when making the delivery') }}
            </div>
        </div>
        @endif
        <div class="col-md-12">
            <div
                class="section-bg-cmn rounded-2 py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-md-3 gap-2 h-100">

                <h5 class="mb-0 fs-16">
                    {{translate('order').' #' }}{{$order['id']}}
                    @if($order['edited_status' ] == 1)
                        <span class="edit-text fw-medium text-muted fs-14">
                        (Edited)
                    </span>
                    @endif
                </h5>
                <p class="fs-14 mb-0">{{date('d M, Y h:i A',strtotime($order->created_at))}}</p>
            </div>
        </div>

        @if($canConfirmPickup)
            <div class="col-12">
                <div class="card border border-primary bg-primary bg-opacity-10 p-3 rounded-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <h6 class="text-primary fw-bold mb-1 d-flex align-items-center gap-2">
                                <i class="fi fi-rr-store-alt"></i> {{ translate('Ready for In-Store Counter Pickup') }}
                            </h6>
                            <p class="fs-13 text-muted mb-0">
                                {{ translate('Inspect your package at the shop counter. The merchant will show you their 6-digit Secret Pickup Code. Snap a photo of your goods on the counter and enter the code to claim your items and earn 5% Instant Cashback!') }}
                            </p>
                        </div>
                        <button type="button" class="btn btn-primary px-4 py-2 rounded-10 fw-semibold" data-bs-toggle="modal" data-bs-target="#confirmInShopPickupModal">
                            <i class="fi fi-rr-camera me-1"></i> {{ translate('Snap Photo & Confirm Pickup') }}
                        </button>
                    </div>
                </div>
            </div>
        @elseif($canConfirmDelivery)
            <div class="col-12">
                <div class="card border border-warning bg-warning bg-opacity-10 p-3 rounded-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="flex-grow-1">
                            <h6 class="text-dark fw-bold mb-1 d-flex align-items-center gap-2">
                                <i class="fi fi-rr-biking"></i> {{ translate('Rider Arrived / Out for Delivery') }}
                            </h6>
                            <p class="fs-13 text-muted mb-0">
                                {{ translate('The delivery rider has arrived at your doorstep. Ask the rider for their 6-digit Delivery Code shown on their phone. Snap a photo of your parcel and enter the code below to complete safe delivery.') }}
                            </p>
                        </div>
                        <button type="button" class="btn btn-warning text-dark px-4 py-2 rounded-10 fw-semibold" data-bs-toggle="modal" data-bs-target="#confirmDoorstepDeliveryModal">
                            <i class="fi fi-rr-camera me-1"></i> {{ translate('Snap Photo & Confirm Delivery') }}
                        </button>
                    </div>
                </div>
            </div>
        @elseif($order->order_status === 'delivered')
            @php
                $verifiedProof = $order->verificationImages ? $order->verificationImages->last() : null;
            @endphp
            @if($verifiedProof)
                <div class="col-12">
                    <div class="section-bg-cmn rounded-2 p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <a href="{{ $verifiedProof->image_full_url['path'] ?? '' }}" target="_blank">
                                <img src="{{ $verifiedProof->image_full_url['path'] ?? '' }}" class="rounded border" style="width: 60px; height: 60px; object-fit: cover;" alt="Proof">
                            </a>
                            <div>
                                <h6 class="mb-1 text-success fw-bold d-flex align-items-center gap-1">
                                    <i class="fi fi-rr-checkbox"></i> {{ translate('Handover Verified with Photo Proof') }}
                                </h6>
                                <p class="fs-12 text-muted mb-0">{{ date('d M Y, h:i A', strtotime($verifiedProof->created_at)) }}</p>
                            </div>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-1 px-3 py-2 fs-12">{{ translate('Custody Verified') }}</span>
                    </div>
                </div>
            @endif
        @endif

        <div class="col-12">

            @php
                $showOnlyPaymentInfo =
                ($order->edited_status == 1 && $order?->latestEditHistory?->order_due_payment_method == "cash_on_delivery") ||
                ($order->edited_status == 1 && $order->edit_due_amount > 0 &&  $order?->payment_method != "cash_on_delivery") ||
                ($order->edited_status == 1 && $order?->latestEditHistory?->order_return_payment_status == 'pending') ||
                ($order->edited_status == 1 && $order?->latestEditHistory?->order_due_payment_status == "paid") ||
                ($order->edited_status == 1 && $order?->latestEditHistory?->order_return_payment_status == "returned");
            @endphp

            <div class="h-100 section-bg-cmn rounded-2 p-3">
                <h5 class="text-capitalize mb-2">{{translate('payment_info')}}</h5>
                <div class="row g-4">
                    <div class="{{ $showOnlyPaymentInfo ? 'col-md-6' : 'col-md-12' }}">
                        <div class="d-flex flex-column gap-2 bg-white rounded py-3 px-3 h-100">
                            <div class="fs-12 d-flex justify-content-start gap-2">
                                <span class="text-muted text-capitalize">{{translate('payment_status')}} :</span>
                                @if($order->edited_status == 1 && $order->edit_due_amount > 0 && $order->latestEditHistory->order_due_payment_method != "cash_on_delivery" && $order->latestEditHistory->order_due_payment_status == "unpaid")
                                    <span
                                        class="text-success text-capitalize fw-semibold">{{ translate('Partially_Paid') }}</span>
                                @else
                                    <span
                                        class="text-{{$order['payment_status'] == 'paid' ? 'success' : 'danger'}} text-capitalize fw-semibold">{{$order['payment_status']}}</span>
                                @endif
                            </div>
                            <div class="fs-12 d-flex justify-content-start gap-2">
                                <span class="text-muted text-capitalize">{{translate('payment_by')}} :</span>
                                <span
                                    class="text-dark text-capitalize fw-semibold">{{translate($order['payment_method'])}}</span>
                            </div>
                            <div class="fs-12 d-flex justify-content-start gap-2">
                                <span class="text-muted text-capitalize">{{translate('Amount')}} :</span>
                                @if(($order['total_order_amount'] ?? 0) > 0)
                                    <span
                                        class="text-dark text-capitalize fw-semibold">{{ webCurrencyConverter(amount:  $order['total_order_amount'])  }}</span>
                                @else
                                    <span
                                        class="text-dark text-capitalize fw-semibold">{{ webCurrencyConverter(amount:  $order['order_amount'])  }}</span>
                                @endif
                            </div>
                            @if($order->payment_method == 'offline_payment' && isset($order->offlinePayments))
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <button type="button"
                                            class="btn btn--reset mt-1 rounded-pill btn-sm text-capitalize fs-12 fw-semibold"
                                            data-bs-toggle="modal" data-bs-target="#verificationModal">
                                        {{ translate('see_payment_details') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($order->edited_status == 1 && ($order?->latestEditHistory?->order_due_payment_method == "offline_payment" || $order?->latestEditHistory?->order_due_payment_method == "cash_on_delivery" || $order?->latestEditHistory?->order_due_payment_status == "paid"))
                        <div class="col-md-6">
                            <div class="d-flex flex-column gap-2 bg-white rounded py-3 px-3 h-100">
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <span class="text-capitalize text-dark">
                                        {{translate('Another_Payment_Info') }}
                                    </span>
                                    <span class="text-{{ $order?->latestEditHistory?->order_due_payment_status == 'paid' ? 'success' : 'danger'}} text-capitalize fw-semibold">
                                        {{ translate($order?->latestEditHistory?->order_due_payment_status) }}
                                    </span>
                                </div>
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <span class="text-muted text-capitalize">
                                        {{ translate('Payment_method') }} :
                                    </span>
                                    <span class="text-dark text-capitalize fw-semibold">
                                        {{ translate($order?->latestEditHistory?->order_due_payment_method) }}
                                    </span>
                                </div>
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <span class="text-muted text-capitalize">{{translate('Due_amount')}} :</span>
                                    <span
                                        class="text-dark text-capitalize fw-semibold">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_due_amount ?? 0) }}</span>
                                </div>
                                @if($order?->latestEditHistory?->order_due_payment_method == "offline_payment" && !empty($order?->latestEditHistory?->order_due_payment_info))
                                    <div class="fs-12 d-flex justify-content-start gap-2">
                                        <button type="button"
                                                class="btn btn--reset mt-1 rounded-pill btn-sm text-capitalize fs-12 fw-semibold"
                                                data-bs-toggle="modal" data-bs-target="#orderDuePaymentInfoModal">
                                            {{ translate('see_payment_details') }}
                                        </button>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @elseif($order->edited_status == 1 && $order->edit_due_amount > 0 &&  $order?->payment_method != "cash_on_delivery")
                        <div class="col-md-6">
                            <div>
                                <div
                                    class="d-flex flex-column text-center justify-content-between align-items-center gap-1 h-100 section-bg-cmn rounded-2 p-3">
                                    <h5 class="fs-16 mb-2 text-danger fw-semibold text-capitalize">{{translate('Pay_Due_Bill')}}</h5>
                                    <h5 class="fw-bold">{{ webCurrencyConverter(amount: $order['edit_due_amount']) }}</h5>
                                    <p class="fs-12">
                                        {{ translate('after_editing_your_product_list,_the_order_total_has_increased._please_pay_the_amount_to_continue_processing_the_order.') }}
                                        {{-- [AI] VM-VEND-PROD-001: due-payment modal excised (customer-order-edit-pay-amount route purged); contact support until retry-pay backend endpoint ships --}}
                                        {{ translate('please_contact_support_to_complete_your_payment.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @elseif($order->edited_status == 1 && $order?->latestEditHistory?->order_return_payment_status == 'pending')
                        <div class="col-md-6">
                            <div
                                class="d-flex flex-column text-center justify-content-between align-items-center gap-1 h-100 section-bg-cmn rounded-2 p-3">
                                <h6 class="fs-16 mb-2 text-danger fw-semibold text-capitalize">{{translate('Amount_to_Be_Returned')}}</h6>
                                <h6>{{ webCurrencyConverter(amount: $order['edit_return_amount']) }}</h6>
                                <p class="fs-12">
                                    {{ translate('after_editing_your_product_list,_you_will_receive_this_amount._please_wait_for_the_admin_to_process_the_returned.') }}
                                </p>
                            </div>
                        </div>
                    @elseif($order->edited_status == 1 && ($order?->latestEditHistory?->order_due_payment_status == "paid" || $order?->latestEditHistory?->order_due_payment_status == "cash_on_delivery"))
                        <div class="col-md-6">
                            <div class="d-flex flex-column gap-2 bg-white rounded py-3 px-3 h-100">
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                        <span
                                            class="text-capitalize text-dark">{{translate('Another_Payment_Info')}}</span>
                                    <span
                                        class="text-{{ $order?->latestEditHistory?->order_due_payment_status == 'paid' ? 'success' : 'danger'}} text-capitalize fw-semibold">{{ $order['payment_status']}}</span>
                                </div>
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                        <span
                                            class="text-muted text-capitalize">{{translate('Payment_method')}} :</span>
                                    <span
                                        class="text-dark text-capitalize fw-semibold">{{translate($order?->latestEditHistory?->order_due_payment_method)}}</span>
                                </div>
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <span class="text-muted text-capitalize">{{translate('Due_amount')}} :</span>
                                    <span
                                        class="text-dark text-capitalize fw-semibold">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_due_amount ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                    @elseif($order->edited_status == 1 && $order?->latestEditHistory?->order_return_payment_status == "returned")
                        <div class="col-md-6">
                            <div
                                class="d-flex flex-column justify-content-between align-items-start gap-2 h-100 section-bg-cmn rounded-2 p-3">
                                <h6 class="fs-14 d-flex w-100 justify-content-between gap-2 mb-2">
                                    <span
                                        class="text-capitalize text-dark fw-semibold">{{translate('Return_Payment_info')}}</span>
                                </h6>
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <span
                                        class="text-muted text-capitalize">{{translate('Payment_status')}} :</span>
                                    <span
                                        class="text-{{$order['payment_status'] == 'paid' ? 'success' : 'danger'}} text-capitalize fw-semibold">{{$order['payment_status']}}</span>
                                </div>
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <span
                                        class="text-muted text-capitalize">{{translate('Return_Payment_method')}} :</span>
                                    <span
                                        class="text-dark text-capitalize fw-semibold">{{ $order?->latestEditHistory?->order_return_payment_method }}</span>
                                </div>
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <span
                                        class="text-muted text-capitalize">{{translate('Return_Amount')}} :</span>
                                    <span
                                        class="text-dark text-capitalize fw-semibold">{{ webCurrencyConverter(amount: $order?->latestEditHistory?->order_return_amount) }}</span>
                                </div>
                                <div class="bg-white py-2 px-2 rounded text-start fs-14">
                                    #Note :{{ $order?->latestEditHistory?->order_return_payment_note }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>


<div class="mt-4">
    <nav>
        <div class="nav nav-nowrap gap-3 gap-xl-4 nav--tabs hide-scrollbar">
            <a href="{{ route('account-order-details', ['id'=>$order->id]) }}"
               class="{{Request::is('account-order-details')  ? 'active' :''}} text-capitalize">{{translate('order_summary')}}</a>
            <a href="{{ route('account-order-details-vendor-info', ['id'=>$order->id]) }}"
               class="{{Request::is('account-order-details-vendor-info')  ? 'active' :''}} text-capitalize">{{translate('vendor_info')}}</a>
            @if($order->order_type != 'POS')
                <a href="{{ route('account-order-details-delivery-man-info', ['id'=>$order->id]) }}"
                   class="{{Request::is('account-order-details-delivery-man-info')  ? 'active' :''}} text-capitalize">{{translate('delivery_man_info')}}</a>
                <a href="{{ route('account-order-details-reviews', ['id'=>$order->id]) }}"
                   class="{{ Request::is('account-order-details-reviews')  ? 'active' :''}} text-capitalize">
                    {{ translate('reviews') }}
                </a>
                <a href="{{route('track-order.order-wise-result-view',['order_id'=>$order['id']])}}"
                   class="{{Request::is('track-order/order-wise-result-view*')  ? 'active' :''}} text-capitalize">
                    {{ translate('track_order') }}
                </a>
            @endif
        </div>
    </nav>
</div>

{{-- Payment Varificaqtion Modal --}}
@if($order->payment_method == 'offline_payment' && isset($order->offlinePayments))
    <div class="modal fade" id="verificationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0 rtl">
                    <div class="d-flex justify-content-end gap-2 p-2">
                        <button class="close-custom-btn btn d-center border-0 fs-16 p-1 w-30 h-30 rounded-pill"
                                type="button" data-bs-dismiss="modal" aria-label="Close">
                            <span class="top--02" aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="pt-0 px-3 px-sm-4 pb-4">
                        <h3 class="text-center mb-4">{{ translate('Payment_Verification') }}</h3>
                        <div class="d-flex flex-column gap-3">

                            @foreach ($order->offlinePayments->payment_info as $key=>$value)
                                @if ($key != 'method_id')
                                    <div class="fs-12 d-flex justify-content-start gap-2">
                                        <span class="text-muted text-capitalize">{{translate($key)}} :</span>
                                        <span class="text-dark text-capitalize fw-semibold"> {{$value ?? "N/a"}}</span>
                                    </div>
                                @endif
                            @endforeach
                            @if($order->payment_note)
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                    <span class="text-muted text-capitalize">{{translate('Payment_Note')}} :</span>
                                    <span
                                        class="text-dark text-capitalize fw-semibold">{{ $order->payment_note }} </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@if($order?->latestEditHistory?->order_due_payment_method === 'offline_payment' && !empty($order?->latestEditHistory?->order_due_payment_info))
    <div class="modal fade" id="orderDuePaymentInfoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0 rtl">
                    <div class="d-flex justify-content-end gap-2 p-2">
                        <button class="close-custom-btn btn d-center border-0 fs-16 p-1 w-30 h-30 rounded-pill"
                                type="button" data-bs-dismiss="modal" aria-label="Close">
                            <span class="top--02" aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="pt-0 px-3 px-sm-4 pb-4">
                        <h3 class="text-center mb-4">{{ translate('Payment_Info') }}</h3>
                        <div class="d-flex flex-column gap-3">
                            @foreach($order->latestEditHistory->order_due_payment_info as $key => $value)
                                <div class="fs-12 d-flex justify-content-start gap-2">
                                <span class="text-muted text-capitalize">
                                    {{ translate($key) }} :
                                </span>
                                    <span class="text-dark fw-semibold">
                                    {{ $value ?? 'N/A' }}
                                </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
{{-- [AI] VM-STORE-001: dead pay-modal include removed (customer-order-edit-pay-amount route purged; retry-pay needs a backend endpoint — follow-up) --}}

@if($canConfirmPickup)
    {{-- In-Shop Pickup Confirmation Modal --}}
    <div class="modal fade" id="confirmInShopPickupModal" tabindex="-1" aria-labelledby="confirmInShopPickupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 border-0 shadow">
                <div class="modal-header border-bottom pb-3">
                    <h5 class="modal-title fs-16 fw-bold text-primary d-flex align-items-center gap-2" id="confirmInShopPickupModalLabel">
                        <i class="fi fi-rr-store-alt"></i> {{ translate('Confirm In-Store Counter Pickup') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('customer.order.confirm-inshop-pickup') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <div class="modal-body p-4">
                        <div class="alert alert-soft-primary p-2 fs-12 mb-3">
                            <i class="fi fi-rr-info me-1"></i>
                            {{ translate('Step 1: Take a photo of your package on the counter. Step 2: Enter the 6-digit Secret Pickup Code provided by the merchant.') }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-dark">
                                {{ translate('1. Photo Proof on Counter') }} <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="image" class="form-control" accept="image/*" capture="environment" required id="pickup-proof-image-input">
                            <small class="text-muted fs-11">{{ translate('Snap directly using your mobile camera or choose from gallery.') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-dark">
                                {{ translate('2. Merchant 6-Digit Pickup Code') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="pickup_code" class="form-control text-center fw-bold fs-20" maxlength="6" pattern="[0-9]{6}" placeholder="• • • • • •" required style="letter-spacing: 6px; font-family: monospace;">
                            <small class="text-muted fs-11">{{ translate('Enter the 6-digit code shown on the merchant screen.') }}</small>
                        </div>

                        <div class="p-2 rounded bg-light border text-center fs-12 text-success fw-semibold">
                            🎁 {{ translate('5% Instant Cashback will be credited to your wallet upon confirmation!') }}
                        </div>
                    </div>
                    <div class="modal-footer border-top pt-3">
                        <button type="button" class="btn btn-secondary px-3 rounded-10" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-10 fw-semibold">{{ translate('Verify & Claim Items') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($canConfirmDelivery)
    {{-- Doorstep Delivery Confirmation Modal --}}
    <div class="modal fade" id="confirmDoorstepDeliveryModal" tabindex="-1" aria-labelledby="confirmDoorstepDeliveryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 border-0 shadow">
                <div class="modal-header border-bottom pb-3">
                    <h5 class="modal-title fs-16 fw-bold text-dark d-flex align-items-center gap-2" id="confirmDoorstepDeliveryModalLabel">
                        <i class="fi fi-rr-biking"></i> {{ translate('Confirm Doorstep Delivery') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('customer.order.confirm-doorstep-delivery') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <div class="modal-body p-4">
                        <div class="alert alert-soft-warning p-2 fs-12 mb-3">
                            <i class="fi fi-rr-info me-1"></i>
                            {{ translate('Step 1: Take a photo of the received parcel. Step 2: Enter the 6-digit Delivery Code displayed on the rider phone.') }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-dark">
                                {{ translate('1. Photo of Parcel Received') }} <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="image" class="form-control" accept="image/*" capture="environment" required id="delivery-proof-image-input">
                            <small class="text-muted fs-11">{{ translate('Snap a quick photo of the parcel in your hand or at your doorstep.') }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-13 fw-semibold text-dark">
                                {{ translate('2. Rider 6-Digit Delivery Code') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="delivery_code" class="form-control text-center fw-bold fs-20" maxlength="6" pattern="[0-9]{6}" placeholder="• • • • • •" required style="letter-spacing: 6px; font-family: monospace;">
                            <small class="text-muted fs-11">{{ translate('Ask the delivery rider to show the 6-digit delivery code from their Rider App screen.') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top pt-3">
                        <button type="button" class="btn btn-secondary px-3 rounded-10" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-warning text-dark px-4 rounded-10 fw-semibold">{{ translate('Verify & Confirm Delivery') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
