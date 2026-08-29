<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'POS') — Victorious MARKET</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* [AI] POS Module Layout — Brand: #5E17EB (Primary Purple), #FFD700 (Gold), #FFFFFF (White) */
        :root {
            --pos-primary: #5E17EB;
            --pos-gold: #FFD700;
            --pos-dark: #1a1a2e;
            --pos-sidebar-width: 240px;
        }
        body { font-family: 'Inter', sans-serif; background: #f4f6fb; }
        .pos-sidebar {
            width: var(--pos-sidebar-width);
            min-height: 100vh;
            background: linear-gradient(160deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            position: fixed; left: 0; top: 0; z-index: 1000;
            display: flex; flex-direction: column;
        }
        .pos-sidebar .brand {
            padding: 1.5rem 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .pos-sidebar .brand-logo {
            font-size: 1.1rem; font-weight: 700; color: #fff;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .pos-sidebar .brand-logo .badge-free {
            font-size: 0.6rem; background: var(--pos-gold); color: #1a1a2e;
            padding: 2px 6px; border-radius: 4px; font-weight: 700;
        }
        .pos-sidebar nav { flex: 1; padding: 1rem 0; }
        .pos-sidebar .nav-link {
            color: rgba(255,255,255,0.65); padding: 0.65rem 1.2rem;
            border-radius: 0; font-size: 0.875rem; display: flex; align-items: center; gap: 0.75rem;
            transition: all 0.2s; border-left: 3px solid transparent;
        }
        .pos-sidebar .nav-link:hover, .pos-sidebar .nav-link.active {
            color: #fff; background: rgba(94,23,235,0.25);
            border-left-color: var(--pos-primary);
        }
        .pos-sidebar .nav-link i { width: 18px; text-align: center; }
        .pos-main { margin-left: var(--pos-sidebar-width); min-height: 100vh; }
        .pos-topbar {
            background: #fff; padding: 0.75rem 1.5rem;
            box-shadow: 0 1px 0 rgba(0,0,0,0.06);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 999;
        }
        .pos-topbar .seller-badge {
            display: flex; align-items: center; gap: 0.5rem;
            font-size: 0.85rem; color: #555;
        }
        .pos-topbar .marketplace-badge {
            font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 20px;
        }
        .pos-topbar .marketplace-badge.pos-only {
            background: rgba(94,23,235,0.1); color: var(--pos-primary);
        }
        .pos-topbar .marketplace-badge.approved {
            background: rgba(25,135,84,0.1); color: #198754;
        }
        .pos-content { padding: 1.5rem; }
        .stat-card {
            background: #fff; border-radius: 12px; padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .stat-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .pos-sidebar { transform: translateX(-100%); }
            .pos-main { margin-left: 0; }
        }

        /* [AI] Custom Modal backdrop overriding standard layout specifically under .modal-backdrop */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75) !important;
            backdrop-filter: blur(6px);
            display: flex !important;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 9998 !important;
        }
        .modal-backdrop .modal {
            background: #1f2937 !important;
            border: 1px solid #374151 !important;
            border-radius: 20px !important;
            width: 100% !important;
            max-width: 580px !important;
            padding: 2rem !important;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5) !important;
            max-height: 90vh !important;
            overflow-y: auto !important;
            position: static !important;
            display: block !important;
            opacity: 1 !important;
            color: #fff !important;
        }
        .modal-backdrop label {
            display: block; font-size: 0.8rem; font-weight: 700; color: #9ca3af; margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .modal-backdrop input, .modal-backdrop select, .modal-backdrop textarea {
            width: 100%; padding: 0.85rem 1rem;
            background: rgba(11, 15, 25, 0.7);
            border: 1px solid #374151;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-family: inherit;
        }
        .modal-backdrop input:focus, .modal-backdrop select:focus, .modal-backdrop textarea:focus {
            outline: none;
            border-color: var(--pos-primary);
        }
        .modal-backdrop .btn-secondary {
            background: #374151; border: 1px solid #4b5563; color: #fff;
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- [AI] POS Sidebar Navigation --}}
<div class="pos-sidebar">
    <div class="brand">
        <div class="brand-logo">
            <i class="fas fa-store" style="color: var(--pos-gold);"></i>
            <span>Vmarket <strong>POS</strong></span>
            @php $seller = auth('seller')->user(); @endphp
            {{-- [AI] FREE badge shown when seller is pending/not yet approved on marketplace --}}
            @if($seller && $seller->status !== 'approved')
                <span class="badge-free">FREE</span>
            @endif
        </div>
        @if($seller)
            <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-top: 4px; truncate: ellipsis;">
                {{ $seller->f_name }} {{ $seller->l_name }}
            </div>
        @endif
    </div>
    <nav>
        <a href="{{ route('pos.dashboard') }}" class="nav-link {{ request()->routeIs('pos.dashboard') ? 'active' : '' }}">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}">
            <i class="fas fa-cash-register"></i> POS Terminal
        </a>
        <a href="{{ route('pos.products.index') }}" class="nav-link {{ request()->routeIs('pos.products.*') ? 'active' : '' }}">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="{{ route('pos.stock.index') }}" class="nav-link {{ request()->routeIs('pos.stock.*') ? 'active' : '' }}">
            <i class="fas fa-warehouse"></i> Stock
        </a>
        <a href="{{ route('pos.transactions.index') }}" class="nav-link {{ request()->routeIs('pos.transactions.*') ? 'active' : '' }}">
            <i class="fas fa-receipt"></i> Transactions
        </a>
        <a href="{{ route('pos.debts.index') }}" class="nav-link {{ request()->routeIs('pos.debts.*') ? 'active' : '' }}">
            <i class="fas fa-hand-holding-usd"></i> Debt Ledger
        </a>
        <a href="{{ route('pos.reports.index') }}" class="nav-link {{ request()->routeIs('pos.reports.*') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i> Reports
        </a>
        <a href="{{ route('pos.warehouses.index') }}" class="nav-link {{ request()->routeIs('pos.warehouses.*') ? 'active' : '' }}">
            <i class="fas fa-code-branch"></i> Branches
        </a>
        <div style="padding: 0.75rem 1.2rem; margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.08);">
            {{-- [AI] Marketplace return button: visible ONLY to sellers with status='approved' --}}
            @if($seller && $seller->status === 'approved')
                <a href="{{ url('/vendor') }}" class="nav-link" style="border-left-color: var(--pos-gold);">
                    <i class="fas fa-arrow-left" style="color: var(--pos-gold);"></i>
                    <span style="color: var(--pos-gold);">🔙 Back to Merchant Panel</span>
                </a>
            @else
                {{-- [AI] Unverified / pending sellers see locked marketplace pill --}}
                <div class="nav-link" style="opacity: 0.5; cursor: not-allowed; font-size: 0.75rem;">
                    <i class="fas fa-lock"></i> Marketplace Pending KYC
                </div>
            @endif
        </div>
    </nav>
