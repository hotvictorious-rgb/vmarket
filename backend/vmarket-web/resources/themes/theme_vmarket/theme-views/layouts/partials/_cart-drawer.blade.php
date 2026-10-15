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

        var _vmLastFocusedElement = null;
        var _focusTimer = null;

        window.openCartDrawer = function(triggerElement) {
            var drawer = document.getElementById('vmCartDrawer');
            var backdrop = document.getElementById('vmCartDrawerBackdrop');
            if (!drawer) return;

            _vmLastFocusedElement = triggerElement || document.activeElement;

            drawer.classList.add('open', 'is-open');
            drawer.setAttribute('aria-hidden', 'false');

            if (backdrop) {
                backdrop.classList.add('open', 'is-open');
                backdrop.setAttribute('aria-hidden', 'false');
            }

            if (window.VmScrollLock) {
                window.VmScrollLock.lock('cartDrawer');
            } else {
                document.body.classList.add('vm-cart-drawer-open');
            }

            var closeBtn = document.getElementById('vmCartDrawerCloseBtn');
            if (closeBtn) {
                if (_focusTimer) clearTimeout(_focusTimer);
                _focusTimer = setTimeout(function() {
                    if (drawer.classList.contains('open') || drawer.classList.contains('is-open')) {
                        closeBtn.focus();
                    }
                }, 60);
            }
        };

        window.closeCartDrawer = function() {
            var drawer = document.getElementById('vmCartDrawer');
            var backdrop = document.getElementById('vmCartDrawerBackdrop');
            if (!drawer) return;

            if (_focusTimer) {
                clearTimeout(_focusTimer);
                _focusTimer = null;
            }

            drawer.classList.remove('open', 'is-open');
            drawer.setAttribute('aria-hidden', 'true');

            if (backdrop) {
                backdrop.classList.remove('open', 'is-open');
                backdrop.setAttribute('aria-hidden', 'true');
            }

            if (window.VmScrollLock) {
                window.VmScrollLock.unlock('cartDrawer');
            } else {
                document.body.classList.remove('vm-cart-drawer-open');
            }

            if (_vmLastFocusedElement && typeof _vmLastFocusedElement.focus === 'function' && document.body.contains(_vmLastFocusedElement)) {
                _vmLastFocusedElement.focus();
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

        // =========================================================================
        // High-Performance Serialized Cart State Machine & Optimistic UI Syncer
        // =========================================================================
        var _cartSyncQueues = {};

        function getSyncQueue(cartId) {
            if (!_cartSyncQueues[cartId]) {
                _cartSyncQueues[cartId] = {
                    inFlight: false,
                    desiredQty: null,
                    confirmedQty: null,
                    timer: null,
                    isRemoving: false
                };
            }
            return _cartSyncQueues[cartId];
        }

        function formatNaira(amount) {
            return '₦' + Number(amount || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function escapeText(str) {
            if (!str) return '';
            var p = document.createElement('p');
            p.textContent = str;
            return p.innerHTML;
        }

        function recalculateDrawerTotals() {
            var subtotal = 0;
            var totalItems = 0;
            var rows = document.querySelectorAll('.vm-drawer-item-row');
            rows.forEach(function(row) {
                var qtyEl = row.querySelector('.vm-drawer-qty-val');
                var qty = qtyEl ? (parseInt(qtyEl.textContent.trim(), 10) || 0) : 0;
                var unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
                subtotal += (qty * unitPrice);
                totalItems += qty;
            });

            var subtotalEl = document.getElementById('vmDrawerSubtotal');
            if (subtotalEl) subtotalEl.textContent = formatNaira(subtotal);

            var totalEl = document.getElementById('vmDrawerTotalPayable');
            if (totalEl) totalEl.textContent = formatNaira(subtotal);

            var countEls = document.querySelectorAll('.vm-drawer-cart-count, #vmHeaderCartBadge, #vmMobileNavCartBadge, .vm-action-badge');
            countEls.forEach(function(el) {
                el.textContent = totalItems;
                if (totalItems > 0) {
                    el.style.display = '';
                } else {
                    el.style.display = 'none';
                }
            });
        }

        function processSyncQueue(cartId) {
            var queue = getSyncQueue(cartId);
            if (queue.isRemoving) return;
            if (queue.inFlight) return;
            if (queue.desiredQty === null || queue.desiredQty === queue.confirmedQty) return;

            queue.inFlight = true;
            var targetQty = queue.desiredQty;

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
                    queue.inFlight = false;
                    if (queue.isRemoving) return;

                    if (response && response.status === 0) {
                        var safeQty = response.qty || queue.confirmedQty || 1;
                        queue.desiredQty = safeQty;
                        queue.confirmedQty = safeQty;

                        var qtyDisplay = document.getElementById('drawer-qty-val-' + cartId);
                        if (qtyDisplay) qtyDisplay.textContent = safeQty;

                        var row = document.getElementById('drawer-cart-item-' + cartId);
                        if (row) {
                            var unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
                            var priceDisplay = document.getElementById('drawer-item-price-' + cartId);
                            if (priceDisplay) priceDisplay.textContent = formatNaira(safeQty * unitPrice);
                        }

                        recalculateDrawerTotals();
                        if (typeof toastr !== 'undefined') {
                            toastr.warning(escapeText(response.message || 'Requested quantity unavailable'));
                        }
                    } else {
                        queue.confirmedQty = targetQty;

                        if (queue.desiredQty !== null && queue.desiredQty !== queue.confirmedQty) {
                            processSyncQueue(cartId);
                        } else {
                            if (typeof updateNavCart === 'function') {
                                updateNavCart();
                            }
                        }
                    }
                },
                error: function(xhr) {
                    queue.inFlight = false;
                    if (queue.isRemoving) return;

                    var rollbackQty = queue.confirmedQty || 1;
                    queue.desiredQty = rollbackQty;

                    var qtyDisplay = document.getElementById('drawer-qty-val-' + cartId);
                    if (qtyDisplay) qtyDisplay.textContent = rollbackQty;

                    var row = document.getElementById('drawer-cart-item-' + cartId);
                    if (row) {
                        var unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
                        var priceDisplay = document.getElementById('drawer-item-price-' + cartId);
                        if (priceDisplay) priceDisplay.textContent = formatNaira(rollbackQty * unitPrice);
                    }

                    recalculateDrawerTotals();
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Unable to update quantity. Please check your connection.');
                    }
                }
            });
        }

        window.stepDrawerQuantity = function(cartId, delta) {
            var row = document.getElementById('drawer-cart-item-' + cartId);
            if (!row) return;

            var queue = getSyncQueue(cartId);
            if (queue.isRemoving) return;

            var qtyDisplay = document.getElementById('drawer-qty-val-' + cartId);
            if (!qtyDisplay) return;

            var currentQty = parseInt(qtyDisplay.textContent.trim(), 10) || 1;
            if (queue.confirmedQty === null) {
                queue.confirmedQty = currentQty;
            }

            var minOrder = parseInt(row.getAttribute('data-min-order'), 10) || 1;
            var maxStockAttr = row.getAttribute('data-max-stock');
            var maxStock = (maxStockAttr !== null && maxStockAttr !== '' && !isNaN(maxStockAttr)) ? parseInt(maxStockAttr, 10) : 0;

            if (maxStock <= 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('This item is currently out of stock');
                }
                return;
            }

            var targetQty = currentQty + delta;

            if (targetQty < minOrder) {
                window.removeDrawerCartItem(cartId);
                return;
            }

            if (targetQty > maxStock) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Maximum available stock reached (' + maxStock + ')');
                }
                return;
            }

            // 1. Instant 0ms Optimistic UI updates
            qtyDisplay.textContent = targetQty;
            queue.desiredQty = targetQty;

            var minusBtn = row.querySelector('.vm-drawer-step-minus');
            if (minusBtn) {
                if (targetQty <= minOrder) {
                    minusBtn.innerHTML = '<i class="bi bi-trash3 text-danger fs-11"></i>';
                    minusBtn.setAttribute('title', 'Remove');
                } else {
                    minusBtn.innerHTML = '<i class="bi bi-dash"></i>';
                    minusBtn.setAttribute('title', 'Decrease');
                }
            }

            var plusBtn = row.querySelector('.vm-drawer-step-plus');
            if (plusBtn) {
                plusBtn.disabled = (targetQty >= maxStock);
            }

            var unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
            var originalPrice = parseFloat(row.getAttribute('data-original-price')) || unitPrice;
            var priceDisplay = document.getElementById('drawer-item-price-' + cartId);
            if (priceDisplay) {
                priceDisplay.textContent = formatNaira(targetQty * unitPrice);
            }
            var oldPriceDisplay = document.getElementById('drawer-item-old-price-' + cartId);
            if (oldPriceDisplay) {
                oldPriceDisplay.textContent = formatNaira(targetQty * originalPrice);
            }

            recalculateDrawerTotals();

            // 2. Debounce and serialize server synchronization (250ms)
            if (queue.timer) clearTimeout(queue.timer);
            queue.timer = setTimeout(function() {
                processSyncQueue(cartId);
            }, 250);
        };

        window.updateDrawerQuantity = function(cartId, targetQty) {
            var row = document.getElementById('drawer-cart-item-' + cartId);
            var qtyDisplay = row ? row.querySelector('.vm-drawer-qty-val') : null;
            var currentQty = qtyDisplay ? parseInt(qtyDisplay.textContent.trim(), 10) : 1;
            window.stepDrawerQuantity(cartId, targetQty - currentQty);
        };

        window.removeDrawerCartItem = function(cartId) {
            var queue = getSyncQueue(cartId);
            queue.isRemoving = true;
            if (queue.timer) {
                clearTimeout(queue.timer);
                queue.timer = null;
            }
            queue.desiredQty = null;

            var row = document.getElementById('drawer-cart-item-' + cartId);
            if (row) {
                row.style.transition = 'all 0.25s ease';
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                row.style.maxHeight = '0';
                row.style.padding = '0';
                row.style.margin = '0';
                row.style.overflow = 'hidden';
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
                    if (row) row.remove();
                    delete _cartSyncQueues[cartId];
                    recalculateDrawerTotals();
                    if (typeof updateNavCart === 'function') {
                        updateNavCart();
                    }
                    if (response && response.message && typeof toastr !== 'undefined') {
                        toastr.info(escapeText(response.message));
                    }
                },
                error: function(xhr) {
                    queue.isRemoving = false;
                    if (row) {
                        row.style.opacity = '1';
                        row.style.transform = 'none';
                        row.style.maxHeight = '';
                        row.style.padding = '';
                        row.style.margin = '';
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Unable to remove item. Please check your connection.');
                    }
                }
            });
        };

        // Smooth wheel event delegation to scroll drawer content from anywhere inside the drawer
        var cartDrawer = document.getElementById('vmCartDrawer');
        if (cartDrawer) {
            cartDrawer.addEventListener('wheel', function(e) {
                var content = document.getElementById('vmCartDrawerContent');
                if (content && !content.contains(e.target)) {
                    content.scrollTop += e.deltaY;
                }
            }, { passive: true });
        }
    })();
</script>

