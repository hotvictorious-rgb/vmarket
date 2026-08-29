@extends('delivery::layouts.app')

@section('title', 'Hub Details & Attached Shops')

@section('content')
<div class="mb-4 d-flex align-items-center justify-content-between">
    <div>
        <a href="{{ route('delivery.hubs.index') }}" class="text-decoration-none text-muted fs-13">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Hubs
        </a>
        <h4 class="fw-bold mt-2" style="color: #1a1a2e;">📍 {{ $hub->name }}</h4>
        <p class="text-muted fs-13 mb-0">Location: {{ $hub->city->name ?? 'LGA' }}, {{ $hub->city->state->name ?? 'Akwa Ibom' }} · Hub ID: #{{ $hub->id }}</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-success bg-opacity-10 text-success fs-12 px-3 py-2 border border-success border-opacity-25 rounded-pill">
            {{ $attachedShops->total() }} Vendor Shops Attached
        </span>
    </div>
</div>

<div class="glass-card p-0">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-store text-primary me-2"></i> Attached Vendor Shops & Branches</h6>
        <span class="fs-12 text-muted">All packages from these shops are picked up through this hub.</span>
    </div>

    <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Shop / Branch Name</th>
                    <th>Merchant Owner</th>
                    <th>Phone / Contact</th>
                    <th>Physical Street Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attachedShops as $shop)
                    <tr>
                        <td class="fw-bold text-dark">{{ $shop->name }}</td>
                        <td>{{ $shop->seller->f_name ?? 'Vendor' }} {{ $shop->seller->l_name ?? '' }}</td>
                        <td><span class="font-monospace text-muted">{{ $shop->contact ?? ($shop->seller->phone ?? 'N/A') }}</span></td>
                        <td>{{ $shop->address ?? 'Uyo, Akwa Ibom' }}</td>
                        <td>
                            @if(($shop->seller->status ?? '') == 'approved')
                                <span class="badge bg-success bg-opacity-10 text-success">Approved Merchant</span>
                            @else
                                <span class="badge bg-warning bg-opacity-10 text-warning">Pending KYC</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No vendor branches attached to this hub yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($attachedShops->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $attachedShops->links() }}
        </div>
    @endif
</div>
@endsection
