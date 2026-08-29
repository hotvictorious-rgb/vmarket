@extends('delivery::layouts.app')

@section('title', 'Courier Profile & Audit')

@section('content')
<div class="mb-4">
    <a href="{{ route('delivery.fleet.index') }}" class="text-decoration-none text-muted fs-13">
        <i class="fa-solid fa-arrow-left me-1"></i> Back to Fleet Directory
    </a>
    <h4 class="fw-bold mt-2" style="color: #1a1a2e;">🛵 Courier: {{ $courier->f_name }} {{ $courier->l_name }}</h4>
    <p class="text-muted fs-13 mb-0">Phone: {{ $courier->phone }} · Base Hub: {{ $courier->hub->name ?? 'Unassigned' }}</p>
</div>

<div class="row g-4">
    <!-- Wallet & Cash-In-Hand Card -->
    <div class="col-md-5">
        <div class="glass-card mb-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-wallet text-primary me-2"></i> Financial Balance & COD</h6>
            <div class="p-3 bg-light rounded-3 mb-3">
                <div class="text-muted fs-12 text-uppercase fw-semibold">Current Cash-In-Hand (POD)</div>
                <div class="fs-24 fw-bold text-danger mt-1">₦{{ number_format($courier->wallet->cash_in_hand ?? 0, 2) }}</div>
                <small class="text-muted fs-11">Unremitted customer cash payments collected at doorsteps.</small>
            </div>

            <div class="p-3 bg-light rounded-3 mb-3">
                <div class="text-muted fs-12 text-uppercase fw-semibold">Total Lifetime Earnings</div>
                <div class="fs-20 fw-bold text-success mt-1">₦{{ number_format($courier->wallet->total_earning ?? 0, 2) }}</div>
            </div>

            <div class="d-flex justify-content-between fs-13 border-top pt-2">
                <span class="text-muted">Total Withdrawn:</span>
                <strong>₦{{ number_format($courier->wallet->total_withdraw ?? 0, 2) }}</strong>
            </div>
        </div>

        <div class="glass-card">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-id-card text-info me-2"></i> Courier Profile Info</h6>
            <ul class="list-group list-group-flush fs-13">
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Vehicle Identity:</span>
                    <strong>{{ $courier->identity_type ?? 'Motorcycle' }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Identity Number:</span>
                    <span class="font-monospace">{{ $courier->identity_number ?? 'N/A' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Active Order Limit:</span>
                    <strong>{{ $courier->max_active_orders_limit ?? 4 }} active drops</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between px-0">
                    <span class="text-muted">Account Status:</span>
                    <span class="badge bg-success bg-opacity-10 text-success">{{ $courier->is_active ? 'Active' : 'Inactive' }}</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Remittance Action & Quick Form -->
    <div class="col-md-7">
        <div class="glass-card mb-4">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-money-bill-transfer text-success me-2"></i> Record Cash-in-Hand Remittance</h6>
            <form action="{{ route('delivery.finance.remittance.record') }}" method="POST">
                @csrf
                <input type="hidden" name="delivery_man_id" value="{{ $courier->id }}">
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Amount to Remit (₦) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control" max="{{ $courier->wallet->cash_in_hand ?? 0 }}" placeholder="e.g. {{ $courier->wallet->cash_in_hand ?? 0 }}" required>
                    <small class="text-muted fs-11">Pessimistically locked deduction from rider Cash-in-Hand ledger.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Remittance Method <span class="text-danger">*</span></label>
                    <select name="remittance_type" class="form-select" required>
                        <option value="cash_deposit_at_hub">Cash Handed Over at Physical Hub</option>
                        <option value="bank_transfer_to_admin">Direct Bank Transfer to Platform Account</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Bank Reference / Deposit Slip No</label>
                    <input type="text" name="reference" class="form-control" placeholder="e.g. TRF-9482938472">
                </div>
                <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">
                    <i class="fa-solid fa-check me-1"></i> Confirm & Settle Remittance
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
