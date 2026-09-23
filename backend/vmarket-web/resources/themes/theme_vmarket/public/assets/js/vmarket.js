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

    // 3. Simple Hero Carousel / Slide Rotator (if multiple banners exist)
    const slides = document.querySelectorAll('.vm-hero-slide');
    if (slides.length > 1) {
        let currentSlide = 0;
        slides.forEach((s, idx) => {
            s.style.display = idx === 0 ? 'block' : 'none';
        });

        setInterval(() => {
            slides[currentSlide].style.display = 'none';
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].style.display = 'block';
        }, 5000);
    }

    // 4. Quantity Increment / Decrement
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
});
