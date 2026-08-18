@extends('layouts.admin.app')

@section('title', translate('Delivery Corridor & Batch Dispatch Portal'))

@section('content')
<div class="content container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="tio-flight-takeoff"></i>
                {{ translate('Corridor & Cluster Batch Dispatch Portal') }}
            </h2>
            <p class="fs-12 text-muted mt-1">
                {{ translate('Cluster and batch multiple orders by geographic corridor for single-trip rider pickup and delivery, with strict rider capacity limit enforcement.') }}
            </p>
        </div>
        <a href="{{ route('admin.delivery-hubs.index') }}" class="btn btn-outline--primary">
            <i class="tio-settings mr-1"></i> {{ translate('Manage Hubs & Landmarks') }}
        </a>
    </div>

    <!-- Active Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form action="{{ url()->current() }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="title-color fs-12">{{ translate('Delivery State') }}</label>
                    <select name="state_id" class="form-control form-control-sm" id="filter-state">
                        <option value="">{{ translate('All States') }}</option>
                        @foreach($states as $st)
                            <option value="{{ $st->id }}" {{ $selectedStateId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="title-color fs-12">{{ translate('Delivery City') }}</label>
                    <select name="city_id" class="form-control form-control-sm" id="filter-city">
                        <option value="">{{ translate('All Cities') }}</option>
                        @foreach($cities as $ct)
                            <option value="{{ $ct->id }}" {{ $selectedCityId == $ct->id ? 'selected' : '' }}>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="title-color fs-12">{{ translate('Delivery Channel') }}</label>
                    <select name="delivery_type" class="form-control form-control-sm">
                        <option value="">{{ translate('All Channels') }}</option>
                        <option value="intra_city_landmark" {{ $selectedDeliveryType == 'intra_city_landmark' ? 'selected' : '' }}>{{ translate('Intra-City Landmark') }}</option>
                        <option value="interstate_park_waybill" {{ $selectedDeliveryType == 'interstate_park_waybill' ? 'selected' : '' }}>{{ translate('Interstate Motor Park Waybill') }}</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn--primary btn-sm flex-grow-1"><i class="tio-filter"></i> {{ translate('Filter Board') }}</button>
                    <a href="{{ route('admin.dispatch-portal.index') }}" class="btn btn-secondary btn-sm"><i class="tio-refresh"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Corridor Clusters Section -->
    @if(empty($corridors))
        <div class="card p-5 text-center">
            <div class="mb-3">
                <i class="tio-inbox fs-40 text-muted"></i>
            </div>
            <h4>{{ translate('No pending orders ready for batch dispatch') }}</h4>
            <p class="text-muted fs-13">{{ translate('All confirmed orders have either been assigned or there are currently no new orders in the queue.') }}</p>
        </div>
    @else
        <form action="{{ route('admin.dispatch-portal.assign-batch') }}" method="POST" id="batch-dispatch-form">
            @csrf

            <!-- Floating Top Batch Bar -->
            <div class="card mb-4 shadow-sm border-primary sticky-top" style="top: 70px; z-index: 100;">
                <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge badge-primary px-3 py-2 fs-14">
                            <i class="tio-layers mr-1"></i> <span id="selected-orders-count">0</span> {{ translate('Orders Selected') }}
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-grow-1 justify-content-end" style="max-width: 600px;">
                        <select name="delivery_man_id" id="delivery-man-select" class="form-control" required>
                            <option value="">{{ translate('--- Select Delivery Rider (Live Capacity) ---') }}</option>
                            @foreach($deliveryMen as $dm)
                                @php
                                    $activeCount = $dm->orders_count;
                                    $maxLimit = $dm->max_active_orders_limit ?? 4;
                                    $available = max(0, $maxLimit - $activeCount);
                                    $isFull = ($available <= 0);
                                @endphp
                                <option value="{{ $dm->id }}" 
                                        data-active="{{ $activeCount }}" 
                                        data-max="{{ $maxLimit }}" 
                                        data-available="{{ $available }}"
                                        {{ $isFull ? 'disabled' : '' }}>
                                    {{ $isFull ? '🔴' : ($available <= 1 ? '🟡' : '🟢') }}
                                    {{ $dm->f_name }} {{ $dm->l_name }} 
                                    [{{ translate('Active:') }} {{ $activeCount }}/{{ $maxLimit }}] 
                                    - {{ $isFull ? translate('FULL CAPACITY') : ($available . ' ' . translate('slots left')) }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button" id="submit-batch-btn" class="btn btn-success text-nowrap px-4 font-weight-bold">
                            <i class="tio-send mr-1"></i> {{ translate('Dispatch Batch') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Corridor Cards Grid -->
            <div class="row g-3">
                @foreach($corridors as $cKey => $corridor)
                    <div class="col-12">
                        <div class="card border mb-3">
                            <div class="card-header bg-soft-primary d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" class="corridor-select-all" data-target=".corridor-{{ $cKey }}">
                                    <h5 class="mb-0 text-dark font-weight-bold">
                                        <span class="text-primary"><i class="tio-poi"></i> {{ $corridor['origin_name'] }}</span>
                                        <i class="tio-arrow-forward mx-2 text-muted"></i>
                                        <span class="text-success"><i class="{{ $corridor['dest_type'] == 'motor_park' ? 'tio-bus' : 'tio-poi' }}"></i> {{ $corridor['dest_name'] }}</span>
                                        @if($corridor['dest_type'] == 'motor_park')
                                            <span class="badge badge-warning ml-2">{{ translate('Interstate Waybill') }}</span>
                                        @else
                                            <span class="badge badge-info ml-2">{{ translate('Local Landmark') }}</span>
                                        @endif
                                    </h5>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge badge-soft-dark">{{ count($corridor['orders']) }} {{ translate('Orders') }}</span>
                                    <span class="font-weight-bold text-dark">₦{{ number_format($corridor['total_amount'], 2) }}</span>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-borderless table-thead-bordered text-center align-middle mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th>{{ translate('Order ID') }}</th>
                                            <th>{{ translate('Vendor Shop') }}</th>
                                            <th>{{ translate('Customer / Recipient') }}</th>
                                            <th>{{ translate('Street / House Note') }}</th>
                                            <th>{{ translate('Amount') }}</th>
                                            <th>{{ translate('Pickup OTP') }}</th>
                                            <th>{{ translate('Assigned Rider') }}</th>
                                            <th>{{ translate('Status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($corridor['orders'] as $order)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="order-checkbox corridor-{{ $cKey }}">
                                                </td>
                                                <td class="font-weight-bold">
                                                    <a href="{{ route('admin.orders.details', ['id' => $order->id]) }}" class="text-primary">
                                                        #{{ $order->id }}
                                                    </a>
                                                </td>
                                                <td class="text-left">
                                                    <span class="font-weight-bold">{{ $order->seller->shop->name ?? 'In-House Store' }}</span>
                                                    <div class="fs-11 text-muted">{{ $order->seller->shop->address ?? '' }}</div>
                                                </td>
                                                <td>
                                                    <span class="font-weight-bold">{{ $order->recipient_name ?? ($order->customer->f_name ?? 'Guest Buyer') }}</span>
                                                    <div class="fs-11 text-muted">{{ $order->recipient_phone ?? ($order->customer->phone ?? 'N/A') }}</div>
                                                </td>
                                                <td class="text-left text-wrap" style="max-width: 250px;">
                                                    <span class="fs-12">{{ $order->house_street_note ?? ($order->shipping_address ?? 'Standard drop-off') }}</span>
                                                </td>
                                                <td class="font-weight-bold text-dark">
                                                    ₦{{ number_format($order->order_amount, 2) }}
                                                </td>
                                                <td>
                                                    @if(!empty($order->pickup_verification_code))
                                                        <span class="badge badge-soft-primary px-2 py-1 font-weight-bold">{{ $order->pickup_verification_code }}</span>
                                                    @else
                                                        <span class="badge badge-secondary fs-10">{{ translate('Auto-Gen') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($order->deliveryMan)
                                                        <span class="badge badge-soft-success font-weight-bold">
                                                            <i class="tio-bike mr-1"></i> {{ $order->deliveryMan->f_name }} {{ $order->deliveryMan->l_name }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-soft-danger">{{ translate('Unassigned') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $order->order_status == 'confirmed' ? 'info' : ($order->order_status == 'processing' ? 'warning' : 'primary') }}">
                                                        {{ translate($order->order_status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    @endif
</div>

@push('script')
<script>
    // Update live counter when checkboxes change
    function updateSelectedCounter() {
        var count = $('.order-checkbox:checked').length;
        $('#selected-orders-count').text(count);
    }

    $('.order-checkbox').on('change', function() {
        updateSelectedCounter();
    });

    // Select all per corridor
    $('.corridor-select-all').on('change', function() {
        var targetClass = $(this).data('target');
        $(targetClass).prop('checked', $(this).prop('checked'));
        updateSelectedCounter();
    });

    // Capacity Limit Guard Check on Submit
    $('#submit-batch-btn').on('click', function(e) {
        e.preventDefault();
        var selectedCount = $('.order-checkbox:checked').length;
        if (selectedCount === 0) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('{{ translate("Please select at least 1 order to dispatch.") }}');
            } else {
                alert('{{ translate("Please select at least 1 order to dispatch.") }}');
            }
            return;
        }

        var riderSelect = $('#delivery-man-select');
        var riderId = riderSelect.val();
        if (!riderId) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('{{ translate("Please select a delivery rider for this batch.") }}');
            } else {
                alert('{{ translate("Please select a delivery rider for this batch.") }}');
            }
            riderSelect.focus();
            return;
        }

        var selectedOption = riderSelect.find(':selected');
        var activeOrders = parseInt(selectedOption.data('active')) || 0;
        var maxLimit = parseInt(selectedOption.data('max')) || 4;
        var availableSlots = parseInt(selectedOption.data('available')) || 0;

        if (selectedCount > availableSlots) {
            var msg = '{{ translate("Rider capacity limit exceeded!") }} ' + 
                '{{ translate("This rider can only take") }} ' + availableSlots + ' {{ translate("more order(s). You selected") }} ' + selectedCount + '.';
            if (typeof toastr !== 'undefined') {
                toastr.error(msg);
            } else {
                alert(msg);
            }
            return;
        }

        $('#batch-dispatch-form').submit();
    });
</script>
@endpush
@endsection
