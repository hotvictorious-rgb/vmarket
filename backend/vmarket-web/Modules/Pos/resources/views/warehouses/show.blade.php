@extends('pos::layouts.app')

@section('title', 'Branch Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="h4 mb-0 text-gray-800"><i class="fas fa-store me-2 text-primary"></i>Branch Details</h2>
            <p class="text-muted small mb-0">Detailed location settings and assigned logistics hub for this branch.</p>
        </div>
        <a href="{{ route('pos.warehouses.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
            <i class="fas fa-arrow-left"></i> Back to Listing
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold text-dark">Store Branch Profile</h5>
                    <a href="{{ route('pos.warehouses.edit', $branch->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Details
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-borderless align-middle">
                        <tbody>
                            <tr>
                                <td class="font-weight-bold text-muted small text-uppercase py-2" style="width: 35%;">Branch Name</td>
                                <td class="py-2 text-dark font-weight-bold">{{ $branch->name }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-muted small text-uppercase py-2">Contact Phone</td>
                                <td class="py-2">{{ $branch->contact ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-muted small text-uppercase py-2">Unique Slug</td>
                                <td class="py-2"><code class="bg-light p-1 rounded">{{ $branch->slug }}</code></td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-muted small text-uppercase py-2">Created At</td>
                                <td class="py-2">{{ \Carbon\Carbon::parse($branch->created_at)->format('F d, Y h:i A') }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold text-muted small text-uppercase py-2">Last Updated</td>
                                <td class="py-2">{{ \Carbon\Carbon::parse($branch->updated_at)->format('F d, Y h:i A') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-map-marked-alt text-primary me-2"></i>Logistics & Fulfillment Settings</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-4">
                        <div class="avatar-initial rounded bg-danger-light text-danger me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(220,53,69,0.08); font-size: 1.3rem;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 font-weight-bold text-dark">Geographic Routing Area</h6>
                            <p class="text-muted small mb-0">
                                Country: <strong>{{ $branch->country ?: 'Nigeria' }}</strong><br>
                                State: <strong>{{ $branch->state_name ?: 'N/A' }}</strong><br>
                                LGA/City: <strong>{{ $branch->lga_name ?: 'N/A' }}</strong>
                            </p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-4">
                        <div class="avatar-initial rounded bg-success-light text-success me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(25,135,84,0.08); font-size: 1.3rem;">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 font-weight-bold text-dark">Assigned Dispatch Hub</h6>
                            @if($branch->hub_name)
                                <p class="text-muted small mb-0">
                                    Hub Terminal: <strong>{{ $branch->hub_name }}</strong>
                                </p>
                            @else
                                <div class="alert alert-warning py-2 px-3 mt-2 mb-0 d-inline-block small">
                                    <i class="fas fa-exclamation-triangle me-1"></i> No logistics hub has been configured for this branch. Please edit the branch to link a delivery hub.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="avatar-initial rounded bg-info-light text-info me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(13,110,253,0.08); font-size: 1.3rem;">
                            <i class="fas fa-road"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 font-weight-bold text-dark">Street Address</h6>
                            <p class="text-muted small mb-0 bg-light p-3 rounded border border-light">
                                {{ $branch->address ?: 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
