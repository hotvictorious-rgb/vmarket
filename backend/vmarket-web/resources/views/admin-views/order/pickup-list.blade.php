@extends('layouts.admin.app')

@section('title', translate('In-Shop Pickup & Inspection Oversight'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h1 class="page-header-title d-flex align-items-center gap-2">
            <i class="tio-shop"></i> {{ translate('In-Shop Pickup Oversight & Reservation Inspections') }}
        </h1>
        <p class="text-muted">{{ translate('Monitor pickup reservations, inspection outcomes (accepted vs rejected), and collection OTP verification.') }}</p>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="row g-2 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <div class="media align-items-center">
                    <div class="media-body">
                        <span class="text-capitalize text-muted fs-12">{{ translate('Pending Inspection') }}</span>
                        <h2 class="mb-0 text-warning">{{ $metrics['pending_inspection'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <div class="media align-items-center">
                    <div class="media-body">
                        <span class="text-capitalize text-muted fs-12">{{ translate('Inspection Accepted') }}</span>
                        <h2 class="mb-0 text-success">{{ $metrics['inspected_accepted'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <div class="media align-items-center">
                    <div class="media-body">
                        <span class="text-capitalize text-muted fs-12">{{ translate('Inspection Rejected') }}</span>
                        <h2 class="mb-0 text-danger">{{ $metrics['inspected_rejected'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body">
                <div class="media align-items-center">
                    <div class="media-body">
                        <span class="text-capitalize text-muted fs-12">{{ translate('Collected / Completed') }}</span>
                        <h2 class="mb-0 text-info">{{ $metrics['completed'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 w-100">
                <form action="{{ route('admin.orders.pickup-list') }}" method="GET" class="d-flex gap-2">
                    <div class="input-group input-group-merge input-group-custom">
                        <input type="search" name="searchValue" class="form-control" placeholder="{{ translate('Search reservation code, customer, shop...') }}" value="{{ request('searchValue') }}">
                    </div>
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="all" {{ $status == 'all' ? 'selected' : '' }}>{{ translate('All Statuses') }}</option>
                        <option value="pending_inspection" {{ $status == 'pending_inspection' ? 'selected' : '' }}>{{ translate('Pending Inspection') }}</option>
                        <option value="inspected_accepted" {{ $status == 'inspected_accepted' ? 'selected' : '' }}>{{ translate('Inspected & Accepted') }}</option>
                        <option value="inspected_rejected" {{ $status == 'inspected_rejected' ? 'selected' : '' }}>{{ translate('Inspected & Rejected') }}</option>
                        <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>{{ translate('Collected / Completed') }}</option>
                    </select>
                    <button type="submit" class="btn btn--primary">{{ translate('Filter') }}</button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Reservation Code') }}</th>
                        <th>{{ translate('Customer') }}</th>
                        <th>{{ translate('Shop') }}</th>
                        <th>{{ translate('Product') }}</th>
                        <th>{{ translate('Inspection Status') }}</th>
                        <th>{{ translate('Rejection Reason / Notes') }}</th>
                        <th>{{ translate('Created At') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reservations as $res)
                        <tr>
                            <td>
                                <span class="font-weight-bold text-primary">{{ $res->reservation_code }}</span>
                            </td>
                            <td>
                                <div>{{ $res->customer ? ($res->customer->f_name . ' ' . $res->customer->l_name) : translate('Guest Customer') }}</div>
                                <small class="text-muted">{{ $res->customer?->phone }}</small>
                            </td>
                            <td>
                                <div>{{ $res->shop?->name ?? translate('N/A') }}</div>
                            </td>
                            <td>
                                <div>{{ $res->product?->name ?? translate('Product Item') }}</div>
                                <small class="text-muted">Qty: {{ $res->quantity }}</small>
                            </td>
                            <td>
                                @if($res->status == 'pending_inspection')
                                    <span class="badge badge-soft-warning">{{ translate('Pending Inspection') }}</span>
                                @elseif($res->status == 'inspected_accepted')
                                    <span class="badge badge-soft-success">{{ translate('Accepted') }}</span>
                                @elseif($res->status == 'inspected_rejected')
                                    <span class="badge badge-soft-danger">{{ translate('Inspection Rejected') }}</span>
                                @elseif($res->status == 'completed')
                                    <span class="badge badge-soft-info">{{ translate('Collected') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ $res->status }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-wrap" style="max-width: 200px; display: inline-block;">
                                    {{ $res->rejection_reason ?? $res->notes ?? '—' }}
                                </span>
                            </td>
                            <td>{{ $res->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center p-4">
                                <div class="text-muted">{{ translate('No pickup reservations found.') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer border-0">
            {!! $reservations->links() !!}
        </div>
    </div>
</div>
@endsection
