@extends('delivery::layouts.app')

@section('title', 'Cash-In-Hand & Financial Reconciliation')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1a1a2e;">💰 Cash-in-Hand & Financial Audit</h4>
        <p class="text-muted fs-13 mb-0">Track unremitted customer cash-on-delivery (COD) held by dispatch riders and record hub deposits.</p>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 fw-semibold text-uppercase">Total Cash-In-Hand with Couriers</div>
                    <div class="fs-28 fw-bold mt-1 text-danger">₦{{ number_format($totalCashInHand, 2) }}</div>
                    <div class="fs-12 text-muted mt-1"><i class="fa-solid fa-lock text-warning me-1"></i> Under atomic balance guard</div>
                </div>
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted fs-12 fw-semibold text-uppercase">Historical Remitted Volume</div>
                    <div class="fs-28 fw-bold mt-1 text-success">₦{{ number_format($totalCollectedCash, 2) }}</div>
                    <div class="fs-12 text-muted mt-1"><i class="fa-solid fa-circle-check text-success me-1"></i> Reconciled into platform account</div>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Couriers Holding Cash Table -->
<div class="glass-card p-0 mb-4">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-money-bills text-danger me-2"></i> Couriers Holding Unremitted Cash ({{ $wallets->total() }})</h6>
        <span class="fs-12 text-muted">Settlement requires pessimistic database row-level locking.</span>
    </div>

    <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Courier Name</th>
                    <th>Phone</th>
                    <th>Base Hub</th>
                    <th>Cash-In-Hand Balance</th>
                    <th>Lifetime Earnings</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($wallets as $wallet)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $wallet->delivery_man->f_name ?? 'Courier' }} {{ $wallet->delivery_man->l_name ?? '' }}</div>
                            <div class="fs-11 text-muted">ID: #{{ $wallet->delivery_man_id }}</div>
                        </td>
                        <td><span class="font-monospace text-muted">{{ $wallet->delivery_man->phone ?? 'N/A' }}</span></td>
                        <td>{{ $wallet->delivery_man->hub->name ?? 'Unassigned' }}</td>
                        <td class="fw-bold font-monospace fs-14 text-danger">₦{{ number_format($wallet->cash_in_hand, 2) }}</td>
                        <td class="fw-bold font-monospace text-success">₦{{ number_format($wallet->total_earning, 2) }}</td>
                        <td>
                            <button type="button" class="btn btn-outline-success btn-sm py-1 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#remitModal{{ $wallet->delivery_man_id }}">
                                <i class="fa-solid fa-check me-1"></i> Record Remit
                            </button>

                            <!-- Remittance Modal -->
                            <div class="modal fade" id="remitModal{{ $wallet->delivery_man_id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('delivery.finance.remittance.record') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="delivery_man_id" value="{{ $wallet->delivery_man_id }}">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Record Cash Remittance</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="p-2 mb-3 bg-light rounded">
                                                    Courier: <strong>{{ $wallet->delivery_man->f_name ?? '' }} {{ $wallet->delivery_man->l_name ?? '' }}</strong><br>
                                                    Current Balance: <strong class="text-danger font-monospace">₦{{ number_format($wallet->cash_in_hand, 2) }}</strong>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label font-weight-bold">Amount Handed Over (₦) <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" name="amount" class="form-control" max="{{ $wallet->cash_in_hand }}" value="{{ $wallet->cash_in_hand }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label font-weight-bold">Remittance Channel <span class="text-danger">*</span></label>
                                                    <select name="remittance_type" class="form-select" required>
                                                        <option value="cash_deposit_at_hub">Physical Cash Handover at Hub Desk</option>
                                                        <option value="bank_transfer_to_admin">Direct Bank Transfer to Platform Account</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label font-weight-bold">Reference / Slip Number</label>
                                                    <input type="text" name="reference" class="form-control" placeholder="e.g. HUB-DEP-4928">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success btn-sm">Confirm Remittance</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">All couriers have 100% reconciled and cleared their Cash-in-Hand balances!</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($wallets->hasPages())
        <div class="p-3 border-top d-flex justify-content-end">
            {{ $wallets->links() }}
        </div>
    @endif
</div>

<!-- Recent Remittances Ledger -->
<div class="glass-card p-0">
    <div class="p-3 border-bottom">
        <h6 class="fw-bold mb-0"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Recent Cash Handover & Settlement Ledger</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-custom table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Transaction Ref</th>
                    <th>Courier</th>
                    <th>Type</th>
                    <th>Amount Remitted</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTransactions as $tx)
                    <tr>
                        <td class="fw-bold font-monospace text-primary">#{{ $tx->transaction_id }}</td>
                        <td>{{ $tx->delivery_man->f_name ?? 'Courier' }} {{ $tx->delivery_man->l_name ?? '' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucwords(str_replace('_', ' ', $tx->transaction_type)) }}</span></td>
                        <td class="fw-bold font-monospace text-success">₦{{ number_format($tx->credit > 0 ? $tx->credit : $tx->amount, 2) }}</td>
                        <td class="text-muted fs-12">{{ $tx->created_at ? $tx->created_at->format('M d, Y H:i A') : 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No remittance records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
