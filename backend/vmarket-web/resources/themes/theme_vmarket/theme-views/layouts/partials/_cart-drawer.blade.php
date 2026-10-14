@php
    $cartCount = \App\Utils\CartManager::get_cart_count();
@endphp

{{-- [AI] Victorious MARKET Fly-Out Cart Drawer --}}
<div id="vmCartDrawerBackdrop" class="vm-cart-drawer-backdrop" aria-hidden="true"></div>

<aside id="vmCartDrawer" class="vm-cart-drawer" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="vmCartDrawerTitle">
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
        if (window._vmCartDrawerInitialized) return;
        window._vmCartDrawerInitialized = true;

        window._vmLastFocusedElement = null;

        window.openCartDrawer = function(triggerElement) {
            var drawer = document.getElementById('vmCartDrawer');
            var backdrop = document.getElementById('vmCartDrawerBackdrop');
            if (!drawer) return;

            window._vmLastFocusedElement = triggerElement || document.activeElement;

            drawer.classList.add('open', 'is-open');
            drawer.setAttribute('aria-hidden', 'false');

            if (backdrop) {
                backdrop.classList.add('open', 'is-open');
                backdrop.setAttribute('aria-hidden', 'false');
            }
            document.body.classList.add('vm-cart-drawer-open');

            // Accessibility: Focus into close button
            var closeBtn = document.getElementById('vmCartDrawerCloseBtn');
            if (closeBtn) {
                setTimeout(function() { closeBtn.focus(); }, 60);
            }
        };

        window.closeCartDrawer = function() {
            var drawer = document.getElementById('vmCartDrawer');
            var backdrop = document.getElementById('vmCartDrawerBackdrop');
            if (!drawer) return;

            drawer.classList.remove('open', 'is-open');
            drawer.setAttribute('aria-hidden', 'true');

            if (backdrop) {
                backdrop.classList.remove('open', 'is-open');
                backdrop.setAttribute('aria-hidden', 'true');
            }
            document.body.classList.remove('vm-cart-drawer-open');

            // Accessibility: Restore focus to opener
            if (window._vmLastFocusedElement && typeof window._vmLastFocusedElement.focus === 'function') {
                window._vmLastFocusedElement.focus();
            }
        };

        // Delegated click handling for triggers, close button, and backdrop
        document.addEventListener('click', function(e) {
            var trigger = e.target.closest('.vm-cart-drawer-trigger, #vmHeaderCartBtn, #vmMobileNavCartBtn');
            if (trigger) {
                e.preventDefault();
                window.openCartDrawer(trigger);
                return;
            }

            if (e.target.closest('#vmCartDrawerCloseBtn') || e.target.closest('.vm-cart-drawer-continue-shopping')) {
                e.preventDefault();
                window.closeCartDrawer();
            } else if (e.target && e.target.id === 'vmCartDrawerBackdrop') {
                e.preventDefault();
                window.closeCartDrawer();
            }
        });

        // Keyboard accessibility: Escape to close and Tab containment
        document.addEventListener('keydown', function(e) {
            var drawer = document.getElementById('vmCartDrawer');
            if (!drawer || (!drawer.classList.contains('is-open') && !drawer.classList.contains('open'))) return;

            if (e.key === 'Escape') {
                window.closeCartDrawer();
                return;
            }

            if (e.key === 'Tab') {
                var focusables = drawer.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
                if (focusables.length === 0) return;
                var first = focusables[0];
                var last = focusables[focusables.length - 1];

                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });

        // Instant Optimistic Stepper Quantity & Item Removal Controllers
        window.updateDrawerQuantity = function(cartId, targetQty) {
            if (targetQty < 1) {
                window.removeDrawerCartItem(cartId);
                return;
            }

            var row = document.getElementById('drawer-cart-item-' + cartId);
            var qtyDisplay = row ? row.querySelector('.vm-drawer-qty-val') : null;
            var prevQty = qtyDisplay ? parseInt(qtyDisplay.textContent.trim(), 10) : 1;

            // Instant Optimistic Tactile Feedback (0ms)
            if (qtyDisplay) {
                qtyDisplay.textContent = targetQty;
            }
            if (row) {
                row.style.opacity = '0.65';
                row.style.pointerEvents = 'none';
            }

            var isAuth = document.getElementById('authentication-status') ? document.getElementById('authentication-status').getAttribute('data-auth') === 'true' : false;
            var updateUrlEl = isAuth ? document.getElementById('update-quantity-basic-url') : document.getElementById('update_quantity_url');
            var updateUrl = (updateUrlEl && updateUrlEl.getAttribute('data-url')) || (document.getElementById('update-quantity-basic-url') && document.getElementById('update-quantity-basic-url').getAttribute('data-url')) || '/cart/updateQuantity';
            var token = (document.querySelector('meta[name="_token"]') && document.querySelector('meta[name="_token"]').getAttribute('content')) || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            $.ajax({
                url: updateUrl,
                type: 'POST',
                data: {
                    _token: token,
                    key: cartId,
                    quantity: targetQty
                },
                success: function(response) {
                    if (row) {
                        row.style.opacity = '1';
                        row.style.pointerEvents = 'auto';
                    }
                    if (response && response.status === 0) {
                        if (qtyDisplay) qtyDisplay.textContent = prevQty;
                        if (typeof toastr !== 'undefined' && response.message) {
                            toastr.warning(response.message);
                        }
                    } else {
                        if (typeof updateNavCart === 'function') {
                            updateNavCart();
                        }
                    }
                },
                error: function(xhr) {
                    if (row) {
                        row.style.opacity = '1';
                        row.style.pointerEvents = 'auto';
                    }
                    if (qtyDisplay) qtyDisplay.textContent = prevQty;
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to update quantity';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    }
                }
            });
        };

        window.removeDrawerCartItem = function(cartId) {
            var row = document.getElementById('drawer-cart-item-' + cartId);
            if (row) {
                row.classList.add('is-removing');
            }

            var removeUrlEl = document.getElementById('remove_from_cart_url');
            var removeUrl = (removeUrlEl && removeUrlEl.getAttribute('data-url')) || '/cart/remove';
            var token = (document.querySelector('meta[name="_token"]') && document.querySelector('meta[name="_token"]').getAttribute('content')) || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            $.ajax({
                url: removeUrl,
                type: 'POST',
                data: {
                    _token: token,
                    key: cartId
                },
                success: function(response) {
                    if (typeof updateNavCart === 'function') {
                        updateNavCart();
                    }
                    if (response && response.message && typeof toastr !== 'undefined') {
                        toastr.info(response.message);
                    }
                },
                error: function(xhr) {
                    if (row) row.classList.remove('is-removing');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to remove item';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    }
                }
            });
        };
    })();
</script>

