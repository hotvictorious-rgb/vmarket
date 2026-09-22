@extends('layouts.admin.app')

@section('title', translate('Delivery Hubs & Landmarks Management'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3 mb-sm-20">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="tio-map"></i>
            {{ translate('Delivery Hubs, Landmarks & Motor Parks') }}
        </h2>
        {{--
            [AI] Phase A8 — Logistics Hub Decoupling
            Removed "Cities & Zones" and "States & Regions" tabs.
            Canonical State/LGA data is seeder-managed and not editable via admin panel.
            Hub geography: State → LGA (canonical) replacing DeliveryState → DeliveryCity (legacy).
        --}}
        <p class="fs-12 text-muted mt-1">
            {{ translate('Configure intra-city landmarks and interstate motor park waybill terminals. Geography is linked to canonical States and LGAs.') }}
        </p>
    </div>

    <div class="row g-3">
        {{-- ── Add Hub Form ─────────────────────────────────────────────── --}}
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
                            <label class="title-color">{{ translate('Select LGA') }} <span class="text-danger">*</span></label>
                            <select class="form-control" name="lga_id" id="hub-lga-select" required>
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

        {{-- ── Hub Listing Table ────────────────────────────────────────── --}}
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
                                <th>{{ translate('LGA / State') }}</th>
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
                                    <td>{{ $hub->lga?->name ?? 'N/A' }} ({{ $hub->lga?->state?->name ?? 'N/A' }})</td>
                                    <td>
                                        <span class="badge {{ $hub->type == 'landmark' ? 'badge-soft-info' : 'badge-soft-warning' }} font-weight-bold">
                                            {{ $hub->type == 'landmark' ? translate('Landmark') : translate('Motor Park') }}
                                        </span>
                                    </td>
                                    <td class="font-weight-bold text-primary">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $hub->base_shipping_cost ?? 0), currencyCode: getCurrencyCode()) }}</td>
                                    <td class="font-weight-bold text-success">{{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $hub->rider_delivery_fee ?? 0), currencyCode: getCurrencyCode()) }}</td>
                                    <td>{{ $hub->estimated_delivery_time ?? 'Standard' }}</td>
                                    <td>
                                        <label class="switcher mx-auto">
                                            <input type="checkbox" class="switcher_input status-toggle"
                                                   data-id="{{ $hub->id }}"
                                                   data-url="{{ route('admin.delivery-hubs.status-hub') }}"
                                                   {{ $hub->is_active ? 'checked' : '' }}>
                                            <span class="switcher_control"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-sm square-btn edit-hub-btn"
                                                    data-id="{{ $hub->id }}"
                                                    data-name="{{ $hub->name }}"
                                                    data-type="{{ $hub->type }}"
                                                    data-state-id="{{ $hub->lga?->state_id ?? '' }}"
                                                    data-lga-id="{{ $hub->lga_id }}"
                                                    data-base-shipping-cost="{{ $hub->base_shipping_cost ?? 0 }}"
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

{{-- ── EDIT HUB MODAL ───────────────────────────────────────────────────── --}}
<div class="modal fade" id="editHubModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">{{ translate('Edit Delivery Hub / Landmark') }}</h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
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
                        <label class="title-color">{{ translate('LGA') }} <span class="text-danger">*</span></label>
                        <select class="form-control" name="lga_id" id="edit-hub-lga-select" required>
                            <option value="">{{ translate('--- Select State First ---') }}</option>
                            @foreach($allLgas as $lga)
                                <option value="{{ $lga->id }}">{{ $lga->name }}</option>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('Update Hub') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
    // ── Add-form: populate LGA select when State changes ──────────────────
    $('#hub-state-select').on('change', function() {
        var stateId = $(this).val();
        if (stateId) {
            $.get('{{ url('admin/delivery-hubs/get-lgas-ajax') }}/' + stateId, function(data) {
                $('#hub-lga-select').empty().append('<option value="">{{ translate("--- Select LGA ---") }}</option>');
                $.each(data, function(index, lga) {
                    $('#hub-lga-select').append('<option value="' + lga.id + '">' + lga.name + '</option>');
                });
            });
        } else {
            $('#hub-lga-select').empty().append('<option value="">{{ translate("--- Select State First ---") }}</option>');
        }
    });

    // ── Edit-modal: populate LGA select when State changes ────────────────
    $('#edit-hub-state-select').on('change', function() {
        var stateId = $(this).val();
        if (stateId) {
            $.get('{{ url('admin/delivery-hubs/get-lgas-ajax') }}/' + stateId, function(data) {
                $('#edit-hub-lga-select').empty().append('<option value="">{{ translate("--- Select LGA ---") }}</option>');
                $.each(data, function(index, lga) {
                    $('#edit-hub-lga-select').append('<option value="' + lga.id + '">' + lga.name + '</option>');
                });
            });
        }
    });

    // ── Open Edit Hub Modal ──────────────────────────────────────────────
    $(document).on('click', '.edit-hub-btn', function() {
        var btn = $(this);
        var stateId = btn.data('state-id');
        var lgaId   = btn.data('lga-id');

        $('#editHubForm').attr('action', btn.data('url'));
        $('#edit-hub-name').val(btn.data('name'));
        $('#edit-hub-type').val(btn.data('type'));
        $('#edit-hub-base-cost').val(btn.data('base-shipping-cost'));
        $('#edit-hub-rider-fee').val(btn.data('rider-fee'));
        $('#edit-hub-estimated-time').val(btn.data('estimated-time'));

        // Pre-select state, then fetch LGAs and pre-select the hub's lga_id
        $('#edit-hub-state-select').val(stateId);
        if (stateId) {
            $.get('{{ url('admin/delivery-hubs/get-lgas-ajax') }}/' + stateId, function(data) {
                $('#edit-hub-lga-select').empty().append('<option value="">{{ translate("--- Select LGA ---") }}</option>');
                $.each(data, function(index, lga) {
                    $('#edit-hub-lga-select').append('<option value="' + lga.id + '">' + lga.name + '</option>');
                });
                $('#edit-hub-lga-select').val(lgaId);
            });
        }

        $('#editHubModal').modal('show');
    });

    // ── Status toggle ────────────────────────────────────────────────────
    $('.status-toggle').on('change', function() {
        var id     = $(this).data('id');
        var url    = $(this).data('url');
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
