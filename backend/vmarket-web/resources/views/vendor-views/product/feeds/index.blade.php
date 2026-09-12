@extends('layouts.vendor.app')

@section('title', translate('Product_Feeds_&_External_Channels'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h1 mb-0 d-flex gap-2 align-items-center">
                <i class="fi fi-sr-sitemap text-primary"></i>
                {{ translate('Product_Feeds_&_Channel_Hub') }}
            </h2>
            <p class="text-muted mb-0 small">
                {{ translate('Manage_your_one_authoritative_catalogue_and_sync_with_Google_Merchant,_Meta_(Facebook/Instagram),_TikTok,_and_WhatsApp.') }}
            </p>
        </div>
        <div>
            <a href="{{ route('vendor-store', $shop->slug) }}" target="_blank" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-arrow-up-right-from-square"></i> {{ translate('View_Live_Public_Store') }}
            </a>
        </div>
    </div>

    <!-- Overview Stats Banner -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-primary text-white shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 text-uppercase fs-12 fw-bold">{{ translate('Total_Catalog_Products') }}</div>
                        <h2 class="text-white font-weight-bold mb-0 mt-1">{{ number_format($totalProducts) }}</h2>
                    </div>
                    <i class="fi fi-sr-boxes fs-36 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-success text-white shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 text-uppercase fs-12 fw-bold">{{ translate('Active_Live_Products') }}</div>
                        <h2 class="text-white font-weight-bold mb-0 mt-1">{{ number_format($activeProducts) }}</h2>
                    </div>
                    <i class="fi fi-sr-badge-check fs-36 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-warning text-white shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 text-uppercase fs-12 fw-bold">{{ translate('Out_of_Stock') }}</div>
                        <h2 class="text-white font-weight-bold mb-0 mt-1">{{ number_format($outOfStockProducts) }}</h2>
                    </div>
                    <i class="fi fi-sr-exclamation fs-36 text-white-50"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-body bg-dark text-white shadow-sm border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 text-uppercase fs-12 fw-bold">{{ translate('Marketplace_Status') }}</div>
                        <h4 class="text-white font-weight-bold mb-0 mt-1 text-capitalize">
                            {{ $seller->marketplace_status ?: 'Approved' }} 🛡️
                        </h4>
                    </div>
                    <i class="fi fi-sr-shield-check fs-36 text-white-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Digital Storefront Sharing Hub -->
    @php($storeUrl = route('vendor-store', $shop->slug))
    @php($shareText = urlencode('Check out our official online store ' . $shop->name . ' on Victorious MARKET: ' . $storeUrl))
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light d-flex align-items-center justify-content-between py-3">
            <h4 class="card-title mb-0 d-flex align-items-center gap-2">
                <i class="fi fi-sr-shop text-primary"></i>
                {{ translate('Your_Public_Digital_Storefront_Link') }}
            </h4>
            <span class="badge bg-success text-white px-3 py-1 fs-12">{{ translate('Live_&_Shareable') }}</span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                {{ translate('Every_product_you_publish_in_Victorious_MARKET_is_automatically_featured_on_your_dedicated_storefront._Share_this_clean_URL_with_your_customers_on_WhatsApp,_Instagram,_and_social_media.') }}
            </p>
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <label class="form-label text-muted small fw-bold text-uppercase">{{ translate('Vanity_Store_URL') }}</label>
                    <div class="input-group">
                        <input type="text" class="form-control font-monospace font-weight-bold text-primary" value="{{ $storeUrl }}" id="storefront-url" readonly>
                        <button class="btn btn-primary" type="button" onclick="navigator.clipboard.writeText('{{ $storeUrl }}'); toastr.success('{{ translate('store_link_copied_to_clipboard') }}');">
                            <i class="fi fi-sr-copy"></i> {{ translate('Copy_Link') }}
                        </button>
                    </div>
                </div>
                <div class="col-lg-5">
                    <label class="form-label text-muted small fw-bold text-uppercase d-block">{{ translate('Quick_Social_Share') }}</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="https://api.whatsapp.com/send?text={{ $shareText }}" target="_blank" class="btn btn-success btn-sm d-flex align-items-center gap-1">
                            <i class="tio-whatsapp"></i> {{ translate('Share_on_WhatsApp') }}
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($storeUrl) }}" target="_blank" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                            <i class="tio-facebook"></i> {{ translate('Facebook') }}
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" data-toggle="modal" data-target="#storeQrModal">
                            <i class="fi fi-sr-qrcode"></i> {{ translate('Store_QR_Code') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Multi-Channel Feeds (Google, Meta, TikTok) -->
    <div class="row g-3 mb-4">
        <!-- Google Merchant Center Feed -->
        @php($googleFeedUrl = url('/api/v1/feed/google-merchant.xml?token=' . $feedToken))
        <div class="col-lg-4">
            <div class="card h-100 border-top border-4 border-danger shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-danger p-2"><i class="fi fi-sr-shopping-bag text-white fs-5"></i></span>
                            <h4 class="mb-0">{{ translate('Google_Shopping') }}</h4>
                        </div>
                        <p class="text-muted small">
                            {{ translate('Vendor-isolated_Google_Merchant_Center_RSS_2.0_XML_feed._Includes_automated_prices,_stock_levels,_discounts,_GTIN,_and_MPN.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ translate('Your_Google_Feed_URL') }}</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" value="{{ $googleFeedUrl }}" id="google-feed-url" readonly>
                                <button class="btn btn-outline-danger" type="button" onclick="navigator.clipboard.writeText('{{ $googleFeedUrl }}'); toastr.success('{{ translate('google_feed_url_copied') }}');">
                                    <i class="fi fi-sr-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted mt-1 d-block">{{ translate('Hard-locked_to_your_products_only.') }}</small>
                        </div>
                    </div>
                    <div class="pt-3 border-top d-flex gap-2">
                        <a href="{{ $googleFeedUrl }}" target="_blank" class="btn btn-outline-danger btn-sm flex-grow-1">
                            <i class="fi fi-sr-arrow-up-right-from-square"></i> {{ translate('Preview_XML') }}
                        </a>
                        <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#googleGuideModal" title="{{ translate('Setup_Instructions') }}">
                            <i class="fi fi-sr-info"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Meta (Facebook & Instagram) Catalog Feed -->
        @php($metaFeedUrl = url('/api/v1/feed/facebook-catalog.csv?token=' . $feedToken))
        <div class="col-lg-4">
            <div class="card h-100 border-top border-4 border-primary shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary p-2"><i class="fi fi-sr-shop text-white fs-5"></i></span>
                            <h4 class="mb-0">{{ translate('Meta_Catalog_(FB_&_IG)') }}</h4>
                        </div>
                        <p class="text-muted small">
                            {{ translate('Meta_Commerce_Manager_Data_Feed_CSV._Supports_Instagram_Shopping_tagging,_Facebook_Shop,_and_dynamic_retargeting_ads.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ translate('Your_Meta_Feed_URL') }}</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" value="{{ $metaFeedUrl }}" id="meta-feed-url" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText('{{ $metaFeedUrl }}'); toastr.success('{{ translate('meta_feed_url_copied') }}');">
                                    <i class="fi fi-sr-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted mt-1 d-block">{{ translate('Updates_automatically_when_prices/stock_change.') }}</small>
                        </div>
                    </div>
                    <div class="pt-3 border-top d-flex gap-2">
                        <a href="{{ $metaFeedUrl }}" target="_blank" class="btn btn-outline-primary btn-sm flex-grow-1">
                            <i class="fi fi-sr-download"></i> {{ translate('Download_CSV') }}
                        </a>
                        <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#metaGuideModal" title="{{ translate('Setup_Instructions') }}">
                            <i class="fi fi-sr-info"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TikTok Shop / Ads Catalog Feed -->
        @php($tiktokFeedUrl = url('/api/v1/feed/tiktok-catalog.csv?token=' . $feedToken))
        <div class="col-lg-4">
            <div class="card h-100 border-top border-4 border-dark shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-dark p-2"><i class="fi fi-sr-video-camera text-white fs-5"></i></span>
                            <h4 class="mb-0">{{ translate('TikTok_Shop_Catalog') }}</h4>
                        </div>
                        <p class="text-muted small">
                            {{ translate('TikTok_Ads_Manager_standard_catalog_CSV._Format-optimized_for_TikTok_video_shopping_ads_and_influencer_linking.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ translate('Your_TikTok_Feed_URL') }}</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" value="{{ $tiktokFeedUrl }}" id="tiktok-feed-url" readonly>
                                <button class="btn btn-outline-dark" type="button" onclick="navigator.clipboard.writeText('{{ $tiktokFeedUrl }}'); toastr.success('{{ translate('tiktok_feed_url_copied') }}');">
                                    <i class="fi fi-sr-copy"></i>
                                </button>
                            </div>
                            <small class="text-muted mt-1 d-block">{{ translate('Synchronized_with_Victorious_MARKET.') }}</small>
                        </div>
                    </div>
                    <div class="pt-3 border-top">
                        <a href="{{ $tiktokFeedUrl }}" target="_blank" class="btn btn-outline-dark btn-sm w-100">
                            <i class="fi fi-sr-download"></i> {{ translate('Download_CSV') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. WhatsApp Integration Status & Security Credentials -->
    <div class="row g-3">
        <!-- WhatsApp Messaging Channel Status -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-light py-3">
                    <h4 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="tio-whatsapp text-success fs-4"></i>
                        {{ translate('WhatsApp_Business_Channel') }}
                    </h4>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        {{ translate('Victorious_MARKET_operates_an_integrated_Meta_WhatsApp_Cloud_API_gateway_for_instant_order_lifecycle_notifications,_delivery_updates,_and_customer_inquiries.') }}
                    </p>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fi fi-sr-bell text-primary mr-2"></i> {{ translate('Instant_New_Order_Alerts') }}</span>
                            <span class="badge badge-soft-success">{{ translate('Enabled') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fi fi-sr-truck-side text-primary mr-2"></i> {{ translate('Rider_Dispatch_&_Pickup_Notices') }}</span>
                            <span class="badge badge-soft-success">{{ translate('Enabled') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fi fi-sr-money text-primary mr-2"></i> {{ translate('Payout_Approval_Confirmations') }}</span>
                            <span class="badge badge-soft-success">{{ translate('Enabled') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fi fi-sr-shopping-cart text-primary mr-2"></i> {{ translate('Interactive_Catalog_Messages') }}</span>
                            <span class="badge badge-soft-info">{{ translate('Cloud_API_Ready') }}</span>
                        </li>
                    </ul>
                    <div class="alert alert-soft-success mb-0 d-flex align-items-center gap-2">
                        <i class="fi fi-sr-check-circle fs-5"></i>
                        <small>{{ translate('Your_storefront_customers_can_chat_directly_with_you_using_the_WhatsApp_chat_button.') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Token Security & Regeneration -->
        <div class="col-lg-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="fi fi-sr-lock text-warning"></i>
                        {{ translate('Feed_Security_&_Token_Rotation') }}
                    </h4>
                    <span class="badge bg-success text-white px-2 py-1 fs-12">{{ translate('Active_&_Encrypted') }}</span>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <p class="text-muted small">
                            {{ translate('Your_feeds_are_protected_by_a_cryptographically_random_feed_token._This_token_strictly_proves_your_store_ownership_and_guarantees_other_merchants_cannot_view_your_data.') }}
                        </p>
                        <div class="bg-light p-3 rounded mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small font-weight-bold text-uppercase">{{ translate('Masked_Token_Preview') }}:</span>
                                <span class="badge badge-soft-secondary font-monospace">{{ $maskedToken }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small font-weight-bold text-uppercase">{{ translate('Last_Generated') }}:</span>
                                <span class="text-dark small">{{ $seller->feed_token_generated_at ? $seller->feed_token_generated_at->diffForHumans() : translate('Never') }}</span>
                            </div>
                        </div>
                        <div class="alert alert-soft-warning mb-3">
                            <small>
                                <i class="fi fi-sr-shield-exclamation mr-1"></i>
                                {{ translate('If_you_suspect_unauthorized_access,_regenerate_your_token_immediately._All_previous_feed_URLs_will_be_permanently_revoked.') }}
                            </small>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-danger w-100" data-toggle="modal" data-target="#regenerateTokenModal">
                            <i class="fi fi-sr-refresh"></i> {{ translate('Regenerate_Secret_Feed_Token') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Store QR Code -->
<div class="modal fade" id="storeQrModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content text-center p-4">
            <h4 class="mb-2">{{ $shop->name }}</h4>
            <p class="text-muted small mb-3">{{ translate('Scan_to_open_official_digital_storefront') }}</p>
            <div class="d-flex justify-content-center mb-3">
                @php($qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . urlencode($storeUrl))
                <img src="{{ $qrUrl }}" alt="Storefront QR Code" class="img-fluid border p-2 rounded" style="max-width: 240px;">
            </div>
            <p class="font-monospace small text-muted text-break mb-3">{{ $storeUrl }}</p>
            <div class="d-flex gap-2 justify-content-center">
                <a href="{{ $qrUrl }}" download="{{ Str::slug($shop->name) }}-qr-code.png" class="btn btn-primary btn-sm">
                    <i class="fi fi-sr-download"></i> {{ translate('Download_QR_Image') }}
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ translate('Close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Google Guide -->
<div class="modal fade" id="googleGuideModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-4">
            <h4 class="modal-title mb-3 d-flex align-items-center gap-2">
                <i class="fi fi-sr-shopping-bag text-danger"></i> {{ translate('Connecting_to_Google_Merchant_Center') }}
            </h4>
            <ol class="small text-muted mb-3 pl-3">
                <li class="mb-2">{{ translate('Create_or_sign_in_to_your_Google_Merchant_Center_account.') }}</li>
                <li class="mb-2">{{ translate('Go_to_Products_>') }} <strong>{{ translate('Data_sources') }}</strong> > <strong>{{ translate('Add_a_product_source') }}</strong>.</li>
                <li class="mb-2">{{ translate('Select') }} <strong>{{ translate('Scheduled_fetch_(RSS/XML)') }}</strong>.</li>
                <li class="mb-2">{{ translate('Paste_your_unique_Google_Feed_URL_from_this_dashboard.') }}</li>
                <li>{{ translate('Set_fetch_frequency_to_Daily_or_Hourly._Google_will_automatically_index_your_products.') }}</li>
            </ol>
            <button type="button" class="btn btn-secondary btn-sm w-100" data-dismiss="modal">{{ translate('Understood') }}</button>
        </div>
    </div>
</div>

<!-- Modal: Meta Guide -->
<div class="modal fade" id="metaGuideModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-4">
            <h4 class="modal-title mb-3 d-flex align-items-center gap-2">
                <i class="fi fi-sr-shop text-primary"></i> {{ translate('Connecting_to_Meta_Commerce_Manager') }}
            </h4>
            <ol class="small text-muted mb-3 pl-3">
                <li class="mb-2">{{ translate('Sign_in_to_Meta_Commerce_Manager_at_business.facebook.com.') }}</li>
                <li class="mb-2">{{ translate('Open_your_Catalog_>') }} <strong>{{ translate('Data_sources') }}</strong> > <strong>{{ translate('Data_feed') }}</strong>.</li>
                <li class="mb-2">{{ translate('Select') }} <strong>{{ translate('Scheduled_feed') }}</strong>.</li>
                <li class="mb-2">{{ translate('Paste_your_unique_Meta_Feed_CSV_URL_and_save.') }}</li>
                <li>{{ translate('Products_will_synchronize_instantly_for_Instagram_tagging_and_Facebook_Shops.') }}</li>
            </ol>
            <button type="button" class="btn btn-secondary btn-sm w-100" data-dismiss="modal">{{ translate('Understood') }}</button>
        </div>
    </div>
</div>

<!-- Modal: Regenerate Token Confirmation -->
<div class="modal fade" id="regenerateTokenModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content p-4 text-center">
            <div class="mb-3">
                <i class="fi fi-sr-shield-exclamation text-danger fs-48"></i>
            </div>
            <h4 class="mb-2">{{ translate('Regenerate_Feed_Token?') }}</h4>
            <p class="text-muted small mb-4">
                {{ translate('This_will_immediately_revoke_your_current_token._Google_Merchant,_Meta,_and_TikTok_feeds_using_the_previous_URL_will_stop_updating_until_you_paste_the_new_URL.') }}
            </p>
            <form action="{{ route('vendor.products.feeds.regenerate-token') }}" method="POST">
                @csrf
                <div class="d-flex gap-2 justify-content-center">
                    <button type="submit" class="btn btn-danger btn-sm">{{ translate('Yes,_Regenerate_Token') }}</button>
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">{{ translate('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
