@extends('theme-views.layouts.app')

@php
    $cart = \App\Utils\CartManager::get_cart();
    $subTotal = \App\Utils\CartManager::cart_total_with_tax($cart);
@endphp

@section('title', translate('Shopping Cart') . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 32px 16px 64px;">
    
    <div class="vm-section-header">
        <h1 class="vm-section-title">{{ translate('Your Shopping Cart') }} ({{ $cart->count() }})</h1>
        <a href="{{ route('products') }}" class="vm-section-link">← {{ translate('Continue Shopping') }}</a>
    </div>

    @if($cart->count() > 0)
        <div style="display: grid; grid-template-columns: 1fr; gap: 28px;">
            <div style="display: grid; grid-template-columns: 1fr; gap: 24px;">
                @if(count($cart) > 0)
                    <div style="background: var(--vm-surface); border: 1px solid var(--vm-border); border-radius: var(--vm-radius-lg); overflow: hidden;">
                        @foreach($cart as $item)
                            @php
                                $product = $item->product;
                            @endphp
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 20px; border-bottom: 1px solid var(--vm-border-light); flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 16px;">
                                    <img src="{{ getStorageImages(path: $item->product?->thumbnail_full_url, type: 'product') }}" 
                                         alt="{{ $item->name }}" 
                                         style="width: 64px; height: 64px; border-radius: var(--vm-radius-sm); object-fit: contain; background: #FAFAFA; border: 1px solid var(--vm-border);">
                                    <div>
                                        <h3 style="font-size: 15px; font-weight: 700; color: var(--vm-dark); margin-bottom: 4px;">
                                            {{ $item->name }}
                                        </h3>
                                        <div style="font-size: 13px; color: var(--vm-text-muted);">
                                            {{ webCurrencyConverter($item->price) }} × {{ $item->quantity }}
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 20px;">
                                    <span style="font-size: 16px; font-weight: 800; color: var(--vm-primary);">
                                        {{ webCurrencyConverter($item->price * $item->quantity) }}
                                    </span>
                                    <a href="{{ route('cart.remove', ['key' => $item->id]) }}" style="color: var(--vm-danger); font-size: 13px; font-weight: 600;">
                                        ✕ {{ translate('Remove') }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Cart Summary & Checkout CTA -->
            <div style="background: var(--vm-surface); border: 1px solid var(--vm-border); border-radius: var(--vm-radius-lg); padding: 24px; max-width: 480px; margin-left: auto; width: 100%;">
                <h3 style="font-size: 18px; font-weight: 800; color: var(--vm-dark); margin-bottom: 16px;">
                    {{ translate('Order Summary') }}
                </h3>

                <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 10px;">
                    <span style="color: var(--vm-text-muted);">{{ translate('Items Subtotal') }}:</span>
                    <span style="font-weight: 700; color: var(--vm-dark);">{{ webCurrencyConverter($subTotal) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 16px;">
                    <span style="color: var(--vm-text-muted);">{{ translate('Delivery Fee') }}:</span>
                    <span style="font-size: 12px; color: var(--vm-text-muted);">{{ translate('Calculated by destination LGA at checkout') }}</span>
                </div>

                <div style="border-top: 2px solid var(--vm-border); padding-top: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: baseline;">
                    <span style="font-size: 16px; font-weight: 800; color: var(--vm-dark);">{{ translate('Estimated Total') }}:</span>
                    <span style="font-size: 24px; font-weight: 800; color: var(--vm-primary);">{{ webCurrencyConverter($subTotal) }}</span>
                </div>

                <a href="{{ route('checkout-details') }}" class="vm-btn-primary" style="font-size: 16px; padding: 14px;">
                    <span>🔒 {{ translate('Proceed to Secure Checkout') }}</span>
                </a>
            </div>
        </div>
    @else
        <div style="text-align: center; padding: 80px 20px; background: #FFFFFF; border-radius: var(--vm-radius-md); border: 1px solid var(--vm-border);">
            <div style="font-size: 48px; margin-bottom: 16px;">🛒</div>
            <h2 style="font-size: 20px; font-weight: 800; color: var(--vm-dark); margin-bottom: 8px;">
                {{ translate('Your shopping cart is currently empty') }}
            </h2>
            <p style="font-size: 14px; color: var(--vm-text-muted); margin-bottom: 24px;">
                {{ translate('Discover verified merchants and quality products available in Akwa Ibom.') }}
            </p>
            <a href="{{ route('products') }}" class="vm-btn-primary" style="display: inline-flex; width: auto; padding: 12px 28px;">
                {{ translate('Start Shopping Now') }}
            </a>
        </div>
    @endif

</div>
@endsection
