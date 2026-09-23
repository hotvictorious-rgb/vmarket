@extends('theme-views.layouts.app')

@section('title', translate('Verified Merchants & Stores') . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 24px 16px 48px;">
    
    <div class="vm-section-header">
        <div>
            <h1 class="vm-section-title">{{ translate('Verified Merchants in Akwa Ibom') }}</h1>
            <p style="font-size: 13.5px; color: var(--vm-text-muted); margin-top: 4px;">
                {{ translate('Shop directly from verified local businesses with guaranteed authenticity, physical in-shop inspection, and swift delivery.') }}
            </p>
        </div>
    </div>

    @if($sellers && count($sellers) > 0)
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; margin-top: 20px;">
            @foreach($sellers as $seller)
                @php
                    $shop = $seller->shop;
                    if (!$shop) continue;
                    $productCount = $shop->products_count ?? 0;
                @endphp
                <div style="background: var(--vm-surface); border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 20px; transition: var(--vm-transition); display: flex; flex-direction: column; gap: 14px; position: relative;" onmouseover="this.style.borderColor='var(--vm-primary)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--vm-shadow-hover)'" onmouseout="this.style.borderColor='var(--vm-border)'; this.style.transform='none'; this.style.boxShadow='none'">
                    
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <img src="{{ getStorageImages(path: $shop->image_full_url, type: 'shop') }}" 
                             alt="{{ $shop->name }}" 
                             style="width: 56px; height: 56px; border-radius: var(--vm-radius-md); object-fit: cover; border: 1.5px solid var(--vm-border); flex-shrink: 0;">
                        <div>
                            <h3 style="font-size: 16px; font-weight: 700; color: var(--vm-dark); display: flex; align-items: center; gap: 6px; line-height: 1.3;">
                                {{ $shop->name }}
                                <span class="vm-verified-badge" style="font-size: 9px; padding: 2px 6px;">
                                    ✓
                                </span>
                            </h3>
                            <div style="font-size: 12px; color: var(--vm-text-muted); margin-top: 3px;">
                                📍 {{ $shop->deliveryCity?->name ?? 'Uyo' }}, {{ $shop->deliveryState?->name ?? 'Akwa Ibom' }}
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--vm-border-light); font-size: 12.5px;">
                        <span style="color: var(--vm-text-muted);">
                            <strong>{{ $productCount }}</strong> {{ translate('Products') }}
                        </span>
                        <a href="{{ route('vendor-shop', ['slug' => $shop->slug]) }}" 
                           class="vm-btn-primary" 
                           style="padding: 7px 14px; font-size: 12.5px; border-radius: var(--vm-radius-full); box-shadow: none;">
                            {{ translate('Visit Store') }} →
                        </a>
                    </div>

                </div>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 60px 20px; background: #FFFFFF; border-radius: var(--vm-radius-md); border: 1px solid var(--vm-border);">
            <p style="font-size: 15px; color: var(--vm-text-muted);">
                {{ translate('No verified merchants found.') }}
            </p>
        </div>
    @endif

</div>
@endsection
