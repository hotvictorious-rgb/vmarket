@extends('layouts.admin.app')

@section('title', translate('Delivery Hubs & Landmarks Management'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3 mb-sm-20">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="tio-map"></i>
            {{ translate('Delivery Hubs, Landmarks & Motor Parks') }}
        </h2>
        <p class="fs-12 text-muted mt-1">
            {{ translate('Configure dynamic states, cities, intra-city landmarks, and interstate motor park waybill terminals with flat delivery rates.') }}
        </p>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#hubs-tab">{{ translate('Landmarks & Motor Parks') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#cities-tab">{{ translate('Cities & Zones') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#states-tab">{{ translate('States & Regions') }}</a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- 1. LANDMARKS & MOTOR PARKS TAB -->
        <div class="tab-pane fade show active" id="hubs-tab">
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 text-capitalize"><i class="tio-add-circle mr-1"></i> {{ translate('Add Landmark / Motor Park') }}</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.delivery-hubs.store-hub') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label class="title-color">{{ translate('Select State') }} <span class="text-danger">*</span></label>
                                    <select class="form-control js-select2-custom" id="hub-state-select" required>
                                        <option value="">{{ translate('--- Select State ---') }}</option>
                                        @foreach($allStates as $st)
                                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="title-color">{{ translate('Select City') }} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="city_id" id="hub-city-select" required>
                                        <option value="">{{ translate('--- Select State First ---') }}</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="title-color">{{ translate('Hub Type') }} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="type" required>
                                        <option value="landmark">{{ translate('Landmark (Intra-City Local Delivery)') }}</option>
                                        <option value="motor_park">{{ translate('Motor Park (Interstate / Cross-City Waybill)') }}</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="title-color">{{ translate('Name / Description') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="{{ translate('e.g. Shelter Afrique / Oron Road or AKTC Park Waterlines') }}" required>
                                </div>

                                <div class="form-group">
                                    <label class="title-color">{{ translate('Customer Checkout Shipping Fee (₦)') }} <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="base_shipping_cost" class="form-control" placeholder="1000.00" required>
                                </div>

                                <div class="form-group">
                                    <label class="title-color">{{ translate('Standard Rider Payout Fee (₦)') }}</label>
                                    <input type="number" step="0.01" name="rider_delivery_fee" class="form-control" placeholder="{{ translate('e.g. 500.00 (Driver earning per order)') }}">
                                    <small class="text-muted">{{ translate('Amount credited to rider wallet upon completing this delivery') }}</small>
                                </div>

                                <div class="form-group">
                                    <label class="title-color">{{ translate('Estimated Delivery Timeframe') }}</label>
                                    <input type="text" name="estimated_delivery_time" class="form-control" placeholder="{{ translate('e.g. 2-4 hours or 24-48 hours') }}">
                                </div>

                                <button type="submit" class="btn btn--primary btn-block text-capitalize">
                                    <i class="tio-save"></i> {{ translate('Save Delivery Hub') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0 text-capitalize">{{ translate('Configured Landmarks & Motor Parks') }}</h5>
                            <form action="{{ url()->current() }}" method="GET" class="d-flex gap-2">
                                <select name="hub_type" class="form-control form-control-sm" onchange="this.form.submit()">
                                    <option value="">{{ translate('All Types') }}</option>
                                    <option value="landmark" {{ request('hub_type') == 'landmark' ? 'selected' : '' }}>{{ translate('Landmarks Only') }}</option>
                                    <option value="motor_park" {{ request('hub_type') == 'motor_park' ? 'selected' : '' }}>{{ translate('Motor Parks Only') }}</option>
                                </select>
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless table-thead-bordered text-center align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ translate('Hub / Landmark Name') }}</th>
                                        <th>{{ translate('City / State') }}</th>
                                        <th>{{ translate('Type') }}</th>
                                        <th>{{ translate('Customer Fee') }}</th>
                                        <th>{{ translate('Rider Payout') }}</th>
                                        <th>{{ translate('Est. Time') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($hubs as $key => $hub)
                                        <tr>
                                            <td>{{ $hubs->firstItem() + $key }}</td>
                                            <td class="font-weight-bold text-left">{{ $hub->name }}</td>
                                            <td>{{ $hub->city->name ?? 'N/A' }} ({{ $hub->city->state->name ?? 'N/A' }})</td>
                                            <td>
                                                <span class="badge {{ $hub->type == 'landmark' ? 'badge-soft-info' : 'badge-soft-warning' }} font-weight-bold">
                                                    {{ $hub->type == 'landmark' ? translate('Landmark') : translate('Motor Park') }}
                                                </span>
                                            </td>
                                            <td class="font-weight-bold text-primary">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $hub->base_shipping_cost), currencyCode: getCurrencyCode()) }}</td>
                                            <td class="font-weight-bold text-success">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $hub->rider_delivery_fee ?? 0), currencyCode: getCurrencyCode()) }}</td>
                                            <td>{{ $hub->estimated_delivery_time ?? 'Standard' }}</td>
                                            <td>
                                                <label class="switcher mx-auto">
                                                    <input type="checkbox" class="switcher_input status-toggle" data-id="{{ $hub->id }}" data-url="{{ route('admin.delivery-hubs.status-hub') }}" {{ $hub->is_active ? 'checked' : '' }}>
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-outline-primary btn-sm square-btn edit-hub-btn"
                                                            data-id="{{ $hub->id }}"
                                                            data-name="{{ $hub->name }}"
                                                            data-type="{{ $hub->type }}"
                                                            data-state-id="{{ $hub->city->state_id ?? '' }}"
                                                            data-city-id="{{ $hub->city_id }}"
                                                            data-base-shipping-cost="{{ $hub->base_shipping_cost }}"
                                                            data-rider-fee="{{ $hub->rider_delivery_fee ?? 0 }}"
                                                            data-estimated-time="{{ $hub->estimated_delivery_time }}"
                                                            data-url="{{ route('admin.delivery-hubs.update-hub', $hub->id) }}"
                                                            title="{{ translate('Edit Hub') }}">
                                                        <i class="tio-edit"></i>
                                                    </button>
                                                    <form action="{{ route('admin.delivery-hubs.delete-hub', $hub->id) }}" method="POST" onsubmit="return confirm('{{ translate('Delete this delivery hub?') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm square-btn" title="{{ translate('Delete Hub') }}">
                                                            <i class="tio-delete"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4 text-muted">{{ translate('No delivery hubs configured yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $hubs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. CITIES TAB -->
        <div class="tab-pane fade" id="cities-tab">
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 text-capitalize"><i class="tio-add-circle mr-1"></i> {{ translate('Add Operational City') }}</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.delivery-hubs.store-city') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label class="title-color">{{ translate('State') }} <span class="text-danger">*</span></label>
                                    <select class="form-control" name="state_id" required>
                                        <option value="">{{ translate('--- Select State ---') }}</option>
                                        @foreach($allStates as $st)
                                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="title-color">{{ translate('City Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="{{ translate('e.g. Uyo, Port Harcourt, Calabar') }}" required>
                                </div>
                                <button type="submit" class="btn btn--primary btn-block text-capitalize">
                                    <i class="tio-save"></i> {{ translate('Save City') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 text-capitalize">{{ translate('Operational Cities') }}</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless table-thead-bordered text-center align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ translate('City Name') }}</th>
                                        <th>{{ translate('State') }}</th>
                                        <th>{{ translate('Landmarks') }}</th>
                                        <th>{{ translate('Motor Parks') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cities as $key => $ct)
                                        <tr>
                                            <td>{{ $cities->firstItem() + $key }}</td>
                                            <td class="font-weight-bold">{{ $ct->name }}</td>
                                            <td>{{ $ct->state->name ?? 'N/A' }}</td>
                                            <td><span class="badge badge-info">{{ $ct->landmarks_count }}</span></td>
                                            <td><span class="badge badge-warning">{{ $ct->motor_parks_count }}</span></td>
                                            <td>
                                                <label class="switcher mx-auto">
                                                    <input type="checkbox" class="switcher_input status-toggle" data-id="{{ $ct->id }}" data-url="{{ route('admin.delivery-hubs.status-city') }}" {{ $ct->is_active ? 'checked' : '' }}>
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-outline-primary btn-sm square-btn edit-city-btn"
                                                            data-id="{{ $ct->id }}"
                                                            data-name="{{ $ct->name }}"
                                                            data-state-id="{{ $ct->state_id }}"
                                                            data-url="{{ route('admin.delivery-hubs.update-city', $ct->id) }}"
                                                            title="{{ translate('Edit City') }}">
                                                        <i class="tio-edit"></i>
                                                    </button>
                                                    <form action="{{ route('admin.delivery-hubs.delete-city', $ct->id) }}" method="POST" onsubmit="return confirm('{{ translate('Delete this city and all associated landmarks?') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm square-btn" title="{{ translate('Delete City') }}">
                                                            <i class="tio-delete"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">{{ translate('No cities configured.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $cities->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. STATES TAB -->
        <div class="tab-pane fade" id="states-tab">
            <div class="row g-3">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 text-capitalize"><i class="tio-add-circle mr-1"></i> {{ translate('Add State / Region') }}</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.delivery-hubs.store-state') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label class="title-color">{{ translate('State Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="{{ translate('e.g. Akwa Ibom, Rivers, Lagos') }}" required>
                                </div>
                                <button type="submit" class="btn btn--primary btn-block text-capitalize">
                                    <i class="tio-save"></i> {{ translate('Save State') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0 text-capitalize">{{ translate('Configured States') }}</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless table-thead-bordered text-center align-middle mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ translate('State Name') }}</th>
                                        <th>{{ translate('Cities Count') }}</th>
                                        <th>{{ translate('Status') }}</th>
                                        <th>{{ translate('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($states as $key => $st)
                                        <tr>
                                            <td>{{ $states->firstItem() + $key }}</td>
                                            <td class="font-weight-bold">{{ $st->name }}</td>
                                            <td><span class="badge badge-info">{{ $st->cities_count }}</span></td>
                                            <td>
                                                <label class="switcher mx-auto">
                                                    <input type="checkbox" class="switcher_input status-toggle" data-id="{{ $st->id }}" data-url="{{ route('admin.delivery-hubs.status-state') }}" {{ $st->is_active ? 'checked' : '' }}>
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-outline-primary btn-sm square-btn edit-state-btn"
                                                            data-id="{{ $st->id }}"
                                                            data-name="{{ $st->name }}"
                                                            data-url="{{ route('admin.delivery-hubs.update-state', $st->id) }}"
                                                            title="{{ translate('Edit State') }}">
                                                        <i class="tio-edit"></i>
                                                    </button>
                                                    <form action="{{ route('admin.delivery-hubs.delete-state', $st->id) }}" method="POST" onsubmit="return confirm('{{ translate('Delete state and all its cities and landmarks?') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm square-btn" title="{{ translate('Delete State') }}">
                                                            <i class="tio-delete"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">{{ translate('No states configured.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $states->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT HUB MODAL -->
<div class="modal fade" id="editHubModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">{{ translate('Edit Delivery Hub / Landmark') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editHubForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="title-color">{{ translate('State') }} <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit-hub-state-select" required>
                            <option value="">{{ translate('--- Select State ---') }}</option>
                            @foreach($allStates as $st)
                                <option value="{{ $st->id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="title-color">{{ translate('City') }} <span class="text-danger">*</span></label>
                        <select class="form-control" name="city_id" id="edit-hub-city-select" required>
                            <option value="">{{ translate('--- Select State First ---') }}</option>
                            @foreach($allCities as $ct)
                                <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="title-color">{{ translate('Hub Type') }} <span class="text-danger">*</span></label>
                        <select class="form-control" name="type" id="edit-hub-type" required>
                            <option value="landmark">{{ translate('Landmark (Intra-City Local Delivery)') }}</option>
                            <option value="motor_park">{{ translate('Motor Park (Interstate / Cross-City Waybill)') }}</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="title-color">{{ translate('Name / Description') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit-hub-name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="title-color">{{ translate('Customer Checkout Shipping Fee (₦)') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="base_shipping_cost" id="edit-hub-base-cost" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="title-color">{{ translate('Standard Rider Payout Fee (₦)') }}</label>
                        <input type="number" step="0.01" name="rider_delivery_fee" id="edit-hub-rider-fee" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="title-color">{{ translate('Estimated Delivery Timeframe') }}</label>
                        <input type="text" name="estimated_delivery_time" id="edit-hub-estimated-time" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Update Hub') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT CITY MODAL -->
<div class="modal fade" id="editCityModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">{{ translate('Edit Operational City') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editCityForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="title-color">{{ translate('State') }} <span class="text-danger">*</span></label>
                        <select class="form-control" name="state_id" id="edit-city-state-select" required>
                            @foreach($allStates as $st)
                                <option value="{{ $st->id }}">{{ $st->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="title-color">{{ translate('City Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit-city-name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Update City') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT STATE MODAL -->
<div class="modal fade" id="editStateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">{{ translate('Edit State / Region') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editStateForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="title-color">{{ translate('State Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit-state-name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Update State') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
    $('#hub-state-select').on('change', function() {
        var stateId = $(this).val();
        if(stateId) {
            $.get('{{ route("admin.delivery-hubs.get-cities-ajax", "") }}/' + stateId, function(data) {
                $('#hub-city-select').empty().append('<option value="">{{ translate("--- Select City ---") }}</option>');
                $.each(data, function(index, city) {
                    $('#hub-city-select').append('<option value="'+ city.id +'">'+ city.name +'</option>');
                });
            });
        } else {
            $('#hub-city-select').empty().append('<option value="">{{ translate("--- Select State First ---") }}</option>');
        }
    });

    $('#edit-hub-state-select').on('change', function() {
        var stateId = $(this).val();
        if(stateId) {
            $.get('{{ route("admin.delivery-hubs.get-cities-ajax", "") }}/' + stateId, function(data) {
                $('#edit-hub-city-select').empty().append('<option value="">{{ translate("--- Select City ---") }}</option>');
                $.each(data, function(index, city) {
                    $('#edit-hub-city-select').append('<option value="'+ city.id +'">'+ city.name +'</option>');
                });
            });
        }
    });

    // Open Edit Hub Modal
    $(document).on('click', '.edit-hub-btn', function() {
        var btn = $(this);
        $('#editHubForm').attr('action', btn.data('url'));
        $('#edit-hub-name').val(btn.data('name'));
        $('#edit-hub-type').val(btn.data('type'));
        $('#edit-hub-state-select').val(btn.data('state-id'));
        $('#edit-hub-city-select').val(btn.data('city-id'));
        $('#edit-hub-base-cost').val(btn.data('base-shipping-cost'));
        $('#edit-hub-rider-fee').val(btn.data('rider-fee'));
        $('#edit-hub-estimated-time').val(btn.data('estimated-time'));
        $('#editHubModal').modal('show');
    });

    // Open Edit City Modal
    $(document).on('click', '.edit-city-btn', function() {
        var btn = $(this);
        $('#editCityForm').attr('action', btn.data('url'));
        $('#edit-city-name').val(btn.data('name'));
        $('#edit-city-state-select').val(btn.data('state-id'));
        $('#editCityModal').modal('show');
    });

    // Open Edit State Modal
    $(document).on('click', '.edit-state-btn', function() {
        var btn = $(this);
        $('#editStateForm').attr('action', btn.data('url'));
        $('#edit-state-name').val(btn.data('name'));
        $('#editStateModal').modal('show');
    });

    $('.status-toggle').on('change', function() {
        var id = $(this).data('id');
        var url = $(this).data('url');
        var status = $(this).prop('checked') ? 1 : 0;
        $.post(url, {_token: '{{ csrf_token() }}', id: id, status: status}, function(response) {
            if (typeof toastr !== 'undefined') {
                toastr.success(response.message);
            }
        });
    });
</script>
@endpush
@endsection
