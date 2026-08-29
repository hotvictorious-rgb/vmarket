@extends('pos::layouts.app')

@section('title', 'Branch Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="h4 mb-0 text-gray-800"><i class="fas fa-code-branch me-2 text-primary"></i>Branch & Warehouse Management</h2>
            <p class="text-muted small mb-0">Configure your physical store counter locations and fulfillment warehouses linked to delivery hubs.</p>
        </div>
        <a href="{{ route('pos.warehouses.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="fas fa-plus"></i> Create New Branch
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Branch Details</th>
                            <th>Contact Phone</th>
                            <th>Location (State / LGA)</th>
                            <th>Logistics Hub</th>
                            <th>Street Address</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initial rounded bg-primary-light text-primary me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(94,23,235,0.08); font-weight: 700;">
                                            {{ strtoupper(substr($branch->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <h6 class="mb-0 font-weight-bold">{{ $branch->name }}</h6>
                                            <span class="text-muted small">ID: {{ $branch->id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $branch->contact ?: 'N/A' }}</td>
                                <td>
                                    <div>{{ $branch->state_name ?: 'N/A' }}</div>
                                    <span class="text-muted small">{{ $branch->lga_name ?: 'N/A' }} ({{ $branch->country }})</span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-dark p-2" style="background-color: #e9ecef !important;">
                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i> {{ $branch->hub_name ?: 'No Hub Configured' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" title="{{ $branch->address }}">
                                        {{ $branch->address ?: 'N/A' }}
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('pos.warehouses.show', $branch->id) }}" class="btn btn-sm btn-outline-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('pos.warehouses.edit', $branch->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('pos.warehouses.destroy', $branch->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this branch?')" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-store-slash fa-3x mb-3 text-light"></i>
                                    <h5>No Branches Registered Yet</h5>
                                    <p class="small text-muted mb-3">Add your store locations or warehouses to start managing transactions and inventories.</p>
                                    <a href="{{ route('pos.warehouses.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Register Your First Branch
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($branches->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $branches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
