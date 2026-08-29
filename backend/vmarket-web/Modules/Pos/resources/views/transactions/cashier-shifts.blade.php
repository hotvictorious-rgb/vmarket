@extends('pos::layouts.app')

@push('styles')
<style>
    .shift-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        margin-top: 1.5rem;
    }
    .status-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-block;
    }
    .status-open {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
    }
    .status-closed {
        background: rgba(100, 116, 139, 0.15);
        color: #64748b;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    th, td {
        padding: 0.85rem;
        text-align: left;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }
    th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
    }
    tr:hover td {
        background: #f8fafc;
    }
</style>
@endpush

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">🕒 Cashier Till Drawer & Shift Logs</h2>
        <p style="font-size: 0.9rem; color: #64748b; margin-top: 0.25rem;">
            Immutable history of drawer shift open/close timestamps and cash reconciliations.
        </p>
    </div>
    <a href="{{ route('pos.transactions.index') }}" class="btn btn-secondary" style="font-size: 0.85rem;">
        🔙 Back to History
    </a>
</div>

<div class="shift-card">
    @if($shifts->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem; color: #64748b;">
            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">📭</span>
            <p>No cashier drawer shifts recorded yet.</p>
        </div>
    @else
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Opened At</th>
                        <th>Closed At</th>
                        <th>Cashier / Worker</th>
                        <th>Opening Cash (₦)</th>
                        <th>Closing Cash (₦)</th>
                        <th>Difference / Drift (₦)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shifts as $s)
                        <tr>
                            <td>{{ date('d M Y, h:i A', strtotime($s->opened_at)) }}</td>
                            <td>
                                @if($s->closed_at)
                                    {{ date('d M Y, h:i A', strtotime($s->closed_at)) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><strong>{{ $s->cashier_name ?? 'Store Cashier' }}</strong></td>
                            <td>₦{{ number_format($s->opening_balance, 2) }}</td>
                            <td>
                                @if($s->closed_at)
                                    ₦{{ number_format($s->closing_balance, 2) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($s->closed_at)
                                    @php $drift = $s->closing_balance - $s->expected_balance; @endphp
                                    @if($drift == 0)
                                        <span style="color: #22c55e; font-weight: 700;">₦0.00 (Balanced)</span>
                                    @elseif($drift > 0)
                                        <span style="color: #3b82f6; font-weight: 700;">+₦{{ number_format(abs($drift), 2) }} (Overage)</span>
                                    @else
                                        <span style="color: #ef4444; font-weight: 700;">-₦{{ number_format(abs($drift), 2) }} (Shortage)</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($s->closed_at)
                                    <span class="status-badge status-closed">Closed</span>
                                @else
                                    <span class="status-badge status-open">Active / Open</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1.5rem;">
            {{ $shifts->links() }}
        </div>
    @endif
</div>
@endsection
