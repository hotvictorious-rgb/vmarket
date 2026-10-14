@php
    $cartCount = \App\Utils\CartManager::get_cart_count();
@endphp

{{-- [AI] Victorious MARKET Fly-Out Cart Drawer --}}
<div id="vmCartDrawerBackdrop" class="vm-cart-drawer-backdrop" aria-hidden="true"></div>

<aside id="vmCartDrawer" class="vm-cart-drawer" role="dialog" aria-modal="true" aria-labelledby="vmCartDrawerTitle">
    {{-- Drawer Header --}}
    <div class="vm-cart-drawer-header">
        <div class="vm-cart-drawer-title-wrap">
            <div class="vm-cart-drawer-icon-wrap">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
            </div>
            <div>
                <h5 class="vm-cart-drawer-title" id="vmCartDrawerTitle">{{ translate('Your Shopping Bag') }}</h5>
                <div class="vm-cart-drawer-subtitle">
                    <span class="vm-drawer-cart-count">{{ $cartCount }}</span> {{ translate('items') }} &bull; <span class="vm-drawer-escrow-badge"><i class="bi bi-shield-check text-warning me-1"></i> {{ translate('Escrow Protected') }}</span>
                </div>
            </div>
        </div>
        <button type="button" class="vm-cart-drawer-close-btn" id="vmCartDrawerCloseBtn" aria-label="{{ translate('Close Cart Drawer') }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    {{-- Trust & SLA Strip --}}
    <div class="vm-cart-drawer-trust-banner">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-warning fs-14"></i>
            <span>{{ translate('Inspect at merchant counter or doorstep before OTP release') }}</span>
        </div>
    </div>

    {{-- Dynamic Drawer Inner Body (Re-rendered via AJAX) --}}
    <div id="vmCartDrawerContent" class="vm-cart-drawer-content">
        @include('theme-views.layouts.partials._cart')
    </div>
</aside>

<script>
    (function() {
        window.openCartDrawer = function() {
            var drawer = document.getElementById('vmCartDrawer');
            var backdrop = document.getElementById('vmCartDrawerBackdrop');
            if (drawer) {
                drawer.classList.add('open', 'is-open');
                drawer.setAttribute('aria-hidden', 'false');
            }
            if (backdrop) {
                backdrop.classList.add('open', 'is-open');
                backdrop.setAttribute('aria-hidden', 'false');
            }
            document.body.classList.add('vm-cart-drawer-open');
        };

        window.closeCartDrawer = function() {
            var drawer = document.getElementById('vmCartDrawer');
            var backdrop = document.getElementById('vmCartDrawerBackdrop');
            if (drawer) {
                drawer.classList.remove('open', 'is-open');
                drawer.setAttribute('aria-hidden', 'true');
            }
            if (backdrop) {
                backdrop.classList.remove('open', 'is-open');
                backdrop.setAttribute('aria-hidden', 'true');
            }
            document.body.classList.remove('vm-cart-drawer-open');
        };

        // Wire close button & backdrop immediately
        document.addEventListener('click', function(e) {
            if (e.target.closest('#vmCartDrawerCloseBtn') || e.target.closest('.vm-cart-drawer-continue-shopping')) {
                e.preventDefault();
                window.closeCartDrawer();
            } else if (e.target && e.target.id === 'vmCartDrawerBackdrop') {
                e.preventDefault();
                window.closeCartDrawer();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.closeCartDrawer();
            }
        });
    })();
</script>

