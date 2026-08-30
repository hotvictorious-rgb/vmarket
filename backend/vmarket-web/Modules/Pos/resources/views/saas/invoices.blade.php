@extends('saas.layout')

@section('title', 'Paystack Subscription Invoices & Billing')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 900; color: #fff; letter-spacing: -0.02em;">💳 Paystack Subscription Ledger</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">Complete payment ledger of subscription upgrades, automated card renewals, and manual grants.</p>
    </div>
    <div style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 14px; padding: 0.75rem 1.5rem; text-align: right;">
        <div style="font-size: 0.75rem; font-weight: 800; color: #34d399; text-transform: uppercase;">Total Collected Revenue</div>
        <div style="font-size: 1.5rem; font-weight: 900; color: #fff;">₦{{ number_format($totalCollected, 2) }}</div>
    </div>
</div>

<!-- Invoices Table -->
<div class="panel-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Invoice / Ref</th>
                    <th>Merchant Company</th>
                    <th>Plan Tier</th>
                    <th>Amount Paid</th>
                    <th>Payment Method</th>
                    <th>Sender & Note</th>
                    <th>Valid Period</th>
                    <th>Status & Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td>
                        <strong style="font-family: monospace; color: #a78bfa; font-size: 0.9rem;">#{{ $inv->reference }}</strong>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">{{ \Carbon\Carbon::parse($inv->created_at)->format('d M Y, h:i A') }}</div>
                    </td>
                    <td>
                        <strong style="color: #fff;">{{ $inv->company->name ?? 'Deleted Company' }}</strong>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $inv->company->owner_email ?? '' }}</div>
                    </td>
                    <td>
                        <span class="badge badge-pro">{{ $inv->plan }}</span>
                    </td>
                    <td>
                        <strong style="color: #4ade80; font-size: 1rem;">₦{{ number_format($inv->amount, 2) }}</strong>
                    </td>
                    <td>
                        @if($inv->payment_method === 'MANUAL_BANK_TRANSFER')
                            <span class="badge" style="background: rgba(245,158,11,0.2); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3);">🏦 Bank Transfer</span>
                        @else
                            <span class="badge badge-pro">💳 {{ $inv->payment_method }}</span>
                        @endif
                    </td>
                    <td>
                        @if($inv->sender_name)
                            <div style="font-size: 0.82rem; font-weight: 700; color: #fff;">{{ $inv->sender_name }}</div>
                        @endif
                        @if($inv->proof_note)
                            <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $inv->proof_note }}">{{ $inv->proof_note }}</div>
                        @endif
                        @if(!$inv->sender_name && !$inv->proof_note)
                            <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                        @endif
                    </td>
                    <td>
                        @if($inv->valid_until)
                            <div style="font-size: 0.82rem; color: #38bdf8; font-weight: 700;">Until {{ $inv->valid_until->format('d M Y') }}</div>
                        @else
                            <span style="color: var(--text-muted);">-</span>
                        @endif
                    </td>
                    <td>
                        @if($inv->status === 'PAID')
                            <span class="badge badge-active">✓ PAID</span>
                        @elseif($inv->status === 'PENDING_APPROVAL')
                            <div style="display: flex; gap: 0.35rem; align-items: center;">
                                <form method="POST" action="{{ route('saas.invoices.approve', $inv->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success" style="font-size: 0.75rem; padding: 0.35rem 0.65rem;" title="Approve Bank Transfer & Activate PRO">
                                        ✓ Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('saas.invoices.reject', $inv->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" style="font-size: 0.75rem; padding: 0.35rem 0.65rem;" title="Reject">
                                        ✕
                                    </button>
                                </form>
                            </div>
                        @else
                            <span class="badge" style="background: rgba(239,68,68,0.2); color: #f87171;">{{ $inv->status }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">No subscription invoices recorded yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $invoices->links() }}
    </div>
</div>

@endsection
