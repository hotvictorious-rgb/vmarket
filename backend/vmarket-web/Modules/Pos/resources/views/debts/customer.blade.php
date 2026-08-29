@extends('pos::layouts.app')

@section('title', 'Customer Debt Ledger')

@section('breadcrumb', 'Debt Ledger / Customer')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h2 class="fs-4 fw-bold text-dark mb-1">👤 Customer Ledger: {{ $customerName }}</h2>
        <p class="text-muted mb-0">Phone: {{ $customerPhone ?: 'N/A' }}</p>
    </div>
    <div class="col-md-6 text-md-end mt-2 mt-md-0">
        <a href="{{ route('pos.debts.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Ledger Summary
        </a>
    </div>
</div>

{{-- [AI] Customer Balance KPI Grid --}}
<div class="row g-3 mb-4 text-white">
    <div class="col-md-4">
        <div class="card bg-dark border-0 p-3" style="border-radius: 16px;">
            <div class="text-uppercase text-muted" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 700;">Total Purchase Value</div>
            <div class="fs-3 fw-bold text-light mt-1">₦{{ number_format($totalBought, 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success border-0 p-3" style="border-radius: 16px; background: #10b981 !important;">
            <div class="text-uppercase text-light opacity-75" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 700;">Total Paid to Date</div>
            <div class="fs-3 fw-bold mt-1">₦{{ number_format($totalPaid, 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger border-0 p-3" style="border-radius: 16px; background: #ef4444 !important; border: 2px solid var(--pos-gold) !important;">
            <div class="text-uppercase text-light opacity-75" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 700;">Outstanding Debt Balances</div>
            <div class="fs-3 fw-bold mt-1 text-warning">₦{{ number_format($totalDebt, 2) }}</div>
        </div>
    </div>
</div>

{{-- [AI] Transaction History Table --}}
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
    <div class="card-header bg-white py-3">
        <h4 class="mb-0 fs-6 fw-bold"><i class="fas fa-history text-primary me-2"></i> counter Invoices & Repayments</h4>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                <tr>
                    <th class="ps-3">Invoice #</th>
                    <th>Date</th>
                    <th>attendant / Cashier</th>
                    <th>Sale total</th>
                    <th>amount paid</th>
                    <th>Outstanding Debt</th>
                    <th>status</th>
                    <th class="pe-3 text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $s)
                    <tr>
                        <td class="ps-3 fw-bold text-primary">
                            <a href="{{ route('pos.receipt', $s->id) }}" target="_blank" style="text-decoration: none;">
                                {{ $s->receipt_number }}
                            </a>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($s->created_at)->format('d M Y, h:i A') }}</td>
                        <td>{{ $s->cashier_name ?? 'Merchant' }}</td>
                        <td class="fw-bold">₦{{ number_format($s->total_amount, 2) }}</td>
                        <td class="text-success">₦{{ number_format($s->paid_amount, 2) }}</td>
                        <td class="text-danger fw-bold">
                            @if($s->debt_amount > 0)
                                ₦{{ number_format($s->debt_amount, 2) }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-{{ $s->debt_amount > 0 ? 'warning' : 'success' }}" style="font-size: 0.65rem;">
                                {{ $s->debt_amount > 0 ? 'unsettled' : 'settled' }}
                            </span>
                        </td>
                        <td class="pe-3 text-end">
                            @if($s->debt_amount > 0)
                                <button type="button" class="btn btn-sm btn-success fw-bold px-3" 
                                    onclick="openRepaymentModal({{ $s->id }}, '{{ $s->receipt_number }}', {{ $s->debt_amount }})" style="font-size: 0.75rem;">
                                    🤝 Record Repayment
                                </button>
                            @else
                                <span class="text-muted"><i class="fas fa-check-circle text-success me-1"></i> Paid</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No sales invoices found under this debtor.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- [AI] Repayment Record Backdrop Modal --}}
<div id="repaymentModal" class="modal-backdrop" style="display: none;">
    <div class="modal" style="max-width: 440px !important;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 0;">🤝 Record Debt Repayment</h3>
            <button type="button" onclick="closeRepaymentModal()" style="background: none; border: none; color: #a0aec0; font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>

        <form id="repaymentForm" method="POST" action="{{ route('pos.debts.payment') }}">
            @csrf
            <input type="hidden" name="pos_sale_id" id="repaySaleId">

            <div class="form-group mb-3">
                <label class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Invoice Number</label>
                <input type="text" id="repayInvoiceNo" readonly style="background: #161625; border-color: rgba(255,255,255,0.08); font-weight: 700; color: #FFD700;">
            </div>

            <div class="form-group mb-3">
                <label class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Outstanding Balance</label>
                <div class="fs-4 fw-bold text-white mt-1">₦<span id="repayBalanceText">0.00</span></div>
            </div>

            <div class="form-group mb-3">
                <label for="repayAmount">Payment Amount Received (₦)</label>
                <input type="number" step="0.01" name="amount" id="repayAmount" required min="0.01" placeholder="e.g. 5000">
            </div>

            <div class="form-group mb-3">
                <label for="repayMethod">Payment Method</label>
                <select name="payment_method" id="repayMethod" required>
                    <option value="cash">Cash</option>
                    <option value="pos_card">POS Card Reader</option>
                    <option value="bank_transfer">Bank / Mobile Transfer</option>
                </select>
            </div>

            <div class="form-group mb-4">
                <label for="repayRef">Reference / Confirmation Details</label>
                <input type="text" name="reference_no" id="repayRef" placeholder="e.g. Bank Transfer ID, Card receipt no">
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="button" class="btn btn-secondary w-100 py-2 fw-bold" onclick="closeRepaymentModal()">Cancel</button>
                <button type="button" class="btn btn-success w-100 py-2 fw-bold" onclick="confirmRepaymentSubmit()">✓ Save Repayment</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openRepaymentModal(saleId, invoiceNo, balance) {
    document.getElementById('repaySaleId').value = saleId;
    document.getElementById('repayInvoiceNo').value = invoiceNo;
    document.getElementById('repayBalanceText').textContent = balance.toLocaleString('en-US', { minimumFractionDigits: 2 });
    document.getElementById('repayAmount').value = '';
    document.getElementById('repayAmount').max = balance;
    document.getElementById('repayRef').value = '';
    
    document.getElementById('repaymentModal').style.display = 'flex';
}

function closeRepaymentModal() {
    document.getElementById('repaymentModal').style.display = 'none';
}

function confirmRepaymentSubmit() {
    const form = document.getElementById('repaymentForm');
    const amount = parseFloat(document.getElementById('repayAmount').value) || 0;
    const maxVal = parseFloat(document.getElementById('repayAmount').max) || 0;

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    if (amount <= 0) {
        alert('Please specify a positive payment amount.');
        return;
    }

    if (amount > maxVal) {
        alert('Repayment amount cannot exceed outstanding debt of ₦' + maxVal.toLocaleString('en-US'));
        return;
    }

    closeRepaymentModal();

    const invoiceNo = document.getElementById('repayInvoiceNo').value;
    const methodSelect = document.getElementById('repayMethod');
    const methodName = methodSelect.options[methodSelect.selectedIndex].text;

    showConfirmPopup({
        icon: '🤝',
        title: 'Confirm Debt Settlement',
        subtitle: 'Review payment allocation settings:',
        borderColor: '#10b981',
        items: [
            { label: 'Invoice No', value: invoiceNo, color: '#93c5fd' },
            { label: 'Repayment Received', value: '₦' + amount.toLocaleString('en-US', { minimumFractionDigits: 2 }), color: '#4ade80', size: '1.1rem' },
            { label: 'Payment Channel', value: methodName, color: '#fcd34d' }
        ],
        impact: {
            text: '🔄 DEBT LEDGER UPDATE: This transaction will instantly decrement customer balance and record cashier cash inflow.',
            type: 'success'
        },
        confirmText: '✅ Process Payment',
        confirmClass: 'btn-success',
        form: form
    });
}
</script>
@endpush
