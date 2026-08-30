@extends('delivery::layouts.app')

@section('title', 'Corridor Routes & Pricing Matrix')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1a1a2e;">🛣️ Corridor Routes & Dynamic Pricing Matrix</h4>
        <p class="text-muted fs-13 mb-0">Define hub-to-hub transportation rates, courier payouts, and delivery timelines.</p>
    </div>
    <div>
        <button type="button" class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#createRouteModal">
            <i class="fa-solid fa-plus me-1"></i> New Corridor Route
        </button>
    </div>
</div>

<div class="glass-card p-0 mb-4">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-calculator text-primary me-2"></i> Active Corridors ({{ $routes->total() }})</h6>
        <span class="fs-12 text-muted">All checkout delivery rates are calculated directly from this matrix.</span>
    </div>

    <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Origin Hub</th>
                    <th>Destination Hub</th>
                    <th>Corridor Type</th>
                    <th>Customer Fee</th>
                    <th>Rider Payout</th>
                    <th>3PL Margin</th>
                    <th>Est. Transit</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($routes as $route)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $route->originHub->name ?? 'Origin Hub' }}</div>
                            <div class="fs-11 text-muted">{{ $route->originHub->city->name ?? 'City' }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-success">{{ $route->destinationHub->name ?? 'Destination Hub' }}</div>
                            <div class="fs-11 text-muted">{{ $route->destinationHub->city->name ?? 'City' }}</div>
                        </td>
                        <td>
                            @if($route->transit_type == 'inter_city_linehaul')
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">Inter-City Linehaul</span>
                            @elseif($route->transit_type == 'regional')
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Regional</span>
                            @else
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Intra-City Express</span>
                            @endif
                        </td>
                        <td class="fw-bold font-monospace text-dark">₦{{ number_format($route->customer_fee, 2) }}</td>
                        <td class="fw-bold font-monospace text-success">₦{{ number_format($route->rider_payout, 2) }}</td>
                        <td class="fw-bold font-monospace text-muted">₦{{ number_format($route->logistics_partner_margin, 2) }}</td>
                        <td><span class="badge bg-light text-dark border">~{{ $route->estimated_hours }} hrs</span></td>
                        <td>
                            @if($route->is_active)
                                <span class="badge-status bg-success bg-opacity-10 text-success border border-success border-opacity-25">Active</span>
                            @else
                                <span class="badge-status bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 edit-route-trigger"
                                    data-id="{{ $route->id }}"
                                    data-origin="{{ $route->originHub->name ?? 'Origin' }}"
                                    data-dest="{{ $route->destinationHub->name ?? 'Destination' }}"
                                    data-customer-fee="{{ $route->customer_fee }}"
                                    data-rider-payout="{{ $route->rider_payout }}"
                                    data-margin="{{ $route->logistics_partner_margin }}"
                                    data-hours="{{ $route->estimated_hours }}"
                                    data-type="{{ $route->transit_type }}"
                                    data-url="{{ route('delivery.routes.update', ['id' => $route->id]) }}"
                                    title="Edit Rates">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">No corridor routes created yet. Click "New Corridor Route" to define one.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($routes->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $routes->links() }}
        </div>
    @endif
</div>

<!-- Create Route Modal -->
<div class="modal fade" id="createRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('delivery.routes.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New Corridor Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Origin Hub (Vendor / Package Origin) <span class="text-danger">*</span></label>
                        <select name="origin_hub_id" class="form-select" required>
                            <option value="">-- Select Origin Hub --</option>
                            @foreach($hubs as $hub)
                                <option value="{{ $hub->id }}">{{ $hub->name }} ({{ $hub->city->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Destination Hub (Customer Destination) <span class="text-danger">*</span></label>
                        <select name="destination_hub_id" class="form-select" required>
                            <option value="">-- Select Destination Hub --</option>
                            @foreach($hubs as $hub)
                                <option value="{{ $hub->id }}">{{ $hub->name }} ({{ $hub->city->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Customer Fee (₦) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="customer_fee" class="form-control" placeholder="1000.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Rider Payout (₦) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="rider_payout" class="form-control" placeholder="700.00" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-weight-bold">3PL Partner Margin (₦) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="logistics_partner_margin" class="form-control" placeholder="100.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Est. Transit Hours <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="estimated_hours" class="form-control" placeholder="2.0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Corridor Transit Type <span class="text-danger">*</span></label>
                        <select name="transit_type" class="form-select" required>
                            <option value="intra_city">Intra-City Express (Direct Delivery)</option>
                            <option value="inter_city_linehaul">Inter-City Linehaul (Hub-to-Hub)</option>
                            <option value="regional">Regional Corridor</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary btn-sm">Create Route</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- [AI] Single Dynamic Reusable Edit Modal -->
<div class="modal fade" id="sharedEditRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="sharedEditRouteForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="sharedEditRouteTitle">Edit Corridor Rates</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-2 mb-3 bg-light rounded text-center" id="routeOriginDestSummary">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Customer Fee (₦)</label>
                            <input type="number" step="0.01" name="customer_fee" id="modalCustomerFee" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Rider Payout (₦)</label>
                            <input type="number" step="0.01" name="rider_payout" id="modalRiderPayout" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-weight-bold">3PL Partner Margin (₦)</label>
                            <input type="number" step="0.01" name="logistics_partner_margin" id="modalPartnerMargin" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Est. Transit Hours</label>
                            <input type="number" step="0.1" name="estimated_hours" id="modalEstHours" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Corridor Transit Type</label>
                        <select name="transit_type" id="modalTransitType" class="form-select" required>
                            <option value="intra_city">Intra-City Express (Direct)</option>
                            <option value="inter_city_linehaul">Inter-City Linehaul (Hub Transfer)</option>
                            <option value="regional">Regional Transit</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary btn-sm">Save Rates</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $(document).on('click', '.edit-route-trigger', function() {
        var btn = $(this);
        $('#sharedEditRouteForm').attr('action', btn.data('url'));
        $('#sharedEditRouteTitle').text('Edit Corridor Rates #' + btn.data('id'));
        $('#routeOriginDestSummary').html('<strong>' + btn.data('origin') + '</strong> ➔ <strong class="text-success">' + btn.data('dest') + '</strong>');
        $('#modalCustomerFee').val(btn.data('customer-fee'));
        $('#modalRiderPayout').val(btn.data('rider-payout'));
        $('#modalPartnerMargin').val(btn.data('margin'));
        $('#modalEstHours').val(btn.data('hours'));
        $('#modalTransitType').val(btn.data('type'));
        
        var modal = new bootstrap.Modal(document.getElementById('sharedEditRouteModal'));
        modal.show();
    });
</script>
@endpush
