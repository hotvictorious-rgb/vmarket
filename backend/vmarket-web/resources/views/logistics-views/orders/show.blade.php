@extends('logistics-views.layouts.app')

@section('title', translate('Waybill_#') . $order->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h3 fw-bold mb-1">
                {{ translate('Waybill_#') }}{{ $order->id }}
                @if($order->package_tier === 'large')
                    <span class="badge badge-soft-danger fs-14 ms-2">🚐 {{ translate('Large_Bulky_Cargo') }}</span>
                @else
                    <span class="badge badge-soft-info fs-14 ms-2">🏍️ {{ translate('Standard_Package') }}</span>
                @endif
            </h2>
            <div class="fs-12 text-muted">
                {{ translate('Placed_On:') }} {{ $order->created_at->format('M d, Y h:i A') }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('logistics.orders.index') }}" class="btn btn-secondary">
                <i class="fi fi-sr-arrow-left me-1"></i> {{ translate('Back_to_Orders') }}
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Waybill Route & Handover Status --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-12 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">{{ translate('Route_&_Handover_Milestones') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6 border-end">
                            <h6 class="text-muted fs-12 text-uppercase mb-2">📍 {{ translate('Merchant_Pickup_Location') }}</h6>
                            <div class="fw-bold fs-15">{{ $order->seller->shop->name ?? translate('Victorious_Market_Hub') }}</div>
                            <div class="fs-13 text-muted">{{ $order->seller->shop->address ?? translate('Akwa_Ibom_State') }}</div>
                            <div class="fs-13 text-muted">{{ $order->origin_lga_name ?? '' }}</div>
                            <div class="mt-2">
                                <span class="badge {{ $order->order_status !== 'confirmed' && $order->order_status !== 'processing' ? 'badge-soft-success' : 'badge-soft-warning' }}">
                                    {{ $order->order_status !== 'confirmed' && $order->order_status !== 'processing' ? translate('Picked_Up_From_Merchant') : translate('Awaiting_Pickup') }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted fs-12 text-uppercase mb-2">🏠 {{ translate('Customer_Doorstep_Delivery') }}</h6>
                            <div class="fw-bold fs-15">{{ $order->customer->f_name ?? translate('Customer') }} {{ $order->customer->l_name ?? '' }}</div>
                            <div class="fs-13 text-muted">{{ $order->destination_lga_name ?? '' }}, {{ $order->destination_state_name ?? '' }}</div>
                            @if($order->shipping_address_data)
                                @php($addr = json_decode($order->shipping_address_data, true))
                                <div class="fs-13 text-muted">{{ $addr['address'] ?? '' }}</div>
                                <div class="fs-13 text-muted">{{ $addr['phone'] ?? '' }}</div>
                            @endif
                            <div class="mt-2">
                                <span class="badge {{ $order->order_status === 'delivered' ? 'badge-soft-success' : 'badge-soft-primary' }}">
                                    {{ $order->order_status === 'delivered' ? translate('Delivered_to_Customer') : translate('Out_for_Delivery') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Parcels / Items List --}}
            <div class="card border-0 shadow-sm rounded-12">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">{{ translate('Package_Items') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-nowrap align-middle mb-0">
                            <thead class="table-light fs-12">
                                <tr>
                                    <th>{{ translate('Product') }}</th>
                                    <th>{{ translate('Package_Size') }}</th>
                                    <th>{{ translate('Qty') }}</th>
                                    <th>{{ translate('Unit_Price') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($order->details as $detail)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $detail->product->name ?? $detail->product_details['name'] ?? 'Product' }}</div>
                                        @if($detail->variant)
                                            <div class="fs-12 text-muted">Variant: {{ $detail->variant }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(($detail->product->package_size ?? 'small') === 'large')
                                            <span class="badge badge-soft-danger">🚐 Large</span>
                                        @else
                                            <span class="badge badge-soft-info">🏍️ Small</span>
                                        @endif
                                    </td>
                                    <td>{{ $detail->qty }}</td>
                                    <td>₦{{ number_format($detail->price, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Financial Settlement & Rider Attribution --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-12 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">{{ translate('Delivery_Financials') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2 fs-14">
                        <span class="text-muted">{{ translate('Gross_Delivery_Fee:') }}</span>
                        <span class="fw-bold">₦{{ number_format($order->shipping_cost, 2) }}</span>
                    </div>
                    @if($order->bulky_surcharge_amount > 0)
                        <div class="d-flex justify-content-between mb-2 fs-14">
                            <span class="text-muted">{{ translate('Bulky_Cargo_Surcharge:') }}</span>
                            <span class="text-danger fw-semibold">+₦{{ number_format($order->bulky_surcharge_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2 fs-14">
                        <span class="text-muted">{{ translate('Platform_Fee_(-15%):') }}</span>
                        <span class="text-danger">-₦{{ number_format($order->delivery_commission_amount ?? round(($order->shipping_cost * 15)/100, 2), 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fs-16">
                        <span class="fw-bold text-dark">{{ translate('Net_Partner_Credit:') }}</span>
                        <span class="fw-bold text-success fs-18">
                            ₦{{ number_format(max(0, $order->shipping_cost - ($order->delivery_commission_amount ?? round(($order->shipping_cost * 15)/100, 2))), 2) }}
                        </span>
                    </div>
                    <small class="text-muted fs-11 mt-2 d-block">
                        {{ translate('Funds_are_credited_to_your_wallet_instantly_upon_verified_doorstep_delivery.') }}
                    </small>
                </div>
            </div>

            {{-- Assigned Rider --}}
            <div class="card border-0 shadow-sm rounded-12">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="fw-bold mb-0">{{ translate('Assigned_Rider') }}</h5>
                </div>
                <div class="card-body">
                    @if($order->deliveryMan)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar avatar-md border rounded bg-light d-flex align-items-center justify-content-center">
                                <span class="fw-bold text-primary">{{ strtoupper(substr($order->deliveryMan->f_name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $order->deliveryMan->f_name }} {{ $order->deliveryMan->l_name }}</div>
                                <div class="fs-12 text-muted">{{ $order->deliveryMan->phone }}</div>
                                <span class="badge badge-soft-info text-capitalize mt-1">
                                    {{ $order->deliveryMan->vehicle_type ?? 'motorbike' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-3 text-muted">
                            <p class="fs-13 mb-3">{{ translate('No_rider_assigned_yet.') }}</p>
                            <button type="button" class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#assignModal">
                                <i class="fi fi-sr-user-add me-1"></i> {{ translate('Assign_Fleet_Rider') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