</div>

{{-- [AI] Main Content Area --}}
<div class="pos-main">
    <div class="pos-topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-light d-md-none" onclick="document.querySelector('.pos-sidebar').style.transform='translateX(0)'">
                <i class="fas fa-bars"></i>
            </button>
            <span class="text-muted" style="font-size: 0.85rem;">@yield('breadcrumb', 'POS')</span>
        </div>
        <div class="seller-badge">
            @if($seller)
                {{-- [AI] Seller status badge: uses sellers.status + sellers.pos_status --}}
                @if($seller->status === 'approved')
                    <span class="marketplace-badge approved"><i class="fas fa-check-circle"></i> Verified Merchant</span>
                @elseif($seller->status === 'pending')
                    <span class="marketplace-badge" style="background: rgba(255,193,7,0.1); color: #856404;">
                        <i class="fas fa-clock"></i> Pending Approval
                    </span>
                @elseif($seller->status === 'denied')
                    <span class="marketplace-badge" style="background: rgba(220,53,69,0.1); color: #dc3545;">
                        <i class="fas fa-times-circle"></i> Denied
                    </span>
                @endif
                <span class="text-muted">|</span>
            @endif
            <form action="{{ route('vendor.auth.logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size: 0.75rem;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger m-3">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="pos-content">
        @yield('content')
    </div>
