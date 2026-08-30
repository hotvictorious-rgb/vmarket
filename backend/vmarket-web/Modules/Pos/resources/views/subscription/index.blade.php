@extends('layouts.app')

@section('title', 'Subscription & Multi-Branch Plan – ' . ($systemSettings->businessName ?? 'Store'))

@section('content')

<div style="max-width: 1100px; margin: 0 auto;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.85rem; font-weight: 900; color: #fff; letter-spacing: -0.02em;">Subscription & Multi-Branch Plan</h1>
            <p style="color: #94a3b8; font-size: 0.9rem; margin-top: 0.25rem;">Manage your shop's plan, unlock additional branch locations, and view Paystack billing history.</p>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-secondary">← Back to Settings</a>
    </div>

    <!-- Active Plan Status Banner -->
    <div style="background: linear-gradient(135deg, rgba(30,41,59,0.8), rgba(15,23,42,0.9)); border: 1.5px solid {{ ($company && $company->isPro()) ? 'rgba(99,102,241,0.5)' : 'rgba(51,65,85,0.6)' }}; border-radius: 20px; padding: 2rem; margin-bottom: 2.5rem; position: relative; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 1.5rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    @if($company && $company->isPro())
                        <span style="font-size: 2rem;">⭐</span>
                        <div>
                            <h2 style="font-size: 1.6rem; font-weight: 900; color: #fff;">Multi-Branch PRO Tier</h2>
                            <p style="color: #818cf8; font-weight: 700; font-size: 0.85rem;">Unlimited Branches · Anti-Theft Transfers · Central Consolidation</p>
                        </div>
                    @else
                        <span style="font-size: 2rem;">🏪</span>
                        <div>
                            <h2 style="font-size: 1.6rem; font-weight: 900; color: #fff;">Free Starter Tier</h2>
                            <p style="color: #94a3b8; font-weight: 600; font-size: 0.85rem;">1 Physical Shop Counter · Free Forever</p>
                        </div>
                    @endif
                </div>

                <div style="margin-top: 1.25rem; display: flex; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="background: #0f172a; border: 1px solid #334155; padding: 0.6rem 1.2rem; border-radius: 12px;">
                        <span style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Branch Usage:</span>
                        <div style="font-size: 1.1rem; font-weight: 900; color: #38bdf8;">
                            {{ $currentBranches }} / {{ ($company && $company->isPro()) ? 'Unlimited (∞)' : ($company->max_branches ?? 1) }} Shops
                        </div>
                    </div>

                    @if($company && $company->subscription_expires_at)
                    <div style="background: #0f172a; border: 1px solid #334155; padding: 0.6rem 1.2rem; border-radius: 12px;">
                        <span style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Subscription Renewal:</span>
                        <div style="font-size: 1.1rem; font-weight: 900; color: {{ $company->subscription_expires_at->isPast() ? '#f87171' : '#34d399' }};">
                            {{ $company->subscription_expires_at->format('d M Y') }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div>
                @if($company && $company->isPro())
                    <button type="button" onclick="initiatePaystackPayment()" class="btn btn-primary btn-lg" style="font-size: 1rem; padding: 0.85rem 1.75rem;">
                        🔄 Extend / Renew Pro (₦{{ number_format($saasSettings->multi_branch_price) }})
                    </button>
                @else
                    <button type="button" onclick="initiatePaystackPayment()" class="btn btn-success btn-lg" style="font-size: 1.05rem; padding: 0.95rem 2rem; box-shadow: 0 4px 20px rgba(16,185,129,0.3);">
                        ⚡ Upgrade to Multi-Branch Pro (₦{{ number_format($saasSettings->multi_branch_price) }}/mo)
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Pricing & Comparison Table -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.75rem; margin-bottom: 3rem;">
        
        <!-- Free Plan Card -->
        <div style="background: #111827; border: 1px solid #1f2937; border-radius: 20px; padding: 2rem;">
            <div style="font-size: 0.8rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Starter Tier</div>
            <h3 style="font-size: 1.5rem; font-weight: 900; color: #fff; margin-top: 0.25rem;">Free Forever</h3>
            <div style="font-size: 2rem; font-weight: 900; color: #fff; margin: 1rem 0;">
                ₦0 <span style="font-size: 0.85rem; font-weight: 600; color: #94a3b8;">/ month</span>
            </div>
            <p style="font-size: 0.82rem; color: #cbd5e1; margin-bottom: 1.5rem;">For single retail shops, kiosks, and boutiques.</p>

            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.88rem;">
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0;">✓ 1 Physical Shop / Cash Register</li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0;">✓ Unlimited Products & Sales</li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0;">✓ Barcode Scanner & Receipt Printing</li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0;">✓ Customer Debt Tracking & Ledgers</li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0;">✓ Auto-Sync to VMarket Marketplace</li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #64748b;">✕ Multiple Branches / Depots</li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #64748b;">✕ Inter-Branch Anti-Theft Waybills</li>
            </ul>
        </div>

        <!-- Pro Multi-Branch Card -->
        <div style="background: linear-gradient(135deg, rgba(30,27,75,0.7), rgba(15,23,42,0.9)); border: 2px solid #6366f1; border-radius: 20px; padding: 2rem; position: relative;">
            <div style="position: absolute; top: 1.5rem; right: 1.5rem; background: #6366f1; color: #fff; font-size: 0.72rem; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 8px; text-transform: uppercase;">
                POPULAR FOR EXPANSION
            </div>
            <div style="font-size: 0.8rem; font-weight: 800; color: #a5b4fc; text-transform: uppercase; letter-spacing: 0.05em;">Enterprise Pro</div>
            <h3 style="font-size: 1.5rem; font-weight: 900; color: #fff; margin-top: 0.25rem;">Multi-Branch Pro</h3>
            <div style="font-size: 2rem; font-weight: 900; color: #4ade80; margin: 1rem 0;">
                ₦{{ number_format($saasSettings->multi_branch_price, 0) }} <span style="font-size: 0.85rem; font-weight: 600; color: #94a3b8;">/ month</span>
            </div>
            <p style="font-size: 0.82rem; color: #cbd5e1; margin-bottom: 1.5rem;">For multi-shop merchants, central warehouses, and growing retail chains.</p>

            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.88rem;">
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0; font-weight: 700;">✓ <strong>Unlimited Physical Shops & Depots</strong></li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0; font-weight: 700;">✓ <strong>Anti-Theft Inter-Shop Transfer Waybills</strong></li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0; font-weight: 700;">✓ <strong>Central Executive Consolidation Radar</strong></li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0;">✓ Cashier & Branch Staff Isolation</li>
                <li style="display: flex; gap: 0.5rem; align-items: center; color: #e2e8f0;">✓ Everything in Free Forever</li>
            </ul>

            <div style="margin-top: 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                @if($saasSettings->paystack_enabled)
                    <button type="button" onclick="initiatePaystackPayment()" class="btn btn-primary btn-block" style="width: 100%; justify-content: center; padding: 0.85rem; font-size: 1rem;">
                        💳 Instant Online Payment (Paystack)
                    </button>
                @endif

                @if($saasSettings->offline_payment_enabled)
                    <button type="button" onclick="document.getElementById('offlinePaymentModal').style.display='flex'" class="btn" style="width: 100%; justify-content: center; padding: 0.85rem; font-size: 1rem; background: rgba(16,185,129,0.15); color: #34d399; border: 1.5px solid rgba(16,185,129,0.4);">
                        🏦 Direct Bank Transfer / Offline Payment
                    </button>
                @endif

                @if(!$saasSettings->paystack_enabled && !$saasSettings->offline_payment_enabled)
                    <div style="padding: 0.75rem; background: rgba(239,68,68,0.15); color: #f87171; border-radius: 10px; font-size: 0.85rem; text-align: center;">
                        Online and offline payment channels are currently undergoing maintenance. Please contact support.
                    </div>
                @endif
            </div>
        </div>

    </div>

    <!-- Offline Bank Transfer Modal -->
    <div id="offlinePaymentModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 100; align-items: center; justify-content: center;">
        <div style="background: #1e293b; border: 1px solid #334155; border-radius: 20px; width: 100%; max-width: 520px; padding: 2rem;">
            <h3 style="font-size: 1.3rem; font-weight: 800; color: #fff; margin-bottom: 0.25rem;">🏦 Direct Bank Transfer</h3>
            <p style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 1.25rem;">Transfer subscription funds directly to our designated bank account and submit your proof.</p>

            <!-- Bank Details Box -->
            <div style="background: #0f172a; border: 1px solid #475569; border-radius: 14px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; font-weight: 800;">Bank Details:</div>
                <div style="margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; color: #cbd5e1;">Bank:</span>
                    <strong style="color: #fff; font-size: 0.95rem;">{{ $saasSettings->bank_name ?? 'Zenith Bank PLC' }}</strong>
                </div>
                <div style="margin-top: 0.35rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; color: #cbd5e1;">Account Number:</span>
                    <strong style="color: #4ade80; font-family: monospace; font-size: 1.1rem; font-weight: 900; letter-spacing: 0.05em;">{{ $saasSettings->bank_account_number ?? '1012345678' }}</strong>
                </div>
                <div style="margin-top: 0.35rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; color: #cbd5e1;">Account Name:</span>
                    <strong style="color: #fff; font-size: 0.88rem;">{{ $saasSettings->bank_account_name ?? 'VMarket Technologies Ltd' }}</strong>
                </div>
                <div style="margin-top: 0.35rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #334155; padding-top: 0.4rem;">
                    <span style="font-size: 0.85rem; color: #cbd5e1;">Amount Due:</span>
                    <strong style="color: #fbbf24; font-size: 1.1rem; font-weight: 900;">₦{{ number_format($saasSettings->multi_branch_price, 2) }}</strong>
                </div>
            </div>

            <form method="POST" action="{{ route('subscription.offline.submit') }}">
                @csrf
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Transaction Reference / Session ID</label>
                    <input type="text" name="reference" required placeholder="e.g. 1000048291039 or Transfer Ref" style="width: 100%; padding: 0.65rem 0.85rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #fff; font-family: monospace;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Sender Account Name</label>
                    <input type="text" name="sender_name" required placeholder="e.g. Chinedu Stores / Access Bank" style="width: 100%; padding: 0.65rem 0.85rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #fff;">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #cbd5e1; margin-bottom: 0.35rem;">Optional Notes / Teller Proof Details</label>
                    <textarea name="proof_note" rows="2" placeholder="Paid via Zenith Mobile App at 11:30 AM" style="width: 100%; padding: 0.65rem 0.85rem; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #fff; font-size: 0.85rem;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" onclick="document.getElementById('offlinePaymentModal').style.display='none'" class="btn" style="background: rgba(255,255,255,0.1); color: #fff;">Cancel</button>
                    <button type="submit" class="btn btn-success">Submit Payment for Approval</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Past Billing Invoices -->
    <div style="background: #111827; border: 1px solid #1f2937; border-radius: 20px; padding: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 800; color: #fff; margin-bottom: 1.25rem;">📄 Past Subscription Receipts</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #334155; text-align: left;">
                        <th style="padding: 0.75rem; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase;">Ref</th>
                        <th style="padding: 0.75rem; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase;">Amount</th>
                        <th style="padding: 0.75rem; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase;">Date Paid</th>
                        <th style="padding: 0.75rem; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase;">Valid Until</th>
                        <th style="padding: 0.75rem; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr style="border-bottom: 1px solid #1e293b;">
                        <td style="padding: 0.75rem; font-family: monospace; color: #a78bfa;">#{{ $inv->reference }}</td>
                        <td style="padding: 0.75rem; font-weight: 700; color: #4ade80;">₦{{ number_format($inv->amount, 2) }}</td>
                        <td style="padding: 0.75rem; font-size: 0.85rem; color: #cbd5e1;">{{ \Carbon\Carbon::parse($inv->created_at)->format('d M Y') }}</td>
                        <td style="padding: 0.75rem; font-size: 0.85rem; color: #38bdf8;">{{ $inv->valid_until ? $inv->valid_until->format('d M Y') : '-' }}</td>
                        <td style="padding: 0.75rem;">
                            <span style="background: rgba(16,185,129,0.2); color: #34d399; padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 800;">
                                {{ $inv->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 2rem;">No previous Paystack payments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Paystack Inline Popup JS -->
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
async function initiatePaystackPayment() {
    try {
        const res = await fetch("{{ route('subscription.paystack.init') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const data = await res.json();
        
        if (!data.success) {
            alert(data.error || "Could not initialize Paystack payment.");
            return;
        }

        // Open Paystack Inline Checkout Modal
        let handler = PaystackPop.setup({
            key: data.key,
            email: data.email,
            amount: data.amount,
            ref: data.reference,
            currency: data.currency,
            onClose: function() {
                // User closed payment window
            },
            callback: function(response) {
                // Send reference to backend for verification and auto-activation
                verifyPayment(response.reference);
            }
        });
        handler.openIframe();
    } catch (e) {
        alert("Error launching Paystack: " + e.message);
    }
}

async function verifyPayment(reference) {
    try {
        const res = await fetch("{{ route('subscription.paystack.verify') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ reference: reference })
        });
        const result = await res.json();
        if (result.success) {
            alert(result.message);
            window.location.reload();
        } else {
            alert("Verification failed: " + result.error);
        }
    } catch (e) {
        alert("Error verifying payment: " + e.message);
    }
}
</script>

@endsection
