@extends('layouts.admin.app')

@section('title', translate('Product_Feeds_&_Social_Catalogs'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" class="mb-1 mr-1" alt="" width="20">
            {{ translate('Product_Feeds_&_Social_Catalogs') }}
        </h2>
        <p class="text-muted mb-0">
            {{ translate('live_streaming_catalog_feeds_for_Google_Shopping,_Facebook/Instagram_Commerce_Manager,_and_TikTok_Shop.') }}
        </p>
    </div>

    <!-- Security Token Card -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h4 class="mb-1 text-primary d-flex align-items-center gap-2">
                        <i class="fi fi-sr-shield-check"></i>
                        {{ translate('Feed_Security_Token') }}
                    </h4>
                    <p class="text-muted mb-0">
                        {{ translate('All_catalog_endpoints_are_secured_with_this_token_to_prevent_unauthorized_scraping.') }}
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group">
                        <input type="text" id="feed-token-input" class="form-control font-monospace" value="{{ $token }}" readonly style="max-width: 320px;">
                        <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $token }}'); toastr.success('{{ translate('token_copied_to_clipboard') }}');">
                            <i class="fi fi-sr-copy"></i> {{ translate('Copy') }}
                        </button>
                    </div>
                    <form action="{{ route('admin.products.product-feeds.regenerate-token') }}" method="POST" onsubmit="return confirm('{{ translate('are_you_sure_you_want_to_regenerate_the_feed_token?_You_will_need_to_update_your_Meta/Google_data_source_URLs.') }}');">
                        @csrf
                        <button type="submit" class="btn btn-danger text-nowrap">
                            <i class="fi fi-sr-refresh"></i> {{ translate('Regenerate_Token') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3 mb-3">
        <div class="col-sm-4">
            <div class="card bg-primary text-white">
                <div class="card-body py-3">
                    <h6 class="text-white-50 mb-1">{{ translate('Active_Live_Products') }}</h6>
                    <h3 class="mb-0 text-white font-weight-bold">{{ number_format($totalProducts) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card bg-info text-white">
                <div class="card-body py-3">
                    <h6 class="text-white-50 mb-1">{{ translate('In-House_Products') }}</h6>
                    <h3 class="mb-0 text-white font-weight-bold">{{ number_format($inhouseProducts) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card bg-success text-white">
                <div class="card-body py-3">
                    <h6 class="text-white-50 mb-1">{{ translate('Vendor_Products') }}</h6>
                    <h3 class="mb-0 text-white font-weight-bold">{{ number_format($vendorProducts) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Catalog Channels -->
    <div class="row g-3">
        <!-- 1. Google Merchant Center -->
        <div class="col-lg-4">
            <div class="card h-100 border-top border-4 border-danger">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-danger p-2"><i class="fi fi-sr-shopping-bag text-white fs-5"></i></span>
                            <h4 class="mb-0">{{ translate('Google_Shopping') }}</h4>
                        </div>
                        <p class="text-muted small">
                            {{ translate('Google_Merchant_Center_RSS_2.0_XML_feed_with_automated_pricing,_stock_levels,_and_sale_discounts.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ translate('Live_Feed_URL') }}</label>
                            @php($googleUrl = url('/api/v1/feed/google-merchant.xml') . '?token=' . $token)
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" value="{{ $googleUrl }}" id="google-feed-url" readonly>
                                <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('{{ $googleUrl }}'); toastr.success('{{ translate('google_feed_url_copied') }}');">
                                    <i class="fi fi-sr-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="pt-3 border-top">
                        <a href="{{ $googleUrl }}" target="_blank" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fi fi-sr-arrow-up-right-from-square"></i> {{ translate('Preview_XML_Feed') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Meta (Facebook & Instagram) Catalog -->
        <div class="col-lg-4">
            <div class="card h-100 border-top border-4 border-primary">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-primary p-2"><i class="fi fi-sr-shop text-white fs-5"></i></span>
                            <h4 class="mb-0">{{ translate('Meta_Catalog_(FB_&_IG)') }}</h4>
                        </div>
                        <p class="text-muted small">
                            {{ translate('Meta_Commerce_Manager_Data_Feed_CSV_supporting_dynamic_retargeting_ads_and_Instagram_Shopping.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ translate('Live_Feed_URL') }}</label>
                            @php($fbUrl = url('/api/v1/feed/facebook-catalog.csv') . '?token=' . $token)
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" value="{{ $fbUrl }}" id="fb-feed-url" readonly>
                                <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('{{ $fbUrl }}'); toastr.success('{{ translate('facebook_catalog_url_copied') }}');">
                                    <i class="fi fi-sr-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="pt-3 border-top">
                        <a href="{{ $fbUrl }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fi fi-sr-download"></i> {{ translate('Download_CSV_Feed') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. TikTok Catalog -->
        <div class="col-lg-4">
            <div class="card h-100 border-top border-4 border-dark">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-dark p-2"><i class="fi fi-sr-video-camera text-white fs-5"></i></span>
                            <h4 class="mb-0">{{ translate('TikTok_Catalog') }}</h4>
                        </div>
                        <p class="text-muted small">
                            {{ translate('TikTok_Ads_Manager_Catalog_CSV_formatted_for_TikTok_Shop_and_video_shopping_ads.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">{{ translate('Live_Feed_URL') }}</label>
                            @php($tiktokUrl = url('/api/v1/feed/tiktok-catalog.csv') . '?token=' . $token)
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-monospace" value="{{ $tiktokUrl }}" id="tiktok-feed-url" readonly>
                                <button class="btn btn-outline-primary" onclick="navigator.clipboard.writeText('{{ $tiktokUrl }}'); toastr.success('{{ translate('tiktok_catalog_url_copied') }}');">
                                    <i class="fi fi-sr-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="pt-3 border-top">
                        <a href="{{ $tiktokUrl }}" target="_blank" class="btn btn-outline-dark btn-sm w-100">
                            <i class="fi fi-sr-download"></i> {{ translate('Download_CSV_Feed') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructions & Filtering Guide -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">{{ translate('Query_Filter_Parameters_(Optional)') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>{{ translate('Parameter') }}</th>
                            <th>{{ translate('Example') }}</th>
                            <th>{{ translate('Description') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>&in_stock_only=1</code></td>
                            <td><code>{{ $googleUrl }}&in_stock_only=1</code></td>
                            <td>{{ translate('Only_include_products_with_inventory_quantity_greater_than_0.') }}</td>
                        </tr>
                        <tr>
                            <td><code>&scope=inhouse</code></td>
                            <td><code>{{ $googleUrl }}&scope=inhouse</code></td>
                            <td>{{ translate('Only_export_official_In-House_Victorious_MARKET_products.') }}</td>
                        </tr>
                        <tr>
                            <td><code>&scope=vendor</code></td>
                            <td><code>{{ $googleUrl }}&scope=vendor</code></td>
                            <td>{{ translate('Only_export_marketplace_vendor_products.') }}</td>
                        </tr>
                        <tr>
                            <td><code>&category_id={id}</code></td>
                            <td><code>{{ $googleUrl }}&category_id=5</code></td>
                            <td>{{ translate('Filter_by_a_specific_category_ID.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
