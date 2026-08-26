@extends('layouts.back-end.app')

@section('title', translate('POS_Management_Dashboard'))

@section('content')
<div class="content container-fluid">
    <div class="mb-4 pb-2">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/pos.png') }}" width="20" alt="">
            {{ translate('Omnichannel_POS_&_Anti-Theft_Command_Center') }}
        </h2>
    </div>

    <!-- Analytics Cards -->
    <div class="row g-2 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-white shadow-sm border-0 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12">{{ translate('Total_POS_Merchants') }}</span>
                        <h3 class="fs-24 fw-bold mt-1 text-primary">{{ $totalMerchants }}</h3>
                    </div>
                    <div class="avatar avatar-lg bg-light-primary rounded-circle">
                        <i class="tio-shop text-primary fs-24"></i>
                    </div>
                </div>
                <div class="fs-12 text-muted mt-2">
                    <span class="text-info fw-bold">{{ $posOnlyCount }}</span> {{ translate('POS_Only') }} | 
                    <span class="text-success fw-bold">{{ $approvedMarketplaceCount }}</span> {{ translate('Marketplace_Live') }}
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-white shadow-sm border-0 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12">{{ translate('Multi-Branch_Pro_Subscribers') }}</span>
                        <h3 class="fs-24 fw-bold mt-1 text-success">{{ $activeSubscriptions }}</h3>
                    </div>
                    <div class="avatar avatar-lg bg-light-success rounded-circle">
                        <i class="tio-premium-outlined text-success fs-24"></i>
                    </div>
                </div>
                <div class="fs-12 text-muted mt-2">
                    {{ translate('Est._MRR') }}: <span class="fw-bold text-dark">{{ setCurrencySymbol(amount: $estimatedMRR, currencyCode: getCurrencyCode()) }}</span>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-white shadow-sm border-0 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12">{{ translate('In-Transit_Waybills') }}</span>
                        <h3 class="fs-24 fw-bold mt-1 text-warning">{{ $inTransitWaybills }}</h3>
                    </div>
                    <div class="avatar avatar-lg bg-light-warning rounded-circle">
                        <i class="tio-bike text-warning fs-24"></i>
                    </div>
                </div>
                <div class="fs-12 text-muted mt-2">
                    {{ translate('Inter-Branch_Holding_Buffer_Active') }}
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-white shadow-sm border-0 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted fs-12">{{ translate('Theft_&_Variance_Alerts') }}</span>
                        <h3 class="fs-24 fw-bold mt-1 text-danger">{{ $varianceTheftFlagged }}</h3>
                    </div>
                    <div class="avatar avatar-lg bg-light-danger rounded-circle">
                        <i class="tio-warning-outlined text-danger fs-24"></i>
                    </div>
                </div>
                <div class="fs-12 text-muted mt-2">
                    {{ translate('Shortage_Discrepancies_Detected') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Inter-Branch Waybills -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="tio-transfer text-primary"></i>
                {{ translate('Live_Inter-Branch_Waybills_&_Theft_Radar') }}
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Waybill_#') }}</th>
                        <th>{{ translate('Vendor') }}</th>
                        <th>{{ translate('Route') }}</th>
                        <th>{{ translate('Driver') }}</th>
                        <th>{{ translate('Dispatched') }}</th>
                        <th>{{ translate('Received') }}</th>
                        <th>{{ translate('Variance') }}</th>
                        <th>{{ translate('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransfers as $transfer)
                    <tr>
                        <td class="fw-bold text-primary">{{ $transfer->waybill_number }}</td>
                        <td>{{ $transfer->seller->shop->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $transfer->originBranch->name ?? 'Origin' }}</span>
                            ➔ 
                            <span class="badge bg-light-primary text-primary">{{ $transfer->destinationBranch->name ?? 'Destination' }}</span>
                        </td>
                        <td>{{ $transfer->driver_name }} ({{ $transfer->driver_phone }})</td>
                        <td>{{ $transfer->total_items_dispatched }} {{ translate('units') }}</td>
                        <td>{{ $transfer->total_items_received }} {{ translate('units') }}</td>
                        <td>
                            @if($transfer->variance_count > 0)
                                <span class="badge bg-danger text-white">{{ $transfer->variance_count }} {{ translate('Shortage_Flagged') }}</span>
                            @else
                                <span class="badge bg-success text-white">0</span>
                            @endif
                        </td>
                        <td>
                            @if($transfer->status === 'in_transit')
                                <span class="badge bg-warning">{{ translate('In_Transit') }}</span>
                            @elseif($transfer->status === 'variance_flagged')
                                <span class="badge bg-danger">{{ translate('Theft_Variance_Detected') }}</span>
                            @else
                                <span class="badge bg-success">{{ translate('Verified_&_Received') }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">{{ translate('No_recent_waybills_recorded') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
