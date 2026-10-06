@extends('layouts.admin.app')

@section('title', translate('Live Dispatch Console & Corridor Routing'))

@push('css_or_js')
<style>
    .corridor-card {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .corridor-header {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
        color: #ffffff;
        padding: 16px 20px;
    }
    .corridor-header-inter {
        background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%);
    }
    .badge-package-small {
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
        border: 1px solid #bae6fd;
    }
    .badge-package-large {
        background-color: #fef3c7;
        color: #b45309;
        font-weight: 800;
        border: 1px solid #fde68a;
    }
    .badge-partner-firm {
        background-color: #ede9fe;
        color: #6d28d9;
        font-weight: 700;
        border: 1px solid #ddd6fe;
    }
    .sticky-dispatch-bar {
        position: sticky;
        top: 70px;
        z-index: 105;
        background: #ffffff;
        border-radius: 12px;
        border: 2px solid #4f46e5;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.15);
    }
    .quick-assign-select {
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 6px;
        max-width: 190px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="tio-flight-takeoff text-primary"></i>
                {{ translate('Live Dispatch Console') }}
            </h2>
            <p class="fs-13 text-muted mt-1 mb-0">
                {{ translate('Canonical LGA Corridor Routing, Dual-Fleet Dispatching (In-House & Partner Logistics), and 2-Tier Cargo Matching.') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.delivery-lanes.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="tio-route mr-1"></i> {{ translate('Manage Delivery Lanes') }}
            </a>
            <a href="{{ route('admin.logistics-companies.index') }}" class="btn btn-outline-info btn-sm">
                <i class="tio-building mr-1"></i> {{ translate('Logistics Partners') }}
            </a>
        </div>
    </div>

    {{-- Live KPI Quick Counters --}}
    <div class="row g-2 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card p-3 border-0 shadow-sm d-flex flex-row align-items-center gap-3">
                <div class="avatar avatar-lg rounded bg-soft-primary text-primary d-flex align-items-center justify-content-center">
                    <i class="tio-layers fs-24"></i>
                </div>
                <div>
                    <div class="fs-12 text-muted text-uppercase fw-bold">{{ translate('Ready for Dispatch') }}</div>
                    <h3 class="mb-0 fw-bold">{{ $totalOrdersCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card p-3 border-0 shadow-sm d-flex flex-row align-items-center gap-3">
                <div class="avatar avatar-lg rounded bg-soft-danger text-danger d-flex align-items-center justify-content-center">
                    <i class="tio-warning fs-24"></i>
                </div>
                <div>
                    <div class="fs-12 text-muted text-uppercase fw-bold">{{ translate('Unassigned Orders') }}</div>
                    <h3 class="mb-0 fw-bold text-danger">{{ $unassignedOrdersCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card p-3 border-0 shadow-sm d-flex flex-row align-items-center gap-3">
                <div class="avatar avatar-lg rounded bg-soft-warning text-warning d-flex align-items-center justify-content-center">
                    <i class="tio-car fs-24"></i>
                </div>
                <div>
                    <div class="fs-12 text-muted text-uppercase fw-bold">{{ translate('Bulky Cargo (Van Required)') }}</div>
                    <h3 class="mb-0 fw-bold text-warning">{{ $largeCargoCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card p-3 border-0 shadow-sm d-flex flex-row align-items-center gap-3">
                <div class="avatar avatar-lg rounded bg-soft-success text-success d-flex align-items-center justify-content-center">
                    <i class="tio-bike fs-24"></i>
                </div>
                <div>
                    <div class="fs-12 text-muted text-uppercase fw-bold">{{ translate('Active Couriers / Partners') }}</div>
                    <h3 class="mb-0 fw-bold text-success">{{ count($deliveryMen) }} / {{ count($logisticsCompanies) }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Console --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body py-3">
            <form action="{{ url()->current() }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="title-color fs-12 fw-bold">{{ translate('Origin LGA (Vendor)') }}</label>
                    <select name="origin_lga_id" class="form-control form-control-sm">
                        <option value="">{{ translate('All Origin LGAs') }}</option>
                        @foreach($allLgas as $lga)
                            <option value="{{ $lga->id }}" {{ $selectedOriginLgaId == $lga->id ? 'selected' : '' }}>{{ $lga->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="title-color fs-12 fw-bold">{{ translate('Destination LGA (Customer)') }}</label>
                    <select name="destination_lga_id" class="form-control form-control-sm">
                        <option value="">{{ translate('All Destination LGAs') }}</option>
                        @foreach($allLgas as $lga)
                            <option value="{{ $lga->id }}" {{ $selectedDestLgaId == $lga->id ? 'selected' : '' }}>{{ $lga->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="title-color fs-12 fw-bold">{{ translate('Package Tier') }}</label>
                    <select name="package_tier" class="form-control form-control-sm">
                        <option value="">{{ translate('All Sizes') }}</option>
                        <option value="small" {{ $selectedPackageTier == 'small' ? 'selected' : '' }}>{{ translate('Small 🏍️') }}</option>
                        <option value="large" {{ $selectedPackageTier == 'large' ? 'selected' : '' }}>{{ translate('Large 🚐 (Bulky)') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="title-color fs-12 fw-bold">{{ translate('Assignment') }}</label>
                    <select name="assignment_status" class="form-control form-control-sm">
                        <option value="all" {{ $selectedAssignmentStatus == 'all' ? 'selected' : '' }}>{{ translate('All Orders') }}</option>
                        <option value="unassigned" {{ $selectedAssignmentStatus == 'unassigned' ? 'selected' : '' }}>{{ translate('Unassigned Only') }}</option>
                        <option value="assigned" {{ $selectedAssignmentStatus == 'assigned' ? 'selected' : '' }}>{{ translate('Assigned Only') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="tio-filter"></i> {{ translate('Filter') }}
                    </button>
                    <a href="{{ route('admin.dispatch-portal.index') }}" class="btn btn-secondary btn-sm" title="{{ translate('Reset Filters') }}">
                        <i class="tio-refresh"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Orders Section --}}
    @if(empty($corridors))
        <div class="card p-5 text-center border-0 shadow-sm">
            <div class="mb-3">
                <i class="tio-inbox fs-40 text-muted"></i>
            </div>
            <h4>{{ translate('No pending orders ready for dispatch') }}</h4>
            <p class="text-muted fs-13 mb-0">{{ translate('All active orders in this view have already been dispatched or no matching orders were found.') }}</p>
        </div>
    @else
        <form action="{{ route('admin.dispatch-portal.assign-batch') }}" method="POST" id="batch-dispatch-form">
            @csrf

            {{-- Floating Sticky Batch Action Bar --}}
            <div class="sticky-dispatch-bar p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge badge-primary px-3 py-2 fs-14 font-weight-bold">
                            <i class="tio-layers mr-1"></i> <span id="selected-orders-count">0</span> {{ translate('Orders Selected') }}
                        </span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active target-type-btn" data-type="rider">
                                <i class="tio-bike mr-1"></i> {{ translate('Courier Rider') }}
                            </button>
                            <button type="button" class="btn btn-outline-primary target-type-btn" data-type="company">
                                <i class="tio-building mr-1"></i> {{ translate('Logistics Partner') }}
                            </button>
                        </div>
                        <input type="hidden" name="target_type" id="target-type-input" value="rider">
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-grow-1 justify-content-end" style="max-width: 650px;">
                        {{-- Rider Selector (Default) --}}
                        <div id="rider-select-wrapper" class="flex-grow-1">
                            <select name="delivery_man_id" id="delivery-man-select" class="form-control form-control-sm">
                                <option value="">{{ translate('--- Select Courier Rider (Workload Capacity) ---') }}</option>
                                @foreach($deliveryMen as $dm)
                                    @php
                                        $activeCount = $dm->orders_count;
                                        $maxLimit = $dm->max_active_orders_limit ?? 6;
                                        $available = max(0, $maxLimit - $activeCount);
                                        $isFull = ($available <= 0);
                                        $vIcon = ($dm->vehicle_type === 'van' || $dm->vehicle_type === 'car' || $dm->vehicle_type === 'truck') ? '🚐' : '🏍️';
                                    @endphp
                                    <option value="{{ $dm->id }}"
                                            data-active="{{ $activeCount }}"
                                            data-max="{{ $maxLimit }}"
                                            data-available="{{ $available }}"
                                            data-vehicle="{{ $dm->vehicle_type }}"
                                            {{ $isFull ? 'disabled' : '' }}>
                                        {{ $isFull ? '🔴' : ($available <= 1 ? '🟡' : '🟢') }}
                                        {{ $vIcon }} {{ $dm->f_name }} {{ $dm->l_name }}
                                        [{{ !empty($dm->logisticsCompany) ? $dm->logisticsCompany->name : 'In-House' }}]
                                        (Active: {{ $activeCount }}/{{ $maxLimit }})
                                        - {{ $isFull ? translate('FULL') : ($available . ' slots') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Logistics Partner Company Selector --}}
                        <div id="company-select-wrapper" class="flex-grow-1" style="display: none;">
                            <select name="logistics_company_id" id="logistics-company-select" class="form-control form-control-sm">
                                <option value="">{{ translate('--- Select Accredited Logistics Partner ---') }}</option>
                                @foreach($logisticsCompanies as $comp)
                                    <option value="{{ $comp->id }}">
                                        🏢 {{ $comp->name }} (Fleet: {{ $comp->delivery_men_count }} riders | Active: {{ $comp->orders_count }} orders)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="button" id="submit-batch-btn" class="btn btn-success btn-sm px-3 text-nowrap fw-bold">
                            <i class="tio-send mr-1"></i> {{ translate('Dispatch Batch') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Corridor Cards --}}
            <div class="row g-3">
                @foreach($corridors as $cKey => $corridor)
                    <div class="col-12">
                        <div class="corridor-card">
                            {{-- Corridor Header --}}
                            <div class="corridor-header {{ $corridor['is_inter_lga'] ? 'corridor-header-inter' : '' }} d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" class="corridor-select-all" data-target=".corridor-{{ $cKey }}">
                                    <h5 class="mb-0 text-white font-weight-bold d-flex align-items-center gap-2">
                                        <span><i class="tio-poi"></i> {{ $corridor['origin_name'] }}</span>
                                        <i class="tio-arrow-forward mx-1 opacity-75"></i>
                                        <span><i class="tio-map-marker-outlined"></i> {{ $corridor['dest_name'] }}</span>
                                        @if($corridor['is_inter_lga'])
                                            <span class="badge badge-warning text-dark ml-2 fs-11">{{ translate('Inter-LGA Corridor') }}</span>
                                        @else
                                            <span class="badge badge-info ml-2 fs-11">{{ translate('Local / Intra-LGA') }}</span>
                                        @endif
                                        @if($corridor['large_count'] > 0)
                                            <span class="badge badge-package-large ml-1 fs-11">🚐 {{ $corridor['large_count'] }} {{ translate('Bulky Cargo') }}</span>
                                        @endif
                                    </h5>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge badge-light text-dark fw-bold">{{ count($corridor['orders']) }} {{ translate('Orders') }}</span>
                                    <span class="fw-bold text-white fs-14">₦{{ number_format($corridor['total_amount'], 2) }}</span>
                                    @php
                                        $orderIdList = implode(',', array_map(function($o) { return $o->id; }, $corridor['orders']));
                                    @endphp
                                    <a href="{{ route('admin.dispatch-portal.print-manifest', ['order_ids' => $orderIdList]) }}"
                                       target="_blank" class="btn btn-light btn-xs font-weight-bold" title="{{ translate('Print Corridor Trip Sheet') }}">
                                        <i class="tio-print mr-1"></i> {{ translate('Trip Manifest') }}
                                    </a>
                                </div>
                            </div>

                            {{-- Orders Table in Corridor --}}
                            <div class="table-responsive">
                                <table class="table table-hover table-borderless table-thead-bordered text-center align-middle mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th>{{ translate('Order ID') }}</th>
                                            <th class="text-left">{{ translate('Vendor (Origin)') }}</th>
                                            <th class="text-left">{{ translate('Customer (Destination)') }}</th>
                                            <th>{{ translate('Package Tier') }}</th>
                                            <th>{{ translate('Fee') }}</th>
                                            <th>{{ translate('OTPs') }}</th>
                                            <th>{{ translate('Current Custody') }}</th>
                                            <th>{{ translate('Fast 1-Click Dispatch') }}</th>
                                            <th>{{ translate('Print') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($corridor['orders'] as $order)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="order-checkbox corridor-{{ $cKey }}">
                                                </td>
                                                <td class="font-weight-bold">
                                                    <a href="{{ route('admin.orders.details', ['id' => $order->id]) }}" class="text-primary font-weight-bold" target="_blank">
                                                        #{{ $order->id }}
                                                    </a>
                                                </td>
                                                <td class="text-left">
                                                    <span class="font-weight-bold fs-13">{{ $order->seller->shop->name ?? 'Victorious Market In-House' }}</span>
                                                    <div class="fs-11 text-muted">{{ $order->origin_lga_name ?? ($order->seller->shop->address ?? '') }}</div>
                                                </td>
                                                <td class="text-left" style="max-width: 200px;">
                                                    <span class="font-weight-bold fs-13">{{ $order->customer->f_name ?? 'Customer' }} {{ $order->customer->l_name ?? '' }}</span>
                                                    <div class="fs-11 text-dark text-truncate" title="{{ $order->destination_lga_name }}">{{ $order->destination_lga_name }} - {{ $order->shipping_address ?? '' }}</div>
                                                    <div class="fs-11 text-muted">{{ $order->customer->phone ?? '' }}</div>
                                                </td>
                                                <td>
                                                    @if($order->package_tier === 'large')
                                                        <span class="badge badge-package-large px-2 py-1">
                                                            🚐 {{ translate('LARGE (Bulky)') }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-package-small px-2 py-1">
                                                            🏍️ {{ translate('SMALL (Courier)') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="font-weight-bold text-dark fs-13">
                                                    ₦{{ number_format($order->shipping_cost, 2) }}
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column gap-1">
                                                        @if(!empty($order->pickup_verification_code))
                                                            <span class="badge badge-soft-info fs-10" title="{{ translate('Merchant Pickup OTP') }}">
                                                                P: {{ $order->pickup_verification_code }}
                                                            </span>
                                                        @endif
                                                        @if(!empty($order->verification_code))
                                                            <span class="badge badge-soft-success fs-10" title="{{ translate('Customer Delivery OTP') }}">
                                                                D: {{ $order->verification_code }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td id="custody-badge-{{ $order->id }}">
                                                    @if($order->deliveryMan)
                                                        <span class="badge badge-soft-success font-weight-bold px-2 py-1">
                                                            <i class="tio-bike mr-1"></i> {{ $order->deliveryMan->f_name }} {{ $order->deliveryMan->l_name }}
                                                        </span>
                                                    @elseif($order->logisticsCompany)
                                                        <span class="badge badge-partner-firm font-weight-bold px-2 py-1">
                                                            <i class="tio-building mr-1"></i> {{ $order->logisticsCompany->name }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-soft-danger px-2 py-1 font-weight-bold">
                                                            <i class="tio-warning mr-1"></i> {{ translate('Unassigned') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        <select class="custom-select custom-select-sm quick-assign-select" id="quick-assign-{{ $order->id }}">
                                                            <option value="">{{ translate('-- Assign to --') }}</option>
                                                            <optgroup label="{{ translate('In-House Couriers') }}">
                                                                @foreach($deliveryMen as $dm)
                                                                    <option value="rider_{{ $dm->id }}" {{ $order->delivery_man_id == $dm->id ? 'selected' : '' }}>
                                                                        🏍️ {{ $dm->f_name }} {{ $dm->l_name }}
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                            <optgroup label="{{ translate('Logistics Partners') }}">
                                                                @foreach($logisticsCompanies as $comp)
                                                                    <option value="company_{{ $comp->id }}" {{ $order->logistics_company_id == $comp->id ? 'selected' : '' }}>
                                                                        🏢 {{ $comp->name }}
                                                                    </option>
                                                                @endforeach
                                                            </optgroup>
                                                            @if($order->delivery_man_id || $order->logistics_company_id)
                                                                <option value="unassign_0" class="text-danger">{{ translate('❌ Unassign') }}</option>
                                                            @endif
                                                        </select>
                                                        <button type="button" class="btn btn-outline-primary btn-xs quick-assign-btn square-btn" data-order-id="{{ $order->id }}" title="{{ translate('Apply Assignment') }}">
                                                            <i class="tio-checkmark"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.dispatch-portal.print-waybill', [$order->id]) }}" target="_blank" class="btn btn-outline-info btn-xs square-btn" title="{{ translate('Print Waybill Label') }}">
                                                        <i class="tio-print"></i>
                                                    </a>
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
    // Live Counter Update
    function updateSelectedCounter() {
        var count = $('.order-checkbox:checked').length;
        $('#selected-orders-count').text(count);
    }

    $('.order-checkbox').on('change', function() {
        updateSelectedCounter();
    });

    $('.corridor-select-all').on('change', function() {
        var targetClass = $(this).data('target');
        $(targetClass).prop('checked', $(this).prop('checked'));
        updateSelectedCounter();
    });

    // Toggle Rider vs Partner Company in Batch Action Bar
    $('.target-type-btn').on('click', function() {
        $('.target-type-btn').removeClass('active');
        $(this).addClass('active');

        var type = $(this).data('type');
        $('#target-type-input').val(type);

        if (type === 'company') {
            $('#rider-select-wrapper').hide();
            $('#delivery-man-select').prop('required', false);
            $('#company-select-wrapper').show();
            $('#logistics-company-select').prop('required', true);
        } else {
            $('#company-select-wrapper').hide();
            $('#logistics-company-select').prop('required', false);
            $('#rider-select-wrapper').show();
            $('#delivery-man-select').prop('required', true);
        }
    });

    // Batch Dispatch Submission
    $('#submit-batch-btn').on('click', function(e) {
        e.preventDefault();
        var selectedCount = $('.order-checkbox:checked').length;
        if (selectedCount === 0) {
            alert('{{ translate("Please select at least 1 order to dispatch.") }}');
            return;
        }

        var targetType = $('#target-type-input').val();
        if (targetType === 'rider') {
            var riderSelect = $('#delivery-man-select');
            if (!riderSelect.val()) {
                alert('{{ translate("Please select a courier rider for this batch.") }}');
                riderSelect.focus();
                return;
            }
            var selectedOption = riderSelect.find(':selected');
            var availableSlots = parseInt(selectedOption.data('available')) || 0;
            if (selectedCount > availableSlots) {
                alert('{{ translate("Rider capacity limit exceeded! Available slots:") }} ' + availableSlots);
                return;
            }
        } else {
            var compSelect = $('#logistics-company-select');
            if (!compSelect.val()) {
                alert('{{ translate("Please select a Logistics Partner Company for this batch.") }}');
                compSelect.focus();
                return;
            }
        }

        $('#batch-dispatch-form').submit();
    });

    // Fast 1-Click Inline Dispatch AJAX
    $('.quick-assign-btn').on('click', function() {
        var orderId = $(this).data('order-id');
        var selectVal = $('#quick-assign-' + orderId).val();

        if (!selectVal) {
            alert('{{ translate("Please select a rider or partner company.") }}');
            return;
        }

        var parts = selectVal.split('_');
        var targetType = parts[0];
        var targetId = parts[1];

        var btn = $(this);
        btn.prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.dispatch-portal.assign-single') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                order_id: orderId,
                target_type: targetType,
                target_id: targetId
            },
            success: function(res) {
                btn.prop('disabled', false);
                if (res.status) {
                    location.reload();
                } else {
                    alert(res.message);
                }
            },
            error: function(err) {
                btn.prop('disabled', false);
                var msg = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : 'An error occurred';
                alert(msg);
            }
        });
    });
</script>
@endpush
@endsection
