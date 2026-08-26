@extends('layouts.back-end.app-seller')

@section('title', translate('Customer_Debt_&_Credit_Ledger'))

@section('content')
<div class="content container-fluid">
    <div class="mb-4 pb-2">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/pos.png') }}" width="20" alt="">
            {{ translate('Customer_Debt_&_Credit_Aging_Ledger') }}
        </h2>
    </div>

    <!-- Aging KPI Cards -->
    <div class="row g-2 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-white shadow-sm border-0 h-100">
                <span class="text-muted fs-12">{{ translate('Total_Outstanding_Debt') }}</span>
                <h3 class="fs-24 fw-bold mt-1 text-danger">{{ setCurrencySymbol(amount: $totalDebt, currencyCode: getCurrencyCode()) }}</h3>
                <div class="fs-12 text-muted mt-1">{{ translate('Across_all_registered_debtors') }}</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-white shadow-sm border-0 h-100 border-start border-4 border-success">
                <span class="text-muted fs-12">🟢 {{ translate('Current_(0-7_Days)') }}</span>
                <h3 class="fs-24 fw-bold mt-1 text-success">{{ $currentCount }} {{ translate('Customers') }}</h3>
                <div class="fs-12 text-muted mt-1">{{ translate('Safe_standard_credit_terms') }}</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-white shadow-sm border-0 h-100 border-start border-4 border-warning">
                <span class="text-muted fs-12">🟡 {{ translate('Due_(8-30_Days)') }}</span>
                <h3 class="fs-24 fw-bold mt-1 text-warning">{{ $dueCount }} {{ translate('Customers') }}</h3>
                <div class="fs-12 text-muted mt-1">{{ translate('Follow-up_recommended') }}</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-white shadow-sm border-0 h-100 border-start border-4 border-danger">
                <span class="text-muted fs-12">🔴 {{ translate('Critical_Overdue_(30+_Days)') }}</span>
                <h3 class="fs-24 fw-bold mt-1 text-danger">{{ $criticalCount }} {{ translate('Customers') }}</h3>
                <div class="fs-12 text-muted mt-1">{{ translate('POS_credit_lock_triggered') }}</div>
            </div>
        </div>
    </div>

    <!-- Filter and Search -->
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <div class="d-flex gap-2">
            <a href="{{ route('vendor.pos.debt-ledger', ['aging' => 'all']) }}" class="btn {{ $aging == 'all' ? 'btn--primary' : 'btn-outline-primary' }} btn-sm">
                {{ translate('All_Debtors') }}
            </a>
            <a href="{{ route('vendor.pos.debt-ledger', ['aging' => 'current']) }}" class="btn {{ $aging == 'current' ? 'btn-success text-white' : 'btn-outline-success' }} btn-sm">
                🟢 {{ translate('Current_(0-7d)') }}
            </a>
            <a href="{{ route('vendor.pos.debt-ledger', ['aging' => 'due']) }}" class="btn {{ $aging == 'due' ? 'btn-warning text-white' : 'btn-outline-warning' }} btn-sm">
                🟡 {{ translate('Due_(8-30d)') }}
            </a>
            <a href="{{ route('vendor.pos.debt-ledger', ['aging' => 'critical']) }}" class="btn {{ $aging == 'critical' ? 'btn-danger text-white' : 'btn-outline-danger' }} btn-sm">
                🔴 {{ translate('Critical_(30+d)') }}
            </a>
        </div>
        <form action="{{ route('vendor.pos.debt-ledger') }}" method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ translate('Search_by_name_or_phone...') }}" value="{{ $search }}">
            <button type="submit" class="btn btn--primary btn-sm"><i class="tio-search"></i></button>
        </form>
    </div>

    <!-- Debt Ledger Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('Customer_Name') }}</th>
                        <th>{{ translate('Phone') }}</th>
                        <th>{{ translate('Total_Credit_Due') }}</th>
                        <th>{{ translate('Credit_Limit') }}</th>
                        <th>{{ translate('Aging_Status') }}</th>
                        <th>{{ translate('Credit_Status') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $ledger)
                    <tr>
                        <td class="fw-bold text-dark">{{ $ledger->customer_name }}</td>
                        <td>{{ $ledger->customer_phone ?? 'N/A' }}</td>
                        <td class="fw-bold text-danger">{{ setCurrencySymbol(amount: $ledger->total_credit_due, currencyCode: getCurrencyCode()) }}</td>
                        <td>{{ setCurrencySymbol(amount: $ledger->credit_limit, currencyCode: getCurrencyCode()) }}</td>
                        <td>
                            @if($ledger->aging_bucket === 'current')
                                <span class="badge bg-light-success text-success">🟢 {{ translate('Current_(0-7d)') }}</span>
                            @elseif($ledger->aging_bucket === 'due')
                                <span class="badge bg-light-warning text-warning">🟡 {{ translate('Due_(8-30d)') }}</span>
                            @else
                                <span class="badge bg-light-danger text-danger">🔴 {{ translate('Critical_(30+d)') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($ledger->is_credit_blocked)
                                <span class="badge bg-danger">{{ translate('Blocked_🔒') }}</span>
                            @else
                                <span class="badge bg-success">{{ translate('Active_Credit_Allowed') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($ledger->total_credit_due > 0)
                                <button type="button" class="btn btn--primary btn-sm" data-bs-toggle="modal" data-bs-target="#repayModal-{{ $ledger->id }}">
                                    <i class="tio-money"></i> {{ translate('Record_Repayment') }}
                                </button>
                            @else
                                <span class="text-muted fs-12">{{ translate('Fully_Settled_✅') }}</span>
                            @endif
                        </td>
                    </tr>

                    <!-- Repayment Modal -->
                    <div class="modal fade" id="repayModal-{{ $ledger->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="{{ route('vendor.pos.debt-repay') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="ledger_id" value="{{ $ledger->id }}">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ translate('Record_Debt_Repayment') }} - {{ $ledger->customer_name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div class="d-flex justify-content-between">
                                                <span>{{ translate('Outstanding_Balance') }}:</span>
                                                <strong class="text-danger">{{ setCurrencySymbol(amount: $ledger->total_credit_due, currencyCode: getCurrencyCode()) }}</strong>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ translate('Repayment_Amount_(NGN)') }}</label>
                                            <input type="number" name="amount" class="form-control" max="{{ $ledger->total_credit_due }}" min="1" step="1" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ translate('Payment_Method') }}</label>
                                            <select name="payment_method" class="form-select" required>
                                                <option value="cash">{{ translate('Cash') }}</option>
                                                <option value="pos_card">{{ translate('POS_Debit_Card') }}</option>
                                                <option value="bank_transfer">{{ translate('Bank_Transfer') }}</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ translate('Notes_/_Reference') }}</label>
                                            <input type="text" name="notes" class="form-control" placeholder="{{ translate('e.g._Cashier_Shift_#3_Installment') }}">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                        <button type="submit" class="btn btn--primary">{{ translate('Confirm_&_Print_Receipt') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">{{ translate('No_debtor_records_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $ledgers->links() }}
        </div>
    </div>
</div>
@endsection
