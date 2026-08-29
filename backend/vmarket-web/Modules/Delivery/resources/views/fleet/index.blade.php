@extends('delivery::layouts.app')

@section('title', 'Fleet & 3PL Logistics Partners')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1a1a2e;">🛵 Fleet & Logistics Partners</h4>
        <p class="text-muted fs-13 mb-0">Manage in-house delivery riders and registered 3rd-party logistics courier companies.</p>
    </div>
    <div>
        <button type="button" class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#createCompanyModal">
            <i class="fa-solid fa-plus me-1"></i> Register 3PL Company
        </button>
    </div>
</div>

<!-- 3PL Partner Companies Grid -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-truck-moving text-primary me-2"></i> Registered 3PL Logistics Partners</h6>
    </div>
    @forelse($companies as $company)
        <div class="col-md-4">
            <div class="glass-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold fs-15 text-dark">{{ $company->name }}</div>
                        <div class="fs-11 text-muted font-monospace">CODE: {{ $company->code }}</div>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">{{ $company->status }}</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fs-12 text-muted mb-1">
                    <span>Active Couriers:</span>
                    <strong class="text-dark">{{ $company->riders_count }} riders</strong>
                </div>
                <div class="d-flex justify-content-between fs-12 text-muted mb-1">
                    <span>Contact:</span>
                    <span class="text-dark">{{ $company->contact_person }} ({{ $company->phone }})</span>
                </div>
                <div class="d-flex justify-content-between fs-12 text-muted">
                    <span>Partner Share:</span>
                    <strong class="text-primary font-monospace">{{ $company->commission_rate }}%</strong>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="p-3 bg-white rounded border text-muted fs-13 text-center">No 3rd-party logistics companies registered yet.</div>
        </div>
    @endforelse
</div>

<!-- All Couriers & In-House Fleet Table -->
<div class="glass-card p-0">
    <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-users text-primary me-2"></i> All Active Couriers & Fleet ({{ $couriers->total() }})</h6>
        <form action="{{ route('delivery.fleet.index') }}" method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search rider name or phone..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-dark btn-sm px-3">Search</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Courier Name</th>
                    <th>Phone / Contact</th>
                    <th>Assigned Base Hub</th>
                    <th>Cash-In-Hand (POD)</th>
                    <th>Active Order Limit</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($couriers as $courier)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $courier->f_name }} {{ $courier->l_name }}</div>
                            <div class="fs-11 text-muted">Rider ID: #{{ $courier->id }}</div>
                        </td>
                        <td><span class="font-monospace text-muted">{{ $courier->phone }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $courier->hub->name ?? 'Unassigned' }}</div>
                            <div class="fs-11 text-muted">{{ $courier->hub->city->name ?? '' }}</div>
                        </td>
                        <td>
                            @php $cash = (float)($courier->wallet->cash_in_hand ?? 0); @endphp
                            @if($cash > 40000)
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 font-monospace fs-12">₦{{ number_format($cash, 2) }} ⚠️ High</span>
                            @else
                                <span class="badge bg-light text-dark border font-monospace fs-12">₦{{ number_format($cash, 2) }}</span>
                            @endif
                        </td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary border">{{ $courier->max_active_orders_limit ?? 4 }} active orders max</span></td>
                        <td>
                            @if($courier->is_active)
                                <span class="badge-status bg-success bg-opacity-10 text-success border border-success border-opacity-25">Active</span>
                            @else
                                <span class="badge-status bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Suspended</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('delivery.fleet.rider.show', ['id' => $courier->id]) }}" class="btn btn-outline-primary btn-sm py-1 px-2" title="View Profile & Audit">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No couriers registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($couriers->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $couriers->links() }}
        </div>
    @endif
</div>

<!-- Register 3PL Modal -->
<div class="modal fade" id="createCompanyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('delivery.fleet.company.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Register 3PL Logistics Partner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. SpeedExpress Logistics Uyo" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Company Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="SPEED-UYO" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Partner Share (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" name="commission_rate" class="form-control" value="10.0" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Contact Person <span class="text-danger">*</span></label>
                            <input type="text" name="contact_person" class="form-control" placeholder="Manager Name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label font-weight-bold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="08012345678" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="logistics@example.com">
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Office Address</label>
                        <input type="text" name="address" class="form-control" placeholder="Street address in Uyo">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary btn-sm">Register Partner</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
