@php
    $currentCity = session('customer_city', 'Uyo');
    $currentState = session('customer_state', 'Akwa Ibom');
    $currentLgaId = session('customer_lga_id');
    $currentMode = session('fulfillment_mode', 'delivery');

    // [AI] 1. Authoritative Admin-Enabled Delivery Coverage from DeliveryLane
    $enabledLanes = \App\Models\DeliveryLane::where('is_enabled', true)
        ->with(['destinationLga.state', 'destinationState'])
        ->get();

    $deliveryLgas = [];
    $deliveryStates = [];
    foreach ($enabledLanes as $lane) {
        $dLga = $lane->destinationLga;
        $dState = $lane->destinationState ?? $dLga?->state;
        if ($dLga && $dState) {
            if (!isset($deliveryStates[$dState->id])) {
                $deliveryStates[$dState->id] = [
                    'id' => $dState->id,
                    'name' => $dState->name,
                ];
            }
            if (!isset($deliveryLgas[$dLga->id])) {
                $deliveryLgas[$dLga->id] = [
                    'id' => $dLga->id,
                    'name' => $dLga->name,
                    'state_id' => $dState->id,
                    'state_name' => $dState->name,
                    'fee' => (float)$lane->delivery_fee,
                    'fee_formatted' => webCurrencyConverter($lane->delivery_fee),
                    'est_time' => $lane->estimated_delivery_time ?: '2-6 hours',
                ];
            }
        }
    }

    // [AI] 2. Authoritative In-Shop Pickup Coverage from verified Merchant Shops
    $pickupShops = \App\Models\Shop::where('pickup_enabled', true)
        ->where('temporary_close', 0)
        ->with(['lga.state', 'state', 'seller'])
        ->get();
@endphp