</div>

{{-- [AI] Universal Action Confirmation Modal --}}
<div id="modalGlobalConfirm" class="modal-backdrop" style="display: none;">
    <div class="modal" id="globalConfirmCard" style="max-width: 480px !important; border: 2px solid #5E17EB !important; border-radius: 20px;">
        <div style="text-align: center; margin-bottom: 1.25rem;">
            <div id="globalConfirmIcon" style="font-size: 2.75rem; margin-bottom: 0.35rem; line-height: 1;">⚡</div>
            <h3 id="globalConfirmTitle" style="font-size: 1.25rem; font-weight: 800; color: #fff;">Confirm Action</h3>
            <p id="globalConfirmSubtitle" style="font-size: 0.82rem; color: #94a3b8; margin-top: 0.25rem;">Review details before proceeding:</p>
        </div>

        <div id="globalConfirmBody" style="background: rgba(15,23,42,0.85); border: 1px solid #374151; border-radius: 14px; padding: 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.6rem;">
        </div>

        <div id="globalConfirmImpactWrap" style="margin-bottom: 1.25rem; display: none;">
            <div id="globalConfirmImpact" style="font-weight: 700; padding: 0.65rem 0.85rem; border-radius: 10px; font-size: 0.82rem;"></div>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" style="flex: 1; padding: 0.75rem; font-weight: 700;" onclick="closeGlobalConfirm()">
                ✕ Cancel
            </button>
            <button type="button" id="globalConfirmProceedBtn" class="btn btn-success" style="flex: 1.3; padding: 0.75rem; font-weight: 800;" onclick="executeGlobalConfirm()">
                ✅ Proceed
            </button>
        </div>
    </div>
</div>

{{-- [AI] Quick Calculator Modal --}}
<div id="modalCalculator" class="modal-backdrop" style="display: none;">
    <div class="modal" style="max-width: 360px !important; padding: 1.5rem; background: #111827 !important; border: 2px solid #374151 !important;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 800; color: #f9fafb;">🧮 POS Calculator</h3>
            <button type="button" onclick="toggleCalculator()" style="background: none; border: none; color: #9ca3af; font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>
        <div id="calcDisplay" style="background: #030712; border: 1px solid #374151; border-radius: 12px; padding: 1rem; font-size: 1.8rem; font-weight: 800; text-align: right; color: #4ade80; overflow-x: auto; margin-bottom: 1rem; min-height: 60px; font-family: monospace;">
            0
        </div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem;">
            <button type="button" class="btn btn-danger" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcClear()">C</button>
            <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('(')">(</button>
            <button type="button" class="btn btn-secondary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput(')')">)</button>
            <button type="button" class="btn btn-primary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('/')">÷</button>

            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('7')">7</button>
            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('8')">8</button>
            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('9')">9</button>
            <button type="button" class="btn btn-primary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('*')">×</button>

            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('4')">4</button>
            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('5')">5</button>
            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('6')">6</button>
            <button type="button" class="btn btn-primary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('-')">−</button>

            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('1')">1</button>
            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('2')">2</button>
            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('3')">3</button>
            <button type="button" class="btn btn-primary" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcInput('+')">+</button>

            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('0')">0</button>
            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('00')">00</button>
            <button type="button" class="btn btn-dark" style="padding: 0.85rem; font-size: 1.1rem; background: #1f2937;" onclick="calcInput('.')">.</button>
            <button type="button" class="btn btn-success" style="padding: 0.85rem; font-size: 1.1rem;" onclick="calcEquals()">=</button>
        </div>
    </div>
