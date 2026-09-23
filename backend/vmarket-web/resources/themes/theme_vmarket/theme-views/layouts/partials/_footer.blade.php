@php
    $companyName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
    $companyEmail = getWebConfig(name: 'company_email') ?? 'support@victoriousmarket.com.ng';
    $companyPhone = getWebConfig(name: 'company_phone') ?? '+234 800 000 0000';
    $copyrightText = getWebConfig(name: 'company_copyright_text') ?? 'All rights reserved.';
@endphp

<footer class="vm-footer">
    <div class="vm-container">
        <div class="vm-footer-grid">
            <!-- Brand Column -->
            <div>
                <h4 style="color: #FFFFFF; font-size: 20px; font-weight: 800; margin-bottom: 12px;">
                    Victorious <span style="color: var(--vm-gold);">MARKET</span>
                </h4>
                <p style="font-size: 13.5px; line-height: 1.6; margin-bottom: 16px;">
                    {{ translate('Nigeria’s omnichannel marketplace and delivery logistics ecosystem. Connecting verified merchants across Akwa Ibom with guaranteed in-shop inspection and swift door-to-door delivery.') }}
                </p>
                <div style="display: flex; gap: 10px; font-size: 13px;">
                    <span>📍 {{ translate('Uyo, Akwa Ibom State, Nigeria') }}</span>
                </div>
            </div>

            <!-- Discovery Column -->
            <div>
                <h4 class="vm-footer-title">{{ translate('Quick Discovery') }}</h4>
                <div class="vm-footer-links">
                    <a href="{{ route('products') }}">{{ translate('All Catalog Products') }}</a>
                    <a href="{{ route('categories') }}">{{ translate('Product Categories') }}</a>
                    <a href="{{ route('vendors') }}">{{ translate('Verified Merchants') }}</a>
                    <a href="{{ route('brands') }}">{{ translate('Official Brands') }}</a>
                </div>
            </div>

            <!-- Customer Care -->
            <div>
                <h4 class="vm-footer-title">{{ translate('Customer Care') }}</h4>
                <div class="vm-footer-links">
                    <a href="{{ route('contacts') }}">{{ translate('Contact & Help Center') }}</a>
                    <a href="{{ route('helpTopic') }}">{{ translate('Frequently Asked Questions') }}</a>
                    <a href="{{ route('business-page.view', ['terms-and-conditions']) }}">{{ translate('Terms & Conditions') }}</a>
                    <a href="{{ route('business-page.view', ['privacy-policy']) }}">{{ translate('Privacy Policy') }}</a>
                </div>
            </div>

            <!-- Platform Guarantees -->
            <div>
                <h4 class="vm-footer-title">{{ translate('Platform Guarantees') }}</h4>
                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <span style="font-size: 18px;">🛡️</span>
                        <div>
                            <strong style="color: #FFFFFF;">{{ translate('Paystack Verified Escrow') }}</strong>
                            <p style="font-size: 12px; color: #94A3B8;">{{ translate('100% secure payments via card, bank transfer, and USSD.') }}</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <span style="font-size: 18px;">🏪</span>
                        <div>
                            <strong style="color: #FFFFFF;">{{ translate('In-Shop Inspection') }}</strong>
                            <p style="font-size: 12px; color: #94A3B8;">{{ translate('Reserve items and inspect physically before collection.') }}</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <span style="font-size: 18px;">⚡</span>
                        <div>
                            <strong style="color: #FFFFFF;">{{ translate('Directional LGA Delivery') }}</strong>
                            <p style="font-size: 12px; color: #94A3B8;">{{ translate('Predictable logistics rates and dedicated rider tracking.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="vm-footer-bottom">
            <div>
                © {{ date('Y') }} {{ $companyName }}. {{ $copyrightText }}
            </div>
            <div style="display: flex; gap: 16px;">
                <span>🔒 {{ translate('SSL 256-Bit Encrypted') }}</span>
                <span>🇳🇬 {{ translate('Made for Nigeria') }}</span>
            </div>
        </div>
    </div>
</footer>
