@extends('delivery::layouts.app')

@section('title', 'Logistics Master Command')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1a1a2e;">🚀 Logistics & Dispatch Command Center</h4>
        <p class="text-muted fs-13 mb-0">Hub-to-hub package consolidation, corridor linehauls, and last-mile doorstep dispatch.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('delivery.shipments.index') }}" class="btn btn-brand-primary">
            <i class="fa-solid fa-plus me-1"></i> New Linehaul Batch
        </a>
    </div>
</div>

<!-- 4 KPI Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 fw-semibold text-uppercase">Active Shipments</div>
                    <div class="fs-24 fw-bold mt-1 text-dark">{{ number_format($activeShipmentsCount) }}</div>
                    <div class="fs-12 text-success mt-1"><i class="fa-solid fa-truck-moving"></i> In sorting & transit</div>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 fw-semibold text-uppercase">Cash-In-Hand in Transit</div>
                    <div class="fs-24 fw-bold mt-1 text-danger">₦{{ number_format($cashInHandTotal, 2) }}</div>
                    <div class="fs-12 text-muted mt-1"><i class="fa-solid fa-shield-halved"></i> Active POD collections</div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 fw-semibold text-uppercase">Active Linehaul Batches</div>
                    <div class="fs-24 fw-bold mt-1 text-warning">{{ number_format($activeLinehaulsCount) }}</div>
                    <div class="fs-12 text-muted mt-1"><i class="fa-solid fa-route"></i> Inter-Hub corridors</div>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fa-solid fa-trailer"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 fw-semibold text-uppercase">Active Couriers</div>
                    <div class="fs-24 fw-bold mt-1 text-success">{{ number_format($activeCouriersCount) }}</div>
                    <div class="fs-12 text-muted mt-1"><i class="fa-solid fa-users"></i> In-House + 3PL fleet</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Active Linehaul Batches -->
    <div class="col-lg-7">
        <div class="glass-card h-100 p-0">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-route text-primary me-2"></i> Active Corridor Linehauls</h6>
                <a href="{{ route('delivery.shipments.index') }}" class="fs-12 text-primary text-decoration-none fw-semibold">View All <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Batch No</th>
                            <th>Corridor Leg</th>
                            <th>Packages</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentBatches as $batch)
                            <tr>
                                <td class="fw-bold font-monospace text-primary">#{{ $batch->batch_no }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $batch->originHub->name ?? 'Hub A' }}</span>
                                    <i class="fa-solid fa-arrow-right text-muted mx-1 fs-11"></i>
                                    <span class="fw-semibold text-success">{{ $batch->destinationHub->name ?? 'Hub B' }}</span>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $batch->package_count }} pkgs</span></td>
                                <td>{{ $batch->driver ? trim($batch->driver->f_name . ' ' . $batch->driver->l_name) : 'Unassigned' }}</td>
                                <td>
                                    @if($batch->status == 'dispatched' || $batch->status == 'in_transit')
                                        <span class="badge-status bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">In Transit</span>
                                    @elseif($batch->status == 'received_at_hub')
                                        <span class="badge-status bg-info bg-opacity-10 text-info border border-info border-opacity-25">At Hub</span>
                                    @else
                                        <span class="badge-status bg-success bg-opacity-10 text-success border border-success border-opacity-25">Completed</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('delivery.shipments.waybill', ['id' => $batch->id]) }}" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Print Waybill">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No active linehaul batches moving right now.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Active Corridor Routes -->
    <div class="col-lg-5">
        <div class="glass-card h-100 p-0">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-map-location-dot text-success me-2"></i> Active Corridor Matrix</h6>
                <a href="{{ route('delivery.routes.index') }}" class="fs-12 text-primary text-decoration-none fw-semibold">Manage <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="p-3">
                <div class="d-flex flex-column gap-3">
                    @forelse($topRoutes as $route)
                        <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold fs-13">
                                    {{ $route->originHub->name ?? 'Origin' }} ➔ {{ $route->destinationHub->name ?? 'Destination' }}
                                </div>
                                <div class="fs-11 text-muted mt-1">
                                    <i class="fa-regular fa-clock me-1"></i> ~{{ $route->estimated_hours }} hrs transit · 
                                    <span class="text-primary fw-semibold">Rider Cut: ₦{{ number_format($route->rider_payout, 2) }}</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fs-14 fw-bold text-dark">₦{{ number_format($route->customer_fee, 2) }}</div>
                                <span class="badge bg-success bg-opacity-10 text-success fs-10 px-2 py-1 rounded">Active</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">No corridor routes created yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Live Out-For-Delivery Doorstep Orders -->
<div class="glass-card p-0">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-person-biking text-warning me-2"></i> Live Doorstep Dispatches in Uyo & Akwa Ibom</h6>
        <span class="badge bg-primary bg-opacity-10 text-primary fs-11 px-3 py-1 rounded-pill">6-Digit OTP Protected</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer & Landmark</th>
                    <th>Vendor Shop</th>
                    <th>Assigned Courier</th>
                    <th>Payment Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($liveDeliveries as $order)
                    <tr>
                        <td class="fw-bold font-monospace text-primary">#{{ $order->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $order->recipient_name ?? ($order->customer->f_name ?? 'Customer') }}</div>
                            <div class="fs-11 text-muted"><i class="fa-solid fa-map-pin text-danger me-1"></i>{{ Str::limit($order->house_street_note ?? ($order->shipping_address ?? 'Uyo'), 35) }}</div>
                        </td>
                        <td>{{ $order->seller->shop->name ?? 'Vmarket Central Hub' }}</td>
                        <td>
                            @if($order->delivery_man)
                                <div class="fw-semibold">{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}</div>
                                <div class="fs-11 text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $order->delivery_man->phone }}</div>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Awaiting Rider</span>
                            @endif
                        </td>
                        <td>
                            @if($order->payment_status == 'paid')
                                <span class="badge bg-success bg-opacity-10 text-success"><i class="fa-solid fa-check me-1"></i> Pre-Paid</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger font-monospace">COD: ₦{{ number_format($order->order_amount, 2) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($order->order_status == 'out_for_delivery')
                                <span class="badge-status bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><i class="fa-solid fa-motorcycle me-1"></i> Out for Delivery</span>
                            @else
                                <span class="badge-status bg-info bg-opacity-10 text-info border border-info border-opacity-25">Processing</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No pending deliveries right now.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
