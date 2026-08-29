@extends('pos::layouts.app')

@section('title', 'POS Dashboard')

@push('styles')
<style>
    /* [AI] Premium Dashboard Styles */
    .exec-header {
        background: linear-gradient(135deg, #1e1e2f 0%, #11111f 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .exec-title h2 {
        font-size: 1.6rem;
        font-weight: 800;
        color: #f8fafc;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .exec-title p {
        color: #94a3b8;
        font-size: 0.88rem;
    }

    .exec-badges {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .exec-badge {
        padding: 0.45rem 0.9rem;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .badge-branch {
        background: rgba(94, 23, 235, 0.18);
        color: var(--pos-gold);
        border: 1px solid rgba(94, 23, 235, 0.3);
    }

    .badge-date {
        background: rgba(37, 99, 235, 0.18);
        color: #93c5fd;
        border: 1px solid rgba(59, 130, 246, 0.3);
    }

    /* Filter Command Hub */
    .filter-hub {
        background: #1f2937;
        border: 1px solid #374151;
        border-radius: 18px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .filter-row-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .filter-control-group {
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .filter-label {
        font-size: 0.8rem;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
    }

    .filter-select {
        background: #111827;
        border: 1px solid #374151;
        color: #f8fafc;
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 700;
        cursor: pointer;
        min-width: 220px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--pos-primary);
        box-shadow: 0 0 0 2px rgba(94, 23, 235, 0.2);
    }

    .custom-range-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.65rem;
        padding-top: 0.75rem;
        border-top: 1px dashed rgba(255,255,255,0.08);
    }

    .custom-range-input {
        background: #111827;
        border: 1px solid #374151;
        border-radius: 8px;
        color: #f8fafc;
        padding: 0.35rem 0.65rem;
        font-size: 0.82rem;
    }

    /* Hero Metrics Grid */
    .hero-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }

    .hero-card {
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 1.4rem 1.5rem;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .hero-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .hero-card.hero-sales::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .hero-card.hero-cash::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
    .hero-card.hero-stock::before { background: linear-gradient(90deg, #6366f1, #818cf8); }
    .hero-card.hero-debt::before { background: linear-gradient(90deg, #d97706, #fbbf24); }

    .hero-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.35);
    }

    .hero-label {
        font-size: 0.76rem;
        font-weight: 800;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .hero-val {
        font-size: 1.65rem;
        font-weight: 900;
        color: #f8fafc;
        margin-top: 0.35rem;
        line-height: 1.15;
    }

    .hero-sub {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 0.35rem;
        display: block;
    }

    .hero-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    /* 3-Column Panels Layout */
    .panels-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .panel-card {
        background: #1f2937;
        border: 1px solid #374151;
        border-radius: 18px;
        padding: 1.35rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        padding-bottom: 0.75rem;
    }

    .panel-title {
        font-size: 0.92rem;
        font-weight: 800;
        color: #f8fafc;
        display: flex;
        align-items: center;
        gap: 0.45rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .panel-list {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .panel-item {
        background: rgba(17, 24, 39, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        padding: 0.85rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .panel-item-left {
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    .panel-item-icon {
        font-size: 1.25rem;
    }

    .panel-item-name {
        font-size: 0.84rem;
        font-weight: 700;
        color: #cbd5e1;
    }

    .panel-item-sub {
        font-size: 0.74rem;
        color: #64748b;
    }

    .panel-item-val {
        font-size: 1rem;
        font-weight: 800;
        text-align: right;
    }
</style>
@endpush

@section('content')

    <!-- [AI] Executive Title Bar -->
    <div class="exec-header">
        <div class="exec-title">
            <h2>🏢 {{ $locationLabel }}</h2>
            <p>Real-time counter sales, cash drawer reconciliations, and low-stock alerts.</p>
        </div>
        <div class="exec-badges">
            <span class="exec-badge badge-branch">
                🏬 {{ $locationLabel }}
            </span>
            <span class="exec-badge badge-date">
                📅 {{ $rangeLabel }}
            </span>
            <a href="{{ route('pos.index') }}" class="btn btn-success btn-sm" style="font-weight: 800; padding: 0.45rem 1rem; border-radius: 10px; box-shadow: 0 4px 15px rgba(25,135,84,0.35);">
                💰 Launch POS Terminal
            </a>
        </div>
    </div>

    <!-- [AI] Filter Controls -->
    <div class="filter-hub text-white">
        <form method="GET" action="{{ route('pos.dashboard') }}" id="dashFilterForm">
            <div class="filter-row-top">
                <!-- Branch Selector -->
                <div class="filter-control-group">
                    <label class="filter-label">🏢 Branch:</label>
                    <select name="warehouse_id" class="filter-select" onchange="document.getElementById('dashFilterForm').submit()">
                        <option value="">All Branches (Consolidated)</option>
                        @foreach($branches as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }} ({{ $wh->contact ?? 'Active' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Period Preset Selector -->
                <div class="filter-control-group">
                    <label class="filter-label">📅 Period:</label>
                    <select name="date_preset" id="datePresetSelect" class="filter-select" onchange="handleDatePresetChange(this.value)">
                        <option value="TODAY" {{ $datePreset === 'TODAY' ? 'selected' : '' }}>Today ({{ \Carbon\Carbon::today()->format('d M') }})</option>
                        <option value="YESTERDAY" {{ $datePreset === 'YESTERDAY' ? 'selected' : '' }}>Yesterday</option>
                        <option value="THIS_WEEK" {{ $datePreset === 'THIS_WEEK' ? 'selected' : '' }}>This Week</option>
                        <option value="THIS_MONTH" {{ $datePreset === 'THIS_MONTH' ? 'selected' : '' }}>This Month ({{ \Carbon\Carbon::now()->format('F') }})</option>
                        <option value="THIS_YEAR" {{ $datePreset === 'THIS_YEAR' ? 'selected' : '' }}>This Year ({{ \Carbon\Carbon::now()->format('Y') }})</option>
                        <option value="ALL" {{ $datePreset === 'ALL' ? 'selected' : '' }}>All-Time</option>
                        <option value="CUSTOM" {{ $datePreset === 'CUSTOM' ? 'selected' : '' }}>🗓️ Custom Date Range...</option>
                    </select>
                </div>

                <!-- Reset Button -->
                @if($datePreset !== 'TODAY' || request('warehouse_id'))
                    <div>
                        <a href="{{ route('pos.dashboard') }}" class="btn btn-outline-secondary btn-sm" style="font-size: 0.78rem; padding: 0.45rem 0.85rem; border-radius: 8px;">
                            ↺ Reset Filters
                        </a>
                    </div>
                @endif
            </div>

            <!-- Custom Date Picker Row -->
            <div id="customDateRangeRow" class="custom-range-row" style="{{ $datePreset === 'CUSTOM' ? 'display: flex;' : 'display: none;' }}">
                <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 700;">Custom Range:</span>
                <input type="date" name="from_date" id="fromDateInput" value="{{ $fromDate ?? request('from_date') }}" class="custom-range-input" {{ $datePreset === 'CUSTOM' ? 'required' : '' }}>
                <span style="color: #64748b; font-size: 0.8rem;">to</span>
                <input type="date" name="to_date" id="toDateInput" value="{{ $toDate ?? request('to_date') }}" class="custom-range-input" {{ $datePreset === 'CUSTOM' ? 'required' : '' }}>
                <button type="submit" class="btn btn-primary btn-sm" style="font-weight: 700; padding: 0.35rem 0.85rem; border-radius: 8px;">
                    Apply Range
                </button>
            </div>
        </form>
    </div>

    <!-- [AI] Cashier Till Summary (Visible only when cashier logs in) -->
    @if($userRole === 'cashier')
        <div style="background: linear-gradient(135deg, rgba(94, 23, 235, 0.15) 0%, rgba(15, 23, 42, 0.8) 100%); border: 1px solid rgba(94, 23, 235, 0.3); border-radius: 18px; padding: 1.5rem; margin-bottom: 1.5rem;" class="text-white">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--pos-gold); margin-bottom: 0.2rem;">👤 Cashier Shift Summary</h3>
                    <p style="color: #cbd5e1; font-size: 0.82rem; margin-bottom: 0;">Showing transactions processed by you for <strong>{{ $rangeLabel }}</strong>.</p>
                </div>
                <a href="{{ route('pos.index') }}" class="btn btn-success" style="font-weight: 800; padding: 0.5rem 1.25rem; border-radius: 10px;">
                    💰 Open Counter Register
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 1rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Receipts Issued</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #f8fafc; margin-top: 0.2rem;">{{ $mySalesCount }}</div>
                </div>

                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 1rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Total Sales</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #10b981; margin-top: 0.2rem;">₦{{ number_format($mySalesAmount, 2) }}</div>
                </div>

                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 1rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Cash in Till</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #38bdf8; margin-top: 0.2rem;">₦{{ number_format($myCashAmount, 2) }}</div>
                </div>

                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 1rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Cards & Transfers</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #a78bfa; margin-top: 0.2rem;">₦{{ number_format($myPosAmount + $myTransferAmount, 2) }}</div>
                </div>

                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 1rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Credit Ledger Issued</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #f87171; margin-top: 0.2rem;">₦{{ number_format($myDebtAmount, 2) }}</div>
                </div>
            </div>
        </div>
    @endif

    <!-- [AI] Dashboard Metric Cards (Consolidated / Branch scope) -->
    <div class="hero-grid text-white">
        <!-- 1. Gross Revenue -->
        <div class="hero-card hero-sales">
            <div>
                <div class="hero-label">Gross Revenue</div>
                <div class="hero-val" style="color: #10b981;">₦{{ number_format($totalRevenue, 2) }}</div>
                <span class="hero-sub">{{ $totalSalesCount }} checkout sale{{ $totalSalesCount == 1 ? '' : 's' }}</span>
            </div>
            <div class="hero-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                💵
            </div>
        </div>

        <!-- 2. Collections Inflow -->
        <div class="hero-card hero-cash">
            <div>
                <div class="hero-label">Inflow Collected</div>
                <div class="hero-val" style="color: #38bdf8;">₦{{ number_format($totalPaid, 2) }}</div>
                <span class="hero-sub">₦{{ number_format($cashRevenue, 0) }} Cash · ₦{{ number_format($posCardRevenue + $transferRevenue, 0) }} Cards/Bank</span>
            </div>
            <div class="hero-icon" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8;">
                🪙
            </div>
        </div>

        <!-- 3. Catalog Inventory Valuation -->
        <div class="hero-card hero-stock">
            <div>
                <div class="hero-label">Central Catalog Stock</div>
                <div class="hero-val" style="color: #818cf8;">₦{{ number_format($totalStockValuation, 2) }}</div>
                <span class="hero-sub">{{ number_format($totalPhysicalUnits) }} total stock units in catalog</span>
            </div>
            <div class="hero-icon" style="background: rgba(129, 140, 248, 0.15); color: #818cf8;">
                🏷️
            </div>
        </div>

        <!-- 4. Outstanding Credit Portfolio -->
        <div class="hero-card hero-debt">
            <div>
                <div class="hero-label">Outstanding Debt Portfolio</div>
                <div class="hero-val" style="color: #fbbf24;">₦{{ number_format($totalOutstandingDebt, 2) }}</div>
                <span class="hero-sub" style="color: #fbbf24;">{{ $activeDebtorsCount }} active debtor{{ $activeDebtorsCount == 1 ? '' : 's' }}</span>
            </div>
            <div class="hero-icon" style="background: rgba(251, 191, 36, 0.15); color: #fbbf24;">
                ⏳
            </div>
        </div>
    </div>

    <!-- [AI] Three Column Operations Panel -->
    <div class="panels-grid">
        <!-- 1. Till Summary & Cash flows -->
        <div class="panel-card">
            <div class="panel-header">
                <span class="panel-title">💳 Drawer Cash Flow</span>
                <a href="{{ route('pos.debts.index') }}" class="btn btn-outline-light btn-sm" style="font-size: 0.72rem; padding: 2px 6px;">
                    Debtors Ledger →
                </a>
            </div>
            <div class="panel-list">
                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">💵</span>
                        <div>
                            <div class="panel-item-name">Drawer Cash Collected</div>
                            <div class="panel-item-sub">Physical cash payments</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-info">₦{{ number_format($cashRevenue, 2) }}</div>
                </div>

                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">💳</span>
                        <div>
                            <div class="panel-item-name">Cards & Bank Inflows</div>
                            <div class="panel-item-sub">Electronic payments</div>
                        </div>
                    </div>
                    <div class="panel-item-val" style="color: #a78bfa;">₦{{ number_format($posCardRevenue + $transferRevenue, 2) }}</div>
                </div>

                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">📝</span>
                        <div>
                            <div class="panel-item-name">New Debts Recorded</div>
                            <div class="panel-item-sub">Credit sales in period</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-warning">₦{{ number_format($totalDebt, 2) }}</div>
                </div>

                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">🤝</span>
                        <div>
                            <div class="panel-item-name">Debt Payments Collected</div>
                            <div class="panel-item-sub">{{ $debtRecoveryCount }} payment{{ $debtRecoveryCount == 1 ? '' : 's' }} in period</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-success">₦{{ number_format($debtRecoveredInPeriod, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- 2. Stock In & Out Flow -->
        <div class="panel-card">
            <div class="panel-header">
                <span class="panel-title">📦 Inventory Flows</span>
                <a href="{{ route('pos.stock.index') }}" class="btn btn-outline-light btn-sm" style="font-size: 0.72rem; padding: 2px 6px;">
                    Stock Hub →
                </a>
            </div>
            <div class="panel-list">
                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">📥</span>
                        <div>
                            <div class="panel-item-name">Stock-In received</div>
                            <div class="panel-item-sub">Supplier deliveries & updates</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-success">+{{ number_format($totalStockInUnits) }} units</div>
                </div>

                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">📤</span>
                        <div>
                            <div class="panel-item-name">Stock-Out sales</div>
                            <div class="panel-item-sub">Total catalog unit movements</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-danger">-{{ number_format($totalStockOutUnits) }} units</div>
                </div>

                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">⚠️</span>
                        <div>
                            <div class="panel-item-name">Low Stock SKUs</div>
                            <div class="panel-item-sub">{{ $outOfStockProducts }} Out of Stock, {{ $lowStockProducts->count() }} Low</div>
                        </div>
                    </div>
                    <div class="panel-item-val" style="color: {{ ($lowStockProducts->count() + $outOfStockProducts) > 0 ? '#fbbf24' : '#10b981' }};">
                        {{ $lowStockProducts->count() + $outOfStockProducts }} SKUs
                    </div>
                </div>

                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">💔</span>
                        <div>
                            <div class="panel-item-name">Damages & Write-offs</div>
                            <div class="panel-item-sub">Expired or broken units</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-warning">{{ number_format($damagedUnits) }} units</div>
                </div>
            </div>
        </div>

        <!-- 3. Catalog Profitability / Margins -->
        <div class="panel-card text-white">
            <div class="panel-header">
                <span class="panel-title">📈 Profitability & Returns</span>
                <a href="{{ route('pos.reports.index') }}" class="btn btn-outline-light btn-sm" style="font-size: 0.72rem; padding: 2px 6px;">
                    Reports →
                </a>
            </div>
            <div class="panel-list">
                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">💰</span>
                        <div>
                            <div class="panel-item-name">Cost of Goods Sold (COGS)</div>
                            <div class="panel-item-sub">Purchase value of sold units</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-warning">₦{{ number_format($totalCOGS, 2) }}</div>
                </div>

                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">💵</span>
                        <div>
                            <div class="panel-item-name">Gross Profit</div>
                            <div class="panel-item-sub">Sales Revenue − COGS</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-success">₦{{ number_format($grossProfit, 2) }}</div>
                </div>

                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">📊</span>
                        <div>
                            <div class="panel-item-name">Profit Margin</div>
                            <div class="panel-item-sub">Gross Profit margin percent</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-info">{{ $profitMarginPct }}%</div>
                </div>

                <div class="panel-item">
                    <div class="panel-item-left">
                        <span class="panel-item-icon">🔄</span>
                        <div>
                            <div class="panel-item-name">Returns & Refunds</div>
                            <div class="panel-item-sub">{{ $totalReturnsCount }} returns ({{ $returnedUnits }} units)</div>
                        </div>
                    </div>
                    <div class="panel-item-val text-danger">₦{{ number_format($totalReturnsValue, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- [AI] Recent Invoices Table -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h4 class="mb-0 fs-6 fw-bold"><i class="fas fa-receipt text-primary me-2"></i> Recent Counter Invoices</h4>
            <a href="{{ route('pos.transactions.index') }}" class="btn btn-sm btn-outline-primary fw-bold" style="font-size: 0.75rem;">View Sales History →</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                    <tr>
                        <th class="ps-3">Invoice #</th>
                        <th>Cashier</th>
                        <th>Customer</th>
                        <th>Payment Breakdowns</th>
                        <th>Total Bill</th>
                        <th>Paid</th>
                        <th>Debt Outstanding</th>
                        <th>Status</th>
                        <th class="pe-3">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $s)
                        <tr>
                            <td class="ps-3 fw-bold text-primary">
                                <a href="{{ route('pos.receipt', $s->id) }}" target="_blank" style="text-decoration: none;">
                                    {{ $s->receipt_number }}
                                </a>
                            </td>
                            <td>{{ $s->cashier_name ?? 'Merchant' }}</td>
                            <td>{{ $s->customer_name }} @if($s->customer_phone)<br><small class="text-muted">{{ $s->customer_phone }}</small>@endif</td>
                            <td>
                                <small class="text-muted">
                                    @if($s->cash_amount > 0) Cash: ₦{{ number_format($s->cash_amount, 0) }} @endif
                                    @if($s->pos_card_amount > 0) Card: ₦{{ number_format($s->pos_card_amount, 0) }} @endif
                                    @if($s->transfer_amount > 0) Bank: ₦{{ number_format($s->transfer_amount, 0) }} @endif
                                </small>
                            </td>
                            <td class="fw-bold text-dark">₦{{ number_format($s->total_amount, 2) }}</td>
                            <td class="text-success">₦{{ number_format($s->paid_amount, 2) }}</td>
                            <td class="text-danger fw-bold">
                                @if($s->debt_amount > 0)
                                    ₦{{ number_format($s->debt_amount, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-{{ $s->status === 'completed' ? 'success' : ($s->status === 'returned' ? 'danger' : 'warning') }}" style="font-size: 0.65rem;">
                                    {{ $s->status }}
                                </span>
                            </td>
                            <td class="pe-3 text-muted" style="font-size: 0.75rem;">{{ \Carbon\Carbon::parse($s->created_at)->format('d M H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No invoices generated in this period yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function handleDatePresetChange(val) {
    const customRow = document.getElementById('customDateRangeRow');
    const fromInput = document.getElementById('fromDateInput');
    const toInput = document.getElementById('toDateInput');
    if (val === 'CUSTOM') {
        customRow.style.display = 'flex';
        fromInput.required = true;
        toInput.required = true;
    } else {
        customRow.style.display = 'none';
        fromInput.required = false;
        toInput.required = false;
        fromInput.value = '';
        toInput.value = '';
        document.getElementById('dashFilterForm').submit();
    }
}
</script>
@endpush
