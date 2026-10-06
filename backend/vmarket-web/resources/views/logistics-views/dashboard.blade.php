@extends('logistics-views.layouts.app')

@section('title', translate('Fleet_Dashboard'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h3 fw-bold mb-1">{{ translate('Welcome_back,') }} {{ $company->name }}</h2>
            <p class="text-muted fs-13 mb-0">{{ translate('Monitor_your_fleet_performance,_assigned_dispatches,_and_wallet_earnings.') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('logistics.riders.create') }}" class="btn btn-primary">
                <i class="fi fi-sr-motorcycle me-1"></i> {{ translate('Add_New_Rider') }}
            </a>
            <a href="{{ route('logistics.wallet.index') }}" class="btn btn-outline-success">
                <i class="fi fi-sr-wallet me-1"></i> {{ translate('Withdraw_Earnings') }}
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-12 p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fs-12 fw-medium">{{ translate('Available_Balance') }}</span>
                    <i class="fi fi-sr-wallet text-success fs-20"></i>
                </div>
                <div class="h3 fw-bold text-success mb-1">
                    ₦{{ number_format($currentBalance, 2) }}
                </div>
                <span class="fs-11 text-muted">{{ translate('Ready_for_instant_withdrawal') }}</span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-12 p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fs-12 fw-medium">{{ translate('Total_Earned') }}</span>
                    <i class="fi fi-sr-money-bill-wave text-primary fs-20"></i>
                </div>
                <div class="h3 fw-bold text-primary mb-1">
                    ₦{{ number_format($totalEarned, 2) }}
                </div>
                <span class="fs-11 text-muted">{{ translate('Net_after_15%_admin_commission') }}</span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-12 p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fs-12 fw-medium">{{ translate('Fleet_Riders') }}</span>
                    <i class="fi fi-sr-motorcycle text-info fs-20"></i>
                </div>
                <div class="h3 fw-bold text-dark mb-1">
                    {{ $activeOnlineRiders }} <span class="fs-16 fw-normal text-muted">/ {{ $totalRiders }} {{ translate('Online') }}</span>
                </div>
                <span class="fs-11 text-muted">{{ translate('Active_fleet_ready_for_dispatch') }}</span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-12 p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fs-12 fw-medium">{{ translate('Deliveries_Today') }}</span>
                    <i class="fi fi-sr-box-check text-warning fs-20"></i>
                </div>
                <div class="h3 fw-bold text-dark mb-1">
                    {{ $deliveriesToday }}
                </div>
                <span class="fs-11 text-muted">{{ $activeDispatches }} {{ translate('active_dispatches_in_transit') }}</span>
            </div>
        </div>
    </div>

    {{-- Recent Dispatches --}}
    <div class="card border-0 shadow-sm rounded-12">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">{{ translate('Recent_Fleet_Dispatches') }}</h5>
            <a href="{{ route('logistics.orders.index') }}" class="fs-13 text-primary fw-semibold text-decoration-none">
                {{ translate('View_All_Orders') }} →
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-nowrap align-middle mb-0">
                    <thead class="table-light text-capitalize fs-12">
                        <tr>
                            <th>{{ translate('Waybill_#') }}</th>
                            <th>{{ translate('Package_Tier') }}</th>
                            <th>{{ translate('Customer') }}</th>
                            <th>{{ translate('Assigned_Rider') }}</th>
                            <th>{{ translate('Delivery_Fee') }}</th>
                            <th>{{ translate('Net_Payout') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td class="fw-bold text-primary">#{{ $order->id }}</td>
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
                                <div class="fw-semibold">{{ $order->customer->f_name ?? translate('Customer') }} {{ $order->customer->l_name ?? '' }}</div>
                                <div class="fs-12 text-muted">{{ $order->destination_lga_name ?? '' }}</div>
                            </td>
                            <td>
                                @if($order->deliveryMan)
                                    <div class="fw-medium">{{ $order->deliveryMan->f_name }} {{ $order->deliveryMan->l_name }}</div>
                                    <span class="fs-11 text-muted text-capitalize">({{ $order->deliveryMan->vehicle_type ?? 'motorbike' }})</span>
                                @else
                                    <span class="badge badge-soft-warning">{{ translate('Unassigned') }}</span>
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
                                <a href="{{ route('logistics.orders.show', $order->id) }}" class="btn btn-outline-primary btn-sm px-3">
                                    {{ translate('Details') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                {{ translate('No_recent_dispatches_assigned_to_your_fleet_yet.') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
