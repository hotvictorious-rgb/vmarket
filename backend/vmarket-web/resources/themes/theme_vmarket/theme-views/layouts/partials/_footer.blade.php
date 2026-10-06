@php
    $companyName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
    $companyEmail = getWebConfig(name: 'company_email');
    $companyPhone = getWebConfig(name: 'company_phone');
    $companyAddress = getWebConfig(name: 'shop_address') ?: getWebConfig(name: 'company_address');
    $footerLogo = !empty($web_config['footer_logo']['status']) ? $web_config['footer_logo']['path'] : (!empty($web_config['web_logo']['status']) ? $web_config['web_logo']['path'] : theme_asset('assets/img/vm_icon.jpg'));
    $copyrightText = getWebConfig(name: 'company_copyright_text');
@endphp

<footer class="vm-footer">
    <div class="vm-container">
        <div class="vm-footer-grid">
            <!-- Brand Column -->
            <div>
                @php
                    $footerNameParts = explode(' ', trim($companyName), 2);
                    $footerFirstWord = $footerNameParts[0] ?? 'Victorious';
                    $footerSecondWord = $footerNameParts[1] ?? 'MARKET';
                @endphp
                <a href="{{ route('home') }}" class="vm-brand" style="margin-bottom: 16px;" title="{{ $companyName }}">
                    <div class="vm-brand-pill">
                        <img src="{{ $footerLogo }}" alt="{{ $companyName }}" class="vm-brand-icon-sq" loading="lazy">
                        <span class="vm-brand-wordmark">
                            <span class="vm-word-victorious">{{ $footerFirstWord }}</span>
                            @if(!empty($footerSecondWord))
                                <span class="vm-word-market">{{ $footerSecondWord }}</span>
                            @endif
                        </span>
                    </div>
                </a>
                <p style="font-size: 13.5px; line-height: 1.6; margin-bottom: 16px;">
                    {{ translate('Nigeria’s omnichannel marketplace and delivery logistics ecosystem. Connecting verified merchants across Akwa Ibom with guaranteed in-shop inspection and swift door-to-door delivery.') }}
                </p>
                @if($companyAddress)
                    <div style="font-size: 13.5px; line-height: 1.5; color: #CBD5E1; margin-bottom: 14px; display: flex; align-items: flex-start; gap: 8px;">
                        <span style="font-size: 16px; line-height: 1.2;">🏢</span>
                        <span><strong style="color: #FFFFFF;">{{ translate('Head Office') }}:</strong> {{ $companyAddress }}</span>
                    </div>
                @endif
                <div style="display: flex; flex-wrap: wrap; gap: 14px; font-size: 13px;">
                    @if($companyPhone)
                        <a href="tel:{{ $companyPhone }}" style="color: #94A3B8; display: inline-flex; align-items: center; gap: 6px;">
                            <span>📞</span> {{ $companyPhone }}
                        </a>
                    @endif
                    @if($companyEmail)
                        <a href="mailto:{{ $companyEmail }}" style="color: #94A3B8; display: inline-flex; align-items: center; gap: 6px;">
                            <span>✉️</span> {{ $companyEmail }}
                        </a>
                    @endif
                </div>
            </div>

            <!-- Discovery Column -->
            <div>
                <h4 class="vm-footer-title">{{ translate('Quick Discovery') }}</h4>
                <div class="vm-footer-links">
                    <a href="{{ route('products') }}">{{ translate('All Catalog Products') }}</a>
                    <a href="{{ route('categories') }}">{{ translate('Product Categories') }}</a>
                    <a href="{{ route('vendors') }}">{{ translate('Verified Merchants') }}</a>
                    @if((int)($web_config['brand_setting'] ?? 0) === 1)
                        <a href="{{ route('brands') }}">{{ translate('Official Brands') }}</a>
                    @endif
                </div>
            </div>

            <!-- Customer Care -->
            <div>
                <h4 class="vm-footer-title">{{ translate('Customer Care') }}</h4>
                <div class="vm-footer-links">
                    <a href="{{ route('contacts') }}">{{ translate('Contact & Help Center') }}</a>
                    <a href="{{ route('helpTopic') }}">{{ translate('Frequently Asked Questions') }}</a>
                    @foreach(($web_config['business_pages'] ?? []) as $businessPageLink)
                        @if((int)($businessPageLink['status'] ?? 0) === 1)
                            <a href="{{ route('business-page.view', [$businessPageLink['slug']]) }}">{{ $businessPageLink['title'] }}</a>
                        @endif
                    @endforeach
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
                {{ $copyrightText ?: ('© ' . date('Y') . ' ' . $companyName . '. ' . translate('All rights reserved.')) }}
            </div>
            <div style="display: flex; gap: 16px;">
                <span>🔒 {{ translate('SSL 256-Bit Encrypted') }}</span>
                <span>🇳🇬 {{ translate('Made for Nigeria') }}</span>
            </div>
        </div>
    </div>
</footer>
