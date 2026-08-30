@extends('saas.layout')

@section('title', 'Master Executive Dashboard')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 900; color: #fff; letter-spacing: -0.02em;">SaaS Platform Command Center</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">Real-time health, tenant subscriptions, and live pulse across all merchant stores.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('saas.settings') }}" class="btn btn-primary">⚙️ Edit Pricing & Paystack</a>
        <a href="{{ route('saas.tenants') }}" class="btn btn-success">➕ Onboard Company</a>
    </div>
</div>

<!-- SaaS Executive KPIs -->
<div class="kpi-grid">
    <div class="kpi-card">
        <h4>Monthly Recurring Revenue</h4>
        <div class="val" style="color: #4ade80;">₦{{ number_format($mrr, 2) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">From {{ $proCompanies }} Multi-Branch Pro stores</div>
    </div>

    <div class="kpi-card">
        <h4>Total Merchant Companies</h4>
        <div class="val" style="color: #818cf8;">{{ number_format($totalCompanies) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">{{ $proCompanies }} Pro · {{ $freeCompanies }} Free Starter</div>
    </div>

    <div class="kpi-card">
        <h4>Active Physical Shops</h4>
        <div class="val" style="color: #38bdf8;">{{ number_format($totalShops) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">Physical branches on counters</div>
    </div>

    <div class="kpi-card">
        <h4>Total Platform GMV (Sales)</h4>
        <div class="val" style="color: #fbbf24;">₦{{ number_format($totalPlatformGMV, 2) }}</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.4rem;">Volume processed across all POS</div>
    </div>
</div>

<!-- Main Split: Live Platform Pulse vs Recent Companies -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    
    <!-- Live Platform Pulse Radar -->
    <div class="panel-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #fff;">📡 Live Platform Pulse</h3>
                <p style="font-size: 0.75rem; color: var(--text-muted);">Real-time stream of actions across all merchant stores</p>
            </div>
            <a href="{{ route('saas.activity') }}" class="btn" style="background: rgba(99,102,241,0.15); color: #818cf8; font-size: 0.8rem; padding: 0.4rem 0.8rem;">View All →</a>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.65rem; max-height: 480px; overflow-y: auto;">
            @forelse($livePulse as $act)
                @php
                    $icon = '⚡';
                    $color = '#818cf8';
                    if (str_contains($act->type, 'SALE')) { $icon = '🛍️'; $color = '#4ade80'; }
                    elseif (str_contains($act->type, 'TRANSFER')) { $icon = '🚚'; $color = '#60a5fa'; }
                    elseif (str_contains($act->type, 'STOCK')) { $icon = '📦'; $color = '#fbbf24'; }
                    elseif (str_contains($act->type, 'PAYSTACK')) { $icon = '💳'; $color = '#a855f7'; }
                @endphp
                <div style="background: rgba(15,23,42,0.6); border: 1px solid var(--border); border-radius: 12px; padding: 0.75rem 1rem; display: flex; align-items: start; gap: 0.75rem;">
                    <div style="font-size: 1.2rem;">{{ $icon }}</div>
                    <div style="flex: 1;">
                        <div style="font-size: 0.84rem; font-weight: 700; color: #f8fafc;">{{ $act->description }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 0.2rem; display: flex; gap: 0.75rem;">
                            <span>👤 {{ $act->userName }}</span>
                            <span>⏱️ {{ \Carbon\Carbon::parse($act->created_at)->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); padding: 2rem;">No platform activities recorded yet.</div>
            @endforelse
        </div>
    </div>

    <!-- Onboarded Companies Breakdown -->
    <div class="panel-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 800; color: #fff;">🏢 Onboarded Merchants</h3>
                <p style="font-size: 0.75rem; color: var(--text-muted);">Active companies and subscription statuses</p>
            </div>
            <a href="{{ route('saas.tenants') }}" class="btn" style="background: rgba(16,185,129,0.15); color: #34d399; font-size: 0.8rem; padding: 0.4rem 0.8rem;">Manage Tenants →</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Shops</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentCompanies as $c)
                    <tr>
                        <td>
                            <strong style="color: #fff; font-size: 0.92rem;">{{ $c->name }}</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $c->owner_email }}</div>
                        </td>
                        <td>
                            @if($c->plan === 'PRO')
                                <span class="badge badge-pro">⭐ PRO</span>
                            @else
                                <span class="badge badge-free">Free</span>
                            @endif
                        </td>
                        <td>
                            <strong style="color: #38bdf8;">{{ $c->warehouses_count }} / {{ $c->plan === 'PRO' ? '∞' : ($c->max_branches ?? 1) }}</strong>
                        </td>
                        <td>
                            @if($c->is_active)
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge" style="background: rgba(239,68,68,0.2); color: #f87171;">Suspended</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">No companies registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
