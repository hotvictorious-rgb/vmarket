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
    .product-bar-item {
        margin-bottom: 1.5rem;
    }
    .product-bar-label {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        margin-bottom: 0.35rem;
    }
    .product-bar-wrap {
        background: #f1f5f9;
        height: 10px;
        border-radius: 50px;
        overflow: hidden;
    }
    .product-bar-fill {
        background: linear-gradient(90deg, #5E17EB, #a855f7);
        height: 100%;
        border-radius: 50px;
        transition: width 0.5s ease-out;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1.5rem;
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
</style>
@endpush

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; color: #1e293b;">🏆 Top Best-Selling Products</h2>
        <p style="font-size: 0.9rem; color: #64748b; margin-top: 0.25rem;">
            A graphical and tabular breakdown of your top 20 items by quantity sold. Date Preset: <strong>{{ str_replace('_', ' ', $datePreset) }}</strong>.
        </p>
    </div>
    <a href="{{ route('pos.reports.index') }}" class="btn btn-secondary" style="font-size: 0.85rem;">
        🔙 Back to Reports
    </a>
</div>

<div class="report-card">
    @if($products->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem; color: #64748b;">
            <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🛍️</span>
            <p>No product sales recorded in this period yet.</p>
        </div>
    @else
        @php 
            $maxSold = (int) $products->max('units_sold') ?: 1; 
        @endphp

        <!-- Bar Charts representation -->
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #475569; margin-bottom: 1.25rem;">📊 Sales Volume Distribution</h3>
        <div style="margin-bottom: 2.5rem;">
            @foreach($products->take(5) as $idx => $p)
                @php $pct = round(($p->units_sold / $maxSold) * 100); @endphp
                <div class="product-bar-item">
                    <div class="product-bar-label">
                        <span><strong>#{{ $idx + 1 }} {{ $p->product_name }}</strong></span>
                        <span class="text-muted">{{ number_format($p->units_sold) }} units sold (₦{{ number_format($p->revenue, 2) }})</span>
                    </div>
                    <div class="product-bar-wrap">
                        <div class="product-bar-fill" style="width: {{ $pct }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Detailed table breakdown -->
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #475569;">📋 Full Top Products Rankings</h3>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">Rank</th>
                        <th>Product / Catalog Item</th>
                        <th>Units Sold</th>
                        <th>Revenue Contribution</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $idx => $p)
                        <tr>
                            <td style="text-align: center; font-weight: 800; color: #5E17EB;">#{{ $idx + 1 }}</td>
                            <td><strong>{{ $p->product_name }}</strong></td>
                            <td>{{ number_format($p->units_sold) }} units</td>
                            <td><strong>₦{{ number_format($p->revenue, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
