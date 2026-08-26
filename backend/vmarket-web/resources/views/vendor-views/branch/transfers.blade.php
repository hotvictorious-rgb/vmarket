@extends('layouts.back-end.app-seller')

@section('title', translate('Inter-Branch_Waybills_&_Transfers'))

@section('content')
<div class="content container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/pos.png') }}" width="20" alt="">
            {{ translate('Inter-Branch_Anti-Theft_Waybills') }}
        </h2>
        <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#newWaybillModal">
            <i class="tio-add"></i> {{ translate('Create_New_Waybill_Transfer') }}
        </button>
    </div>

    @if(!$activeSub && count($branches) <= 1)
        <div class="alert alert-warning d-flex align-items-center justify-content-between mb-4">
            <div>
                <strong>🚀 {{ translate('Unlock_Multi-Branch_Pro') }}:</strong>
                {{ translate('Connect_multiple_store_branches,_track_in-transit_stock_buffers,_and_stop_driver_theft.') }}
            </div>
            <a href="{{ route('vendor.subscription.index') }}" class="btn btn-warning btn-sm text-dark fw-bold">
                {{ translate('Upgrade_to_Pro') }}
            </a>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Waybill_#') }}</th>
                        <th>{{ translate('Origin_Branch') }}</th>
                        <th>{{ translate('Destination_Branch') }}</th>
                        <th>{{ translate('Driver_Details') }}</th>
                        <th>{{ translate('Dispatched') }}</th>
                        <th>{{ translate('Received') }}</th>
                        <th>{{ translate('Shortage_Variance') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $transfer)
                    <tr>
                        <td class="fw-bold text-primary">{{ $transfer->waybill_number }}</td>
                        <td>{{ $transfer->originBranch->name ?? 'Origin' }}</td>
                        <td>{{ $transfer->destinationBranch->name ?? 'Destination' }}</td>
                        <td>{{ $transfer->driver_name }} ({{ $transfer->driver_phone }})</td>
                        <td>{{ $transfer->total_items_dispatched }} {{ translate('units') }}</td>
                        <td>{{ $transfer->total_items_received }} {{ translate('units') }}</td>
                        <td>
                            @if($transfer->variance_count > 0)
                                <span class="badge bg-danger text-white">{{ $transfer->variance_count }} {{ translate('Missing') }}</span>
                            @else
                                <span class="badge bg-success text-white">0</span>
                            @endif
                        </td>
                        <td>
                            @if($transfer->status === 'in_transit')
                                <span class="badge bg-warning text-white">{{ translate('In_Transit_Holding_Buffer') }}</span>
                            @elseif($transfer->status === 'variance_flagged')
                                <span class="badge bg-danger text-white">{{ translate('Theft_Variance_Detected') }}</span>
                            @else
                                <span class="badge bg-success text-white">{{ translate('Verified_&_Stocked') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($transfer->status === 'in_transit')
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#receiveModal-{{ $transfer->id }}">
                                    <i class="tio-checkmark-circle"></i> {{ translate('Physically_Count_&_Receive') }}
                                </button>
                            @else
                                <span class="text-muted fs-12">{{ translate('Completed') }}</span>
                            @endif
                        </td>
                    </tr>

                    <!-- Physical Count Receive Modal -->
                    <div class="modal fade" id="receiveModal-{{ $transfer->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <form action="{{ route('vendor.branch.transfers.receive', $transfer->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ translate('Physically_Verify_Stock_Count') }} - {{ $transfer->waybill_number }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-info fs-12 mb-3">
                                            {{ translate('Count_every_physical_carton/item_received_from_the_driver._Any_shortage_will_be_flagged_on_the_Theft_Radar.') }}
                                        </div>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>{{ translate('Product') }}</th>
                                                    <th>{{ translate('Dispatched_Qty') }}</th>
                                                    <th>{{ translate('Actual_Physical_Count_Received') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($transfer->items as $item)
                                                <tr>
                                                    <td>{{ $item->product->name ?? 'Product' }}</td>
                                                    <td class="fw-bold">{{ $item->dispatched_quantity }}</td>
                                                    <td>
                                                        <input type="hidden" name="received_items[{{ $loop->index }}][product_id]" value="{{ $item->product_id }}">
                                                        <input type="number" name="received_items[{{ $loop->index }}][quantity]" class="form-control" value="{{ $item->dispatched_quantity }}" min="0" max="{{ $item->dispatched_quantity }}" required>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                        <button type="submit" class="btn btn-success">{{ translate('Confirm_Physical_Count_&_Stock') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">{{ translate('No_waybills_dispatched_yet') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $transfers->links() }}
        </div>
    </div>
</div>
@endsection
