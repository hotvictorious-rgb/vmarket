@extends('pos::layouts.app')

@push('styles')
<style>
    .report-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
        margin-top: 1.5rem;
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
    .positive-margin {
        color: #22c55e;
        font-weight: 700;
    }
    .negative-margin {
        color: #ef4444;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">📈 Product-Level Profit & Loss breakdown</h2>
        <p style="font-size: 0.9rem; color: #64748b; margin-top: 0.25rem;">
            Review gross margins and itemized cost vs. revenue metrics for date preset: <strong>{{ str_replace('_', ' ', $datePreset) }}</strong>.
        </p>
    </div>
    <a href="{{ route('pos.reports.index') }}" class="btn btn-secondary" style="font-size: 0.85rem;">
        🔙 Back to Reports
    </a>
</div>

<div class="report-card">
    @if($breakdown->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem; color: #64748b;">
            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">📊</span>
            <p>No completed sales transactions recorded in this period to calculate margins.</p>
        </div>
    @else
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Product / Catalog Item</th>
                        <th style="text-align: center;">Units Sold</th>
                        <th>Gross Revenue (₦)</th>
                        <th>Cost of Goods Sold (₦)</th>
                        <th>Gross Profit (₦)</th>
                        <th>Profit Margin (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $totalUnits = 0; 
                        $totalRevenue = 0; 
                        $totalCOGS = 0; 
                        $totalProfit = 0;
                    @endphp
                    @foreach($breakdown as $p)
                        @php 
                            $totalUnits += $p->qty; 
                            $totalRevenue += $p->revenue; 
                            $totalCOGS += $p->cogs; 
                            $totalProfit += $p->profit;
                        @endphp
                        <tr>
                            <td><strong>{{ $p->product_name }}</strong></td>
                            <td style="text-align: center; font-weight: bold;">{{ number_format($p->qty) }}</td>
                            <td>₦{{ number_format($p->revenue, 2) }}</td>
                            <td>₦{{ number_format($p->cogs, 2) }}</td>
                            <td>
                                @if($p->profit >= 0)
                                    <span style="color: #22c55e;">₦{{ number_format($p->profit, 2) }}</span>
                                @else
                                    <span style="color: #ef4444;">-₦{{ number_format(abs($p->profit), 2) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($p->margin >= 0)
                                    <span class="positive-margin">+{{ $p->margin }}%</span>
                                @else
                                    <span class="negative-margin">{{ $p->margin }}%</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #cbd5e1;">
                        <td>TOTAL SUMMARY</td>
                        <td style="text-align: center;">{{ number_format($totalUnits) }}</td>
                        <td>₦{{ number_format($totalRevenue, 2) }}</td>
                        <td>₦{{ number_format($totalCOGS, 2) }}</td>
                        <td>
                            @if($totalProfit >= 0)
                                <span style="color: #22c55e;">₦{{ number_format($totalProfit, 2) }}</span>
                            @else
                                <span style="color: #ef4444;">-₦{{ number_format(abs($totalProfit), 2) }}</span>
                            @endif
                        </td>
                        <td>
                            @php $overallMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0; @endphp
                            @if($overallMargin >= 0)
                                <span class="positive-margin">+{{ $overallMargin }}%</span>
                            @else
                                <span class="negative-margin">{{ $overallMargin }}%</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
@endsection
