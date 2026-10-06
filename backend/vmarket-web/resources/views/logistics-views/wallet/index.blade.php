@extends('logistics-views.layouts.app')

@section('title', translate('Wallet_&_Financial_Ledger'))

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h3 fw-bold mb-1">{{ translate('Wallet_&_Payouts') }}</h2>
            <p class="text-muted fs-13 mb-0">{{ translate('Monitor_your_delivery_earnings,_request_bank_withdrawals,_and_audit_transactions.') }}</p>
        </div>
        <div>
            <button type="button" class="btn btn-success px-4" data-bs-toggle="modal" data-bs-target="#withdrawModal"
                    {{ ($wallet->current_balance ?? 0) < 1000 ? 'disabled' : '' }}>
                <i class="fi fi-sr-money-bill-wave me-1"></i> {{ translate('Request_Withdrawal') }}
            </button>
        </div>
    </div>

    {{-- Wallet KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-12 p-3 bg-white">
                <span class="text-muted fs-12 fw-medium mb-1">{{ translate('Available_for_Withdrawal') }}</span>
                <div class="h2 fw-bold text-success mb-1">
                    ₦{{ number_format($wallet->current_balance ?? 0, 2) }}
                </div>
                <span class="fs-11 text-muted">{{ translate('Minimum_payout_threshold:_₦1,000') }}</span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-12 p-3 bg-white">
                <span class="text-muted fs-12 fw-medium mb-1">{{ translate('Total_Delivery_Earnings') }}</span>
                <div class="h2 fw-bold text-primary mb-1">
                    ₦{{ number_format($wallet->total_earned ?? 0, 2) }}
                </div>
                <span class="fs-11 text-muted">{{ translate('Cumulative_net_earnings') }}</span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-12 p-3 bg-white">
                <span class="text-muted fs-12 fw-medium mb-1">{{ translate('Pending_Withdrawals') }}</span>
                <div class="h2 fw-bold text-warning mb-1">
                    ₦{{ number_format($wallet->pending_withdraw ?? 0, 2) }}
                </div>
                <span class="fs-11 text-muted">{{ translate('Processing_by_admin') }}</span>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-12 p-3 bg-white">
                <span class="text-muted fs-12 fw-medium mb-1">{{ translate('Total_Paid_Out') }}</span>
                <div class="h2 fw-bold text-info mb-1">
                    ₦{{ number_format($wallet->withdrawn ?? 0, 2) }}
                </div>
                <span class="fs-11 text-muted">{{ translate('Successfully_transferred_to_bank') }}</span>
            </div>
        </div>
    </div>

    {{-- Settlement Bank Details --}}
    <div class="card border-0 shadow-sm rounded-12 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center">
                        <i class="fi fi-sr-bank fs-24"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">{{ translate('Registered_Payout_Bank_Account') }}</h6>
                        <div class="fs-14">
                            <strong>{{ $company->bank_name ?? translate('Not_Set') }}</strong> —
                            <span class="font-monospace fw-bold">{{ $company->account_number ?? '' }}</span>
                            ({{ $company->account_name ?? '' }})
                        </div>
                    </div>
                </div>
                <div class="fs-12 text-muted">
                    {{ translate('Withdrawals_are_disbursed_directly_to_this_account.') }}
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction History Ledger --}}
    <div class="card border-0 shadow-sm rounded-12">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="fw-bold mb-0">{{ translate('Earnings_&_Payout_Ledger') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-nowrap align-middle mb-0">
                    <thead class="table-light fs-12 text-capitalize">
                        <tr>
                            <th>{{ translate('Date_&_Time') }}</th>
                            <th>{{ translate('Order_#') }}</th>
                            <th>{{ translate('Rider') }}</th>
                            <th>{{ translate('Gross_Fee') }}</th>
                            <th>{{ translate('Platform_Fee') }}</th>
                            <th>{{ translate('Net_Credit') }}</th>
                            <th>{{ translate('Balance_After') }}</th>
                            <th>{{ translate('Transaction_Note') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($transactions as $tx)
                        <tr>
                            <td class="fs-13 text-muted">{{ $tx->created_at->format('M d, Y h:i A') }}</td>
                            <td>
                                @if($tx->order_id)
                                    <span class="fw-bold text-primary">#{{ $tx->order_id }}</span>
                                @else
                                    <span class="badge badge-soft-info">{{ ucfirst($tx->transaction_type) }}</span>
                                @endif
                            </td>
                            <td>{{ $tx->deliveryMan ? $tx->deliveryMan->f_name . ' ' . $tx->deliveryMan->l_name : '-' }}</td>
                            <td>₦{{ number_format($tx->gross_delivery_fee, 2) }}</td>
                            <td class="text-danger">-₦{{ number_format($tx->admin_commission_amount, 2) }}</td>
                            <td class="fw-bold text-success">+₦{{ number_format($tx->net_partner_amount, 2) }}</td>
                            <td class="fw-bold">₦{{ number_format($tx->balance_after, 2) }}</td>
                            <td class="fs-12 text-muted text-truncate" style="max-width: 260px;" title="{{ $tx->transaction_note }}">
                                {{ $tx->transaction_note }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                {{ translate('No_transactions_recorded_in_your_ledger_yet.') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3">
                {!! $transactions->links() !!}
            </div>
        </div>
    </div>
</div>

{{-- Request Withdrawal Modal --}}
<div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('logistics.wallet.withdraw') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Request_Earnings_Withdrawal') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border py-2 mb-3 fs-13">
                        <div class="d-flex justify-content-between mb-1">
                            <span>{{ translate('Available_Balance:') }}</span>
                            <strong class="text-success">₦{{ number_format($wallet->current_balance ?? 0, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>{{ translate('Payout_Destination:') }}</span>
                            <strong>{{ $company->bank_name }} ({{ $company->account_number }})</strong>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Withdrawal_Amount_(₦)') }} <span class="text-danger">*</span></label>
                        <input type="number" name="amount" min="1000" max="{{ $wallet->current_balance ?? 0 }}" step="50"
                               class="form-control form-control-lg" placeholder="10000" required>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Note_/_Reference_(Optional)') }}</label>
                        <textarea name="transaction_note" rows="2" class="form-control" placeholder="{{ translate('Weekly_fleet_settlement') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ translate('Submit_Withdrawal_Request') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
