@extends('pos::layouts.app')

@push('styles')
<style>
    .log-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        margin-top: 1.5rem;
    }
    .change-badge {
        font-weight: 700;
        font-family: monospace;
    }
    .change-positive {
        color: #22c55e;
    }
    .change-negative {
        color: #ef4444;
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
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">📦 Immutable Inventory Movement Logs</h2>
        <p style="font-size: 0.9rem; color: #64748b; margin-top: 0.25rem;">
            Chronological audit trail of all physical stock additions, dispatches, adjustments, and returns.
        </p>
    </div>
    <a href="{{ route('pos.transactions.index') }}" class="btn btn-secondary" style="font-size: 0.85rem;">
        🔙 Back to History
    </a>
</div>

<div class="log-card">
    <!-- Filter Bar -->
    <form method="GET" action="{{ route('pos.transactions.inventory-log') }}" style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        <input type="text" name="search" value="{{ $search }}" placeholder="🔍 Search by SKU or product name..." 
               style="flex: 1; min-width: 250px; padding: 0.6rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
        
        <select name="type" style="padding: 0.6rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; background: white;">
            <option value="">All Movement Types</option>
            @foreach($types as $t)
                <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>{{ str_replace('_', ' ', $t) }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.25rem; font-size: 0.9rem;">
            Filter Logs
        </button>
        @if($search || $type)
            <a href="{{ route('pos.transactions.inventory-log') }}" class="btn btn-secondary" style="padding: 0.6rem 1.25rem; font-size: 0.9rem; display: flex; align-items: center;">
                Reset
            </a>
        @endif
    </form>

    @if($logs->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem; color: #64748b;">
            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">📭</span>
            <p>No inventory movements recorded matching filters.</p>
        </div>
    @else
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Product SKU</th>
                        <th>Product Name</th>
                        <th>Movement Type</th>
                        <th>Quantity Change</th>
                        <th>Recorded By</th>
                        <th>Notes / Context</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ date('d M Y, h:i A', strtotime($log->created_at)) }}</td>
                            <td><code style="background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 4px;">{{ $log->product_code }}</code></td>
                            <td><strong>{{ $log->product_name }}</strong></td>
                            <td>
                                <span style="font-weight: bold; font-size: 0.8rem; color: #475569;">
                                    {{ str_replace('_', ' ', $log->type) }}
                                </span>
                            </td>
                            <td>
                                @if($log->quantity_change > 0)
                                    <span class="change-badge change-positive">+{{ $log->quantity_change }}</span>
                                @else
                                    <span class="change-badge change-negative">{{ $log->quantity_change }}</span>
                                @endif
                            </td>
                            <td>{{ $log->recorded_by ?? 'System' }}</td>
                            <td style="color: #64748b; font-size: 0.85rem;">{{ $log->notes }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1.5rem;">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
