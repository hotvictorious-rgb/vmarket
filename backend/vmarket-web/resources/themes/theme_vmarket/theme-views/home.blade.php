@extends('theme-views.layouts.app')

@section('title', getWebConfig(name: 'company_name') . ' | ' . translate('Leading Omnichannel Marketplace in Akwa Ibom'))

@section('content')
<div class="vm-container">

    <!-- 1. Top Hero Section: Side Category Dropdown (Left) + Conditional Hero Slider (Right) -->
    <div class="vm-hero-wrapper">
        
        <!-- Category Side Dropdown Navigation (Desktop Left) -->
        <aside class="vm-category-sidebar" aria-label="{{ translate('Categories Navigation') }}">
            <div class="vm-category-sidebar-header">
                <div class="vm-cat-header-left">
                    <span class="vm-cat-header-icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </span>
                    <span class="vm-cat-header-title">{{ translate('All Categories') }}</span>
                </div>
                <span class="vm-cat-header-badge">{{ isset($categories) ? count($categories) : 0 }}</span>
            </div>

            @php
                $catMeta = [
                    'phones-accessories' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
                        'color' => '#5E17EB',
                        'bg' => 'rgba(94, 23, 235, 0.08)',
                    ],
                    'electronics-gadgets' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
                        'color' => '#0284C7',
                        'bg' => 'rgba(2, 132, 199, 0.08)',
                    ],
                    'fashion-clothing' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.5a2 2 0 0 0 1.54 1.63L6 11.2V20a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-8.8l1.6-.38a2 2 0 0 0 1.54-1.63l.58-3.5a2 2 0 0 0-1.34-2.23z"/></svg>',
                        'color' => '#DB2777',
                        'bg' => 'rgba(219, 39, 119, 0.08)',
                    ],
                    'furniture-beddings' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11V7a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v4"/><path d="M2 13a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5H2v-5z"/><line x1="4" y1="18" x2="4" y2="21"/><line x1="20" y1="18" x2="20" y2="21"/></svg>',
                        'color' => '#D97706',
                        'bg' => 'rgba(217, 119, 6, 0.08)',
                    ],
                    'musical-instruments' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
                        'color' => '#7C3AED',
                        'bg' => 'rgba(124, 58, 237, 0.08)',
                    ],
                    'home-appliances' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><circle cx="12" cy="14" r="4"/><line x1="12" y1="6" x2="12.01" y2="6"/></svg>',
                        'color' => '#059669',
                        'bg' => 'rgba(5, 150, 105, 0.08)',
                    ],
                    'kitchen-appliances' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.8V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v9.8"/><rect x="4" y="14" width="16" height="8" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>',
                        'color' => '#EA580C',
                        'bg' => 'rgba(234, 88, 12, 0.08)',
                    ],
                    'beauty-personal-care' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.912 5.885L20 9.8l-4.5 3.9 1.4 6.3L12 16.8l-4.9 3.2 1.4-6.3L4 9.8l6.088-.915L12 3z"/></svg>',
                        'color' => '#E11D48',
                        'bg' => 'rgba(225, 29, 72, 0.08)',
                    ],
                    'automobile' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9C2.1 11.1 2 11.6 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>',
                        'color' => '#475569',
                        'bg' => 'rgba(71, 85, 105, 0.08)',
                    ],
                    'bags-luggages' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="15" rx="2"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="12" y1="11" x2="12" y2="13"/></svg>',
                        'color' => '#9333EA',
                        'bg' => 'rgba(147, 51, 234, 0.08)',
                    ],
                    'groceries-foodstuffs' => [
                        'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
                        'color' => '#16A34A',
                        'bg' => 'rgba(22, 163, 74, 0.08)',
                    ],
                ];
            @endphp

            <ul class="vm-category-sidebar-list">
                @if(isset($categories) && count($categories) > 0)
                    @foreach($categories as $cat)
                        @php
                            $meta = $catMeta[$cat->slug] ?? [
                                'icon' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                                'color' => '#5E17EB',
                                'bg' => 'rgba(94, 23, 235, 0.08)',
                            ];
                            $hasCustomIcon = !empty($cat->icon) && $cat->icon !== 'default.png' && $cat->icon !== 'def.png' && !str_contains($cat->icon, 'default.png');
                        @endphp
                        <li class="vm-category-sidebar-item">
                            <a href="{{ route('category-products', $cat->slug) }}" class="vm-category-sidebar-link">
                                <span class="vm-cat-item-content">
                                    @if($hasCustomIcon)
                                        <img src="{{ getStorageImages(path: $cat->icon_full_url, type: 'category') }}" 
                                             alt="{{ $cat->name }}" 
                                             class="vm-cat-item-icon" 
                                             onerror="this.style.display='none'">
                                    @else
                                        <span class="vm-cat-item-icon-badge" style="background: {{ $meta['bg'] }}; color: {{ $meta['color'] }};">
                                            {!! $meta['icon'] !!}
                                        </span>
                                    @endif
                                    <span class="vm-cat-item-text">{{ $cat->name }}</span>
                                </span>
                                <span class="vm-cat-item-end">
                                    @if(isset($cat->childes) && count($cat->childes) > 0)
                                        <span class="vm-cat-sub-count">{{ count($cat->childes) }}</span>
                                        <span class="vm-cat-chevron">›</span>
                                    @endif
                                </span>
                            </a>

                            {{-- Flyout Subcategories Menu --}}
                            @if(isset($cat->childes) && count($cat->childes) > 0)
                                <div class="vm-category-flyout">
                                    <div class="vm-flyout-topbar">
                                        <div class="vm-flyout-title-group">
                                            <span class="vm-flyout-icon-badge" style="background: {{ $meta['bg'] }}; color: {{ $meta['color'] }};">
                                                {!! $meta['icon'] !!}
                                            </span>
                                            <div>
                                                <h3 class="vm-flyout-header">{{ $cat->name }}</h3>
                                                <span class="vm-flyout-badge">{{ count($cat->childes) }} {{ translate('Subcategories Available') }}</span>
                                            </div>
                                        </div>
                                        <a href="{{ route('category-products', $cat->slug) }}" class="vm-flyout-view-all">
                                            {{ translate('Browse All') }} →
                                        </a>
                                    </div>
                                    <div class="vm-flyout-grid">
                                        @foreach($cat->childes as $subCat)
                                            <a href="{{ route('category-products', $subCat->slug) }}" class="vm-flyout-link">
                                                <span class="vm-flyout-bullet">•</span>
                                                <span class="vm-flyout-text">{{ $subCat->name }}</span>
                                                <span class="vm-flyout-arrow">›</span>
                                            </a>
                                        @endforeach
                                    </div>
                                    <div class="vm-flyout-footer-banner">
                                        <span class="vm-flyout-footer-icon">⚡</span>
                                        <span class="vm-flyout-footer-text">{{ translate('Direct LGA Delivery • Verified Merchants in Akwa Ibom') }}</span>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                @endif
                <li class="vm-category-sidebar-item vm-cat-view-all-item">
                    <a href="{{ route('categories') }}" class="vm-category-sidebar-link vm-cat-view-all-link">
                        <span class="vm-cat-item-content">
                            <span class="vm-cat-item-icon-badge vm-view-all-badge">
                                ✦
                            </span>
                            <span class="vm-cat-item-text">{{ translate('Explore All Categories') }}</span>
                        </span>
                        <span class="vm-cat-chevron">→</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Hero Slider (Desktop: 1:2 on Right | Mobile: Conditional 2:1 Small Centered) -->
        <div class="vm-hero-slider-container">
            <div class="vm-hero-slider" id="vmHeroSlider">
                @if(isset($bannerTypeMainBanner) && count($bannerTypeMainBanner) > 0)
                    @foreach($bannerTypeMainBanner as $idx => $banner)
                        @php
                            $bannerPhoto = is_array($banner) ? ($banner['photo_full_url'] ?? ['path' => '']) : ($banner->photo_full_url ?? ['path' => '']);
                            $bannerUrl = is_array($banner) ? ($banner['url'] ?? '#') : ($banner->url ?? '#');
                            $photoName = is_array($banner) ? ($banner['photo'] ?? '') : ($banner->photo ?? '');
                            $hasCustomImage = !empty($photoName) && $photoName !== 'def.png' && !str_contains($photoName, 'def.png');
                            $title = is_array($banner) ? ($banner['title'] ?? '') : ($banner->title ?? '');
                            $subTitle = is_array($banner) ? ($banner['sub_title'] ?? '') : ($banner->sub_title ?? '');
                            $btnText = is_array($banner) ? ($banner['button_text'] ?? '') : ($banner->button_text ?? '');
                            $bgColor = is_array($banner) ? ($banner['background_color'] ?? '') : ($banner->background_color ?? '');

                            // Generative Variations: 4 Moods & 3 Layout Placements
                            $moodStyles = ['vm-mood-luxe', 'vm-mood-midnight', 'vm-mood-velvet', 'vm-mood-emerald'];
                            $layoutStyles = ['vm-layout-text-left', 'vm-layout-text-right', 'vm-layout-centered'];

                            $activeMood = ($bgColor && $bgColor !== '#5E17EB') ? '' : $moodStyles[$idx % 4];
                            $activeLayout = $layoutStyles[$idx % 3];

                            $badgePills = [
                                ['icon' => '👑', 'label' => translate('Verified Marketplace')],
                                ['icon' => '⚡', 'label' => translate('Direct LGA Delivery • Akwa Ibom')],
                                ['icon' => '🛡️', 'label' => translate('Paystack Escrow Protected')],
                                ['icon' => '🏪', 'label' => translate('Physical In-Shop Inspection')],
                            ];
                            $badge = $badgePills[$idx % 4];
                        @endphp
                        <div class="vm-hero-slide {{ $idx === 0 ? 'active' : '' }}" data-slide="{{ $idx }}">
                            <div class="vm-hero-card-banner {{ $activeMood }} {{ $activeLayout }}" 
                                 style="{{ ($bgColor && $bgColor !== '#5E17EB') ? 'background: linear-gradient(135deg, ' . $bgColor . ' 0%, #170733 100%) !important;' : '' }}">
                                
                                <div class="vm-hero-card-content">
                                    <div class="vm-hero-badge-pill">
                                        <span class="vm-badge-dot"></span>
                                        <span>{{ $badge['icon'] }} {{ $badge['label'] }}</span>
                                    </div>
                                    <h2 class="vm-hero-title">
                                        {{ $title ?: translate('Seamless Shopping, Swift Logistics') }}
                                    </h2>
                                    <p class="vm-hero-subtitle">
                                        {{ $subTitle ?: translate('Akwa Ibom’s Premier Platform • Physical In-Shop Inspection • Escrow Protected') }}
                                    </p>
                                    <div class="vm-hero-actions">
                                        <a href="{{ $bannerUrl ?: route('products') }}" class="vm-hero-cta-btn">
                                            {{ $btnText ?: translate('Shop Verified Items') }} →
                                        </a>
                                        <a href="{{ route('vendors') }}" class="vm-hero-secondary-btn">
                                            {{ translate('Explore Stores') }}
                                        </a>
                                    </div>
                                </div>

                                <div class="vm-hero-visual-zone">
                                    @if($hasCustomImage)
                                        <div class="vm-pedestal-orb">
                                            <img src="{{ getStorageImages(path: $bannerPhoto, type: 'banner') }}" 
                                                 alt="{{ $title ?: getWebConfig(name: 'company_name') }}" 
                                                 class="vm-product-cutout-img" 
                                                 loading="lazy">
                                        </div>
                                    @else
                                        <div class="vm-trust-pill-grid">
                                            <div class="vm-trust-pill-item">
                                                <span class="vm-trust-pill-icon">🛡️</span>
                                                <span>100% Genuine Escrow</span>
                                            </div>
                                            <div class="vm-trust-pill-item">
                                                <span class="vm-trust-pill-icon">⚡</span>
                                                <span>Door-to-Door Logistics</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endforeach
                @else
                    {{-- Default Branded Hero Card Fallback --}}
                    <div class="vm-hero-slide active" data-slide="0">
                        <div class="vm-hero-card-banner">
                            <div class="vm-hero-card-content">
                                <div class="vm-hero-badge-pill">
                                    <span class="vm-badge-dot"></span>
                                    <span>{{ translate('Verified Omnichannel Market') }}</span>
                                </div>
                                <h2 class="vm-hero-title">{{ translate('Seamless Shopping, Swift Logistics') }}</h2>
                                <p class="vm-hero-subtitle">{{ translate('Akwa Ibom’s Premier Platform • Physical In-Shop Inspection • Escrow Protected') }}</p>
                                <div class="vm-hero-actions">
                                    <a href="{{ route('products') }}" class="vm-hero-cta-btn">{{ translate('Shop Verified Items') }} →</a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(isset($bannerTypeMainBanner) && count($bannerTypeMainBanner) > 1)
                    <button type="button" class="vm-slider-arrow vm-slider-prev" id="vmHeroPrev" aria-label="{{ translate('Previous slide') }}">‹</button>
                    <button type="button" class="vm-slider-arrow vm-slider-next" id="vmHeroNext" aria-label="{{ translate('Next slide') }}">›</button>
                @endif
            </div>

            <!-- Dedicated Pagination Track: Outside content zone so it NEVER covers text or buttons -->
            @if(isset($bannerTypeMainBanner) && count($bannerTypeMainBanner) > 1)
                <div class="vm-slider-dots" id="vmHeroDots">
                    @foreach($bannerTypeMainBanner as $idx => $b)
                        <button type="button" class="vm-slider-dot {{ $idx === 0 ? 'active' : '' }}" data-slide="{{ $idx }}" aria-label="{{ translate('Slide') }} {{ $idx + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    <!-- 2. Value Proposition Strip -->
    <div class="vm-val-prop-strip">
        <div class="vm-val-prop-item">
            <div class="vm-val-prop-icon-wrap primary">🛡️</div>
            <div>
                <strong class="vm-val-prop-title">{{ translate('100% Authentic Items') }}</strong>
                <span class="vm-val-prop-desc">{{ translate('Verified local merchants') }}</span>
            </div>
        </div>
        <div class="vm-val-prop-item">
            <div class="vm-val-prop-icon-wrap gold">🏪</div>
            <div>
                <strong class="vm-val-prop-title">{{ translate('In-Shop Inspection') }}</strong>
                <span class="vm-val-prop-desc">{{ translate('Inspect physically before payment') }}</span>
            </div>
        </div>
        <div class="vm-val-prop-item">
            <div class="vm-val-prop-icon-wrap primary">⚡</div>
            <div>
                <strong class="vm-val-prop-title">{{ translate('Directional LGA Delivery') }}</strong>
                <span class="vm-val-prop-desc">{{ translate('Uyo, Eket & nationwide logistics') }}</span>
            </div>
        </div>
        <div class="vm-val-prop-item">
            <div class="vm-val-prop-icon-wrap success">💳</div>
            <div>
                <strong class="vm-val-prop-title">{{ translate('Paystack Escrow') }}</strong>
                <span class="vm-val-prop-desc">{{ translate('Cards, transfers & cashbacks') }}</span>
            </div>
        </div>
    </div>

    <!-- 2.5 Omnichannel Proximity Status Bar -->
    <div class="vm-proximity-strip">
        <div class="vm-proximity-info">
            <span class="vm-proximity-icon">📍</span>
            <div class="vm-proximity-text">
                <span class="vm-proximity-label">{{ translate('Active Marketplace Coverage') }}:</span>
                <strong class="vm-proximity-highlight">{{ $activeCity ?? 'Uyo' }}, {{ $activeState ?? 'Akwa Ibom' }}</strong>
                <span class="vm-proximity-dot">•</span>
                <span class="vm-proximity-mode-tag {{ ($fulfillmentMode ?? 'delivery') }}">
                    {{ ($fulfillmentMode ?? 'delivery') === 'pickup' ? translate('🏪 In-Shop Inspection (Zero Delivery Fee)') : translate('⚡ Direct Doorstep LGA Logistics') }}
                </span>
            </div>
        </div>
        <button type="button" class="vm-proximity-switch-btn" id="vmProximityTrigger">
            <span>{{ translate('Change Location or Mode') }}</span>
            <span class="vm-caret">▾</span>
        </button>
    </div>

    <!-- 2.6 Amazon-Inspired Quad Discovery & Recommendation Grid -->
    <div class="vm-amazon-discovery-grid">

        <!-- Card 1: Omnichannel Proximity: Verified Stores Closer to You -->
        <div class="vm-discovery-card">
            <div class="vm-discovery-card-header">
                <div class="vm-discovery-badge-pill">
                    <span class="vm-badge-dot"></span>
                    <span>{{ ($fulfillmentMode ?? 'delivery') === 'pickup' ? translate('In-Shop Pickup Ready') : translate('Verified Local Hubs') }}</span>
                </div>
                <h3 class="vm-discovery-card-title">{{ translate('Stores Closer to You in') }} {{ $activeCity ?? 'Uyo' }}</h3>
            </div>
            
            <div class="vm-discovery-quad-grid">
                @if(isset($nearbyShops) && $nearbyShops->count() > 0)
                    @foreach($nearbyShops->take(4) as $nShop)
                        <a href="{{ route('vendor-shop', $nShop->slug) }}" class="vm-discovery-tile">
                            <div class="vm-discovery-tile-thumb-wrap">
                                <img src="{{ getStorageImages(path: $nShop->image_full_url, type: 'shop') }}" 
                                     alt="{{ $nShop->name }}" 
                                     class="vm-discovery-tile-img" 
                                     loading="lazy">
                                <span class="vm-tile-floating-badge">{{ $nShop->pickup_enabled ? '🏪 In-Shop' : '🚚 Fast' }}</span>
                            </div>
                            <span class="vm-discovery-tile-caption" title="{{ $nShop->name }}">{{ Str::limit($nShop->name, 14) }}</span>
                        </a>
                    @endforeach
                @else
                    @for($i = 1; $i <= 4; $i++)
                        <a href="{{ route('vendors') }}" class="vm-discovery-tile">
                            <div class="vm-discovery-tile-thumb-wrap">
                                <img src="{{ theme_asset('assets/img/vm_icon.jpg') }}" alt="Store" class="vm-discovery-tile-img">
                            </div>
                            <span class="vm-discovery-tile-caption">{{ translate('Verified Merchant') }}</span>
                        </a>
                    @endfor
                @endif
            </div>

            <div class="vm-discovery-card-footer">
                <a href="{{ route('vendors') }}" class="vm-discovery-card-link">
                    {{ translate('Explore Stores in') }} {{ $activeCity ?? 'Uyo' }} →
                </a>
            </div>
        </div>

        <!-- Card 2: Shop Kitchen Must-Haves -->
        <div class="vm-discovery-card">
            <div class="vm-discovery-card-header">
                <div class="vm-discovery-badge-pill gold">
                    <span class="vm-badge-dot gold"></span>
                    <span>{{ translate('Kitchen & Home') }}</span>
                </div>
                <h3 class="vm-discovery-card-title">{{ translate('Shop Kitchen Must-Haves') }}</h3>
            </div>

            <div class="vm-discovery-quad-grid">
                <a href="{{ route('products', ['name' => 'Blender']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box orange">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 13.8V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v9.8"/><rect x="4" y="14" width="16" height="8" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Blenders & Grinders') }}</span>
                </a>

                <a href="{{ route('products', ['name' => 'Microwave']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box orange">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="15" y1="4" x2="15" y2="20"/><circle cx="18" cy="9" r="1"/><circle cx="18" cy="15" r="1"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Microwaves & Ovens') }}</span>
                </a>

                <a href="{{ route('products', ['name' => 'Cooker']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box orange">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="2"/><circle cx="16" cy="8" r="2"/><circle cx="8" cy="16" r="2"/><circle cx="16" cy="16" r="2"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Gas Cookers & Stoves') }}</span>
                </a>

                <a href="{{ route('products', ['name' => 'Fryer']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box orange">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Air Fryers & Fryers') }}</span>
                </a>
            </div>

            <div class="vm-discovery-card-footer">
                <a href="{{ route('products') }}" class="vm-discovery-card-link">
                    {{ translate('Explore Kitchen Appliances') }} →
                </a>
            </div>
        </div>

        <!-- Card 3: Top Tech, Phones & PC Gear -->
        <div class="vm-discovery-card">
            <div class="vm-discovery-card-header">
                <div class="vm-discovery-badge-pill purple">
                    <span class="vm-badge-dot purple"></span>
                    <span>{{ translate('Tech & Gadgets') }}</span>
                </div>
                <h3 class="vm-discovery-card-title">{{ translate('Level Up Your Tech & PC') }}</h3>
            </div>

            <div class="vm-discovery-quad-grid">
                <a href="{{ route('products', ['name' => 'Phone']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box purple">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Smartphones & iPhones') }}</span>
                </a>

                <a href="{{ route('products', ['name' => 'Laptop']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box purple">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Laptops & Computers') }}</span>
                </a>

                <a href="{{ route('products', ['name' => 'Headphone']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box purple">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Audio & Headphones') }}</span>
                </a>

                <a href="{{ route('products', ['name' => 'Charger']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box purple">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2" width="12" height="20" rx="2"/><polygon points="13 7 9 13 12 13 11 17 15 11 12 11 13 7"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Power Banks & Gear') }}</span>
                </a>
            </div>

            <div class="vm-discovery-card-footer">
                <a href="{{ route('products') }}" class="vm-discovery-card-link">
                    {{ translate('Explore All Tech Deals') }} →
                </a>
            </div>
        </div>

        <!-- Card 4: Trending Fashion & Beauty Finds -->
        <div class="vm-discovery-card">
            <div class="vm-discovery-card-header">
                <div class="vm-discovery-badge-pill pink">
                    <span class="vm-badge-dot pink"></span>
                    <span>{{ translate('Fashion & Beauty') }}</span>
                </div>
                <h3 class="vm-discovery-card-title">{{ translate('Start Looking Sharp') }}</h3>
            </div>

            <div class="vm-discovery-quad-grid">
                <a href="{{ route('products', ['name' => 'Men']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box pink">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.38 3.46L16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.5a2 2 0 0 0 1.54 1.63L6 11.2V20a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-8.8l1.6-.38a2 2 0 0 0 1.54-1.63l.58-3.5a2 2 0 0 0-1.34-2.23z"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Men’s Clothing') }}</span>
                </a>

                <a href="{{ route('products', ['name' => 'Women']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box pink">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h12l3 7-9 12L3 10l3-7z"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Women’s Fashion') }}</span>
                </a>

                <a href="{{ route('products', ['name' => 'Shoe']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box pink">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 17h20v2H2zM4 17l2-7h12l2 7z"/><circle cx="9" cy="13" r="1"/><circle cx="15" cy="13" r="1"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Shoes & Sneakers') }}</span>
                </a>

                <a href="{{ route('products', ['name' => 'Skin']) }}" class="vm-discovery-tile">
                    <div class="vm-discovery-tile-thumb-wrap">
                        <div class="vm-discovery-tile-icon-box pink">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                        </div>
                    </div>
                    <span class="vm-discovery-tile-caption">{{ translate('Beauty & Skincare') }}</span>
                </a>
            </div>

            <div class="vm-discovery-card-footer">
                <a href="{{ route('products') }}" class="vm-discovery-card-link">
                    {{ translate('Discover Trending Fashion') }} →
                </a>
            </div>
        </div>

    </div>

    <!-- 3. Featured Categories Grid -->
    @if(isset($categories) && count($categories) > 0)
        <div class="vm-section-block">
            <div class="vm-section-header">
                <div>
                    <h2 class="vm-section-title">{{ translate('Browse Popular Categories') }}</h2>
                    <p class="vm-section-desc">{{ translate('Quality goods curated from certified merchants across Akwa Ibom.') }}</p>
                </div>
                <a href="{{ route('categories') }}" class="vm-section-link">{{ translate('View All Categories') }} →</a>
            </div>
            <div class="vm-categories-grid">
                @foreach($categories->take(10) as $cat)
                    <a href="{{ route('category-products', $cat->slug) }}" class="vm-category-card">
                        <img src="{{ getStorageImages(path: $cat->icon_full_url, type: 'category') }}" 
                             alt="{{ $cat->name }}" 
                             class="vm-category-icon" 
                             loading="lazy">
                        <span class="vm-category-name">{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 4. Featured Products Showcase -->
    @if(isset($featuredProductsList) && count($featuredProductsList) > 0)
        <div class="vm-section-block">
            <div class="vm-section-header">
                <div>
                    <h2 class="vm-section-title">{{ translate('Featured Products') }}</h2>
                    <p class="vm-section-desc">{{ translate('Handpicked high-demand products with guaranteed local stock.') }}</p>
                </div>
                <a href="{{ route('products') }}" class="vm-section-link">{{ translate('Explore All Products') }} →</a>
            </div>
            <div class="vm-products-grid">
                @foreach($featuredProductsList->take(10) as $product)
                    @php
                        $price = $product->unit_price;
                        $discount = \App\Utils\Helpers::get_product_discount($product, $price);
                        $finalPrice = $price - $discount;
                        $shop = $product->seller?->shop;
                    @endphp
                    <div class="vm-product-card">
                        <a href="{{ route('product', $product->slug) }}" class="vm-product-img-wrap">
                            <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}" 
                                 alt="{{ $product->name }}" 
                                 class="vm-product-img" 
                                 loading="lazy">
                            @if($discount > 0)
                                <span class="vm-discount-badge">
                                    @if ($product->discount_type == 'percent')
                                        -{{ round($product->discount) }}%
                                    @else
                                        -{{ webCurrencyConverter($discount) }}
                                    @endif
                                </span>
                            @endif
                        </a>
                        <div class="vm-product-info">
                            @if($shop)
                                <a href="{{ route('vendor-shop', $shop->slug) }}" class="vm-product-shop">
                                    <span>🏪</span>
                                    <span>{{ Str::limit($shop->name, 18) }}</span>
                                </a>
                            @endif
                            <a href="{{ route('product', $product->slug) }}" class="vm-product-name" title="{{ $product->name }}">
                                {{ $product->name }}
                            </a>
                            <div class="vm-product-price-row">
                                <span class="vm-price-current">{{ webCurrencyConverter($finalPrice) }}</span>
                                @if($discount > 0)
                                    <span class="vm-price-old">{{ webCurrencyConverter($price) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 5. Top Verified Merchants Strip -->
    @if(isset($topVendorsList) && count($topVendorsList) > 0)
        <div class="vm-merchants-card-strip">
            <div class="vm-section-header">
                <div>
                    <h2 class="vm-section-title">{{ translate('Top Verified Merchants') }}</h2>
                    <p class="vm-section-desc">
                        {{ translate('Official retail stores offering physical counter inspection and rapid dispatch.') }}
                    </p>
                </div>
                <a href="{{ route('vendors') }}" class="vm-section-link">{{ translate('All Stores') }} →</a>
            </div>

            <div class="vm-merchants-grid">
                @foreach($topVendorsList->take(6) as $vendorItem)
                    @php
                        $shop = $vendorItem->shop;
                        if (!$shop) continue;
                    @endphp
                    <a href="{{ route('vendor-shop', $shop->slug) }}" class="vm-merchant-box">
                        <img src="{{ getStorageImages(path: $shop->image_full_url, type: 'shop') }}" 
                             alt="{{ $shop->name }}" 
                             class="vm-merchant-img">
                        <div class="vm-merchant-details">
                            <strong class="vm-merchant-name">{{ Str::limit($shop->name, 16) }}</strong>
                            <span class="vm-merchant-location">
                                📍 {{ $shop->deliveryCity?->name ?? 'Uyo' }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 6. Latest Products in Market -->
    @if(isset($latestProductsList) && count($latestProductsList) > 0)
        <div class="vm-section-block">
            <div class="vm-section-header">
                <div>
                    <h2 class="vm-section-title">{{ translate('Latest Products in Market') }}</h2>
                    <p class="vm-section-desc">{{ translate('Fresh arrivals uploaded directly from verified regional warehouses.') }}</p>
                </div>
                <a href="{{ route('products') }}" class="vm-section-link">{{ translate('View All') }} →</a>
            </div>
            <div class="vm-products-grid">
                @foreach($latestProductsList->take(10) as $product)
                    @php
                        $price = $product->unit_price;
                        $discount = \App\Utils\Helpers::get_product_discount($product, $price);
                        $finalPrice = $price - $discount;
                        $shop = $product->seller?->shop;
                    @endphp
                    <div class="vm-product-card">
                        <a href="{{ route('product', $product->slug) }}" class="vm-product-img-wrap">
                            <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}" 
                                 alt="{{ $product->name }}" 
                                 class="vm-product-img" 
                                 loading="lazy">
                        </a>
                        <div class="vm-product-info">
                            @if($shop)
                                <a href="{{ route('vendor-shop', $shop->slug) }}" class="vm-product-shop">
                                    <span>🏪</span>
                                    <span>{{ Str::limit($shop->name, 18) }}</span>
                                </a>
                            @endif
                            <a href="{{ route('product', $product->slug) }}" class="vm-product-name" title="{{ $product->name }}">
                                {{ $product->name }}
                            </a>
                            <div class="vm-product-price-row">
                                <span class="vm-price-current">{{ webCurrencyConverter($finalPrice) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- 7. Strategic Banner Touchpoint: Sleek Footer Slider (Admin-Controlled Banners) -->
    @if(isset($bannerTypeFooterBanner) && count($bannerTypeFooterBanner) > 0)
        <div class="vm-footer-slider-container">
            <div class="vm-footer-slider" id="vmFooterSlider">
                @foreach($bannerTypeFooterBanner as $idx => $banner)
                    @php
                        $bannerPhoto = is_array($banner) ? ($banner['photo_full_url'] ?? ['path' => '']) : ($banner->photo_full_url ?? ['path' => '']);
                        $bannerUrl = is_array($banner) ? ($banner['url'] ?? '#') : ($banner->url ?? '#');
                        $photoName = is_array($banner) ? ($banner['photo'] ?? '') : ($banner->photo ?? '');
                        $hasCustomImage = !empty($photoName) && $photoName !== 'def.png' && !str_contains($photoName, 'def.png');
                        $title = is_array($banner) ? ($banner['title'] ?? '') : ($banner->title ?? '');
                        $subTitle = is_array($banner) ? ($banner['sub_title'] ?? '') : ($banner->sub_title ?? '');
                        $btnText = is_array($banner) ? ($banner['button_text'] ?? '') : ($banner->button_text ?? '');
                        $bgColor = is_array($banner) ? ($banner['background_color'] ?? '') : ($banner->background_color ?? '');

                        // Generative Mood Matrix for Footer Slider
                        $footerMoods = ['vm-mood-midnight', 'vm-mood-luxe', 'vm-mood-velvet', 'vm-mood-emerald'];
                        $activeFooterMood = ($bgColor && $bgColor !== '#0F172A' && $bgColor !== '#5E17EB') ? '' : $footerMoods[$idx % 4];

                        $footerBadges = [
                            ['icon' => '🛡️', 'label' => translate('Paystack Escrow Protected')],
                            ['icon' => '⚡', 'label' => translate('Akwa Ibom 31 LGA Express Logistics')],
                            ['icon' => '🏪', 'label' => translate('Physical In-Shop Inspection Guarantee')],
                            ['icon' => '👑', 'label' => translate('Verified Direct-From-Source Vendors')],
                        ];
                        $badge = $footerBadges[$idx % 4];
                    @endphp
                    <div class="vm-footer-slide {{ $idx === 0 ? 'active' : '' }}" data-slide="{{ $idx }}">
                        @if($hasCustomImage && empty($title) && empty($subTitle))
                            <a href="{{ $bannerUrl }}" class="vm-footer-slide-link">
                                <img src="{{ getStorageImages(path: $bannerPhoto, type: 'banner') }}" 
                                     alt="{{ $title ?: getWebConfig(name: 'company_name') }} Offer" 
                                     class="vm-footer-slide-img" 
                                     loading="lazy">
                            </a>
                        @else
                            <div class="vm-footer-banner-card {{ $activeFooterMood }}" 
                                 style="{{ ($bgColor && $bgColor !== '#0F172A' && $bgColor !== '#5E17EB') ? 'background: linear-gradient(135deg, ' . $bgColor . ' 0%, #170733 100%) !important;' : '' }}">
                                <div class="vm-footer-banner-content">
                                    <span class="vm-footer-badge">{{ $badge['icon'] }} {{ $badge['label'] }}</span>
                                    <h3 class="vm-footer-title">{{ $title ?: translate('Direct LGA Logistics & Escrow Security') }}</h3>
                                    <p class="vm-footer-subtitle">{{ $subTitle ?: translate('Reliable door-to-door delivery across Uyo, Eket, and all 31 Akwa Ibom local governments.') }}</p>
                                </div>
                                @if($hasCustomImage)
                                    <div class="vm-footer-banner-visual">
                                        <img src="{{ getStorageImages(path: $bannerPhoto, type: 'banner') }}" 
                                             alt="{{ $title ?: getWebConfig(name: 'company_name') }}" 
                                             class="vm-footer-cutout-img" 
                                             loading="lazy">
                                    </div>
                                @endif
                                <div class="vm-footer-banner-action">
                                    <a href="{{ $bannerUrl ?: route('products') }}" class="vm-footer-cta-btn">
                                        {{ $btnText ?: translate('Shop Verified') }} →
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if(count($bannerTypeFooterBanner) > 1)
                    <button type="button" class="vm-slider-arrow vm-slider-prev vm-footer-prev" id="vmFooterPrev" aria-label="{{ translate('Previous') }}">‹</button>
                    <button type="button" class="vm-slider-arrow vm-slider-next vm-footer-next" id="vmFooterNext" aria-label="{{ translate('Next') }}">›</button>
                @endif
            </div>

            <!-- Dedicated Footer Pagination Track: Outside content zone so it NEVER covers text or buttons -->
            @if(count($bannerTypeFooterBanner) > 1)
                <div class="vm-slider-dots vm-footer-dots" id="vmFooterDots">
                    @foreach($bannerTypeFooterBanner as $idx => $b)
                        <button type="button" class="vm-slider-dot {{ $idx === 0 ? 'active' : '' }}" data-slide="{{ $idx }}" aria-label="{{ translate('Slide') }} {{ $idx + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

</div>

<!-- 8. Strategic Touchpoint 3: Session Promotional Popup Modal (Polite Bottom-Right Floating Card) -->
@if(isset($bannerTypePopupBanner) && $bannerTypePopupBanner)
    @php
        $popup = $bannerTypePopupBanner;
        $pTitle = is_array($popup) ? ($popup['title'] ?? '') : ($popup->title ?? '');
        $pSubTitle = is_array($popup) ? ($popup['sub_title'] ?? '') : ($popup->sub_title ?? '');
        $pBtnText = is_array($popup) ? ($popup['button_text'] ?? '') : ($popup->button_text ?? '');
        $pUrl = is_array($popup) ? ($popup['url'] ?? '#') : ($popup->url ?? '#');
        $pPhoto = is_array($popup) ? ($popup['photo_full_url'] ?? ['path' => '']) : ($popup->photo_full_url ?? ['path' => '']);
        $pPhotoName = is_array($popup) ? ($popup['photo'] ?? '') : ($popup->photo ?? '');
        $hasCustomPopupImg = !empty($pPhotoName) && $pPhotoName !== 'def.png' && !str_contains($pPhotoName, 'def.png');
    @endphp
    <div class="vm-popup-card" id="vmPromoPopupCard" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="vmPopupTitle">
        <button type="button" class="vm-popup-close" id="vmPopupCloseBtn" aria-label="{{ translate('Close') }}">&times;</button>
        <div class="vm-popup-header">
            <img src="{{ theme_asset('assets/img/vm_icon.jpg') }}" alt="VM" class="vm-popup-logo-icon">
            <div class="vm-popup-brand">
                <span class="vm-brand-victorious">Victorious</span>&nbsp;<span class="vm-brand-market">MARKET</span>
            </div>
        </div>
        <div class="vm-popup-content">
            <h4 class="vm-popup-title" id="vmPopupTitle">{{ $pTitle ?: translate('Welcome to Victorious MARKET!') }}</h4>
            <p class="vm-popup-desc">
                {{ $pSubTitle ?: translate('Enjoy genuine items, in-shop physical inspection before collection, and Paystack escrow guarantee.') }}
            </p>
            <div class="vm-popup-footer">
                <a href="{{ $pUrl ?: route('products') }}" class="vm-popup-action-btn">
                    {{ $pBtnText ?: translate('Start Shopping') }} →
                </a>
            </div>
        </div>
    </div>
@endif

@endsection
