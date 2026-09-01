@extends('saas.layout')

@section('title', 'Merchant Companies & Tenants')

@section('content')

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-size: 1.85rem; font-weight: 900; color: #fff; letter-spacing: -0.02em;">Merchant Companies & Tenants</h1>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">Manage all subscribed businesses, subscription tiers, branch counts, and marketplace sync keys.</p>
    </div>
    <button onclick="document.getElementById('newCompanyModal').style.display='flex'" class="btn btn-success">
        ➕ Onboard New Company
    </button>
</div>

<!-- Filters & Search -->
<div class="panel-card" style="padding: 1rem 1.5rem; margin-bottom: 1.5rem;">
    <form method="GET" action="{{ route('pos.saas.tenants') }}" style="display: flex; gap: 1rem; align-items: center;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Search company name, owner, email, or phone..." 
               style="flex: 1; padding: 0.65rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.9rem;">

        <select name="plan" style="padding: 0.65rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.9rem;">
            <option value="">All Subscription Plans</option>
            <option value="PRO" {{ request('plan') === 'PRO' ? 'selected' : '' }}>PRO Multi-Branch</option>
            <option value="FREE" {{ request('plan') === 'FREE' ? 'selected' : '' }}>Free Starter</option>
        </select>

        <button type="submit" class="btn btn-primary">Filter</button>
        @if(request()->hasAny(['search', 'plan']))
            <a href="{{ route('pos.saas.tenants') }}" class="btn" style="background: rgba(255,255,255,0.1); color: #fff;">Clear</a>
        @endif
    </form>
</div>

