@extends('saas.layout')

@section('title', 'SaaS Master Platform Configuration')

@section('content')

<div style="margin-bottom: 2rem;">
    <h1 style="font-size: 1.85rem; font-weight: 900; color: #fff; letter-spacing: -0.02em;">SaaS Platform & Pricing Configuration</h1>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.25rem;">Dynamically configure Paystack billing, subscription pricing, free branch allowances, and marketplace bridge settings.</p>
</div>

<form method="POST" action="{{ route('pos.saas.settings.update') }}">
    @csrf

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.75rem;">
        
        <!-- Subscription & Pricing Rules -->
        <div class="panel-card">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 0.25rem;">💰 Subscription & Branch Tier Rules</h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 1.5rem;">Configure the Free vs Paid Multi-Branch tier parameters.</p>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">SaaS Platform Name</label>
                <input type="text" name="platform_name" value="{{ old('platform_name', $settings->platform_name) }}" 
                       style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.95rem; font-weight: 600;" required>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Multi-Branch PRO Monthly Price (₦)</label>
                <input type="number" step="100" min="0" name="multi_branch_price" value="{{ old('multi_branch_price', $settings->multi_branch_price) }}" 
                       style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #4ade80; font-size: 1.1rem; font-weight: 800;" required>
                <small style="color: var(--text-muted); font-size: 0.75rem;">Amount charged when a merchant adds a 2nd physical shop/warehouse.</small>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Free Branch Limit (Default: 1)</label>
                <input type="number" min="1" max="10" name="free_branch_limit" value="{{ old('free_branch_limit', $settings->free_branch_limit) }}" 
                       style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.95rem; font-weight: 600;" required>
                <small style="color: var(--text-muted); font-size: 0.75rem;">Number of physical shops allowed on the Free Forever plan before upgrade prompt.</small>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Platform Currency Symbol</label>
                <input type="text" name="currency" value="{{ old('currency', $settings->currency) }}" 
                       style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.95rem; font-weight: 600;" required>
            </div>
        </div>

        <!-- Paystack Payment Gateway & Marketplace Sync -->
        <div class="panel-card">
            <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 0.25rem;">💳 Paystack Gateway Integration</h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 1.5rem;">Automated subscription collection via Debit Cards, USSD, and Bank Transfer.</p>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Paystack Public Key</label>
                <input type="text" name="paystack_public_key" value="{{ old('paystack_public_key', $settings->paystack_public_key) }}" placeholder="pk_live_xxxxxxxxxxxxxxxx"
                       style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-family: monospace; font-size: 0.85rem;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Paystack Secret Key</label>
                <input type="password" name="paystack_secret_key" value="{{ old('paystack_secret_key', $settings->paystack_secret_key) }}" placeholder="sk_live_xxxxxxxxxxxxxxxx"
                       style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-family: monospace; font-size: 0.85rem;">
            </div>

            <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <input type="checkbox" name="paystack_enabled" id="paystack_enabled" value="1" {{ $settings->paystack_enabled ? 'checked' : '' }} style="width: 18px; height: 18px;">
                <label for="paystack_enabled" style="font-size: 0.88rem; font-weight: 700; color: #cbd5e1; cursor: pointer;">Enable Automated Paystack Gateway (Active/Deactive)</label>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin-bottom: 1.5rem;">

            <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 0.25rem;">🏦 Offline Direct Bank Transfer (Active/Deactive)</h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 1rem;">Allow merchants to transfer subscription funds directly to your company bank account.</p>

            <div style="margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                <input type="checkbox" name="offline_payment_enabled" id="offline_payment_enabled" value="1" {{ $settings->offline_payment_enabled ? 'checked' : '' }} style="width: 18px; height: 18px;">
                <label for="offline_payment_enabled" style="font-size: 0.88rem; font-weight: 700; color: #34d399; cursor: pointer;">Enable Manual Offline Bank Transfer Option</label>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Bank Name</label>
                <input type="text" name="bank_name" value="{{ old('bank_name', $settings->bank_name) }}" placeholder="e.g. Zenith Bank PLC / GTBank"
                       style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Account Number</label>
                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $settings->bank_account_number) }}" placeholder="1012345678"
                           style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #4ade80; font-family: monospace; font-size: 1rem; font-weight: 800;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Account Name</label>
                    <input type="text" name="bank_account_name" value="{{ old('bank_account_name', $settings->bank_account_name) }}" placeholder="VMarket Technologies Ltd"
                           style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #fff; font-size: 0.9rem; font-weight: 700;">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Merchant Transfer Instructions</label>
                <textarea name="offline_payment_instructions" rows="2" style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #cbd5e1; font-size: 0.85rem;">{{ old('offline_payment_instructions', $settings->offline_payment_instructions) }}</textarea>
            </div>

            <hr style="border: 0; border-top: 1px solid var(--border); margin-bottom: 1.5rem;">

            <h4 style="font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 0.25rem;">🌐 VMarket Online Marketplace Bridge</h4>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;">Link this POS system to your centralized VMarket e-commerce portal.</p>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.4rem;">Marketplace Bridge URL</label>
                <input type="url" name="marketplace_api_url" value="{{ old('marketplace_api_url', $settings->marketplace_api_url) }}" placeholder="https://vmarket.ng/api/v1/pos-bridge"
                       style="width: 100%; padding: 0.75rem 1rem; background: #0f172a; border: 1px solid var(--border); border-radius: 10px; color: #38bdf8; font-family: monospace; font-size: 0.85rem;">
            </div>

            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <input type="checkbox" name="marketplace_bridge_active" id="marketplace_bridge_active" value="1" {{ $settings->marketplace_bridge_active ? 'checked' : '' }} style="width: 18px; height: 18px;">
                <label for="marketplace_bridge_active" style="font-size: 0.88rem; font-weight: 700; color: #cbd5e1; cursor: pointer;">Activate Marketplace REST APIs</label>
            </div>
        </div>

    </div>

    <div style="margin-top: 1.5rem; text-align: right;">
        <button type="submit" class="btn btn-success btn-lg" style="font-size: 1rem; padding: 0.85rem 2rem;">
            💾 Save SaaS Master Settings
        </button>
    </div>
</form>

@endsection
