@php
    use App\Utils\Helpers;
    use App\Enums\EmailTemplateKey;
    $eCommerceLogo = getWebConfig(name: 'company_web_logo');
@endphp

<style>
    .aside {
        display: flex !important;
        flex-direction: column !important;
        height: 100vh !important;
        max-height: 100vh !important;
    }
    .aside-header {
        flex-shrink: 0 !important;
    }
    .aside-body {
        flex: 1 1 auto !important;
        height: calc(100vh - 52px) !important;
        max-height: calc(100vh - 52px) !important;
        overflow-y: auto !important;
    }
    .aside-nav {
        padding-bottom: 80px !important;
    }
</style>

<aside class="js-aside aside d-none d-lg-block">
    <div class="aside-header d-flex align-items-center gap-2 justify-content-between">
        <a class="navbar-logo" href="{{ route('admin.dashboard.index') }}">
            <img height="24" src="{{ getStorageImages(path: $eCommerceLogo, type: 'backend-logo') }}"
                 alt="{{ translate('logo') }}">
        </a>
        <button type="button" class="js-aside-toggle navbar-aside-toggle btn-icon border-0">
            <i class="fi fi-rr-menu-burger"></i>
        </button>
    </div>
    <div class="aside-body search-aside-attribute-container py-4 pt-0">
        <div class="aside-search-form pt-lg-3 pb-3">
            <div class="input-group flex-nowrap">
                <input type="text" class="form-control search-aside-attribute"
                       placeholder="{{ translate('search_menu') }}">
                <span class="input-group-text"><i class="fi fi-rr-search"></i></span>
            </div>
        </div>

        <ul class="aside-nav navbar-nav gap-2">
            {{-- Dashboard --}}
            <li>
                <a class="nav-link {{ Request::is('admin/dashboard') ? 'active' : '' }}"
                   title="{{ translate('dashboard') }}" href="{{ route('admin.dashboard.index') }}">
                    <i class="fi fi-sr-home"></i>
                    <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                        {{ translate('dashboard') }}
                    </span>
                </a>
            </li>

            {{-- ======================================================================== --}}
            {{-- PILLAR 1: OPERATIONS & ORDERS                                             --}}
            {{-- ======================================================================== --}}
            @if(Helpers::module_permission_check('order_management'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/orders*') || Request::is('admin/refund-section*')) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="{{ translate('operations_&_orders') }}">{{ translate('operations_&_orders') }}</small>
                </li>

                {{-- Orders Submenu --}}
                <li class="{{ Request::is('admin/orders*') && !Request::is('admin/orders/details/*') ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ Request::is('admin/orders*') ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('orders') }}">
                        <i class="fi fi-sr-shopping-cart"></i>
                        <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('orders') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('orders') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/list/all') ? 'active' : '' }}"
                               href="{{ route('admin.orders.list', ['all']) }}" title="{{ translate('all') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('all') }}</span>
                                <span class="badge fw-bold badge-info badge-sm text-bg-info">
                                    {{ \App\Models\Order::count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/list/pending') ? 'active' : '' }}"
                               href="{{ route('admin.orders.list',['pending']) }}" title="{{ translate('pending') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('pending') }}</span>
                                <span class="badge fw-bold badge-info badge-sm text-bg-info">
                                    {{ \App\Models\Order::where(['order_status'=>'pending'])->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/list/confirmed') ? 'active' : '' }}"
                               href="{{ route('admin.orders.list',['confirmed']) }}" title="{{ translate('confirmed') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('confirmed') }}</span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                    {{ \App\Models\Order::where(['order_status'=>'confirmed'])->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/list/processing') ? 'active' : '' }}"
                               href="{{ route('admin.orders.list',['processing']) }}" title="{{ translate('packaging') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('packaging') }}</span>
                                <span class="badge fw-bold badge-warning badge-sm text-bg-warning">
                                    {{ \App\Models\Order::where(['order_status'=>'processing'])->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/list/out_for_delivery') ? 'active' : '' }}"
                               href="{{ route('admin.orders.list',['out_for_delivery']) }}" title="{{ translate('out_for_delivery') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('out_for_delivery') }}</span>
                                <span class="badge fw-bold badge-warning badge-sm text-bg-warning">
                                    {{ \App\Models\Order::where(['order_status'=>'out_for_delivery'])->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/list/delivered') ? 'active' : '' }}"
                               href="{{ route('admin.orders.list',['delivered']) }}" title="{{ translate('delivered') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('delivered') }}</span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                    {{ \App\Models\Order::where(['order_status'=>'delivered'])->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/list/returned') ? 'active' : '' }}"
                               href="{{ route('admin.orders.list',['returned']) }}" title="{{ translate('returned') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('returned') }}</span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                    {{ \App\Models\Order::where(['order_status'=>'returned'])->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/list/failed') ? 'active' : '' }}"
                               href="{{ route('admin.orders.list',['failed']) }}" title="{{ translate('failed') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('failed_to_deliver') }}</span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                    {{ \App\Models\Order::where(['order_status'=>'failed'])->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/list/canceled') ? 'active' : '' }}"
                               href="{{ route('admin.orders.list',['canceled']) }}" title="{{ translate('canceled') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('canceled') }}</span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                    {{ \App\Models\Order::where(['order_status'=>'canceled'])->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/orders/pickup-list*') ? 'active' : '' }}"
                               href="{{ route('admin.orders.pickup-list') }}" title="{{ translate('in_shop_pickup') }}">
                                <span class="flex-grow-1 text-truncate fw-semibold text-primary">{{ translate('in_shop_pickup') }}</span>
                                <span class="badge fw-bold badge-primary badge-sm text-bg-primary">
                                    {{ \App\Models\Order::where(['order_type' => 'pickup'])->count() }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Refunds Submenu --}}
                <li class="{{ Request::is('admin/refund-section*') ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ Request::is('admin/refund-section*') ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('refund_Requests') }}">
                        <i class="fi fi-sr-undo-alt"></i>
                        <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('refund_Requests') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('refund_Requests') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/refund-section/refund/list/pending') ? 'active' : '' }}"
                               href="{{ route('admin.refund-section.refund.list',['pending']) }}" title="{{ translate('pending') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('pending') }}</span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                    {{ \App\Models\RefundRequest::where('status','pending')->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/refund-section/refund/list/approved') ? 'active' : '' }}"
                               href="{{ route('admin.refund-section.refund.list',['approved']) }}" title="{{ translate('approved') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('approved') }}</span>
                                <span class="badge fw-bold badge-info badge-sm text-bg-info">
                                    {{ \App\Models\RefundRequest::where('status','approved')->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/refund-section/refund/list/refunded') ? 'active' : '' }}"
                               href="{{ route('admin.refund-section.refund.list',['refunded']) }}" title="{{ translate('refunded') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('refunded') }}</span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                    {{ \App\Models\RefundRequest::where('status','refunded')->count() }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/refund-section/refund/list/rejected') ? 'active' : '' }}"
                               href="{{ route('admin.refund-section.refund.list',['rejected']) }}" title="{{ translate('rejected') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('rejected') }}</span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                    {{ \App\Models\RefundRequest::where('status','rejected')->count() }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            {{-- ======================================================================== --}}
            {{-- PILLAR 2: CATALOG & INVENTORY                                             --}}
            {{-- ======================================================================== --}}
            @if(Helpers::module_permission_check('product_management'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/products*') || Request::is('admin/category*') || Request::is('admin/sub*') || Request::is('admin/brand*') || Request::is('admin/category-specifications*') || Request::is('admin/attribute*')) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="{{ translate('catalog_&_inventory') }}">{{ translate('catalog_&_inventory') }}</small>
                </li>

                {{-- In-House Products Submenu --}}
                <li class="{{ (Request::is('admin/products/list/in-house*') || Request::is('admin/products/bulk-import') || Request::is('admin/products/request-restock-list') || Request::is('admin/products/add') || Request::is('admin/products/view/in-house/*') || Request::is('admin/products/barcode/*') || Request::is('admin/products/stock-limit-list/in_house')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/products/list/in-house*') || Request::is('admin/products/bulk-import') || Request::is('admin/products/request-restock-list') || Request::is('admin/products/add') || Request::is('admin/products/view/in-house/*') || Request::is('admin/products/barcode/*') || Request::is('admin/products/stock-limit-list/in_house')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('In_House_Products') }}">
                        <i class="fi fi-sr-box-open"></i>
                        <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('In_House_Products') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('In_House_Products') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ (Request::is('admin/products/list/in-house*') || Request::is('admin/products/view/in-house/*') || Request::is('admin/products/stock-limit-list/in-house*') || Request::is('admin/products/barcode/*')) ? 'active' : '' }}"
                               href="{{ route('admin.products.list', ['in-house']) }}" title="{{ translate('Product_List') }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('Product_List') }}</span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                    {{ getAdminProductsCount('all') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/products/add') ? 'active' : '' }}"
                               href="{{ route('admin.products.add') }}" title="{{ translate('add_New_Product') }}">
                                <span class="text-truncate">{{ translate('add_New_Product') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/products/stock-limit-list/in_house') ? 'active' : '' }}"
                               href="{{ route('admin.products.stock-limit-list', ['in_house']) }}" title="{{ translate('Limited_stock') }}">
                                <span class="text-truncate">{{ translate('Limited_Stock') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/products/request-restock-list') ? 'active' : '' }}"
                               href="{{ route('admin.products.request-restock-list') }}" title="{{ translate('Request_Restock_List') }}">
                                <span class="text-truncate">{{ translate('Request_Restock_List') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/products/bulk-import') ? 'active' : '' }}"
                               href="{{ route('admin.products.bulk-import') }}" title="{{ translate('bulk_import') }}">
                                <span class="text-truncate">{{ translate('Bulk_Import') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Vendor Products Submenu --}}
                <li class="{{ (Request::is('admin/products/list/vendor*') || Request::is('admin/products/view/vendor/*') || Request::is('admin/products/updated-product-list')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/products/list/vendor*') || Request::is('admin/products/view/vendor/*') || Request::is('admin/products/updated-product-list')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('vendor_Products') }}">
                        <i class="fi fi-sr-seller"></i>
                        <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('vendor_Products') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('vendor_Products') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ str_contains(url()->current().'?request_status='.request()->get('request_status'),'admin/products/list/vendor?request_status=0') ? 'active' : '' }}"
                               title="{{ translate('new_Products_Requests') }}"
                               href="{{ route('admin.products.list',['vendor', 'request_status'=>'0']) }}">
                                <span class="flex-grow-1 text-truncate">{{ Str::limit(translate('new_Products_Requests'), 18, '...') }}</span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                    {{ getVendorProductsCount('new-product') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-capitalize {{ Request::is('admin/products/updated-product-list') ? 'active' : '' }}"
                               title="{{ translate('product_update_requests') }}"
                               href="{{ route('admin.products.updated-product-list') }}">
                                <span class="flex-grow-1 text-truncate">{{ Str::limit(translate('product_update_requests'), 18, '...') }}</span>
                                <span class="badge fw-bold badge-info badge-sm text-bg-info">
                                    {{ getVendorProductsCount('product-updated-request') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ str_contains(url()->current().'?request_status='.request()->get('request_status'),'/admin/products/list/vendor?request_status=1') ? 'active' : '' }}"
                               title="{{ translate('approved_Products') }}"
                               href="{{ route('admin.products.list',['vendor', 'request_status'=>'1']) }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('approved_Products') }}</span>
                                <span class="badge fw-bold badge-success badge-sm text-bg-success">
                                    {{ getVendorProductsCount('approved') }}
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ str_contains(url()->current().'?request_status='.request()->get('request_status'),'/admin/products/list/vendor?request_status=2') ? 'active' : '' }}"
                               title="{{ translate('denied_Products') }}"
                               href="{{ route('admin.products.list',['vendor', 'request_status'=>'2']) }}">
                                <span class="flex-grow-1 text-truncate">{{ translate('denied_Products') }}</span>
                                <span class="badge fw-bold badge-danger badge-sm text-bg-danger">
                                    {{ getVendorProductsCount('denied') }}
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Product Gallery --}}
                <li>
                    <a class="nav-link {{ Request::is('admin/products/product-gallery*') ? 'active' : '' }}"
                       href="{{ route('admin.products.product-gallery') }}" title="{{ translate('Product_Gallery') }}">
                        <i class="fi fi-sr-boxes"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Product_Gallery') }}
                        </span>
                    </a>
                </li>

                {{-- Product Feeds & Catalogs --}}
                <li>
                    <a class="nav-link {{ Request::is('admin/products/product-feeds*') ? 'active' : '' }}"
                       href="{{ route('admin.products.product-feeds') }}" title="{{ translate('Product_Feeds_&_Catalogs') }}">
                        <i class="fi fi-sr-share"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Product_Feeds_&_Catalogs') }}
                        </span>
                    </a>
                </li>

                {{-- Categories & Specifications Submenu --}}
                <li class="{{ (Request::is('admin/category*') || Request::is('admin/sub-category*') || Request::is('admin/sub-sub-category*') || Request::is('admin/category-specifications*') || Request::is('admin/attribute*')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/category*') || Request::is('admin/sub-category*') || Request::is('admin/sub-sub-category*') || Request::is('admin/category-specifications*') || Request::is('admin/attribute*')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('Categories_&_Attributes') }}">
                        <i class="fi fi-sr-apps"></i>
                        <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('categories') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('categories') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/category/*') ? 'active' : '' }}"
                               href="{{ route('admin.category.view') }}" title="{{ translate('categories') }}">
                                <span class="text-truncate">{{ translate('categories') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/sub-category/*') ? 'active' : '' }}"
                               href="{{ route('admin.sub-category.view') }}" title="{{ translate('sub_Categories') }}">
                                <span class="text-truncate">{{ translate('sub_Categories') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/sub-sub-category/*') ? 'active' : '' }}"
                               href="{{ route('admin.sub-sub-category.view') }}" title="{{ translate('sub_Sub_Categories') }}">
                                <span class="text-truncate">{{ translate('sub_Sub_Categories') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/category-specifications*') ? 'active' : '' }}"
                               href="{{ route('admin.category-specifications.index') }}" title="{{ translate('Specifications_&_Questions') }}">
                                <span class="text-truncate font-weight-bold text-primary">{{ translate('Specifications') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/attribute*') ? 'active' : '' }}"
                               href="{{ route('admin.attribute.view') }}" title="{{ translate('product_Attribute_Setup') }}">
                                <span class="text-truncate">{{ translate('Attributes') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Brand Setup --}}
                <li>
                    <a class="nav-link {{ Request::is('admin/brand*') ? 'active' : '' }}"
                       href="{{ route('admin.brand.list') }}" title="{{ translate('brand_setup') }}">
                        <i class="fi fi-sr-brand"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Brand_Setup') }}
                        </span>
                    </a>
                </li>
            @endif

            {{-- ======================================================================== --}}
            {{-- PILLAR 3: LOGISTICS & FULFILLMENT (Canonical VMarket Infrastructure)       --}}
            {{-- ======================================================================== --}}
            @if(Helpers::module_permission_check('order_management') || Helpers::module_permission_check('user_section'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/delivery-lanes*') || Request::is('admin/delivery-hubs*') || Request::is('admin/dispatch-portal*') || (Request::is('admin/delivery-man*') && !Request::is('admin/delivery-man/withdraw*'))) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="{{ translate('logistics_&_fulfillment') }}">{{ translate('logistics_&_fulfillment') }}</small>
                </li>

                {{-- Batch Dispatch Console --}}
                <li>
                    <a class="nav-link {{ Request::is('admin/dispatch-portal*') ? 'active' : '' }}"
                       href="{{ route('admin.dispatch-portal.index') }}" title="{{ translate('Batch_Dispatch_Console') }}">
                        <i class="fi fi-sr-truck-side"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            <span class="fw-semibold">{{ translate('Dispatch_Portal') }}</span>
                        </span>
                    </a>
                </li>

                {{-- Delivery Lanes (Directional LGA -> LGA Pricing & ETA) --}}
                <li>
                    <a class="nav-link {{ Request::is('admin/delivery-lanes*') ? 'active' : '' }}"
                       href="{{ route('admin.delivery-lanes.index') }}" title="{{ translate('Delivery_Lanes') }}">
                        <i class="fi fi-sr-route"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Delivery_Lanes') }}
                        </span>
                    </a>
                </li>

                {{-- Logistics Hubs & Transfer Landmarks --}}
                <li>
                    <a class="nav-link {{ Request::is('admin/delivery-hubs*') ? 'active' : '' }}"
                       href="{{ route('admin.delivery-hubs.index') }}" title="{{ translate('Hubs_&_Landmarks') }}">
                        <i class="fi fi-sr-map-marker-home"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Hubs_&_Landmarks') }}
                        </span>
                    </a>
                </li>

                {{-- Delivery Riders Submenu --}}
                <li class="{{ (Request::is('admin/delivery-man/list') || Request::is('admin/delivery-man/add') || Request::is('admin/delivery-man/update*') || Request::is('admin/delivery-man/order-history-log*') || Request::is('admin/delivery-man/order-wise-earning*') || Request::is('admin/delivery-man/emergency-contact*')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle text-capitalize {{ (Request::is('admin/delivery-man/list') || Request::is('admin/delivery-man/add') || Request::is('admin/delivery-man/update*') || Request::is('admin/delivery-man/emergency-contact*')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('delivery_men') }}">
                        <i class="fi fi-sr-person-carry-box"></i>
                        <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('delivery_riders') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('delivery_riders') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/delivery-man/list') || Request::is('admin/delivery-man/update*') || Request::is('admin/delivery-man/order-history-log*') || Request::is('admin/delivery-man/order-wise-earning*') ? 'active' : '' }}"
                               href="{{ route('admin.delivery-man.list') }}" title="{{ translate('rider_list') }}">
                                <span class="text-truncate">{{ translate('rider_list') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/delivery-man/add') ? 'active' : '' }}"
                               href="{{ route('admin.delivery-man.add') }}" title="{{ translate('add_new_rider') }}">
                                <span class="text-truncate">{{ translate('add_new') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/delivery-man/emergency-contact*') ? 'active' : '' }}"
                               href="{{ route('admin.delivery-man.emergency-contact.index') }}" title="{{ translate('emergency_contact') }}">
                                <span class="text-truncate">{{ translate('Emergency_Contact') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            {{-- ======================================================================== --}}
            {{-- PILLAR 4: MERCHANTS & CUSTOMERS                                           --}}
            {{-- ======================================================================== --}}
            @if(Helpers::module_permission_check('user_section') || Helpers::module_permission_check('support_section'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/vendors*') || Request::is('admin/customer*') || Request::is('admin/reviews*') || Request::is('admin/support-ticket*') || Request::is('admin/contact*') || Request::is('admin/messages*')) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="{{ translate('merchants_&_customers') }}">{{ translate('merchants_&_customers') }}</small>
                </li>

                {{-- Vendors & Shops Submenu --}}
                @if(Helpers::module_permission_check('user_section'))
                    <li class="{{ (Request::is('admin/vendors/vendor-list') || Request::is('admin/vendors/add') || Request::is('admin/vendors/view*') || Request::is('admin/vendors/marketplace-applications*')) ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ (Request::is('admin/vendors/vendor-list') || Request::is('admin/vendors/add') || Request::is('admin/vendors/view*') || Request::is('admin/vendors/marketplace-applications*')) ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('vendors') }}">
                            <i class="fi fi-sr-shop"></i>
                            <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">{{ translate('vendors') }}</span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('vendors') }}</li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/vendors/marketplace-applications*') ? 'active' : '' }}"
                                   href="{{ route('admin.vendors.marketplace-applications') }}" title="{{ translate('Marketplace_Applications') }}">
                                    <span class="flex-grow-1 text-truncate">{{ translate('Marketplace_Applications') }}</span>
                                    @php($pendingApps = \App\Models\Seller::where('marketplace_status', 'pending_approval')->count())
                                    @if($pendingApps > 0)
                                        <span class="badge fw-bold badge-warning badge-sm text-bg-warning">{{ $pendingApps }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/vendors/vendor-list') || Request::is('admin/vendors/view*') ? 'active' : '' }}"
                                   title="{{ translate('vendor_List') }}" href="{{ route('admin.vendors.vendor-list') }}">
                                    <span class="text-truncate">{{ translate('vendor_List') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/vendors/add') ? 'active' : '' }}"
                                   title="{{ translate('add_New_Vendor') }}" href="{{ route('admin.vendors.add') }}">
                                    <span class="text-truncate">{{ translate('add_New_Vendor') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Customers Submenu --}}
                    <li class="{{ (Request::is('admin/customer/list') || Request::is('admin/customer/view*') || Request::is('admin/reviews*') || Request::is('admin/customer/subscriber-list')) ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ (Request::is('admin/customer/list') || Request::is('admin/customer/view*') || Request::is('admin/reviews*') || Request::is('admin/customer/subscriber-list')) ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('customers') }}">
                            <i class="fi fi-sr-user"></i>
                            <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">{{ translate('customers') }}</span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('customers') }}</li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/customer/list') || Request::is('admin/customer/view*') ? 'active' : '' }}"
                                   href="{{ route('admin.customer.list') }}" title="{{ translate('Customer_List') }}">
                                    <span class="text-truncate">{{ translate('customer_List') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/reviews*') ? 'active' : '' }}"
                                   href="{{ route('admin.reviews.list') }}" title="{{ translate('customer_Reviews') }}">
                                    <span class="text-truncate">{{ translate('customer_Reviews') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/customer/subscriber-list') ? 'active' : '' }}"
                                   href="{{ route('admin.customer.subscriber-list') }}" title="{{ translate('subscribers') }}">
                                    <span class="text-truncate">{{ translate('subscribers') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- Support & Communication Submenu --}}
                @if(Helpers::module_permission_check('support_section'))
                    <li class="{{ (Request::is('admin/support-ticket*') || Request::is('admin/contact*') || Request::is('admin/messages*')) ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ (Request::is('admin/support-ticket*') || Request::is('admin/contact*') || Request::is('admin/messages*')) ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('help_&_support') }}">
                            <i class="fi fi-sr-headphones"></i>
                            <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">{{ translate('help_&_support') }}</span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('help_&_support') }}</li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/messages*') ? 'active' : '' }}"
                                   title="{{ translate('inbox') }}"
                                   href="{{ route('admin.messages.index', ['type' => 'customer']) }}">
                                    <span class="text-truncate">{{ translate('inbox') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/contact*') ? 'active' : '' }}"
                                   href="{{ route('admin.contact.list') }}" title="{{ translate('messages') }}">
                                    <span class="flex-grow-1 text-truncate">{{ translate('messages') }}</span>
                                    @php($message=\App\Models\Contact::where('seen',0)->count())
                                    @if($message != 0)
                                        <span class="badge fw-bold badge-danger badge-sm text-bg-danger">{{ $message }}</span>
                                    @endif
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/support-ticket*') ? 'active' : '' }}"
                                   href="{{ route('admin.support-ticket.view') }}" title="{{ translate('support_Ticket') }}">
                                    <span class="flex-grow-1 text-truncate">{{ translate('support_Ticket') }}</span>
                                    @php($openTickets = \App\Models\SupportTicket::where('status','open')->count())
                                    @if($openTickets > 0)
                                        <span class="badge fw-bold badge-warning badge-sm text-bg-warning">{{ $openTickets }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            @endif

            {{-- ======================================================================== --}}
            {{-- PILLAR 5: FINANCE & SETTLEMENTS                                           --}}
            {{-- ======================================================================== --}}
            @if(Helpers::module_permission_check('user_section') || Helpers::module_permission_check('report'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/vendors/withdraw*') || Request::is('admin/delivery-man/withdraw*') || Request::is('admin/cashback*') || Request::is('admin/transaction*')) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="{{ translate('finance_&_settlements') }}">{{ translate('finance_&_settlements') }}</small>
                </li>

                {{-- Withdrawals & Payouts Submenu --}}
                <li class="{{ (Request::is('admin/vendors/withdraw*') || Request::is('admin/delivery-man/withdraw*')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/vendors/withdraw*') || Request::is('admin/delivery-man/withdraw*')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('withdrawals_&_payouts') }}">
                        <i class="fi fi-sr-money-bill-wave"></i>
                        <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('withdrawals') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('withdrawals') }}</li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/vendors/withdraw-list') || Request::is('admin/vendors/withdraw-view/*') ? 'active' : '' }}"
                               href="{{ route('admin.vendors.withdraw_list') }}" title="{{ translate('vendor_withdrawals') }}">
                                <span class="text-truncate">{{ translate('vendor_withdraws') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/vendors/withdraw-method/*') ? 'active' : '' }}"
                               href="{{ route('admin.vendors.withdraw-method.list') }}" title="{{ translate('withdrawal_Methods') }}">
                                <span class="text-truncate">{{ translate('withdrawal_Methods') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Request::is('admin/delivery-man/withdraw-list') || Request::is('admin/delivery-man/withdraw-view*') ? 'active' : '' }}"
                               href="{{ route('admin.delivery-man.withdraw-list') }}" title="{{ translate('rider_withdraws') }}">
                                <span class="text-truncate">{{ translate('rider_withdraws') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Victorious Points & Cashback Ledger --}}
                <li>
                    <a class="nav-link {{ Request::is('admin/cashback*') ? 'active' : '' }}"
                       href="{{ route('admin.cashback.index') }}" title="{{ translate('Victorious_Points_Ledger') }}">
                        <i class="fi fi-sr-coins"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('Victorious_Points_Ledger') }}
                        </span>
                    </a>
                </li>

                {{-- Transaction Ledger --}}
                @if(Helpers::module_permission_check('report'))
                    <li>
                        <a class="nav-link {{ Request::is('admin/transaction*') ? 'active' : '' }}"
                           href="{{ route('admin.transaction.order-transaction-list') }}" title="{{ translate('transaction_Report') }}">
                            <i class="fi fi-sr-receipt"></i>
                            <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                                {{ translate('transaction_Report') }}
                            </span>
                        </a>
                    </li>
                @endif
            @endif

            {{-- ======================================================================== --}}
            {{-- PILLAR 6: MARKETING & PROMOTIONS                                          --}}
            {{-- ======================================================================== --}}
            @if(Helpers::module_permission_check('promotion_management'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/banner*') || Request::is('admin/coupon*') || Request::is('admin/notification*') || Request::is('admin/push-notification*') || Request::is('admin/deal*') || Request::is('admin/business-settings/announcement*')) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="{{ translate('marketing_&_promotions') }}">{{ translate('marketing_&_promotions') }}</small>
                </li>

                {{-- Offers & Deals Submenu --}}
                <li class="{{ (Request::is('admin/coupon*') || Request::is('admin/deal*')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/coupon*') || Request::is('admin/deal*')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('offers_&_Deals') }}">
                        <i class="fi fi-sr-badge-percent"></i>
                        <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('offers_&_Deals') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('offers_&_Deals') }}</li>
                        <li>
                            <a class="nav-link {{ Request::is('admin/coupon*') ? 'active' : '' }}"
                               href="{{ route('admin.coupon.add') }}" title="{{ translate('coupon') }}">
                                <span class="text-truncate">{{ translate('coupon') }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ (Request::is('admin/deal/flash') || Request::is('admin/deal/flash-add') || Request::is('admin/deal/update*')) ? 'active' : '' }}"
                               href="{{ route('admin.deal.flash') }}" title="{{ translate('flash_Deals') }}">
                                <span class="text-truncate">{{ translate('flash_Deals') }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ (Request::is('admin/deal/day') || Request::is('admin/deal/day-update*')) ? 'active' : '' }}"
                               href="{{ route('admin.deal.day') }}" title="{{ translate('deal_of_the_day') }}">
                                <span class="text-truncate">{{ translate('deal_of_the_day') }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ (Request::is('admin/deal/feature') || Request::is('admin/deal/feature/new') || Request::is('admin/deal/feature-update*')) ? 'active' : '' }}"
                               href="{{ route('admin.deal.feature') }}" title="{{ translate('featured_Deal') }}">
                                <span class="text-truncate">{{ translate('featured_Deal') }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link {{ Request::is('admin/deal/clearance-sale*') ? 'active' : '' }}"
                               href="{{ route('admin.deal.clearance-sale.index') }}" title="{{ translate('Clearance_Sale') }}">
                                <span class="text-truncate">{{ translate('Clearance_Sale') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Banners Setup --}}
                <li>
                    <a class="nav-link {{ Request::is('admin/banner*') ? 'active' : '' }}"
                       href="{{ route('admin.banner.list') }}" title="{{ translate('banner_Setup') }}">
                        <i class="fi fi-sr-pennant"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('banner_Setup') }}
                        </span>
                    </a>
                </li>

                {{-- Announcements --}}
                <li>
                    <a class="nav-link {{ Request::is('admin/business-settings/announcement*') ? 'active' : '' }}"
                       href="{{ route('admin.business-settings.announcement') }}" title="{{ translate('announcement') }}">
                        <i class="fi fi-sr-megaphone-sound-waves"></i>
                        <span class="aside-mini-hidden-element text-truncate flex-grow-1">
                            {{ translate('announcement') }}
                        </span>
                    </a>
                </li>

                {{-- Notifications Submenu --}}
                <li class="{{ (Request::is('admin/notification*') || Request::is('admin/push-notification/index*')) ? 'sub-menu-opened' : '' }}">
                    <a class="nav-link nav-link-toggle {{ (Request::is('admin/notification*') || Request::is('admin/push-notification/index*')) ? 'active' : '' }}"
                       href="javascript:" title="{{ translate('notifications') }}">
                        <i class="fi fi-sr-paper-plane"></i>
                        <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                            <span class="text-truncate max-w-180">{{ translate('notifications') }}</span>
                            <i class="fi fi-sr-angle-down"></i>
                        </span>
                    </a>
                    <ul class="aside-submenu navbar-nav">
                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('notifications') }}</li>
                        <li>
                            <a class="nav-link {{ !Request::is('admin/notification/push') && Request::is('admin/notification/*') ? 'active' : '' }}"
                               href="{{ route('admin.notification.index') }}" title="{{ translate('send_notification') }}">
                                <span class="text-truncate text-capitalize">{{ translate('send_notification') }}</span>
                            </a>
                        </li>
                        <li>
                            <a class="nav-link text-capitalize {{ Request::is('admin/push-notification/index*') ? 'active' : '' }}"
                               href="{{ route('admin.push-notification.index') }}" title="{{ translate('push_notifications_setup') }}">
                                <span class="text-truncate text-capitalize">{{ translate('push_notifications_setup') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            {{-- Dynamic Theme Routes --}}
            @php($getEnabledThemeRoutes=0)
            @if (count(config('get_theme_routes')) > 0)
                @foreach (config('get_theme_routes')['route_list'] as $route)
                    @if(isset($route['module_permission']) && Helpers::module_permission_check($route['module_permission']))
                        @php($getEnabledThemeRoutes++)
                    @endif
                @endforeach
            @endif

            @if($getEnabledThemeRoutes > 0)
                @if (count(config('get_theme_routes')) > 0)
                    <li class="nav-item nav-item_title">
                        <small class="nav-subtitle" title="{{ config('get_theme_routes')['name'] ?? '' }} {{ translate('Menu') }}">
                            {{ config('get_theme_routes')['name'] ?? '' }} {{ translate('Menu') }}
                        </small>
                    </li>
                    @foreach (config('get_theme_routes')['route_list'] as $route)
                        @if(isset($route['module_permission']) && Helpers::module_permission_check($route['module_permission']))
                            <li class="{{ (Request::is($route['path']) || Request::is($route['path'].'*')) ? 'active' : '' }} @foreach ($route['route_list'] as $sub_route){{ (Request::is($sub_route['path']) || Request::is($sub_route['path'].'*')) ? 'active' : '' }}@endforeach">
                                <a class="nav-link {{ count($route['route_list']) > 0 ? 'nav-link-toggle':'' }}"
                                   href="{{ count($route['route_list']) > 0 ? 'javascript:':$route['url'] }}"
                                   title="{{ translate($route['name']) }}">
                                    {!! $route['icon'] !!}
                                    <span class="aside-mini-hidden-element text-truncate">{{ translate($route['name']) }}</span>
                                </a>

                                @if (count($route['route_list']) > 0)
                                    <ul class="aside-submenu navbar-nav">
                                        <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('system_settings') }}</li>
                                        @foreach ($route['route_list'] as $sub_route)
                                            <li class="{{ (Request::is($sub_route['path']) || Request::is($sub_route['path'].'*')) ? 'active' : '' }}">
                                                <a class="nav-link" href="{{$sub_route['url']}}"
                                                   title="{{ translate($sub_route['name']) }}">
                                                    <span class="text-truncate">{{ translate($sub_route['name']) }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endif
                    @endforeach
                @endif
            @endif

            {{-- ======================================================================== --}}
            {{-- PILLAR 7: GOVERNANCE, REPORTS & SYSTEM SETTINGS                           --}}
            {{-- ======================================================================== --}}
            @if(Helpers::module_permission_check('system_settings') || Helpers::module_permission_check('business_settings') || Helpers::module_permission_check('report') || Helpers::module_permission_check('3rd_party_setup'))
                <li class="nav-item nav-item_title {{ (Request::is('admin/audit-logs*') || Request::is('admin/employee*') || Request::is('admin/custom-role*') || Request::is('admin/report*') || Request::is('admin/business-settings*') || Request::is('admin/system-setup*') || Request::is('admin/third-party*')) ? 'scroll-here' : '' }}">
                    <small class="nav-subtitle" title="{{ translate('governance_&_settings') }}">{{ translate('governance_&_settings') }}</small>
                </li>

                {{-- Governance & Staff Submenu --}}
                @if(Helpers::module_permission_check('system_settings'))
                    <li class="{{ (Request::is('admin/audit-logs*') || Request::is('admin/employee*') || Request::is('admin/custom-role*')) ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ (Request::is('admin/audit-logs*') || Request::is('admin/employee*') || Request::is('admin/custom-role*')) ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('governance_&_staff') }}">
                            <i class="fi fi-sr-shield-check"></i>
                            <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">{{ translate('Governance_&_Staff') }}</span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Governance_&_Staff') }}</li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/audit-logs*') ? 'active' : '' }}"
                                   href="{{ route('admin.audit-logs.index') }}" title="{{ translate('admin_audit_logs') }}">
                                    <span class="text-truncate font-weight-bold text-primary">{{ translate('Audit_Logs') }}</span>
                                </a>
                            </li>
                            @if(auth('admin')->user()->admin_role_id == 1)
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('admin/custom-role*') ? 'active' : '' }}"
                                       href="{{ route('admin.custom-role.create') }}" title="{{ translate('employee_Role_Setup') }}">
                                        <span class="text-truncate">{{ translate('employee_Role_Setup') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ (Request::is('admin/employee/list') || Request::is('admin/employee/add') || Request::is('admin/employee/update*')) ? 'active' : '' }}"
                                       href="{{ route('admin.employee.list') }}" title="{{ translate('employees') }}">
                                        <span class="text-truncate">{{ translate('employees') }}</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- Reports & Analytics Submenu --}}
                @if(Helpers::module_permission_check('report'))
                    <li class="{{ (Request::is('admin/report*') || Request::is('admin/stock*')) ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ (Request::is('admin/report*') || Request::is('admin/stock*')) ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('reports_&_Analysis') }}">
                            <i class="fi fi-sr-stats"></i>
                            <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">{{ translate('reports_&_Analysis') }}</span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('reports_&_Analysis') }}</li>
                            <li>
                                <a class="nav-link {{ (Request::is('admin/report/admin-earning') || Request::is('admin/report/vendor-earning')) ? 'active' : '' }}"
                                   href="{{ route('admin.report.admin-earning') }}" title="{{ translate('Earning_Reports') }}">
                                    <span class="text-truncate">{{ translate('Earning_Reports') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/report/inhouse-product-sale') ? 'active' : '' }}"
                                   href="{{ route('admin.report.inhouse-product-sale') }}" title="{{ translate('inhouse_Sales') }}">
                                    <span class="text-truncate">{{ translate('inhouse_Sales') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/report/vendor-report') ? 'active' : '' }}"
                                   href="{{ route('admin.report.vendor-report') }}" title="{{ translate('vendor_Sales') }}">
                                    <span class="text-truncate text-capitalize">{{ translate('vendor_Sales') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/report/all-product') ? 'active' : '' }}"
                                   href="{{ route('admin.report.all-product') }}" title="{{ translate('product_Report') }}">
                                    <span class="text-truncate">{{ translate('product_Report') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/report/order') ? 'active' : '' }}"
                                   href="{{ route('admin.report.order') }}" title="{{ translate('order_Report') }}">
                                    <span class="text-truncate">{{ translate('order_Report') }}</span>
                                </a>
                            </li>
                            @if(getCheckAddonPublishedStatus(moduleName: 'TaxModule'))
                                @foreach(include(base_path("Modules/TaxModule/Addon/tax_report_routes.php")) as $route)
                                    <li>
                                        <a class="nav-link {{ strstr(Request::url(), $route['path']) ? 'active' : '' }}"
                                           href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                            <span class="text-truncate">{{ translate($route['name']) }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- Business & Platform Settings Submenu --}}
                @if(Helpers::module_permission_check('business_settings'))
                    <li class="{{ (Request::is('admin/business-settings*') && !Request::is('admin/business-settings/announcement*')) || Request::is('admin/seo-settings*') || Request::is('admin/pages-and-media*') ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ ((Request::is('admin/business-settings*') && !Request::is('admin/business-settings/announcement*')) || Request::is('admin/seo-settings*') || Request::is('admin/pages-and-media*')) ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('Business_Settings') }}">
                            <i class="fi fi-sr-settings"></i>
                            <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">{{ translate('Business_Settings') }}</span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('Business_Settings') }}</li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/business-settings/web-config*') ? 'active' : '' }}"
                                   href="{{ route('admin.business-settings.web-config.index') }}" title="{{ translate('Business_Setup') }}">
                                    <span class="text-truncate">{{ translate('Business_Setup') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/business-settings/inhouse-shop') ? 'active' : '' }}"
                                   href="{{ route('admin.business-settings.inhouse-shop') }}" title="{{ translate('Inhouse_Shop') }}">
                                    <span class="text-truncate">{{ translate('Inhouse_Shop') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/business-settings/priority-setup*') ? 'active' : '' }}"
                                   href="{{ route('admin.business-settings.priority-setup.index') }}" title="{{ translate('Priority_Setup') }}">
                                    <span class="text-truncate">{{ translate('Priority_Setup') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/seo-settings*') ? 'active' : '' }}"
                                   href="{{ route('admin.seo-settings.web-master-tool') }}" title="{{ translate('SEO_Settings') }}">
                                    <span class="text-truncate">{{ translate('SEO_Settings') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ Request::is('admin/pages-and-media/list') || Request::is('admin/pages-and-media/page*') ? 'active' : '' }}"
                                   href="{{ route('admin.pages-and-media.list') }}" title="{{ translate('business_Pages') }}">
                                    <span class="text-truncate">{{ translate('business_Pages') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/pages-and-media/social-media') ? 'active' : '' }}"
                                   href="{{ route('admin.pages-and-media.social-media') }}" title="{{ translate('social_Media_Links') }}">
                                    <span class="text-truncate">{{ translate('social_Media_Links') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/pages-and-media/vendor-registration-settings/*') ? 'active' : '' }}"
                                   href="{{ route('admin.pages-and-media.vendor-registration-settings.index') }}" title="{{ translate('vendor_Registration') }}">
                                    <span class="text-truncate">{{ translate('vendor_Registration') }}</span>
                                </a>
                            </li>
                            @if(getCheckAddonPublishedStatus(moduleName: 'TaxModule'))
                                @foreach(include(base_path("Modules/TaxModule/Addon/tax_routes.php")) as $route)
                                    <li>
                                        <a class="nav-link {{ strstr(Request::url(), $route['path']) ? 'active' : '' }}"
                                           href="{{ $route['url'] }}" title="{{ translate($route['name']) }}">
                                            <span class="text-truncate">{{ translate($route['name']) }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- System Setup Submenu --}}
                @if(Helpers::module_permission_check('system_settings'))
                    <li class="{{ Request::is('admin/system-setup*') ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ Request::is('admin/system-setup*') ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('System_Setup') }}">
                            <i class="fi fi-sr-customize"></i>
                            <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">{{ translate('System_Setup') }}</span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('System_Setup') }}</li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/system-setup/environment-setup') ? 'active' : '' }}"
                                   href="{{ route('admin.system-setup.environment-setup') }}" title="{{ translate('environment_setup') }}">
                                    <span class="text-truncate">{{ translate('Environment_Setup') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/system-setup/login-settings*') ? 'active' : '' }}"
                                   href="{{ route('admin.system-setup.login-settings.customer-login-setup') }}" title="{{ translate('Login_Settings') }}">
                                    <span class="text-truncate">{{ translate('Login_Settings') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/system-setup/email-templates*') ? 'active' : '' }}"
                                   href="{{ route('admin.system-setup.email-templates.view', ['admin', EmailTemplateKey::ADMIN_EMAIL_LIST[0]]) }}" title="{{ translate('Email_Template') }}">
                                    <span class="text-truncate">{{ translate('Email_Template') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/system-setup/file-manager*') ? 'active' : '' }}"
                                   href="{{ route('admin.system-setup.file-manager.index') }}" title="{{ translate('Gallery') }}">
                                    <span class="text-truncate">{{ translate('Gallery') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif

                {{-- 3rd Party Setup Submenu --}}
                @if(Helpers::module_permission_check('3rd_party_setup'))
                    <li class="{{ Request::is('admin/third-party*') ? 'sub-menu-opened' : '' }}">
                        <a class="nav-link nav-link-toggle {{ Request::is('admin/third-party*') ? 'active' : '' }}"
                           href="javascript:" title="{{ translate('3rd_Party_Setup') }}">
                            <i class="fi fi-sr-workflow-setting-alt"></i>
                            <span class="aside-mini-hidden-element flex-grow-1 d-flex justify-content-between align-items-center">
                                <span class="text-truncate max-w-180">{{ translate('3rd_Party_Setup') }}</span>
                                <i class="fi fi-sr-angle-down"></i>
                            </span>
                        </a>
                        <ul class="aside-submenu navbar-nav">
                            <li class="nav-item px-3 py-2 fw-semibold text-dark bg-section2 aside-mini-show-element">{{ translate('3rd_Party_Setup') }}</li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/third-party/payment-method*') ? 'active' : '' }}"
                                   href="{{ route('admin.third-party.payment-method.index') }}" title="{{ translate('Payment_Methods') }}">
                                    <span class="text-truncate">{{ translate('Payment_Methods') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/third-party/firebase-configuration*') ? 'active' : '' }}"
                                   href="{{ route('admin.third-party.firebase-configuration.setup') }}" title="{{ translate('Firebase') }}">
                                    <span class="text-truncate">{{ translate('Firebase') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ Request::is('admin/third-party/analytics-index*') ? 'active' : '' }}"
                                   href="{{ route('admin.third-party.analytics-index') }}" title="{{ translate('Marketing_Tools') }}">
                                    <span class="text-truncate">{{ translate('Marketing_Tools') }}</span>
                                </a>
                            </li>
                            <li>
                                <a class="nav-link {{ (Request::is('admin/third-party/mail') || Request::is('admin/third-party/sms-module') || Request::is('admin/third-party/recaptcha') || Request::is('admin/third-party/social-login/view') || Request::is('admin/third-party/map-api')) ? 'active' : '' }}"
                                   href="{{ route('admin.third-party.social-login.view') }}" title="{{ translate('Other_Configuration') }}">
                                    <span class="text-truncate">{{ translate('Other_Configuration') }}</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            @endif

            {{-- Bottom scroll clearance spacer --}}
            <li class="py-3 my-2 d-none d-lg-block" style="height: 60px; pointer-events: none;" aria-hidden="true"></li>
        </ul>
    </div>
</aside>

<div class="offcanvas offcanvas-start bg-panel d-lg-none w-280" tabindex="-1" id="offcanvasAside"
     aria-labelledby="offcanvasAsideLabel">
    <div class="offcanvas-header d-flex align-items-center gap-2 justify-content-between">
        <a class="navbar-logo" href="{{ route('admin.dashboard.index') }}">
            <img height="24" src="{{ getStorageImages(path: $eCommerceLogo, type: 'backend-logo') }}"
                 alt="{{ translate('logo') }}">
        </a>

        <button type="button" class="bg-transparent p-0 text-white border-0" data-bs-dismiss="offcanvas"
                aria-label="Close">
            <i class="fi fi-rr-cross"></i>
        </button>
    </div>

    <div class="offcanvas-body js-offcanvas-body pt-0">

    </div>
</div>
