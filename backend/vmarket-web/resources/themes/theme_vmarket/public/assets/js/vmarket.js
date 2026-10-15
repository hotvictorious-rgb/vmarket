/**
 * Victorious MARKET (VMarket) — Lightweight Vanilla Storefront JS
 * Zero external dependencies. Fast, accessible, mobile-first.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Shared Centralized Scroll-Lock Manager (Prevents conflicting body locks across modals/drawers)
    const VmScrollLock = {
        _locks: new Set(),
        lock(key) {
            this._locks.add(key);
            document.body.classList.add('vm-cart-drawer-open');
        },
        unlock(key) {
            this._locks.delete(key);
            if (this._locks.size === 0) {
                document.body.classList.remove('vm-cart-drawer-open');
            }
        }
    };
    window.VmScrollLock = VmScrollLock;

    // 1. Mobile Menu / Drawer Toggle
    const mobileMenuBtn = document.getElementById('vmMobileMenuBtn');
    const mobileDrawer = document.getElementById('vmMobileDrawer');
    const mobileDrawerClose = document.getElementById('vmDrawerClose');
    const mobileDrawerOverlay = document.getElementById('vmDrawerOverlay');

    function openDrawer() {
        if (mobileDrawer) mobileDrawer.classList.add('open');
        if (mobileDrawerOverlay) mobileDrawerOverlay.classList.add('open');
        VmScrollLock.lock('mobileDrawer');
    }

    function closeDrawer() {
        if (mobileDrawer) mobileDrawer.classList.remove('open');
        if (mobileDrawerOverlay) mobileDrawerOverlay.classList.remove('open');
        VmScrollLock.unlock('mobileDrawer');
    }

    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openDrawer);
    if (mobileDrawerClose) mobileDrawerClose.addEventListener('click', closeDrawer);
    if (mobileDrawerOverlay) mobileDrawerOverlay.addEventListener('click', closeDrawer);

    // 2. Product Detail Image Gallery Switcher
    const mainDetailImg = document.getElementById('vmMainDetailImg');
    const detailThumbs = document.querySelectorAll('.vm-detail-thumb');

    detailThumbs.forEach(thumb => {
        thumb.addEventListener('click', function() {
            detailThumbs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const targetSrc = this.getAttribute('data-full-src');
            if (mainDetailImg && targetSrc) {
                mainDetailImg.src = targetSrc;
            }
        });
    });

    // 3. Reusable Responsive Touch Slider Engine (Hero Slider & Footer Slider)
    function initVmSlider(sliderId, options = {}) {
        const container = document.getElementById(sliderId);
        if (!container) return;

        const slides = container.querySelectorAll('.vm-hero-slide, .vm-footer-slide');
        if (!slides || slides.length <= 1) return;

        const autoInterval = options.interval || 5000;
        const prevBtn = document.getElementById(options.prevBtnId);
        const nextBtn = document.getElementById(options.nextBtnId);
        const dotsContainer = document.getElementById(options.dotsId);
        const dots = dotsContainer ? dotsContainer.querySelectorAll('.vm-slider-dot') : [];

        let current = 0;
        let timer = null;

        function goToSlide(index) {
            slides[current].classList.remove('active');
            if (dots[current]) dots[current].classList.remove('active');

            current = (index + slides.length) % slides.length;

            slides[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');
        }

        function startAutoPlay() {
            if (timer) clearInterval(timer);
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }
            timer = setInterval(() => {
                goToSlide(current + 1);
            }, autoInterval);
        }

        function resetTimer() {
            startAutoPlay();
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                goToSlide(current + 1);
                resetTimer();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                goToSlide(current - 1);
                resetTimer();
            });
        }

        dots.forEach((dot, idx) => {
            dot.addEventListener('click', (e) => {
                e.preventDefault();
                goToSlide(idx);
                resetTimer();
            });
        });

        // Touch Swipe Navigation for Mobile
        let startX = 0;
        let endX = 0;

        container.addEventListener('touchstart', (e) => {
            if (e.touches && e.touches[0]) {
                startX = e.touches[0].clientX;
            }
        }, { passive: true });

        container.addEventListener('touchend', (e) => {
            if (e.changedTouches && e.changedTouches[0]) {
                endX = e.changedTouches[0].clientX;
                const diff = endX - startX;
                if (Math.abs(diff) > 40) {
                    if (diff < 0) {
                        goToSlide(current + 1);
                    } else {
                        goToSlide(current - 1);
                    }
                    resetTimer();
                }
            }
        }, { passive: true });

        // Pause on mouse hover (Desktop)
        container.addEventListener('mouseenter', () => {
            if (timer) clearInterval(timer);
        });

        container.addEventListener('mouseleave', () => {
            startAutoPlay();
        });

        // Show pagination & arrows on click / touch for 3.5s, then fade out
        const wrapper = container.closest('.vm-hero-slider-container, .vm-footer-slider-container') || container;
        let touchTimeout = null;
        function triggerActiveControls() {
            wrapper.classList.add('active-hover');
            if (touchTimeout) clearTimeout(touchTimeout);
            touchTimeout = setTimeout(() => {
                wrapper.classList.remove('active-hover');
            }, 3500);
        }

        wrapper.addEventListener('click', triggerActiveControls);
        wrapper.addEventListener('touchstart', triggerActiveControls, { passive: true });

        startAutoPlay();
    }

    // Initialize Hero Slider (5.5s auto-rotate)
    initVmSlider('vmHeroSlider', {
        prevBtnId: 'vmHeroPrev',
        nextBtnId: 'vmHeroNext',
        dotsId: 'vmHeroDots',
        interval: 5500,
    });

    // Initialize Footer Slider (6.5s auto-rotate)
    initVmSlider('vmFooterSlider', {
        prevBtnId: 'vmFooterPrev',
        nextBtnId: 'vmFooterNext',
        dotsId: 'vmFooterDots',
        interval: 6500,
    });

    // 4. Strategic Touchpoint 3: Promotional Popup Modal (Polite Bottom-Right Card)
    const popupCard = document.getElementById('vmPromoPopupCard');
    const popupCloseBtn = document.getElementById('vmPopupCloseBtn');

    if (popupCard) {
        const hasSeen = sessionStorage.getItem('vm_promo_popup_seen');
        if (!hasSeen) {
            setTimeout(() => {
                popupCard.style.display = 'block';
            }, 2500);
        }

        function dismissPopup() {
            popupCard.style.display = 'none';
            sessionStorage.setItem('vm_promo_popup_seen', 'true');
        }

        if (popupCloseBtn) {
            popupCloseBtn.addEventListener('click', dismissPopup);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && popupCard.style.display === 'block') {
                dismissPopup();
            }
        });
    }

    // 5. Quantity Increment / Decrement
    // [AI] VMarket V1: skip vanilla handler when authoritative clamped
    // cartQuantityInitialize (.btn-number) is present to avoid double-fire.
    const qtyInput = document.getElementById('vmQtyInput');
    const qtyPlus = document.getElementById('vmQtyPlus');
    const qtyMinus = document.getElementById('vmQtyMinus');

    if (qtyInput && qtyPlus && qtyMinus) {
        qtyPlus.addEventListener('click', () => {
            let current = parseInt(qtyInput.value) || 1;
            const max = parseInt(qtyInput.getAttribute('max')) || 9999;
            if (current < max) {
                qtyInput.value = current + 1;
            }
        });

        qtyMinus.addEventListener('click', () => {
            let current = parseInt(qtyInput.value) || 1;
            const min = parseInt(qtyInput.getAttribute('min')) || 1;
            if (current > min) {
                qtyInput.value = current - 1;
            }
        });
    }

    // 6. Omnichannel Location & Fulfillment Modal Engine (Amazon-Inspired)
    const locModal = document.getElementById('vmLocationModalBackdrop');
    const locCard = document.getElementById('vmLocationModalCard');
    const locTriggers = [
        document.getElementById('vmHeaderLocationBtn'),
        document.getElementById('vmMobileLocationBtn'),
        document.getElementById('vmProximityTrigger')
    ].filter(Boolean);
    const locCloseBtn = document.getElementById('vmLocationModalClose');
    const locCancelBtn = document.getElementById('vmLocationModalCancel');

    function openLocationModal() {
        if (locModal) {
            locModal.style.display = 'flex';
            if (window.VmScrollLock) {
                window.VmScrollLock.lock('locationModal');
            } else {
                document.body.style.overflow = 'hidden';
            }
        }
    }

    function closeLocationModal() {
        if (locModal) {
            locModal.style.display = 'none';
            if (window.VmScrollLock) {
                window.VmScrollLock.unlock('locationModal');
            } else {
                document.body.style.overflow = '';
            }
        }
    }

    locTriggers.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openLocationModal();
        });
    });

    if (locCloseBtn) locCloseBtn.addEventListener('click', closeLocationModal);
    if (locCancelBtn) locCancelBtn.addEventListener('click', closeLocationModal);

    if (locModal) {
        locModal.addEventListener('click', (e) => {
            if (e.target === locModal) {
                closeLocationModal();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && locModal && locModal.style.display === 'flex') {
            closeLocationModal();
        }
    });

    // LGA Dataset & Autocomplete Engine
    let lgaDataset = [];
    const lgaDataScript = document.getElementById('vmLgaData');
    if (lgaDataScript) {
        try {
            lgaDataset = JSON.parse(lgaDataScript.textContent) || [];
        } catch (e) {
            console.error('Error parsing LGA dataset:', e);
        }
    }

    const lgaSearchInput = document.getElementById('vmLgaSearchInput');
    const lgaDropdownList = document.getElementById('vmLgaDropdownList');
    const lgaClearBtn = document.getElementById('vmLgaClearBtn');
    const hiddenCity = document.getElementById('vmHiddenCity');
    const hiddenState = document.getElementById('vmHiddenState');
    const hiddenLgaId = document.getElementById('vmHiddenLgaId');
    const hiddenLgaName = document.getElementById('vmHiddenLgaName');
    const hiddenStateId = document.getElementById('vmHiddenStateId');
    const previewCity = document.getElementById('vmPreviewCity');
    const cityChips = document.querySelectorAll('.vm-city-chip');

    function selectLgaItem(id, name, stateName, stateId) {
        if (hiddenLgaId) hiddenLgaId.value = id || '';
        if (hiddenLgaName) hiddenLgaName.value = name || '';
        if (hiddenCity) hiddenCity.value = name || '';
        if (hiddenState) hiddenState.value = stateName || '';
        if (hiddenStateId) hiddenStateId.value = stateId || '';
        if (lgaSearchInput) lgaSearchInput.value = name || '';
        if (lgaClearBtn) lgaClearBtn.style.display = name ? 'block' : 'none';
        if (previewCity) previewCity.textContent = `${name}, ${stateName || 'Nigeria'}`;

        // Sync chips
        cityChips.forEach(c => {
            if (c.getAttribute('data-lga-id') == id || c.getAttribute('data-city') == name) {
                c.classList.add('active');
            } else {
                c.classList.remove('active');
            }
        });

        // Hide dropdown
        if (lgaDropdownList) lgaDropdownList.style.display = 'none';
    }

    if (lgaSearchInput && lgaDropdownList) {
        lgaSearchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim().toLowerCase();
            if (lgaClearBtn) lgaClearBtn.style.display = query ? 'block' : 'none';

            if (!query || query.length < 1) {
                lgaDropdownList.style.display = 'none';
                return;
            }

            // Filter LGAs matching query
            const matches = lgaDataset.filter(item => {
                const nameMatch = item.name.toLowerCase().includes(query);
                const stateMatch = item.state_name && item.state_name.toLowerCase().includes(query);
                return nameMatch || stateMatch;
            }).slice(0, 15);

            if (matches.length === 0) {
                lgaDropdownList.innerHTML = '';
                const emptyDiv = document.createElement('div');
                emptyDiv.style.cssText = 'padding: 12px; text-align: center; color: #64748B; font-size: 12.5px;';
                emptyDiv.textContent = 'No matching LGA found for "' + e.target.value.trim() + '"';
                lgaDropdownList.appendChild(emptyDiv);
                lgaDropdownList.style.display = 'block';
                return;
            }

            lgaDropdownList.innerHTML = '';
            const fragment = document.createDocumentFragment();
            matches.forEach(item => {
                const itemEl = document.createElement('div');
                itemEl.className = 'vm-lga-dropdown-item';
                itemEl.setAttribute('data-id', item.id);
                itemEl.setAttribute('data-name', item.name);
                itemEl.setAttribute('data-state', item.state_name || '');
                itemEl.setAttribute('data-state-id', item.state_id || '');

                const infoDiv = document.createElement('div');
                const strong = document.createElement('strong');
                strong.textContent = item.name;
                const stateSpan = document.createElement('span');
                stateSpan.className = 'vm-lga-dropdown-state';
                stateSpan.textContent = ' • ' + (item.state_name || 'Nigeria');
                infoDiv.appendChild(strong);
                infoDiv.appendChild(stateSpan);

                const selectBadge = document.createElement('span');
                selectBadge.className = 'vm-lga-coverage-badge';
                selectBadge.textContent = 'Select';

                itemEl.appendChild(infoDiv);
                itemEl.appendChild(selectBadge);
                fragment.appendChild(itemEl);
            });
            lgaDropdownList.appendChild(fragment);
            lgaDropdownList.style.display = 'block';
        });

        // Event delegation for LGA dropdown clicks (attached once, not on every keystroke)
        lgaDropdownList.addEventListener('click', (e) => {
            const itemEl = e.target.closest('.vm-lga-dropdown-item');
            if (!itemEl) return;
            const id = itemEl.getAttribute('data-id');
            const name = itemEl.getAttribute('data-name');
            const state = itemEl.getAttribute('data-state');
            const stateId = itemEl.getAttribute('data-state-id');
            selectLgaItem(id, name, state, stateId);
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!lgaSearchInput.contains(e.target) && !lgaDropdownList.contains(e.target)) {
                lgaDropdownList.style.display = 'none';
            }
        });
    }

    if (lgaClearBtn) {
        lgaClearBtn.addEventListener('click', () => {
            if (lgaSearchInput) {
                lgaSearchInput.value = '';
                lgaSearchInput.focus();
            }
            if (lgaClearBtn) lgaClearBtn.style.display = 'none';
            if (lgaDropdownList) lgaDropdownList.style.display = 'none';
        });
    }

    // Quick Chips click handling
    cityChips.forEach(chip => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            const id = chip.getAttribute('data-lga-id');
            const city = chip.getAttribute('data-city');
            const state = chip.getAttribute('data-state');
            selectLgaItem(id, city, state, '');
        });
    });

    // AJAX Submission on "Done"
    const locForm = document.getElementById('vmLocationForm');
    if (locForm) {
        locForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('vmLocationModalSubmit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Saving...</span>';
            }

            const formData = new FormData(locForm);

            fetch(locForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update all active city labels in DOM immediately
                    const cityLabels = document.querySelectorAll('.vm-active-city-label');
                    cityLabels.forEach(el => el.textContent = data.city);

                    closeLocationModal();
                    // Smooth page reload so proximity recommendations, nearby shops, and all products filter
                    window.location.reload();
                } else {
                    alert(data.message || 'Unable to update location');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<span>Done</span>';
                    }
                }
            })
            .catch(err => {
                console.error('Location update error:', err);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Done</span>';
                }
                alert('Network connection error. Please try again.');
            });
        });
    }

    // 7. Smooth Auth Modal Switching (Login <-> Register)
    document.querySelectorAll('[data-bs-target="#registerModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const loginEl = document.getElementById('loginModal');
            if (loginEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(loginEl);
                if (modalInstance) modalInstance.hide();
            }
        });
    });
    document.querySelectorAll('[data-bs-target="#loginModal"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const regEl = document.getElementById('registerModal');
            if (regEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(regEl);
                if (modalInstance) modalInstance.hide();
            }
        });
    });

    // ==========================================================================
    // 8. [AI] Victorious MARKET Fly-Out Cart Drawer Controller
    // ==========================================================================
    // Serialized per-item queue state machine ensuring zero race conditions
    const _cartSyncQueues = {};

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
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }

    function recalculateDrawerTotals() {
        let subtotal = 0;
        let totalItems = 0;
        const rows = document.querySelectorAll('.vm-drawer-item-row');
        rows.forEach(row => {
            const qtyEl = row.querySelector('.vm-drawer-qty-val');
            const qty = qtyEl ? (parseInt(qtyEl.textContent.trim(), 10) || 0) : 0;
            const unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
            subtotal += (qty * unitPrice);
            totalItems += qty;
        });

        const subtotalEl = document.getElementById('vmDrawerSubtotal');
        if (subtotalEl) subtotalEl.textContent = formatNaira(subtotal);

        const totalEl = document.getElementById('vmDrawerTotalPayable');
        if (totalEl) totalEl.textContent = formatNaira(subtotal);

        const countEls = document.querySelectorAll('.vm-drawer-cart-count, #vmHeaderCartBadge, #vmMobileNavCartBadge, .vm-action-badge');
        countEls.forEach(el => {
            el.textContent = totalItems;
            if (totalItems > 0) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    function processSyncQueue(cartId) {
        const queue = getSyncQueue(cartId);
        if (queue.isRemoving) return;
        if (queue.inFlight) return;
        if (queue.desiredQty === null || queue.desiredQty === queue.confirmedQty) return;

        queue.inFlight = true;
        const targetQty = queue.desiredQty;

        const isAuth = document.getElementById('authentication-status') ? document.getElementById('authentication-status').getAttribute('data-auth') === 'true' : false;
        const updateUrlEl = isAuth ? document.getElementById('update-quantity-basic-url') : document.getElementById('update_quantity_url');
        const updateUrl = (updateUrlEl && updateUrlEl.getAttribute('data-url')) || (document.getElementById('update-quantity-basic-url') && document.getElementById('update-quantity-basic-url').getAttribute('data-url')) || '/cart/updateQuantity';
        const token = (document.querySelector('meta[name="_token"]') && document.querySelector('meta[name="_token"]').getAttribute('content')) || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

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
                    const safeQty = response.qty || queue.confirmedQty || 1;
                    queue.desiredQty = safeQty;
                    queue.confirmedQty = safeQty;

                    const qtyDisplay = document.getElementById('drawer-qty-val-' + cartId);
                    if (qtyDisplay) qtyDisplay.textContent = safeQty;

                    const row = document.getElementById('drawer-cart-item-' + cartId);
                    if (row) {
                        const unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
                        const priceDisplay = document.getElementById('drawer-item-price-' + cartId);
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

                const rollbackQty = queue.confirmedQty || 1;
                queue.desiredQty = rollbackQty;

                const qtyDisplay = document.getElementById('drawer-qty-val-' + cartId);
                if (qtyDisplay) qtyDisplay.textContent = rollbackQty;

                const row = document.getElementById('drawer-cart-item-' + cartId);
                if (row) {
                    const unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
                    const priceDisplay = document.getElementById('drawer-item-price-' + cartId);
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
        const row = document.getElementById('drawer-cart-item-' + cartId);
        if (!row) return;

        const queue = getSyncQueue(cartId);
        if (queue.isRemoving) return;

        const qtyDisplay = document.getElementById('drawer-qty-val-' + cartId);
        if (!qtyDisplay) return;

        const currentQty = parseInt(qtyDisplay.textContent.trim(), 10) || 1;
        if (queue.confirmedQty === null) {
            queue.confirmedQty = currentQty;
        }

        const minOrder = parseInt(row.getAttribute('data-min-order'), 10) || 1;
        const maxStockAttr = row.getAttribute('data-max-stock');
        const maxStock = (maxStockAttr !== null && maxStockAttr !== '' && !isNaN(maxStockAttr)) ? parseInt(maxStockAttr, 10) : 0;

        if (maxStock <= 0) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('This item is currently out of stock');
            }
            return;
        }

        const targetQty = currentQty + delta;

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

        const minusBtn = row.querySelector('.vm-drawer-step-minus');
        if (minusBtn) {
            if (targetQty <= minOrder) {
                minusBtn.innerHTML = '<i class="bi bi-trash3 text-danger fs-11"></i>';
                minusBtn.setAttribute('title', 'Remove');
            } else {
                minusBtn.innerHTML = '<i class="bi bi-dash"></i>';
                minusBtn.setAttribute('title', 'Decrease');
            }
        }

        const plusBtn = row.querySelector('.vm-drawer-step-plus');
        if (plusBtn) {
            plusBtn.disabled = (targetQty >= maxStock);
        }

        const unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
        const originalPrice = parseFloat(row.getAttribute('data-original-price')) || unitPrice;
        const priceDisplay = document.getElementById('drawer-item-price-' + cartId);
        if (priceDisplay) {
            priceDisplay.textContent = formatNaira(targetQty * unitPrice);
        }
        const oldPriceDisplay = document.getElementById('drawer-item-old-price-' + cartId);
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
        const row = document.getElementById('drawer-cart-item-' + cartId);
        const qtyDisplay = row ? row.querySelector('.vm-drawer-qty-val') : null;
        const currentQty = qtyDisplay ? parseInt(qtyDisplay.textContent.trim(), 10) : 1;
        window.stepDrawerQuantity(cartId, targetQty - currentQty);
    };

    window.removeDrawerCartItem = function(cartId) {
        const queue = getSyncQueue(cartId);
        queue.isRemoving = true;
        if (queue.timer) {
            clearTimeout(queue.timer);
            queue.timer = null;
        }
        queue.desiredQty = null;

        const row = document.getElementById('drawer-cart-item-' + cartId);
        if (row) {
            row.style.transition = 'all 0.25s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(20px)';
            row.style.maxHeight = '0';
            row.style.padding = '0';
            row.style.margin = '0';
            row.style.overflow = 'hidden';
        }

        const removeUrlEl = document.getElementById('remove_from_cart_url');
        const removeUrl = (removeUrlEl && removeUrlEl.getAttribute('data-url')) || '/cart/remove';
        const token = (document.querySelector('meta[name="_token"]') && document.querySelector('meta[name="_token"]').getAttribute('content')) || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

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

    let _focusTimer = null;
    window.openCartDrawer = function(triggerElement) {
        const drawer = document.getElementById('vmCartDrawer');
        const backdrop = document.getElementById('vmCartDrawerBackdrop');
        if (!drawer) return;

        window._vmLastFocusedElement = triggerElement || document.activeElement;

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

        const closeBtn = document.getElementById('vmCartDrawerCloseBtn');
        if (closeBtn) {
            if (_focusTimer) clearTimeout(_focusTimer);
            _focusTimer = setTimeout(() => {
                if (drawer.classList.contains('open') || drawer.classList.contains('is-open')) {
                    closeBtn.focus();
                }
            }, 60);
        }
    };

    window.closeCartDrawer = function() {
        const drawer = document.getElementById('vmCartDrawer');
        const backdrop = document.getElementById('vmCartDrawerBackdrop');
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

        if (window._vmLastFocusedElement && typeof window._vmLastFocusedElement.focus === 'function' && document.body.contains(window._vmLastFocusedElement)) {
            window._vmLastFocusedElement.focus();
        }
    };

    // Event delegation for cart drawer interactions
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.vm-cart-drawer-trigger, #vmHeaderCartBtn, #vmMobileNavCartBtn');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            window.openCartDrawer(trigger);
            return;
        }

        if (e.target.closest('#vmCartDrawerCloseBtn') || e.target.closest('.vm-cart-drawer-continue-shopping')) {
            e.preventDefault();
            window.closeCartDrawer();
            return;
        }

        if (e.target && e.target.id === 'vmCartDrawerBackdrop') {
            e.preventDefault();
            window.closeCartDrawer();
            return;
        }
    });

    // ESC key closes cart drawer
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const drawer = document.getElementById('vmCartDrawer');
            if (drawer && (drawer.classList.contains('open') || drawer.classList.contains('is-open'))) {
                window.closeCartDrawer();
            }
        }
    });

    // Smooth wheel event delegation to scroll drawer content from anywhere inside the drawer
    const drawerEl = document.getElementById('vmCartDrawer');
    if (drawerEl) {
        drawerEl.addEventListener('wheel', function(e) {
            const content = document.getElementById('vmCartDrawerContent');
            if (content && !content.contains(e.target)) {
                content.scrollTop += e.deltaY;
            }
        }, { passive: true });
    }
});


