@extends('layouts.admin.app')

@section('title', translate('Delivery Lanes & National Shipping Matrix'))

@push('css_or_js')
<style>
    .lane-nav-tabs .nav-link {
        font-weight: 700;
        font-size: 14px;
        padding: 12px 24px;
        color: #475569;
        border: none;
        border-bottom: 3px solid transparent;
        background: transparent;
    }
    .lane-nav-tabs .nav-link.active {
        color: #4f46e5;
        border-bottom: 3px solid #4f46e5;
        background: transparent;
    }
    .badge-lane-intra {
        background-color: #e0f2fe;
        color: #0369a1;
        font-weight: 700;
        border: 1px solid #bae6fd;
    }
    .badge-lane-inter {
        background-color: #f3e8ff;
        color: #7e22ce;
        font-weight: 700;
        border: 1px solid #e9d5ff;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    {{-- Header --}}
    <div class="mb-4">
        <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2">
            <i class="tio-directions text-primary"></i>
            {{ translate('Delivery Lanes & Shipping Matrix') }}
        </h2>
        <p class="fs-13 text-muted mb-0">
            {{ translate('2-Tier Hierarchical Routing: Configure local intra-state courier lanes (LGA to LGA), national interstate corridors (State to State), and safety-net fallback rates.') }}
        </p>
    </div>

    {{-- Navigation Tabs --}}
    <ul class="nav nav-tabs lane-nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $laneType === 'intra_state' ? 'active' : '' }}" 
               href="{{ route('admin.delivery-lanes.index', ['lane_type' => 'intra_state']) }}">
                <i class="tio-bike mr-1"></i> {{ translate('Local Intra-State (LGA to LGA)') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $laneType === 'inter_state' ? 'active' : '' }}" 
               href="{{ route('admin.delivery-lanes.index', ['lane_type' => 'inter_state']) }}">
                <i class="tio-truck mr-1"></i> {{ translate('National Inter-State (State to State)') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $laneType === 'default_rates' ? 'active' : '' }}" 
               href="{{ route('admin.delivery-lanes.index', ['lane_type' => 'default_rates']) }}">
                <i class="tio-settings mr-1"></i> {{ translate('Safety-Net Default Rates') }}
            </a>
        </li>
    </ul>

    @if($laneType === 'default_rates')
        {{-- TAB 3: DEFAULT SAFETY-NET RATES --}}
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="tio-shield-check text-success mr-1"></i> {{ translate('Nationwide Default Delivery Fallback Rates') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted fs-13 mb-4">
                            {{ translate('These rates act as automatic safety-net fallbacks whenever a customer checks out between locations that have not had a custom route individually defined. This mathematically guarantees zero checkout blockages across all 774 LGAs and 36 States in Nigeria.') }}
                        </p>

                        <form action="{{ route('admin.delivery-lanes.update-default-rates') }}" method="POST">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card p-3 bg-soft-primary border border-primary h-100">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="tio-bike fs-24 text-primary"></i>
                                            <h6 class="mb-0 fw-bold text-dark">{{ translate('Default Intra-State Fee (Local)') }}</h6>
                                        </div>
                                        <p class="fs-12 text-muted mb-3">
                                            {{ translate('Applied when merchant and buyer are in the same state, but no custom LGA-to-LGA rate is set.') }}
                                        </p>
                                        <div class="form-group mb-0">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text fw-bold">₦</span>
                                                </div>
                                                <input type="number" step="0.01" min="0" name="default_intrastate_delivery_fee" 
                                                       class="form-control" value="{{ $defaultIntraFee }}" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card p-3 bg-soft-secondary border border-purple h-100" style="border-color: #8b5cf6 !important;">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="tio-truck fs-24" style="color: #7c3aed;"></i>
                                            <h6 class="mb-0 fw-bold text-dark">{{ translate('Default Inter-State Fee (National)') }}</h6>
                                        </div>
                                        <p class="fs-12 text-muted mb-3">
                                            {{ translate('Applied when merchant and buyer are in different states, but no custom State-to-State corridor is set.') }}
                                        </p>
                                        <div class="form-group mb-0">
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text fw-bold">₦</span>
                                                </div>
                                                <input type="number" step="0.01" min="0" name="default_interstate_delivery_fee" 
                                                       class="form-control" value="{{ $defaultInterFee }}" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary px-4 fw-bold">
                                    <i class="tio-save mr-1"></i> {{ translate('Save Default Rates') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    @elseif($laneType === 'inter_state')
        {{-- TAB 2: INTER-STATE (STATE TO STATE) LANES --}}
        <div class="row g-3">
            {{-- Create Form --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="tio-add-circle mr-1 text-primary"></i> {{ translate('Add Inter-State Lane') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.delivery-lanes.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="lane_type" value="inter_state">

                            <div class="border rounded p-3 mb-3 bg-soft-secondary">
                                <h6 class="fw-bold text-dark mb-2">
                                    <i class="tio-flight-takeoff text-primary mr-1"></i> {{ translate('Origin State') }}
                                </h6>
                                <div class="form-group mb-2">
                                    <label class="title-color fs-12">{{ translate('Country') }}</label>
                                    <select class="form-control form-control-sm" name="origin_country_id" required>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" {{ $country->iso_code === 'NGA' ? 'selected' : '' }}>{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="title-color fs-12">{{ translate('State') }} <span class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" name="origin_state_id" required>
                                        <option value="">{{ translate('--- Select Origin State ---') }}</option>
                                        @foreach($states as $st)
                                            <option value="{{ $st->id }}" {{ $st->name === 'Akwa Ibom' ? 'selected' : '' }}>{{ $st->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="border rounded p-3 mb-3 bg-soft-info">
                                <h6 class="fw-bold text-dark mb-2">
                                    <i class="tio-flight-land text-info mr-1"></i> {{ translate('Destination State') }}
                                </h6>
                                <div class="form-group mb-2">
                                    <label class="title-color fs-12">{{ translate('Country') }}</label>
                                    <select class="form-control form-control-sm" name="destination_country_id" required>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" {{ $country->iso_code === 'NGA' ? 'selected' : '' }}>{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="title-color fs-12">{{ translate('State') }} <span class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" name="destination_state_id" required>
                                        <option value="">{{ translate('--- Select Destination State ---') }}</option>
                                        @foreach($states as $st)
                                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="border rounded p-3 mb-3 bg-light">
                                <h6 class="fw-bold text-dark mb-2">
                                    <i class="tio-money-vs text-success mr-1"></i> {{ translate('Shipping Terms') }}
                                </h6>
                                <div class="form-group mb-2">
                                    <label class="title-color fs-12">{{ translate('Interstate Delivery Fee') }} (₦) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="delivery_fee" class="form-control form-control-sm" placeholder="4500.00" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="title-color fs-12">{{ translate('Estimated Delivery Duration') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="estimated_delivery_time" class="form-control form-control-sm" placeholder="2-4 business days" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block fw-bold">
                                <i class="tio-add mr-1"></i> {{ translate('Create Interstate Lane') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Table List --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="tio-truck mr-1 text-primary"></i> {{ translate('Inter-State Corridors (State to State)') }}
                            <span class="badge badge-soft-dark ml-2">{{ $lanes->total() }}</span>
                        </h5>
                        <form action="{{ url()->current() }}" method="GET" class="d-flex gap-2">
                            <input type="hidden" name="lane_type" value="inter_state">
                            <input type="search" name="searchValue" class="form-control form-control-sm" 
                                   placeholder="{{ translate('Search State...') }}" value="{{ $searchValue }}">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="tio-search"></i></button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered align-middle text-center mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-left">{{ translate('Origin State') }}</th>
                                    <th class="text-left">{{ translate('Destination State') }}</th>
                                    <th>{{ translate('Delivery Fee') }}</th>
                                    <th>{{ translate('Estimated Lead Time') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                    <th>{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lanes as $key => $lane)
                                    <tr>
                                        <td>{{ $lanes->firstItem() + $key }}</td>
                                        <td class="text-left">
                                            <span class="fw-bold text-dark fs-13">{{ $lane->originState->name ?? 'N/A' }}</span>
                                        </td>
                                        <td class="text-left">
                                            <span class="fw-bold text-primary fs-13">{{ $lane->destinationState->name ?? 'N/A' }}</span>
                                        </td>
                                        <td class="fw-bold text-dark fs-14">
                                            ₦{{ number_format($lane->delivery_fee, 2) }}
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-info">{{ $lane->estimated_delivery_time ?? '2-4 business days' }}</span>
                                        </td>
                                        <td>
                                            <label class="switcher mx-auto">
                                                <input type="checkbox" class="switcher_input status-toggle" 
                                                       data-id="{{ $lane->id }}" {{ $lane->is_enabled ? 'checked' : '' }}>
                                                <span class="switcher_control"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <button type="button" class="btn btn-outline-primary btn-xs square-btn edit-lane-btn"
                                                        data-id="{{ $lane->id }}"
                                                        data-fee="{{ $lane->delivery_fee }}"
                                                        data-time="{{ $lane->estimated_delivery_time }}"
                                                        data-origin="{{ $lane->originState->name ?? '' }}"
                                                        data-dest="{{ $lane->destinationState->name ?? '' }}">
                                                    <i class="tio-edit"></i>
                                                </button>
                                                <form action="{{ route('admin.delivery-lanes.delete', [$lane->id]) }}" method="POST" onsubmit="return confirm('{{ translate('Delete this lane?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-xs square-btn">
                                                        <i class="tio-delete"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            {{ translate('No interstate lanes found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer py-2">
                        {{ $lanes->appends(['lane_type' => 'inter_state', 'searchValue' => $searchValue])->links() }}
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- TAB 1: INTRA-STATE (LGA TO LGA) LANES --}}
        <div class="row g-3">
            {{-- Create Form --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="tio-add-circle mr-1 text-primary"></i> {{ translate('Add Intra-State LGA Lane') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.delivery-lanes.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="lane_type" value="intra_state">

                            <div class="border rounded p-3 mb-3 bg-soft-secondary">
                                <h6 class="fw-bold text-dark mb-2">
                                    <i class="tio-flight-takeoff text-primary mr-1"></i> {{ translate('Origin (Merchant)') }}
                                </h6>
                                <div class="form-group mb-2">
                                    <label class="title-color fs-12">{{ translate('Country') }}</label>
                                    <select class="form-control form-control-sm" name="origin_country_id" id="origin-country-select" required>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" {{ $country->iso_code === 'NGA' ? 'selected' : '' }}>{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="title-color fs-12">{{ translate('State') }} <span class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" name="origin_state_id" id="origin-state-select" required>
                                        <option value="">{{ translate('--- Select State ---') }}</option>
                                        @foreach($states as $st)
                                            <option value="{{ $st->id }}" {{ $st->name === 'Akwa Ibom' ? 'selected' : '' }}>{{ $st->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="title-color fs-12">{{ translate('Origin LGA') }} <span class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" name="origin_lga_id" id="origin-lga-select" required>
                                        <option value="">{{ translate('--- Select Origin LGA ---') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="border rounded p-3 mb-3 bg-soft-info">
                                <h6 class="fw-bold text-dark mb-2">
                                    <i class="tio-flight-land text-info mr-1"></i> {{ translate('Destination (Customer)') }}
                                </h6>
                                <div class="form-group mb-2">
                                    <label class="title-color fs-12">{{ translate('Country') }}</label>
                                    <select class="form-control form-control-sm" name="destination_country_id" id="dest-country-select" required>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}" {{ $country->iso_code === 'NGA' ? 'selected' : '' }}>{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="title-color fs-12">{{ translate('State') }} <span class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" name="destination_state_id" id="dest-state-select" required>
                                        <option value="">{{ translate('--- Select State ---') }}</option>
                                        @foreach($states as $st)
                                            <option value="{{ $st->id }}" {{ $st->name === 'Akwa Ibom' ? 'selected' : '' }}>{{ $st->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="title-color fs-12">{{ translate('Destination LGA') }} <span class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" name="destination_lga_id" id="dest-lga-select" required>
                                        <option value="">{{ translate('--- Select Destination LGA ---') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="border rounded p-3 mb-3 bg-light">
                                <h6 class="fw-bold text-dark mb-2">
                                    <i class="tio-money-vs text-success mr-1"></i> {{ translate('Shipping Terms') }}
                                </h6>
                                <div class="form-group mb-2">
                                    <label class="title-color fs-12">{{ translate('Delivery Fee') }} (₦) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="delivery_fee" class="form-control form-control-sm" placeholder="1500.00" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="title-color fs-12">{{ translate('Estimated Duration') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="estimated_delivery_time" class="form-control form-control-sm" placeholder="2-6 hours or 24 hours" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block fw-bold">
                                <i class="tio-add mr-1"></i> {{ translate('Create Intra-State Lane') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Table List --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 text-dark fw-bold">
                            <i class="tio-bike mr-1 text-primary"></i> {{ translate('Local Intra-State Lanes (LGA to LGA)') }}
                            <span class="badge badge-soft-dark ml-2">{{ $lanes->total() }}</span>
                        </h5>
                        <form action="{{ url()->current() }}" method="GET" class="d-flex gap-2">
                            <input type="hidden" name="lane_type" value="intra_state">
                            <input type="search" name="searchValue" class="form-control form-control-sm" 
                                   placeholder="{{ translate('Search LGA...') }}" value="{{ $searchValue }}">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="tio-search"></i></button>
                        </form>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered align-middle text-center mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-left">{{ translate('Origin') }}</th>
                                    <th class="text-left">{{ translate('Destination') }}</th>
                                    <th>{{ translate('Delivery Fee') }}</th>
                                    <th>{{ translate('Estimated Lead Time') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                    <th>{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lanes as $key => $lane)
                                    <tr>
                                        <td>{{ $lanes->firstItem() + $key }}</td>
                                        <td class="text-left">
                                            <span class="fw-bold text-dark fs-13">{{ $lane->originLga->name ?? 'N/A' }}</span>
                                            <div class="fs-11 text-muted">{{ $lane->originState->name ?? '' }}</div>
                                        </td>
                                        <td class="text-left">
                                            <span class="fw-bold text-primary fs-13">{{ $lane->destinationLga->name ?? 'N/A' }}</span>
                                            <div class="fs-11 text-muted">{{ $lane->destinationState->name ?? '' }}</div>
                                        </td>
                                        <td class="fw-bold text-dark fs-14">
                                            ₦{{ number_format($lane->delivery_fee, 2) }}
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-info">{{ $lane->estimated_delivery_time ?? '24-48 hours' }}</span>
                                        </td>
                                        <td>
                                            <label class="switcher mx-auto">
                                                <input type="checkbox" class="switcher_input status-toggle" 
                                                       data-id="{{ $lane->id }}" {{ $lane->is_enabled ? 'checked' : '' }}>
                                                <span class="switcher_control"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <button type="button" class="btn btn-outline-primary btn-xs square-btn edit-lane-btn"
                                                        data-id="{{ $lane->id }}"
                                                        data-fee="{{ $lane->delivery_fee }}"
                                                        data-time="{{ $lane->estimated_delivery_time }}"
                                                        data-origin="{{ ($lane->originLga->name ?? '') . ', ' . ($lane->originState->name ?? '') }}"
                                                        data-dest="{{ ($lane->destinationLga->name ?? '') . ', ' . ($lane->destinationState->name ?? '') }}">
                                                    <i class="tio-edit"></i>
                                                </button>
                                                <form action="{{ route('admin.delivery-lanes.delete', [$lane->id]) }}" method="POST" onsubmit="return confirm('{{ translate('Delete this lane?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-xs square-btn">
                                                        <i class="tio-delete"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            {{ translate('No intra-state lanes found.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer py-2">
                        {{ $lanes->appends(['lane_type' => 'intra_state', 'searchValue' => $searchValue])->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editLaneModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="editModalTitle">{{ translate('Update Delivery Lane') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editLaneForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-soft-info py-2 fs-12 mb-3">
                        <strong id="editModalRouteInfo"></strong>
                    </div>

                    <div class="form-group">
                        <label class="title-color">{{ translate('Delivery Fee') }} (₦) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="delivery_fee" id="edit-fee" class="form-control" required>
                    </div>

                    <div class="form-group mb-0">
                        <label class="title-color">{{ translate('Estimated Delivery Duration') }} <span class="text-danger">*</span></label>
                        <input type="text" name="estimated_delivery_time" id="edit-time" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Update Lane') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
    // AJAX LGA Loaders
    function loadLgas(stateId, targetSelectId) {
        if (!stateId) return;
        $.ajax({
            url: "{{ route('admin.delivery-lanes.get-lgas-ajax') }}",
            type: "GET",
            data: { state_id: stateId },
            success: function(data) {
                var options = '<option value="">{{ translate("--- Select LGA ---") }}</option>';
                $.each(data, function(i, lga) {
                    options += '<option value="' + lga.id + '">' + lga.name + '</option>';
                });
                $(targetSelectId).html(options);
            }
        });
    }

    $('#origin-state-select').on('change', function() {
        loadLgas($(this).val(), '#origin-lga-select');
    });

    $('#dest-state-select').on('change', function() {
        loadLgas($(this).val(), '#dest-lga-select');
    });

    // Auto-trigger on initial load if pre-selected
    if ($('#origin-state-select').val()) {
        loadLgas($('#origin-state-select').val(), '#origin-lga-select');
    }
    if ($('#dest-state-select').val()) {
        loadLgas($('#dest-state-select').val(), '#dest-lga-select');
    }

    // Status Toggle
    $('.status-toggle').on('change', function() {
        var id = $(this).data('id');
        var status = $(this).prop('checked') ? 1 : 0;
        $.ajax({
            url: "{{ route('admin.delivery-lanes.status') }}",
            type: "POST",
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                status: status
            },
            success: function(res) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message);
                }
            }
        });
    });

    // Edit Modal Trigger
    $('.edit-lane-btn').on('click', function() {
        var id = $(this).data('id');
        var fee = $(this).data('fee');
        var time = $(this).data('time');
        var origin = $(this).data('origin');
        var dest = $(this).data('dest');

        $('#editModalRouteInfo').text(origin + ' ➔ ' + dest);
        $('#edit-fee').val(fee);
        $('#edit-time').val(time);
        $('#editLaneForm').attr('action', "{{ url('admin/delivery-lanes/update') }}/" + id);
        $('#editLaneModal').modal('show');
    });
</script>
@endpush
@endsection
