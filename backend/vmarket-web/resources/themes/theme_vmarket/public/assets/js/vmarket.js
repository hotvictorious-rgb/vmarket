/**
 * Victorious MARKET (VMarket) — Lightweight Vanilla Storefront JS
 * Zero external dependencies. Fast, accessible, mobile-first.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu / Drawer Toggle
    const mobileMenuBtn = document.getElementById('vmMobileMenuBtn');
    const mobileDrawer = document.getElementById('vmMobileDrawer');
    const mobileDrawerClose = document.getElementById('vmDrawerClose');
    const mobileDrawerOverlay = document.getElementById('vmDrawerOverlay');

    function openDrawer() {
        if (mobileDrawer) mobileDrawer.classList.add('open');
        if (mobileDrawerOverlay) mobileDrawerOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (mobileDrawer) mobileDrawer.classList.remove('open');
        if (mobileDrawerOverlay) mobileDrawerOverlay.classList.remove('open');
        document.body.style.overflow = '';
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
    const qtyInput = document.getElementById('vmQtyInput');
    const qtyPlus = document.getElementById('vmQtyPlus');
    const qtyMinus = document.getElementById('vmQtyMinus');

    if (qtyInput && qtyPlus && qtyMinus) {
        qtyPlus.addEventListener('click', () => {
            let current = parseInt(qtyInput.value) || 1;
            qtyInput.value = current + 1;
        });

        qtyMinus.addEventListener('click', () => {
            let current = parseInt(qtyInput.value) || 1;
            if (current > 1) {
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
            document.body.style.overflow = 'hidden';
        }
    }

    function closeLocationModal() {
        if (locModal) {
            locModal.style.display = 'none';
            document.body.style.overflow = '';
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

    // Fulfillment Mode Switcher (Doorstep Delivery vs In-Shop Pickup)
    const optDelivery = document.getElementById('vmOptDelivery');
    const optPickup = document.getElementById('vmOptPickup');
    const deliverySection = document.getElementById('vmDeliverySection');
    const pickupSection = document.getElementById('vmPickupSection');

    function switchFulfillmentMode(mode) {
        if (mode === 'delivery') {
            if (optDelivery) optDelivery.classList.add('active');
            if (optPickup) optPickup.classList.remove('active');
            if (deliverySection) deliverySection.style.display = 'block';
            if (pickupSection) pickupSection.style.display = 'none';
            const radio = optDelivery ? optDelivery.querySelector('input') : null;
            if (radio) radio.checked = true;
        } else {
            if (optPickup) optPickup.classList.add('active');
            if (optDelivery) optDelivery.classList.remove('active');
            if (pickupSection) pickupSection.style.display = 'block';
            if (deliverySection) deliverySection.style.display = 'none';
            const radio = optPickup ? optPickup.querySelector('input') : null;
            if (radio) radio.checked = true;
        }
    }

    if (optDelivery) {
        optDelivery.addEventListener('click', () => switchFulfillmentMode('delivery'));
    }
    if (optPickup) {
        optPickup.addEventListener('click', () => switchFulfillmentMode('pickup'));
    }

    // LGA Chips & Select Synchronization
    const cityChips = document.querySelectorAll('.vm-city-chip');
    const selectLga = document.getElementById('vmSelectLga');
    const selectState = document.getElementById('vmSelectState');
    const hiddenCity = document.getElementById('vmHiddenCity');
    const hiddenState = document.getElementById('vmHiddenState');
    const hiddenLgaId = document.getElementById('vmHiddenLgaId');
    const hiddenStateId = document.getElementById('vmHiddenStateId');
    const laneAlertText = document.getElementById('vmDeliveryLaneText');

    function updateActiveLocationDisplay(lgaId, cityName, stateName, fee, time) {
        if (hiddenLgaId) hiddenLgaId.value = lgaId || '';
        if (hiddenCity) hiddenCity.value = cityName || '';
        if (hiddenState) hiddenState.value = stateName || '';

        // Sync dropdown
        if (selectLga && lgaId) {
            selectLga.value = lgaId;
        }

        // Sync chips active state
        cityChips.forEach(c => {
            if (c.getAttribute('data-lga-id') == lgaId || c.getAttribute('data-city') == cityName) {
                c.classList.add('active');
            } else {
                c.classList.remove('active');
            }
        });

        // Update live preview alert
        if (laneAlertText && (fee || time)) {
            laneAlertText.textContent = `⚡ Direct LGA Lane: Delivery Fee ${fee || '₦500'} • Estimated Time: ${time || '2-6 hours'} (Direct dispatch to ${cityName})`;
        }
    }

    cityChips.forEach(chip => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            const lgaId = chip.getAttribute('data-lga-id');
            const city = chip.getAttribute('data-city');
            const state = chip.getAttribute('data-state');
            const fee = chip.getAttribute('data-fee');
            const time = chip.getAttribute('data-time');
            updateActiveLocationDisplay(lgaId, city, state, fee, time);
        });
    });

    if (selectLga) {
        selectLga.addEventListener('change', () => {
            const selectedOpt = selectLga.options[selectLga.selectedIndex];
            if (selectedOpt) {
                const lgaId = selectedOpt.value;
                const city = selectedOpt.getAttribute('data-city');
                const state = selectedOpt.getAttribute('data-state');
                const fee = selectedOpt.getAttribute('data-fee');
                const time = selectedOpt.getAttribute('data-time');
                updateActiveLocationDisplay(lgaId, city, state, fee, time);
            }
        });
    }

    // In-Shop Pickup Radio Selection
    const pickupCards = document.querySelectorAll('.vm-pickup-shop-card');
    pickupCards.forEach(card => {
        card.addEventListener('click', () => {
            pickupCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            const radio = card.querySelector('input');
            if (radio) {
                radio.checked = true;
                const city = radio.getAttribute('data-city');
                const state = radio.getAttribute('data-state');
                const lgaId = radio.getAttribute('data-lga-id');
                if (hiddenCity) hiddenCity.value = city;
                if (hiddenState) hiddenState.value = state;
                if (hiddenLgaId) hiddenLgaId.value = lgaId || '';
            }
        });
    });

    // Geolocation Auto-Detection
    const geoBtn = document.getElementById('vmGeoDetectBtn');
    if (geoBtn) {
        geoBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if ('geolocation' in navigator) {
                geoBtn.innerHTML = '<span>Detecting...</span>';
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        // Successfully captured coordinates, set default flagship hub Uyo
                        geoBtn.innerHTML = '<span>📍 Detected (Uyo)</span>';
                        updateActiveLocationDisplay(69, 'Uyo', 'Akwa Ibom', '₦500.00', '2-6 hours');
                    },
                    (err) => {
                        geoBtn.innerHTML = '<span>Auto-Detect</span>';
                        alert('Could not detect exact location. Please select your LGA from the list.');
                    },
                    { timeout: 8000 }
                );
            }
        });
    }

    // AJAX Submission of Location Preference
    const locForm = document.getElementById('vmLocationForm');
    if (locForm) {
        locForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('vmLocationModalSubmit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Updating...</span>';
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
                    // Smooth page reload so proximity recommendations, nearby shops, and products align
                    window.location.reload();
                } else {
                    alert(data.message || 'Unable to update location');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<span>Confirm Location</span>';
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span>Confirm Location</span>';
                }
                window.location.reload();
            });
        });
    }
});

