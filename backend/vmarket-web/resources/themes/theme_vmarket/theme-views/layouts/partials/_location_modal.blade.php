@php
    $currentCity = session('customer_city', 'Uyo');
    $currentState = session('customer_state', 'Akwa Ibom');
    $currentLgaId = session('customer_lga_id');

    // [AI] Canonical active LGAs in Nigeria (cached)
    $allLgas = \Illuminate\Support\Facades\Cache::remember('vmarket_canonical_lgas_list', 10800, function () {
        return \App\Models\Lga::with('state')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'name' => $l->name,
                    'state_name' => $l->state?->name ?? 'Nigeria',
                    'state_id' => $l->state_id,
                ];
            });
    });

    // Enabled delivery lanes destination IDs
    $enabledDestinationLgaIds = \App\Models\DeliveryLane::where('is_enabled', true)
        ->pluck('destination_lga_id')
        ->toArray();

    // Primary quick LGA chips
    $popularLgaNames = ['Uyo', 'Eket', 'Ikot Ekpene', 'Oron', 'Abak', 'Ikot Abasi'];
    $popularLgas = $allLgas->whereIn('name', $popularLgaNames)->values();
    if ($popularLgas->isEmpty()) {
        $popularLgas = $allLgas->take(6);
    }
@endphp

<!-- VMarket Omnichannel Location Picker Modal -->
<div class="vm-modal-backdrop" id="vmLocationModalBackdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="vmLocationModalTitle">
    <div class="vm-location-modal" id="vmLocationModalCard">
        <!-- Close Button -->
        <button type="button" class="vm-modal-close-btn" id="vmLocationModalClose" aria-label="{{ translate('Close') }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>

        <!-- Modal Header -->
        <div class="vm-modal-header">
            <div class="vm-modal-icon-pill">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
            </div>
            <div>
                <h3 class="vm-modal-title" id="vmLocationModalTitle">{{ translate('Choose your location') }}</h3>
                <p class="vm-modal-subtitle">
                    {{ translate('Delivery options and delivery speeds may vary for different locations') }}
                </p>
            </div>
        </div>

        <form id="vmLocationForm" action="{{ route('set-customer-location') }}" method="POST">
            @csrf
            
            <input type="hidden" name="lga_id" id="vmHiddenLgaId" value="{{ $currentLgaId }}">
            <input type="hidden" name="lga_name" id="vmHiddenLgaName" value="{{ $currentCity }}">
            <input type="hidden" name="state_id" id="vmHiddenStateId" value="">
            <input type="hidden" name="city" id="vmHiddenCity" value="{{ $currentCity }}">
            <input type="hidden" name="state" id="vmHiddenState" value="{{ $currentState }}">

            <!-- Country Badge Bar -->
            <div class="vm-country-bar">
                <div class="vm-country-badge">
                    <span class="vm-flag-emoji">🇳🇬</span>
                    <span class="vm-country-name">{{ translate('Nigeria') }}</span>
                </div>
                <span class="vm-country-sub">{{ translate('Nationwide Local Governments') }}</span>
            </div>

            <!-- Residential LGA Input & Autocomplete Search -->
            <div class="vm-form-group vm-lga-search-group">
                <label for="vmLgaSearchInput" class="vm-form-label">{{ translate('type your residential lga') }}</label>
                <div class="vm-input-icon-wrap">
                    <span class="vm-input-lead-icon">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" 
                           id="vmLgaSearchInput" 
                           class="vm-modal-input vm-lga-search-input" 
                           placeholder="{{ translate('type your residential lga') }}" 
                           value="{{ $currentCity }}" 
                           autocomplete="off">
                    <button type="button" class="vm-input-clear-btn" id="vmLgaClearBtn" style="{{ $currentCity ? '' : 'display: none;' }}" aria-label="{{ translate('Clear') }}">×</button>
                </div>

                <!-- Autocomplete Dropdown List -->
                <div class="vm-lga-dropdown-list" id="vmLgaDropdownList" style="display: none;">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

            <!-- Quick LGA Chips -->
            <div class="vm-form-group" style="margin-bottom: 14px;">
                <span class="vm-chips-heading">{{ translate('Popular Local Governments') }}:</span>
                <div class="vm-city-chips-grid">
                    @foreach($popularLgas as $pLga)
                        <button type="button" 
                                class="vm-city-chip {{ ($currentCity === $pLga['name'] || $currentLgaId == $pLga['id']) ? 'active' : '' }}" 
                                data-lga-id="{{ $pLga['id'] }}" 
                                data-city="{{ $pLga['name'] }}" 
                                data-state="{{ $pLga['state_name'] }}">
                            <span class="vm-chip-dot"></span>
                            <span class="vm-chip-name">{{ $pLga['name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Active Selected Location Feedback Pill -->
            <div class="vm-selected-location-card" id="vmSelectedLocCard">
                <div class="vm-selected-loc-left">
                    <span class="vm-selected-pin">📍</span>
                    <div>
                        <strong class="vm-selected-city-text" id="vmPreviewCity">{{ $currentCity }}, {{ $currentState }}</strong>
                        <small class="vm-selected-status-text" id="vmPreviewStatus">{{ translate('Pickup and delivery available in this local government') }}</small>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Done Button -->
            <div class="vm-modal-footer-single">
                <button type="submit" class="vm-btn-done" id="vmLocationModalSubmit">
                    <span>{{ translate('Done') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Embedded JSON Dataset for instant client-side autocomplete -->
<script id="vmLgaData" type="application/json">
{!! json_encode($allLgas) !!}
</script>