<!-- VMarket Omnichannel Location & LGA Coverage Switcher Modal -->
<div class="vm-modal-backdrop" id="vmLocationModalBackdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="vmLocationModalTitle">
    <div class="vm-location-modal" id="vmLocationModalCard">
        <!-- Close Button -->
        <button type="button" class="vm-modal-close-btn" id="vmLocationModalClose" aria-label="{{ translate('Close') }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <!-- Modal Header -->
        <div class="vm-modal-header">
            <div class="vm-modal-icon-pill">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
            </div>
            <div>
                <h3 class="vm-modal-title" id="vmLocationModalTitle">{{ translate('Choose Your Location & Delivery') }}</h3>
                <p class="vm-modal-subtitle">
                    {{ translate('Select your LGA from admin-enabled delivery coverage areas or choose in-store pickup.') }}
                </p>
            </div>
        </div>

        <form id="vmLocationForm" action="{{ route('set-customer-location') }}" method="POST">
            @csrf
            
            <input type="hidden" name="lga_id" id="vmHiddenLgaId" value="{{ $currentLgaId }}">
            <input type="hidden" name="state_id" id="vmHiddenStateId" value="">
            <input type="hidden" name="city" id="vmHiddenCity" value="{{ $currentCity }}">
            <input type="hidden" name="state" id="vmHiddenState" value="{{ $currentState }}">

            <!-- 1. Fulfillment Mode Segmented Toggle -->
            <div class="vm-form-group">
                <label class="vm-form-label">{{ translate('Fulfillment Preference') }}</label>
                <div class="vm-fulfillment-toggle-group">
                    <label class="vm-fulfillment-toggle-option {{ $currentMode === 'delivery' ? 'active' : '' }}" id="vmOptDelivery">
                        <input type="radio" name="fulfillment_mode" value="delivery" {{ $currentMode === 'delivery' ? 'checked' : '' }} class="vm-sr-only">
                        <span class="vm-toggle-icon">🚚</span>
                        <div class="vm-toggle-text">
                            <strong>{{ translate('Doorstep Delivery') }}</strong>
                            <small>{{ translate('Direct dispatch to your LGA via verified logistics') }}</small>
                        </div>
                    </label>

                    <label class="vm-fulfillment-toggle-option {{ $currentMode === 'pickup' ? 'active' : '' }}" id="vmOptPickup">
                        <input type="radio" name="fulfillment_mode" value="pickup" {{ $currentMode === 'pickup' ? 'checked' : '' }} class="vm-sr-only">
                        <span class="vm-toggle-icon">🏪</span>
                        <div class="vm-toggle-text">
                            <strong>{{ translate('In-Shop Pickup') }}</strong>
                            <small>{{ translate('Inspect physically, collect with zero delivery fee') }}</small>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 2. Doorstep Delivery Section (Admin-Enabled LGAs) -->
            <div id="vmDeliverySection" style="{{ $currentMode === 'pickup' ? 'display: none;' : '' }}">
                <div class="vm-form-group">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
                        <label class="vm-form-label" style="margin-bottom: 0;">{{ translate('Admin-Enabled Delivery LGAs') }}</label>
                        <button type="button" class="vm-geo-detect-btn" id="vmGeoDetectBtn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                            <span>{{ translate('Auto-Detect') }}</span>
                        </button>
                    </div>

                    <!-- Quick LGA Chips from backend active lanes -->
                    <div class="vm-city-chips-grid">
                        @foreach($deliveryLgas as $lgaItem)
                            <button type="button" 
                                    class="vm-city-chip {{ ($currentCity === $lgaItem['name'] || $currentLgaId == $lgaItem['id']) ? 'active' : '' }}" 
                                    data-lga-id="{{ $lgaItem['id'] }}" 
                                    data-city="{{ $lgaItem['name'] }}" 
                                    data-state="{{ $lgaItem['state_name'] }}"
                                    data-fee="{{ $lgaItem['fee_formatted'] }}"
                                    data-time="{{ $lgaItem['est_time'] }}">
                                <span class="vm-chip-dot"></span>
                                <span class="vm-chip-name">{{ $lgaItem['name'] }}</span>
                                <span class="vm-chip-tag">{{ $lgaItem['fee_formatted'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- State & LGA Selectors (Populated from backend) -->
                <div class="vm-form-row">
                    <div class="vm-form-col">
                        <label for="vmSelectState" class="vm-form-label">{{ translate('State') }}</label>
                        <div class="vm-select-wrapper">
                            <select id="vmSelectState" class="vm-modal-select" required>
                                @foreach($deliveryStates as $st)
                                    <option value="{{ $st['id'] }}" {{ $currentState === $st['name'] ? 'selected' : '' }}>
                                        {{ $st['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="vm-select-caret">▾</span>
                        </div>
                    </div>

                    <div class="vm-form-col">
                        <label for="vmSelectLga" class="vm-form-label">{{ translate('Select Your LGA') }}</label>
                        <div class="vm-select-wrapper">
                            <select id="vmSelectLga" class="vm-modal-select" required>
                                @foreach($deliveryLgas as $lgaItem)
                                    <option value="{{ $lgaItem['id'] }}" 
                                            data-city="{{ $lgaItem['name'] }}"
                                            data-state="{{ $lgaItem['state_name'] }}"
                                            data-fee="{{ $lgaItem['fee_formatted'] }}"
                                            data-time="{{ $lgaItem['est_time'] }}"
                                            {{ ($currentCity === $lgaItem['name'] || $currentLgaId == $lgaItem['id']) ? 'selected' : '' }}>
                                        {{ $lgaItem['name'] }} ({{ $lgaItem['fee_formatted'] }} • {{ $lgaItem['est_time'] }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="vm-select-caret">▾</span>
                        </div>
                    </div>
                </div>

                <!-- Live Delivery Lane Preview Alert -->
                <div class="vm-location-alert-box" id="vmDeliveryLaneAlert">
                    <span class="vm-alert-icon">⚡</span>
                    <span class="vm-alert-text" id="vmDeliveryLaneText">
                        {{ translate('Active LGA Lane: Door-to-door logistics with Paystack escrow protection.') }}
                    </span>
                </div>
            </div>

            <!-- 3. In-Shop Pickup Section (Verified Merchant Shops) -->
            <div id="vmPickupSection" style="{{ $currentMode === 'pickup' ? '' : 'display: none;' }}">
                <div class="vm-form-group">
                    <label class="vm-form-label">{{ translate('Select Verified Pickup Point Closer to You') }}</label>
                    <p style="font-size: 12px; color: var(--vm-text-muted); margin-bottom: 10px;">
                        {{ translate('Reserve products online, inspect items physically at the counter before final collection. Zero delivery fee.') }}
                    </p>

                    <div class="vm-pickup-shops-list">
                        @if($pickupShops->count() > 0)
                            @foreach($pickupShops as $pShop)
                                <label class="vm-pickup-shop-card {{ $currentCity === ($pShop->lga?->name ?? 'Uyo') ? 'active' : '' }}">
                                    <input type="radio" name="pickup_shop_select" value="{{ $pShop->id }}" 
                                           data-city="{{ $pShop->lga?->name ?? 'Uyo' }}"
                                           data-state="{{ $pShop->state?->name ?? 'Akwa Ibom' }}"
                                           data-lga-id="{{ $pShop->lga_id }}"
                                           data-shop-name="{{ $pShop->name }}"
                                           {{ $currentCity === ($pShop->lga?->name ?? 'Uyo') ? 'checked' : '' }} 
                                           class="vm-sr-only">
                                    <img src="{{ getStorageImages(path: $pShop->image_full_url, type: 'shop') }}" alt="{{ $pShop->name }}" class="vm-pickup-shop-thumb">
                                    <div class="vm-pickup-shop-info">
                                        <div class="vm-pickup-shop-name-row">
                                            <strong>{{ $pShop->name }}</strong>
                                            <span class="vm-pickup-badge">0 Free Pickup</span>
                                        </div>
                                        <span class="vm-pickup-shop-address">📍 {{ $pShop->address ?: ($pShop->lga?->name . ', ' . $pShop->state?->name) }}</span>
                                        <small class="vm-pickup-prep-time">⏱️ {{ translate('Ready for pickup in') }} {{ $pShop->pickup_preparation_time_minutes ? $pShop->pickup_preparation_time_minutes . ' mins' : '30 mins' }}</small>
                                    </div>
                                </label>
                            @endforeach
                        @else
                            <div style="padding: 16px; text-align: center; color: var(--vm-text-muted); font-size: 13px;">
                                {{ translate('No pickup hubs currently active in this location.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 4. Modal Footer Action Buttons -->
            <div class="vm-modal-footer">
                <button type="button" class="vm-btn-secondary" id="vmLocationModalCancel">
                    {{ translate('Cancel') }}
                </button>
                <button type="submit" class="vm-btn-primary" id="vmLocationModalSubmit">
                    <span>{{ translate('Confirm Location') }}</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>

        </form>
    </div>
</div>
