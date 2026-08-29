@extends('layouts.admin.app')

@section('title', translate('Marketplace_Vendor_Applications'))

@section('content')
<div class="content container-fluid">
    <div class="mb-4 pb-2">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/vendor.png') }}" width="20" alt="">
            {{ translate('Marketplace_Online_Selling_Applications') }}
        </h2>
    </div>

    <!-- Filter Buttons -->
    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('admin.pos-management.marketplace-applications', ['status' => 'all']) }}" class="btn {{ $status == 'all' ? 'btn--primary' : 'btn-outline-primary' }} btn-sm">
            {{ translate('All_Vendors') }}
        </a>
        <a href="{{ route('admin.pos-management.marketplace-applications', ['status' => 'pending_approval']) }}" class="btn {{ $status == 'pending_approval' ? 'btn-warning text-white' : 'btn-outline-warning' }} btn-sm">
            {{ translate('Pending_Marketplace_Approval') }}
        </a>
        <a href="{{ route('admin.pos-management.marketplace-applications', ['status' => 'approved']) }}" class="btn {{ $status == 'approved' ? 'btn-success' : 'btn-outline-success' }} btn-sm">
            {{ translate('Approved_Live_Storefronts') }}
        </a>
        <a href="{{ route('admin.pos-management.marketplace-applications', ['status' => 'pos_only']) }}" class="btn {{ $status == 'pos_only' ? 'btn-secondary' : 'btn-outline-secondary' }} btn-sm">
            {{ translate('POS_Only_(Private)') }}
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Store_Name') }}</th>
                        <th>{{ translate('Owner') }}</th>
                        <th>{{ translate('Dispatch_Hub') }}</th>
                        <th>{{ translate('Bank_Account_(Payouts)') }}</th>
                        <th>{{ translate('Marketplace_Status') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendors as $vendor)
                    <tr>
                        <td class="fw-bold text-dark">{{ $vendor->shop->name ?? 'N/A' }}</td>
                        <td>{{ $vendor->f_name }} {{ $vendor->l_name }} ({{ $vendor->phone }})</td>
                        <td>
                            @if(isset($vendor->shop->deliveryCity))
                                <span class="badge bg-light-info text-info">📍 {{ $vendor->shop->deliveryCity->name }} Hub</span>
                            @else
                                <span class="text-muted fs-12">{{ translate('Hub_Not_Set') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($vendor->account_no)
                                <div class="fs-12">
                                    <span class="fw-bold">{{ $vendor->bank_name }}</span> - {{ $vendor->account_no }}<br>
                                    <span class="text-muted">({{ $vendor->holder_name }})</span>
                                </div>
                            @else
                                <span class="badge bg-light text-muted">{{ translate('No_Bank_Details') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($vendor->marketplace_status === 'approved')
                                <span class="badge bg-success">{{ translate('Marketplace_Active_🟢') }}</span>
                            @elseif($vendor->marketplace_status === 'pending_approval')
                                <span class="badge bg-warning text-white">{{ translate('Pending_Approval_🟡') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ translate('POS_Only_🔒') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($vendor->marketplace_status !== 'approved')
                                <form action="{{ route('admin.pos-management.marketplace-applications.approve', $vendor->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('{{ translate('Approve_vendor_for_public_marketplace_selling?') }}')">
                                        <i class="tio-done"></i> {{ translate('Approve_Online_Selling') }}
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.pos-management.marketplace-applications.reject', $vendor->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('{{ translate('Switch_vendor_back_to_private_POS_only?') }}')">
                                        {{ translate('Revert_to_POS_Only') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">{{ translate('No_vendor_applications_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $vendors->links() }}
        </div>
    </div>
</div>
@endsection
