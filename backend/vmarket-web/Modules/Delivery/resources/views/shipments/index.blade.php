@extends('delivery::layouts.app')

@section('title', 'Shipments & Linehaul Consolidation')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1a1a2e;">📦 Shipments & Linehaul Consolidation</h4>
        <p class="text-muted fs-13 mb-0">Consolidate vendor packages into linehaul batches and track last-mile doorstep handshakes.</p>
    </div>
    <div>
        <button type="button" class="btn btn-brand-primary" data-bs-toggle="modal" data-bs-target="#createBatchModal">
            <i class="fa-solid fa-boxes-packing me-1"></i> Consolidate New Linehaul Batch
        </button>
    </div>
</div>

<!-- Active Linehaul Batches Carousel / List -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-truck-ramp-box text-primary me-2"></i> Recent Linehaul Dispatches</h6>
    </div>
    @forelse($batches as $batch)
        <div class="col-md-4">
            <div class="glass-card">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-primary font-monospace fs-14">#{{ $batch->batch_no }}</span>
                    @if($batch->status == 'dispatched' || $batch->status == 'in_transit')
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">In Transit</span>
                    @else
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">{{ $batch->status }}</span>
                    @endif
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fs-12 mb-1">
                    <span class="text-muted">Origin Hub:</span>
                    <strong>{{ $batch->originHub->name ?? '' }}</strong>
                </div>
                <div class="d-flex justify-content-between fs-12 mb-1">
                    <span class="text-muted">Destination Hub:</span>
                    <strong class="text-success">{{ $batch->destinationHub->name ?? '' }}</strong>
                </div>
                <div class="d-flex justify-content-between fs-12 mb-1">
                    <span class="text-muted">Packages:</span>
                    <span>{{ $batch->package_count }} items</span>
                </div>
                <div class="d-flex justify-content-between fs-12 mb-2">
                    <span class="text-muted">Driver Transit Code:</span>
                    <span class="badge bg-dark font-monospace">{{ $batch->transit_otp ?? 'N/A' }}</span>
                </div>
                <a href="{{ route('delivery.shipments.waybill', ['id' => $batch->id]) }}" class="btn btn-outline-dark btn-sm w-100 py-1 fs-12">
                    <i class="fa-solid fa-print me-1"></i> Print Waybill & Manifest
                </a>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="p-3 bg-white rounded border text-muted fs-13 text-center">No linehaul batches created yet.</div>
        </div>
    @endforelse
</div>

<!-- All Customer Orders & Shipments Table -->
<div class="glass-card p-0">
    <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i> Individual Package Stream ({{ $shipments->total() }})</h6>
        <form action="{{ route('delivery.shipments.index') }}" method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Order ID or phone..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-dark btn-sm px-3">Search</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer & Delivery Address</th>
                    <th>Vendor Origin</th>
                    <th>Batch / Linehaul ID</th>
                    <th>Assigned Courier</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipments as $order)
                    <tr>
                        <td class="fw-bold text-primary font-monospace">#{{ $order->id }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $order->recipient_name ?? ($order->customer->f_name ?? 'Customer') }}</div>
                            <div class="fs-11 text-muted"><i class="fa-solid fa-map-pin text-danger me-1"></i>{{ Str::limit($order->house_street_note ?? ($order->shipping_address ?? 'Uyo'), 40) }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $order->seller->shop->name ?? 'Vmarket Central Hub' }}</div>
                            <div class="fs-11 text-muted">{{ $order->seller->shop->hub->name ?? 'Hub' }}</div>
                        </td>
                        <td>
                            @if($order->batch_dispatch_id)
                                <span class="badge bg-light text-primary border font-monospace">{{ $order->batch_dispatch_id }}</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Direct Last-Mile</span>
                            @endif
                        </td>
                        <td>
                            @if($order->delivery_man)
                                <div class="fw-semibold">{{ $order->delivery_man->f_name }} {{ $order->delivery_man->l_name }}</div>
                                <div class="fs-11 text-muted font-monospace">{{ $order->delivery_man->phone }}</div>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            @if($order->order_status == 'delivered')
                                <span class="badge-status bg-success bg-opacity-10 text-success border border-success border-opacity-25"><i class="fa-solid fa-circle-check me-1"></i> Delivered</span>
                            @elseif($order->order_status == 'out_for_delivery')
                                <span class="badge-status bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><i class="fa-solid fa-motorcycle me-1"></i> Out for Delivery</span>
                            @else
                                <span class="badge-status bg-info bg-opacity-10 text-info border border-info border-opacity-25">{{ ucfirst($order->order_status) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No shipments found matching filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($shipments->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $shipments->links() }}
        </div>
    @endif
</div>

<!-- Consolidate Batch Modal -->
<div class="modal fade" id="createBatchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('delivery.shipments.batch.create') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Consolidate Linehaul Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Origin Hub (Departure) <span class="text-danger">*</span></label>
                            <select name="origin_hub_id" class="form-select" required>
                                <option value="">-- Select Origin Hub --</option>
                                @foreach($hubs as $hub)
                                    <option value="{{ $hub->id }}">{{ $hub->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Destination Hub (Arrival) <span class="text-danger">*</span></label>
                            <select name="destination_hub_id" class="form-select" required>
                                <option value="">-- Select Destination Hub --</option>
                                @foreach($hubs as $hub)
                                    <option value="{{ $hub->id }}">{{ $hub->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Linehaul Driver</label>
                            <select name="driver_id" class="form-select">
                                <option value="">-- Assign Driver (Optional) --</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->f_name }} {{ $driver->l_name }} ({{ $driver->phone }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-bold">Vehicle Registration Number</label>
                            <input type="text" name="vehicle_no" class="form-control" placeholder="e.g. UYY-482-XA">
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">Select Packages to Consolidate into this Batch:</h6>
                    <div class="border rounded p-3 bg-light max-h-300 overflow-y-auto">
                        @php
                            $availableOrders = \App\Models\Order::whereIn('order_status', ['confirmed', 'processing'])
                                ->whereNull('batch_dispatch_id')
                                ->take(20)
                                ->get();
                        @endphp
                        @forelse($availableOrders as $ord)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="order_ids[]" value="{{ $ord->id }}" id="chkOrd{{ $ord->id }}">
                                <label class="form-check-label fs-13 d-flex justify-content-between" for="chkOrd{{ $ord->id }}">
                                    <span><strong>Order #{{ $ord->id }}</strong> · {{ $ord->recipient_name ?? ($ord->customer->f_name ?? 'Customer') }} ({{ $ord->house_street_note ?? ($ord->shipping_address ?? 'Uyo') }})</span>
                                    <span class="badge bg-light text-dark border">₦{{ number_format($ord->order_amount, 2) }}</span>
                                </label>
                            </div>
                        @empty
                            <div class="text-muted fs-13 text-center">No unprocessed orders awaiting linehaul batching.</div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-brand-primary btn-sm">Generate Linehaul Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
