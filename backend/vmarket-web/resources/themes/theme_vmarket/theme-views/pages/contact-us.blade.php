@extends('theme-views.layouts.app')

@php
    $companyName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
    $companyEmail = getWebConfig(name: 'company_email') ?? 'support@victoriousmarket.com.ng';
    $companyPhone = getWebConfig(name: 'company_phone') ?? '+234 800 000 0000';
@endphp

@section('title', translate('Contact & Support') . ' | ' . $companyName)

@section('content')
<div class="vm-container" style="padding: 32px 16px 64px;">
    
    <div style="max-width: 780px; margin: 0 auto;">
        <div style="text-align: center; margin-bottom: 36px;">
            <h1 style="font-size: 28px; font-weight: 800; color: var(--vm-dark); margin-bottom: 8px;">
                {{ translate('Contact & Customer Support') }}
            </h1>
            <p style="font-size: 15px; color: var(--vm-text-muted);">
                {{ translate('Have questions about an order, merchant verification, in-shop inspection, or delivery? Reach out to our dedicated support team.') }}
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 36px;">
            <div style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 24px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 12px;">📍</div>
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">{{ translate('Headquarters') }}</h3>
                <p style="font-size: 13.5px; color: var(--vm-text-muted);">{{ translate('Uyo, Akwa Ibom State, Nigeria') }}</p>
            </div>
            <div style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 24px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 12px;">✉️</div>
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">{{ translate('Email Support') }}</h3>
                <a href="mailto:{{ $companyEmail }}" style="font-size: 13.5px; color: var(--vm-primary); font-weight: 600;">{{ $companyEmail }}</a>
            </div>
            <div style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 24px; text-align: center;">
                <div style="font-size: 32px; margin-bottom: 12px;">📞</div>
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">{{ translate('Customer Care') }}</h3>
                <a href="tel:{{ $companyPhone }}" style="font-size: 13.5px; color: var(--vm-primary); font-weight: 600;">{{ $companyPhone }}</a>
            </div>
        </div>

        <div style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-lg); padding: 32px;">
            <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 20px;">{{ translate('Send Us a Message') }}</h2>
            <form action="{{ route('contacts') }}" method="POST" style="display: flex; flex-direction: column; gap: 16px;">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">{{ translate('Your Name') }}</label>
                        <input type="text" name="name" required class="vm-search-input" style="border: 1px solid var(--vm-border); width: 100%; border-radius: var(--vm-radius-sm); padding: 10px 14px;">
                    </div>
                    <div>
                        <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">{{ translate('Your Email') }}</label>
                        <input type="email" name="email" required class="vm-search-input" style="border: 1px solid var(--vm-border); width: 100%; border-radius: var(--vm-radius-sm); padding: 10px 14px;">
                    </div>
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">{{ translate('Mobile Number') }}</label>
                    <input type="tel" name="mobile_number" required class="vm-search-input" style="border: 1px solid var(--vm-border); width: 100%; border-radius: var(--vm-radius-sm); padding: 10px 14px;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">{{ translate('Subject') }}</label>
                    <input type="text" name="subject" required class="vm-search-input" style="border: 1px solid var(--vm-border); width: 100%; border-radius: var(--vm-radius-sm); padding: 10px 14px;">
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">{{ translate('Message') }}</label>
                    <textarea name="message" rows="5" required style="border: 1px solid var(--vm-border); width: 100%; border-radius: var(--vm-radius-sm); padding: 10px 14px; font-family: inherit; font-size: 14px; resize: vertical;"></textarea>
                </div>
                <button type="submit" class="vm-btn-primary" style="padding: 12px 24px; font-size: 15px; width: fit-content; margin-top: 8px;">
                    {{ translate('Submit Message') }} →
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
