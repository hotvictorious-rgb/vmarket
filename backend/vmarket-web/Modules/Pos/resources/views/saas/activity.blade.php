@extends('saas.layout')

@section('title', 'Global Platform Live Activity Pulse')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 900; color: #fff; letter-spacing: -0.02em;">📡 Live Platform Audit & Activity Pulse</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">Real-time audit log stream of sales, transfers, inventory adjustments, and Paystack subscriptions across all stores.</p>
    </div>
</div>

<!-- Filters -->
<div class="panel-card" style="padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
    <form method="GET" action="{{ route('saas.activity') }}" style="display: flex; gap: 1rem; align-items: center;">
        <select name="company_id" style="flex: 1; padding: 0.65rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.9rem;">
            <option value="">All Merchant Companies</option>
            @foreach($companies as $comp)
                <option value="{{ $comp->id }}" {{ request('company_id') == $comp->id ? 'selected' : '' }}>{{ $comp->name }}</option>
            @endforeach
        </select>

        <select name="type" style="padding: 0.65rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.9rem;">
            <option value="">All Activity Types</option>
            <option value="SALE" {{ request('type') === 'SALE' ? 'selected' : '' }}>🛍️ POS Sales</option>
            <option value="TRANSFER" {{ request('type') === 'TRANSFER' ? 'selected' : '' }}>🚚 Inter-Shop Transfers</option>
            <option value="STOCK_IN" {{ request('type') === 'STOCK_IN' ? 'selected' : '' }}>📦 Stock-Ins</option>
            <option value="PAYSTACK_SUBSCRIPTION" {{ request('type') === 'PAYSTACK_SUBSCRIPTION' ? 'selected' : '' }}>💳 Paystack Upgrades</option>
        </select>

        <button type="submit" class="btn btn-primary">Filter Feed</button>
        @if(request()->hasAny(['company_id', 'type']))
            <a href="{{ route('saas.activity') }}" class="btn" style="background: rgba(255,255,255,0.1); color: #fff;">Clear</a>
        @endif
    </form>
</div>

<!-- Activity Stream -->
<div class="panel-card">
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @forelse($activities as $act)
            @php
                $icon = '⚡';
                $color = '#818cf8';
                $badgeBg = 'rgba(99,102,241,0.15)';
                if (str_contains($act->type, 'SALE')) { $icon = '🛍️'; $color = '#4ade80'; $badgeBg = 'rgba(16,185,129,0.15)'; }
                elseif (str_contains($act->type, 'TRANSFER')) { $icon = '🚚'; $color = '#60a5fa'; $badgeBg = 'rgba(59,130,246,0.15)'; }
                elseif (str_contains($act->type, 'STOCK')) { $icon = '📦'; $color = '#fbbf24'; $badgeBg = 'rgba(245,158,11,0.15)'; }
                elseif (str_contains($act->type, 'PAYSTACK')) { $icon = '💳'; $color = '#a855f7'; $badgeBg = 'rgba(168,85,247,0.15)'; }
                elseif (str_contains($act->type, 'RETURN')) { $icon = '🔄'; $color = '#f87171'; $badgeBg = 'rgba(239,68,68,0.15)'; }
            @endphp
            <div style="background: rgba(15,23,42,0.6); border: 1px solid var(--border); border-radius: 14px; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem; flex: 1;">
                    <div style="font-size: 1.5rem; background: {{ $badgeBg }}; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        {{ $icon }}
                    </div>
                    <div>
                        <div style="font-size: 0.92rem; font-weight: 700; color: #fff;">{{ $act->description }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem; display: flex; gap: 1rem;">
                            <span>👤 Performed By: <strong>{{ $act->userName }}</strong></span>
                            <span>🏷️ Type: <strong style="color: {{ $color }};">{{ $act->type }}</strong></span>
                        </div>
                    </div>
                </div>

                <div style="text-align: right; white-space: nowrap;">
                    <div style="font-size: 0.8rem; font-weight: 700; color: #cbd5e1;">
                        {{ \Carbon\Carbon::parse($act->created_at)->format('d M Y, h:i A') }}
                    </div>
                    <div style="font-size: 0.72rem; color: #818cf8;">
                        {{ \Carbon\Carbon::parse($act->created_at)->diffForHumans() }}
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align: center; color: var(--text-muted); padding: 3rem;">No activities found matching criteria.</div>
        @endforelse
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $activities->links() }}
    </div>
</div>

@endsection
