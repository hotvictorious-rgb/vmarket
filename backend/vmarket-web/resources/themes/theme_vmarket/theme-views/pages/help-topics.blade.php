@extends('theme-views.layouts.app')

@section('title', translate('Frequently Asked Questions') . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 32px 16px 64px; max-width: 900px;">
    
    <div style="text-align: center; margin-bottom: 36px;">
        <h1 style="font-size: 28px; font-weight: 800; color: var(--vm-dark); margin-bottom: 8px;">
            {{ translate('Frequently Asked Questions (FAQ)') }}
        </h1>
        <p style="font-size: 14.5px; color: var(--vm-text-muted);">
            {{ translate('Everything you need to know about purchasing, inspecting, and receiving deliveries on Victorious MARKET.') }}
        </p>
    </div>

    @if(isset($helps) && count($helps) > 0)
        <div style="display: flex; flex-direction: column; gap: 16px;">
            @foreach($helps as $help)
                <div style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 20px 24px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: var(--vm-dark); margin-bottom: 8px;">
                        Q: {{ $help['question'] }}
                    </h3>
                    <p style="font-size: 14px; line-height: 1.6; color: var(--vm-text-muted);">
                        {{ $help['answer'] }}
                    </p>
                </div>
            @endforeach
        </div>
    @else
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 20px 24px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--vm-dark); margin-bottom: 8px;">
                    {{ translate('How does In-Shop Pickup & Physical Inspection work?') }}
                </h3>
                <p style="font-size: 14px; line-height: 1.6; color: var(--vm-text-muted);">
                    {{ translate('You can reserve items online from verified local merchants for up to 24 hours without paying upfront. Visit the physical merchant shop in Uyo or other locations, inspect the item in person, and if satisfied, complete your secure payment.') }}
                </p>
            </div>
            <div style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 20px 24px;">
                <h3 style="font-size: 16px; font-weight: 700; color: var(--vm-dark); margin-bottom: 8px;">
                    {{ translate('How is delivery fee calculated?') }}
                </h3>
                <p style="font-size: 14px; line-height: 1.6; color: var(--vm-text-muted);">
                    {{ translate('Delivery fees are authoritative and directional based on Origin LGA and Destination LGA. For example, intra-LGA deliveries in Uyo have fixed, predictable rates, while inter-LGA deliveries are calculated automatically at checkout.') }}
                </p>
            </div>
        </div>
    @endif

</div>
@endsection
