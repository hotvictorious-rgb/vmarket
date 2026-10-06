@extends('layouts.admin.app')

@section('title', $company->name . ' - ' . translate('Logistics_Partner_Profile'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-building text-primary"></i>
                {{ $company->name }}
            </h2>
            <div class="fs-12 text-muted mt-1">
                {{ translate('Registered:') }} {{ $company->created_at->format('M d, Y h:i A') }} |
                <span class="badge {{ $company->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">
                    {{ ucfirst($company->status) }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.logistics-companies.edit', $company->id) }}" class="btn btn-outline-primary">
                <i class="fi fi-sr-pencil"></i>
                {{ translate('Edit_Company') }}
            </a>
            <a href="{{ route('admin.logistics-companies.index') }}" class="btn btn-secondary">
                <i class="fi fi-sr-arrow-left"></i>
                {{ translate('Back_to_List') }}
            </a>
        </div>
    </div>

    {{-- Financial KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-light border-0 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-12 fw-medium">{{ translate('Available_Wallet_Balance') }}</span>
                    <i class="fi fi-sr-wallet text-success fs-20"></i>
                </div>
                <div class="h2 mb-0 fw-bold text-success">
                    ₦{{ number_format($company->wallet->current_balance ?? 0, 2) }}
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-light border-0 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-12 fw-medium">{{ translate('Total_Delivery_Earnings') }}</span>
                    <i class="fi fi-sr-money-bill-wave text-primary fs-20"></i>
                </div>
                <div class="h2 mb-0 fw-bold text-primary">
                    ₦{{ number_format($company->wallet->total_earned ?? 0, 2) }}
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-light border-0 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-12 fw-medium">{{ translate('Pending_Withdrawals') }}</span>
                    <i class="fi fi-sr-time-forward text-warning fs-20"></i>
                </div>
                <div class="h2 mb-0 fw-bold text-warning">
                    ₦{{ number_format($company->wallet->pending_withdraw ?? 0, 2) }}
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-light border-0 shadow-sm">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fs-12 fw-medium">{{ translate('Total_Paid_Out') }}</span>
                    <i class="fi fi-sr-check-circle text-info fs-20"></i>
                </div>
                <div class="h2 mb-0 fw-bold text-info">
                    ₦{{ number_format($company->wallet->withdrawn ?? 0, 2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Company Details & Bank Info --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">{{ translate('Company_Profile') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <div><strong>{{ translate('Official_Email:') }}</strong> {{ $company->company_email }}</div>
                        <div><strong>{{ translate('Official_Phone:') }}</strong> {{ $company->company_phone }}</div>
                        <div><strong>{{ translate('Contact_Person:') }}</strong> {{ $company->contact_person_name ?? translate('None') }} ({{ $company->contact_person_phone ?? '' }})</div>
                        <div><strong>{{ translate('CAC_Number:') }}</strong> {{ $company->cac_number ?? translate('Unverified') }}</div>
                        <div><strong>{{ translate('Base_LGA:') }}</strong> {{ $company->lga->name ?? translate('Not_set') }}, {{ $company->state->name ?? 'Nigeria' }}</div>
                        <div><strong>{{ translate('Office_Address:') }}</strong> {{ $company->address ?? translate('N/A') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">{{ translate('Bank_Settlement_Account') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <div><strong>{{ translate('Bank_Name:') }}</strong> {{ $company->bank_name ?? translate('Not_configured') }}</div>
                        <div><strong>{{ translate('Account_Number:') }}</strong> <span class="font-monospace fs-14 fw-bold">{{ $company->account_number ?? translate('Not_configured') }}</span></div>
                        <div><strong>{{ translate('Account_Name:') }}</strong> {{ $company->account_name ?? translate('Not_configured') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fleet Riders Section --}}
    <div class="card mb-4">
        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fi fi-sr-motorcycle text-primary"></i>
                {{ translate('Fleet_Riders') }} ({{ $company->deliveryMen->count() }})
            </h4>
        </div>
        <div class="card-body px-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap card-table w-100">
                    <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('Rider') }}</th>
                            <th>{{ translate('Vehicle') }}</th>
                            <th>{{ translate('Phone') }}</th>
                            <th>{{ translate('Email') }}</th>
                            <th>{{ translate('Active_Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($company->deliveryMen as $rider)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $rider->f_name }} {{ $rider->l_name }}</div>
                            </td>
                            <td>
                                <span class="badge badge-soft-info text-capitalize">
                                    {{ $rider->vehicle_type ?? 'motorbike' }}
                                </span>
                            </td>
                            <td>{{ $rider->phone }}</td>
                            <td>{{ $rider->email }}</td>
                            <td>
                                <span class="badge {{ $rider->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                    {{ $rider->is_active ? translate('Active') : translate('Inactive') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">
                                {{ translate('No_riders_registered_under_this_company_yet') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Financial Transactions Ledger Tab --}}
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h4 class="mb-0">
                <i class="fi fi-sr-document text-primary"></i>
                {{ translate('Financial_Transactions_&_Delivery_Ledger') }}
            </h4>
        </div>
        <div class="card-body px-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap card-table w-100">
                    <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('Date') }}</th>
                            <th>{{ translate('Order_ID') }}</th>
                            <th>{{ translate('Rider') }}</th>
                            <th>{{ translate('Gross_Fee') }}</th>
                            <th>{{ translate('Platform_Fee') }}</th>
                            <th>{{ translate('Net_Credit') }}</th>
                            <th>{{ translate('Balance_After') }}</th>
                            <th>{{ translate('Note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('M d, Y h:i A') }}</td>
                            <td>
                                @if($tx->order_id)
                                    <a href="{{ route('admin.orders.details', ['id' => $tx->order_id]) }}" class="fw-bold text-primary">
                                        #{{ $tx->order_id }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $tx->deliveryMan ? $tx->deliveryMan->f_name . ' ' . $tx->deliveryMan->l_name : '-' }}</td>
                            <td>₦{{ number_format($tx->gross_delivery_fee, 2) }}</td>
                            <td class="text-danger">-₦{{ number_format($tx->admin_commission_amount, 2) }}</td>
                            <td class="fw-bold text-success">+₦{{ number_format($tx->net_partner_amount, 2) }}</td>
                            <td class="fw-bold">₦{{ number_format($tx->balance_after, 2) }}</td>
                            <td class="fs-12 text-muted text-truncate" style="max-width: 200px;" title="{{ $tx->transaction_note }}">
                                {{ $tx->transaction_note }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-3 text-muted">
                                {{ translate('No_financial_transactions_recorded_yet') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 mt-3">
                {!! $transactions->links() !!}
            </div>
        </div>
    </div>
</div>
@endsection
