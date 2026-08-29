@extends('delivery::layouts.app')

@section('title', 'Logistics Hubs')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1a1a2e;">📍 Official Logistics Hubs</h4>
        <p class="text-muted fs-13 mb-0">Manage aggregation points, motor parks, and sorting facilities across Akwa Ibom and Nigeria.</p>
    </div>
    <div>
        <a href="{{ route('delivery.hubs.create') }}" class="btn btn-brand-primary">
            <i class="fa-solid fa-plus me-1"></i> Add New Hub
        </a>
    </div>
</div>

<div class="glass-card p-0">
    <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
        <form action="{{ route('delivery.hubs.index') }}" method="GET" class="d-flex gap-2 flex-grow-1 max-w-500">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search hub name or city..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-dark btn-sm px-3">Search</button>
            @if(request()->hasAny(['search', 'city_id']))
                <a href="{{ route('delivery.hubs.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            @endif
        </form>
        <div class="text-muted fs-13">Total Hubs: <strong>{{ $hubs->total() }}</strong></div>
    </div>

    <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Hub Name & Code</th>
                    <th>Location / State / LGA</th>
                    <th>Type</th>
                    <th>Base Shipping Rate</th>
                    <th>Rider Fee</th>
                    <th>Est. Delivery Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hubs as $hub)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark fs-14">{{ $hub->name }}</div>
                            <div class="fs-11 text-muted font-monospace">HUB-ID: #{{ $hub->id }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $hub->city->name ?? 'LGA' }}</div>
                            <div class="fs-11 text-muted">{{ $hub->city->state->name ?? 'Akwa Ibom' }}</div>
                        </td>
                        <td>
                            @if($hub->type == 'motor_park')
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25"><i class="fa-solid fa-bus me-1"></i> Motor Park</span>
                            @else
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="fa-solid fa-building me-1"></i> Landmark / Hub</span>
                            @endif
                        </td>
                        <td class="fw-bold text-dark font-monospace">₦{{ number_format($hub->base_shipping_cost, 2) }}</td>
                        <td class="fw-bold text-success font-monospace">₦{{ number_format($hub->rider_delivery_fee, 2) }}</td>
                        <td><span class="badge bg-light text-dark border"><i class="fa-regular fa-clock me-1"></i>{{ $hub->estimated_delivery_time ?? '1-3 Hours' }}</span></td>
                        <td>
                            @if($hub->is_active)
                                <span class="badge-status bg-success bg-opacity-10 text-success border border-success border-opacity-25">Active</span>
                            @else
                                <span class="badge-status bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('delivery.hubs.show', ['id' => $hub->id]) }}" class="btn btn-outline-primary btn-sm py-1 px-2" title="View Attached Shops">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#editHubModal{{ $hub->id }}" title="Edit Rates">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </div>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editHubModal{{ $hub->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('delivery.hubs.update', ['id' => $hub->id]) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Edit Logistics Hub #{{ $hub->id }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label font-weight-bold">Hub Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $hub->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label font-weight-bold">City / LGA</label>
                                                    <select name="city_id" class="form-select" required>
                                                        @foreach($cities as $city)
                                                            <option value="{{ $city->id }}" {{ $hub->city_id == $city->id ? 'selected' : '' }}>{{ $city->name }} ({{ $city->state->name ?? 'State' }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label font-weight-bold">Hub Type</label>
                                                    <select name="type" class="form-select" required>
                                                        <option value="landmark" {{ $hub->type == 'landmark' ? 'selected' : '' }}>Landmark / Central Hub</option>
                                                        <option value="motor_park" {{ $hub->type == 'motor_park' ? 'selected' : '' }}>Motor Park / Corridor Depot</option>
                                                    </select>
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label font-weight-bold">Base Shipping Cost (₦)</label>
                                                        <input type="number" step="0.01" name="base_shipping_cost" class="form-control" value="{{ $hub->base_shipping_cost }}" required>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label font-weight-bold">Rider Fee (₦)</label>
                                                        <input type="number" step="0.01" name="rider_delivery_fee" class="form-control" value="{{ $hub->rider_delivery_fee }}" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label font-weight-bold">Estimated Delivery Time</label>
                                                    <input type="text" name="estimated_delivery_time" class="form-control" value="{{ $hub->estimated_delivery_time }}">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-brand-primary btn-sm">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">No logistics hubs found. Click "Add New Hub" to create one.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($hubs->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $hubs->links() }}
        </div>
    @endif
</div>
@endsection