</div>

{{-- [AI] Action Blocked / Constraint Reason Modal --}}
<div id="modalActionBlocked" class="modal-backdrop" style="display: none;">
    <div class="modal" style="max-width: 480px !important; border: 2px solid #dc2626 !important;">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
            <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(220,38,38,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0;">
                ⛔
            </div>
            <div>
                <h3 style="font-size: 1.2rem; font-weight: 800; color: #f87171; margin-bottom: 0.15rem;" id="actionBlockedTitle">Action Blocked</h3>
                <span style="font-size: 0.78rem; color: #94a3b8;" id="actionBlockedSubtitle">Constraint Failed</span>
            </div>
            <button type="button" onclick="closeActionBlockedModal()" style="margin-left: auto; background: none; border: none; color: #94a3b8; font-size: 1.25rem; cursor: pointer;">✕</button>
        </div>
        <p style="font-size: 0.84rem; color: #cbd5e1; margin-bottom: 0.75rem;">
            Submission failed due to unmet requirements:
        </p>
        <div id="actionBlockedReasonsList" style="background: rgba(15,23,42,0.85); border: 1px solid rgba(220,38,38,0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem; max-height: 250px; overflow-y: auto;">
        </div>
        <button type="button" class="btn btn-danger w-100" style="font-weight: 800; padding: 0.75rem; border-radius: 10px;" onclick="closeActionBlockedModal()">
            ✕ Close & Edit
        </button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
    // ─── Calculator Logic ──────────────────────────────────────────────
    let calcExpression = '';
    function toggleCalculator() {
        const modal = document.getElementById('modalCalculator');
        modal.style.display = (modal.style.display === 'none' || modal.style.display === '') ? 'flex' : 'none';
    }
    function calcInput(val) {
        if (calcExpression === '0' && val !== '.') calcExpression = '';
        calcExpression += val;
        document.getElementById('calcDisplay').textContent = calcExpression;
    }
    function calcClear() {
        calcExpression = '';
        document.getElementById('calcDisplay').textContent = '0';
    }
    function calcEquals() {
        try {
            const sanitized = calcExpression.replace(/[^0-9+\-*/().]/g, '');
            if (!sanitized) return;
            const result = Function('"use strict";return (' + sanitized + ')')();
            calcExpression = String(result);
            document.getElementById('calcDisplay').textContent = Number(result).toLocaleString('en-US', { maximumFractionDigits: 4 });
        } catch (e) {
            document.getElementById('calcDisplay').textContent = 'Error';
            calcExpression = '';
        }
    }

    // ─── Confirm Action Modal Logic ────────────────────────────────────
    let pendingConfirmAction = null;
    function showConfirmPopup({
        icon = '⚡',
        title = 'Confirm Action',
        subtitle = 'Review details before proceeding:',
        items = [],
        message = '',
        impact = null,
        confirmText = '✅ Proceed',
        confirmClass = 'btn-success',
        borderColor = '#5E17EB',
        onConfirm = null,
        form = null
    }) {
        document.getElementById('globalConfirmIcon').textContent = icon;
        document.getElementById('globalConfirmTitle').textContent = title;
        document.getElementById('globalConfirmSubtitle').textContent = subtitle;

        const card = document.getElementById('globalConfirmCard');
        if (card) card.style.borderColor = borderColor;

        const bodyEl = document.getElementById('globalConfirmBody');
        bodyEl.innerHTML = '';

        if (items && items.length > 0) {
            items.forEach(item => {
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.justifyContent = 'space-between';
                row.style.alignItems = 'center';
                row.style.borderBottom = '1px dashed #374151';
                row.style.paddingBottom = '0.45rem';

                const labelSpan = document.createElement('span');
                labelSpan.style.color = '#94a3b8';
                labelSpan.textContent = item.label + ':';

                const valSpan = document.createElement('strong');
                valSpan.textContent = item.value;
                valSpan.style.color = item.color || '#f8fafc';
                if (item.size) valSpan.style.fontSize = item.size;

                row.appendChild(labelSpan);
                row.appendChild(valSpan);
                bodyEl.appendChild(row);
            });
        } else if (message) {
            const p = document.createElement('div');
            p.style.color = '#cbd5e1';
            p.style.lineHeight = '1.5';
            p.innerHTML = message;
            bodyEl.appendChild(p);
        }

        const impactWrap = document.getElementById('globalConfirmImpactWrap');
        const impactEl = document.getElementById('globalConfirmImpact');
        if (impact && impact.text) {
            impactWrap.style.display = 'block';
            impactEl.textContent = impact.text;
            if (impact.type === 'danger') {
                impactEl.style.background = 'rgba(220,38,38,0.15)';
                impactEl.style.color = '#f87171';
                impactEl.style.border = '1px solid #ef4444';
            } else if (impact.type === 'warning') {
                impactEl.style.background = 'rgba(245,158,11,0.15)';
                impactEl.style.color = '#fbbf24';
                impactEl.style.border = '1px solid #f59e0b';
            } else if (impact.type === 'info') {
                impactEl.style.background = 'rgba(59,130,246,0.15)';
                impactEl.style.color = '#60a5fa';
                impactEl.style.border = '1px solid #3b82f6';
            } else {
                impactEl.style.background = 'rgba(34,197,94,0.15)';
                impactEl.style.color = '#4ade80';
                impactEl.style.border = '1px solid #22c55e';
            }
        } else {
            impactWrap.style.display = 'none';
        }

        const proceedBtn = document.getElementById('globalConfirmProceedBtn');
        proceedBtn.textContent = confirmText;
        proceedBtn.className = 'btn ' + confirmClass;

        pendingConfirmAction = () => {
            if (typeof onConfirm === 'function') {
                onConfirm();
            } else if (form) {
                if (typeof form.submit === 'function') {
                    form.submit();
                } else {
                    HTMLFormElement.prototype.submit.call(form);
                }
            }
        };

        document.getElementById('modalGlobalConfirm').style.display = 'flex';
    }

    function closeGlobalConfirm() {
        document.getElementById('modalGlobalConfirm').style.display = 'none';
        pendingConfirmAction = null;
    }

    function executeGlobalConfirm() {
        const act = pendingConfirmAction;
        closeGlobalConfirm();
        if (act) act();
    }

    // ─── Action Blocked Modal Logic ────────────────────────────────────
    function showActionBlockedModal({
        title = 'Action Blocked',
        subtitle = 'Constraint Validation Failed',
        reasons = []
    }) {
        document.getElementById('actionBlockedTitle').textContent = title;
        document.getElementById('actionBlockedSubtitle').textContent = subtitle;
        const container = document.getElementById('actionBlockedReasonsList');
        container.innerHTML = '';

        reasons.forEach(reason => {
            const div = document.createElement('div');
            div.style.color = '#f87171';
            div.style.fontSize = '0.85rem';
            div.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
            div.style.paddingBottom = '0.35rem';
            div.textContent = '• ' + reason;
            container.appendChild(div);
        });

        document.getElementById('modalActionBlocked').style.display = 'flex';
    }

    function closeActionBlockedModal() {
        document.getElementById('modalActionBlocked').style.display = 'none';
    }

    // Close modals on backdrop click
    window.addEventListener('click', function(e) {
        if (e.target.id === 'modalGlobalConfirm') closeGlobalConfirm();
        if (e.target.id === 'modalCalculator') toggleCalculator();
        if (e.target.id === 'modalActionBlocked') closeActionBlockedModal();
    });
</script>
@stack('scripts')
</body>
</html>
