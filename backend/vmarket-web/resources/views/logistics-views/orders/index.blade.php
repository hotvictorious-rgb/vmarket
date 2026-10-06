@extends('logistics-views.layouts.app')

@section('title', translate('Fleet_Dispatches_&_Orders'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h3 fw-bold mb-1">{{ translate('Fleet_Dispatches_&_Waybills') }}</h2>
            <p class="text-muted fs-13 mb-0">{{ translate('Monitor_and_dispatch_customer_orders_serviced_by_your_logistics_company.') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('logistics.orders.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ translate('All_Orders') }}
            </a>
            <a href="{{ route('logistics.orders.index', ['status' => 'ongoing']) }}" class="btn btn-sm {{ $status === 'ongoing' ? 'btn-warning text-white' : 'btn-outline-warning' }}">
                {{ translate('In_Transit') }}
            </a>
            <a href="{{ route('logistics.orders.index', ['status' => 'delivered']) }}" class="btn btn-sm {{ $status === 'delivered' ? 'btn-success' : 'btn-outline-success' }}">
                {{ translate('Delivered') }}
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-12">
        <div class="card-header bg-white border-0 py-3">
            <form action="{{ url()->current() }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="input-group" style="max-width: 320px;">
                    <input type="search" name="searchValue" class="form-control"
                           placeholder="{{ translate('Search_order_#,_customer...') }}"
                           value="{{ $searchValue }}">
                    <button type="submit" class="btn btn-primary">{{ translate('Search') }}</button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-nowrap align-middle mb-0">
                    <thead class="table-light fs-12 text-capitalize">
                        <tr>
                            <th>{{ translate('Waybill_#') }}</th>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Package_Tier') }}</th>
                            <th>{{ translate('Pickup_Shop') }}</th>
                            <th>{{ translate('Delivery_Destination') }}</th>
                            <th>{{ translate('Assigned_Rider') }}</th>
                            <th>{{ translate('Gross_Fee') }}</th>
                            <th>{{ translate('Net_Payout') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="fw-bold text-primary">#{{ $order->id }}</td>
                            <td class="fs-12 text-muted">{{ $order->created_at->format('M d, Y h:i A') }}</td>
                            <td>
                                @if($order->package_tier === 'large')
                                    <span class="badge badge-soft-danger px-2 py-1">
                                        🚐 {{ translate('Large_(Bulky)') }}
                                    </span>
                                @else
                                    <span class="badge badge-soft-info px-2 py-1">
                                        🏍️ {{ translate('Small_(Bike)') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->seller->shop->name ?? translate('In-House_Shop') }}</div>
                                <div class="fs-11 text-muted">{{ $order->origin_lga_name ?? '' }}</div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $order->destination_lga_name ?? translate('LGA_Destination') }}</div>
                                <div class="fs-11 text-muted">{{ $order->destination_state_name ?? '' }}</div>
                            </td>
                            <td>
                                @if($order->deliveryMan)
                                    <div class="fw-medium">{{ $order->deliveryMan->f_name }} {{ $order->deliveryMan->l_name }}</div>
                                    <span class="fs-11 text-muted text-capitalize">({{ $order->deliveryMan->vehicle_type ?? 'motorbike' }})</span>
                                @else
                                    <button type="button" class="btn btn-outline-warning btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#assignModal-{{ $order->id }}">
                                        <i class="fi fi-sr-user-add"></i> {{ translate('Assign_Rider') }}
                                    </button>
                                @endif
                            </td>
                            <td>₦{{ number_format($order->shipping_cost, 2) }}</td>
                            <td class="fw-bold text-success">
                                ₦{{ number_format(max(0, $order->shipping_cost - ($order->delivery_commission_amount ?? round(($order->shipping_cost * 15)/100, 2))), 2) }}
                            </td>
                            <td>
                                @if($order->order_status === 'delivered')
                                    <span class="badge badge-soft-success">{{ translate('Delivered') }}</span>
                                @elseif($order->order_status === 'out_for_delivery')
                                    <span class="badge badge-soft-primary">{{ translate('In_Transit') }}</span>
                                @else
                                    <span class="badge badge-soft-warning">{{ ucfirst($order->order_status) }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('logistics.orders.show', $order->id) }}" class="btn btn-outline-primary btn-sm px-3">
                                        {{ translate('Details') }}
                                    </a>
                                    @if($order->order_status !== 'delivered')
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#assignModal-{{ $order->id }}" title="{{ translate('Reassign_Rider') }}">
                                            <i class="fi fi-sr-refresh"></i>
                                        </button>
                                    @endif
                                </div>

                                {{-- Assign Rider Modal --}}
                                <div class="modal fade" id="assignModal-{{ $order->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('logistics.orders.assign-rider') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">{{ translate('Assign_Fleet_Rider_to_Order_#') }}{{ $order->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    @if($order->package_tier === 'large')
                                                        <div class="alert alert-warning py-2 fs-12 mb-3">
                                                            <strong>⚠️ Bulky Cargo Alert:</strong> This order contains large/bulky items. Please assign a van or cargo vehicle.
                                                        </div>
                                                    @endif
                                                    <div class="form-group mb-0">
                                                        <label class="form-label fs-13 fw-semibold">{{ translate('Select_Rider_from_Your_Fleet') }}</label>
                                                        <select name="delivery_man_id" class="form-select" required>
                                                            <option value="">{{ translate('Choose_available_rider...') }}</option>
                                                            @foreach($companyRiders as $rider)
                                                                <option value="{{ $rider->id }}" {{ $order->delivery_man_id == $rider->id ? 'selected' : '' }}>
                                                                    {{ $rider->f_name }} {{ $rider->l_name }} ({{ ucfirst($rider->vehicle_type ?? 'motorbike') }}) {{ $rider->is_online ? '● Online' : '○ Offline' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                                    <button type="submit" class="btn btn-primary">{{ translate('Confirm_Dispatch') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                {{ translate('No_waybills_or_dispatches_found.') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {!! $orders->links() !!}
            </div>
        </div>
    </div>
</div>
@endsection
