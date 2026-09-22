@extends('layouts.admin.app')

@section('title', translate('Authoritative Delivery Lanes (LGA to LGA)'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3 mb-sm-20">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="tio-directions"></i>
            {{ translate('Authoritative Delivery Lanes (LGA to LGA)') }}
        </h2>
        <p class="fs-12 text-muted mt-1">
            {{ translate('Configure directional Origin LGA to Destination LGA delivery lanes, authoritative delivery fees, estimated delivery duration, and lane availability.') }}
        </p>
    </div>

    <div class="row g-3">
        <!-- 1. ADD NEW DELIVERY LANE FORM -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-capitalize">
                        <i class="tio-add-circle mr-1"></i> {{ translate('Add Directional Delivery Lane') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.delivery-lanes.store') }}" method="POST">
                        @csrf

                        <div class="border rounded p-3 mb-3 bg-soft-secondary">
                            <h6 class="font-weight-bold text-dark mb-2">
                                <i class="tio-flight-takeoff text-primary mr-1"></i> {{ translate('Origin Location (Merchant / Hub)') }}
                            </h6>

                            <div class="form-group mb-2">
                                <label class="title-color fs-12">{{ translate('Origin Country') }} <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="origin_country_id" id="origin-country-select" required>
                                    <option value="">{{ translate('--- Select Country ---') }}</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-2">
                                <label class="title-color fs-12">{{ translate('Origin State') }} <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="origin_state_id" id="origin-state-select" required>
                                    <option value="">{{ translate('--- Select Country First ---') }}</option>
                                </select>
                            </div>

                            <div class="form-group mb-0">
                                <label class="title-color fs-12">{{ translate('Origin LGA') }} <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="origin_lga_id" id="origin-lga-select" required>
                                    <option value="">{{ translate('--- Select State First ---') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3 bg-soft-info">
                            <h6 class="font-weight-bold text-dark mb-2">
                                <i class="tio-flight-land text-info mr-1"></i> {{ translate('Destination Location (Customer Delivery)') }}
                            </h6>

                            <div class="form-group mb-2">
                                <label class="title-color fs-12">{{ translate('Destination Country') }} <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="destination_country_id" id="dest-country-select" required>
                                    <option value="">{{ translate('--- Select Country ---') }}</option>
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $country->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mb-2">
                                <label class="title-color fs-12">{{ translate('Destination State') }} <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="destination_state_id" id="dest-state-select" required>
                                    <option value="">{{ translate('--- Select Country First ---') }}</option>
                                </select>
                            </div>

                            <div class="form-group mb-0">
                                <label class="title-color fs-12">{{ translate('Destination LGA') }} <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="destination_lga_id" id="dest-lga-select" required>
                                    <option value="">{{ translate('--- Select State First ---') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="title-color">{{ translate('Authoritative Delivery Fee (₦)') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="delivery_fee" class="form-control" placeholder="1500.00" min="0" required>
                            <small class="text-muted">{{ translate('Flat customer shipping charge for this route') }}</small>
                        </div>

                        <div class="form-group">
                            <label class="title-color">{{ translate('Estimated Delivery Time') }} <span class="text-danger">*</span></label>
                            <input type="text" name="estimated_delivery_time" class="form-control" placeholder="{{ translate('e.g. 2-4 hours, Same Day, 24-48 hours') }}" required>
                        </div>

                        <button type="submit" class="btn btn--primary btn-block text-capitalize">
                            <i class="tio-save"></i> {{ translate('Save Delivery Lane') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. DELIVERY LANES TABLE LIST -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 text-capitalize">{{ translate('Configured Delivery Lanes') }}</h5>
                        <span class="badge badge-soft-dark mt-1">{{ $lanes->total() }} {{ translate('Total Active Lanes') }}</span>
                    </div>
                    <form action="{{ url()->current() }}" method="GET" class="d-flex gap-2">
                        <div class="input-group input-group-sm">
                            <input type="search" name="searchValue" class="form-control" placeholder="{{ translate('Search LGA...') }}" value="{{ request('searchValue') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn--primary"><i class="tio-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-thead-bordered text-center align-middle mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>{{ translate('Origin LGA') }}</th>
                                <th></th>
                                <th>{{ translate('Destination LGA') }}</th>
                                <th>{{ translate('Delivery Fee') }}</th>
                                <th>{{ translate('Est. Duration') }}</th>
                                <th>{{ translate('Enabled') }}</th>
                                <th>{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lanes as $key => $lane)
                                <tr>
                                    <td>{{ $lanes->firstItem() + $key }}</td>
                                    <td class="text-left">
                                        <div class="font-weight-bold text-dark">{{ $lane->originLga?->name ?? 'N/A' }}</div>
                                        <div class="fs-12 text-muted">{{ $lane->originState?->name ?? 'N/A' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-primary px-2 py-1">
                                            <i class="tio-arrow-forward"></i>
                                        </span>
                                    </td>
                                    <td class="text-left">
                                        <div class="font-weight-bold text-dark">{{ $lane->destinationLga?->name ?? 'N/A' }}</div>
                                        <div class="fs-12 text-muted">{{ $lane->destinationState?->name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="font-weight-bold text-primary">
                                        {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $lane->delivery_fee ?? 0), currencyCode: getCurrencyCode()) }}
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-info">{{ $lane->estimated_delivery_time }}</span>
                                    </td>
                                    <td>
                                        <label class="switcher mx-auto">
                                            <input type="checkbox" class="switcher_input lane-status-toggle"
                                                   data-id="{{ $lane->id }}"
                                                   data-url="{{ route('admin.delivery-lanes.status') }}"
                                                   {{ $lane->is_enabled ? 'checked' : '' }}>
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm square-btn edit-lane-btn"
                                                    data-id="{{ $lane->id }}"
                                                    data-origin="{{ $lane->originLga?->name }} ({{ $lane->originState?->name }})"
                                                    data-destination="{{ $lane->destinationLga?->name }} ({{ $lane->destinationState?->name }})"
                                                    data-fee="{{ $lane->delivery_fee }}"
                                                    data-time="{{ $lane->estimated_delivery_time }}"
                                                    data-url="{{ route('admin.delivery-lanes.update', $lane->id) }}"
                                                    title="{{ translate('Edit Lane') }}">
                                                <i class="tio-edit"></i>
                                            </button>
                                            <form action="{{ route('admin.delivery-lanes.delete', $lane->id) }}" method="POST" onsubmit="return confirm('{{ translate('Are you sure you want to delete this delivery lane?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm square-btn" title="{{ translate('Delete Lane') }}">
                                                    <i class="tio-delete"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <div class="mb-2"><i class="tio-directions text-muted fs-40"></i></div>
                                        {{ translate('No delivery lanes configured yet. Add your first directional LGA route above.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $lanes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- EDIT DELIVERY LANE MODAL -->
<div class="modal fade" id="editLaneModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">{{ translate('Edit Delivery Lane Pricing & Duration') }}</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editLaneForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-soft-secondary mb-3">
                        <div class="fs-12 text-muted mb-1">{{ translate('Directional Lane Route') }}:</div>
                        <div class="font-weight-bold text-dark d-flex align-items-center gap-2">
                            <span id="modal-origin-display"></span>
                            <i class="tio-arrow-forward text-primary"></i>
                            <span id="modal-dest-display"></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="title-color">{{ translate('Authoritative Delivery Fee (₦)') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="delivery_fee" id="edit-delivery-fee" class="form-control" required min="0">
                    </div>

                    <div class="form-group">
                        <label class="title-color">{{ translate('Estimated Delivery Time') }} <span class="text-danger">*</span></label>
                        <input type="text" name="estimated_delivery_time" id="edit-estimated-time" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Update Delivery Lane') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
    "use strict";

    // AJAX Cascading for Origin
    function loadStates(countryId, stateSelector, lgaSelector, callback) {
        if (!countryId) {
            $(stateSelector).empty().append('<option value="">{{ translate("--- Select Country First ---") }}</option>');
            $(lgaSelector).empty().append('<option value="">{{ translate("--- Select State First ---") }}</option>');
            return;
        }

        $.get('{{ route("admin.delivery-lanes.get-states-ajax") }}', { country_id: countryId }, function(data) {
            $(stateSelector).empty().append('<option value="">{{ translate("--- Select State ---") }}</option>');
            $(lgaSelector).empty().append('<option value="">{{ translate("--- Select State First ---") }}</option>');
            $.each(data, function(index, state) {
                $(stateSelector).append('<option value="' + state.id + '">' + state.name + '</option>');
            });
            if (callback) callback();
        });
    }

    function loadLgas(stateId, lgaSelector, callback) {
        if (!stateId) {
            $(lgaSelector).empty().append('<option value="">{{ translate("--- Select State First ---") }}</option>');
            return;
        }

        $.get('{{ route("admin.delivery-lanes.get-lgas-ajax") }}', { state_id: stateId }, function(data) {
            $(lgaSelector).empty().append('<option value="">{{ translate("--- Select LGA ---") }}</option>');
            $.each(data, function(index, lga) {
                $(lgaSelector).append('<option value="' + lga.id + '">' + lga.name + '</option>');
            });
            if (callback) callback();
        });
    }

    // Origin listeners
    $('#origin-country-select').on('change', function() {
        loadStates($(this).val(), '#origin-state-select', '#origin-lga-select');
    });

    $('#origin-state-select').on('change', function() {
        loadLgas($(this).val(), '#origin-lga-select');
    });

    // Destination listeners
    $('#dest-country-select').on('change', function() {
        loadStates($(this).val(), '#dest-state-select', '#dest-lga-select');
    });

    $('#dest-state-select').on('change', function() {
        loadLgas($(this).val(), '#dest-lga-select');
    });

    // Auto load states for default selected country on page load
    $(document).ready(function() {
        if ($('#origin-country-select').val()) {
            loadStates($('#origin-country-select').val(), '#origin-state-select', '#origin-lga-select');
        }
        if ($('#dest-country-select').val()) {
            loadStates($('#dest-country-select').val(), '#dest-state-select', '#dest-lga-select');
        }
    });

    // Edit Modal populate
    $(document).on('click', '.edit-lane-btn', function() {
        var btn = $(this);
        $('#editLaneForm').attr('action', btn.data('url'));
        $('#modal-origin-display').text(btn.data('origin'));
        $('#modal-dest-display').text(btn.data('destination'));
        $('#edit-delivery-fee').val(btn.data('fee'));
        $('#edit-estimated-time').val(btn.data('time'));
        $('#editLaneModal').modal('show');
    });

    // Status toggle
    $('.lane-status-toggle').on('change', function() {
        var id = $(this).data('id');
        var url = $(this).data('url');
        var status = $(this).prop('checked') ? 1 : 0;

        $.post(url, {
            _token: '{{ csrf_token() }}',
            id: id,
            status: status
        }, function(response) {
            if (typeof toastr !== 'undefined') {
                toastr.success(response.message);
            }
        }).fail(function() {
            if (typeof toastr !== 'undefined') {
                toastr.error('{{ translate("Failed to update status") }}');
            }
        });
    });
</script>
@endpush
@endsection