<!-- Companies Table -->
<div class="panel-card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Company / Merchant</th>
                    <th>Plan & Status</th>
                    <th>Branch Allowance</th>
                    <th>Marketplace API Key</th>
                    <th>Subscription Expiry</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $c)
                <tr>
                    <td>
                        <div style="font-weight: 800; color: #fff; font-size: 0.95rem;">{{ $c->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">
                            👤 {{ $c->owner_name ?? 'N/A' }} · 📧 {{ $c->owner_email ?? 'N/A' }} · 📞 {{ $c->owner_phone ?? 'N/A' }}
                        </div>
                    </td>
                    <td>
                        @if($c->plan === 'PRO')
                            <span class="badge badge-pro">⭐ PRO (₦{{ number_format($c->plan_price) }}/mo)</span>
                        @else
                            <span class="badge badge-free">Free 1-Shop</span>
                        @endif
                        <div style="margin-top: 0.3rem;">
                            @if($c->is_active)
                                <span class="badge badge-active">Active</span>
                            @else
                                <span class="badge" style="background: rgba(239,68,68,0.2); color: #f87171;">Suspended</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <strong style="color: #38bdf8; font-size: 0.95rem;">{{ $c->warehouses_count }} Shops</strong>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                            Limit: {{ $c->plan === 'PRO' ? 'Unlimited (∞)' : ($c->max_branches ?? 1) }}
                        </div>
                    </td>
                    <td>
                        <code style="font-size: 0.72rem; background: #0f172a; padding: 0.3rem 0.5rem; border-radius: 6px; color: #a78bfa; border: 1px solid var(--border);">
                            {{ substr($c->api_key, 0, 14) }}...
                        </code>
                    </td>
                    <td>
                        @if($c->subscription_expires_at)
                            <div style="font-size: 0.82rem; font-weight: 700; color: {{ $c->subscription_expires_at->isPast() ? '#f87171' : '#34d399' }};">
                                {{ $c->subscription_expires_at->format('d M Y') }}
                            </div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">
                                {{ $c->subscription_expires_at->diffForHumans() }}
                            </div>
                        @else
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Never (Free)</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                            @if($c->plan !== 'PRO')
                                <form method="POST" action="{{ route('pos.saas.tenants.plan', $c->id) }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="action" value="upgrade_pro">
                                    <button type="submit" class="btn" style="background: rgba(99,102,241,0.2); color: #a5b4fc; font-size: 0.75rem; padding: 0.35rem 0.65rem;" title="Upgrade to Pro Multi-Branch for 30 Days">
                                        ⭐ Pro
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('pos.saas.tenants.plan', $c->id) }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="action" value="downgrade_free">
                                    <button type="submit" class="btn" style="background: rgba(100,116,139,0.2); color: #cbd5e1; font-size: 0.75rem; padding: 0.35rem 0.65rem;" title="Revert to Free 1-Shop">
                                        Free
                                    </button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('pos.saas.tenants.plan', $c->id) }}" style="display: inline;">
                                @csrf
                                <input type="hidden" name="action" value="extend_trial">
                                <button type="submit" class="btn" style="background: rgba(245,158,11,0.2); color: #fbbf24; font-size: 0.75rem; padding: 0.35rem 0.65rem;" title="Extend 14-Day Free Trial">
                                    +14d
                                </button>
                            </form>

                            <form method="POST" action="{{ route('pos.saas.tenants.plan', $c->id) }}" style="display: inline;">
                                @csrf
                                <input type="hidden" name="action" value="toggle_status">
                                <button type="submit" class="btn" style="background: rgba(239,68,68,0.2); color: #f87171; font-size: 0.75rem; padding: 0.35rem 0.65rem;" title="Suspend / Activate">
                                    {{ $c->is_active ? '⏸' : '▶' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem;">No companies match your search.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $companies->links() }}
    </div>
</div>

<!-- Onboard Company Modal -->
<div id="newCompanyModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 100; align-items: center; justify-content: center;">
    <div style="background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; width: 100%; max-width: 520px; padding: 2rem;">
        <h3 style="font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 0.25rem;">➕ Onboard New Company</h3>
        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.5rem;">Set up a new independent merchant and generate their primary shop counter.</p>

        <form method="POST" action="{{ route('pos.saas.tenants.store') }}">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Business / Company Name</label>
                <input type="text" name="name" required placeholder="e.g. Chinedu Supermarket Ltd" style="width: 100%; padding: 0.65rem 0.85rem; background: #0f172a; border: 1px solid var(--border); border-radius: 8px; color: #fff;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Owner Full Name</label>
                <input type="text" name="owner_name" required placeholder="e.g. Chief Chinedu Okeke" style="width: 100%; padding: 0.65rem 0.85rem; background: #0f172a; border: 1px solid var(--border); border-radius: 8px; color: #fff;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Owner Work Email</label>
                <input type="email" name="owner_email" required placeholder="owner@company.com" style="width: 100%; padding: 0.65rem 0.85rem; background: #0f172a; border: 1px solid var(--border); border-radius: 8px; color: #fff;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Phone Number</label>
                <input type="text" name="owner_phone" placeholder="08030000000" style="width: 100%; padding: 0.65rem 0.85rem; background: #0f172a; border: 1px solid var(--border); border-radius: 8px; color: #fff;">
            </div>

            <div style="margin-bottom: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Initial Plan</label>
                    <select name="plan" style="width: 100%; padding: 0.65rem 0.85rem; background: #0f172a; border: 1px solid var(--border); border-radius: 8px; color: #fff;">
                        <option value="FREE">Free Forever (1 Shop)</option>
                        <option value="PRO">Multi-Branch PRO (₦{{ number_format($settings->multi_branch_price) }})</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Branch Limit</label>
                    <input type="number" name="max_branches" value="1" min="1" max="50" style="width: 100%; padding: 0.65rem 0.85rem; background: #0f172a; border: 1px solid var(--border); border-radius: 8px; color: #fff;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="document.getElementById('newCompanyModal').style.display='none'" class="btn" style="background: rgba(255,255,255,0.1); color: #fff;">Cancel</button>
                <button type="submit" class="btn btn-success">Save & Generate Company</button>
            </div>
        </form>
    </div>
</div>

@endsection
