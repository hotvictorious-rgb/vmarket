@extends('layouts.admin.app')

@section('title', translate('Product_Approval_&_Pricing_Gateway_Portal'))

@push('css_or_js')
<style>
    .pricing-gateway-card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }
    .badge-markup {
        background-color: #eef2ff;
        color: #4338ca;
        font-weight: 600;
        border: 1px solid #c7d2fe;
        border-radius: 6px;
        padding: 4px 8px;
    }
    .badge-vendor-cost {
        background-color: #f8fafc;
        color: #334155;
        font-weight: 700;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 4px 8px;
    }
    .price-input-container {
        max-width: 160px;
    }
</style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-shield-check text-primary"></i>
                {{ translate('Product_Approval_&_Pricing_Gateway') }}
                <span class="badge badge-soft-danger fs-14 rounded-pill">{{ $totalPendingCount }} {{ translate('Pending') }}</span>
            </h2>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.category.view') }}" class="btn btn-outline-primary">
                    <i class="fi fi-rr-settings"></i> {{ translate('Configure_Category_Markups') }}
                </a>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card mb-3 bg-primary bg-opacity-10 border-0">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fi fi-sr-info fs-20"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-primary fw-bold">{{ translate('Zero-Trust_Pricing_Gateway_Policy') }}</h5>
                        <p class="mb-0 text-muted fs-13">
                            {{ translate('Vendors_only_see_their_net_payout_price._The_system_automatically_applies_category_markup_to_generate_customer_selling_prices._You_can_fine-tune_retail_prices_below_before_publishing_live_to_storefronts.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter and Search -->
        <div class="card mb-3">
            <div class="card-body">
                <form action="{{ route('admin.products.approval-portal') }}" method="GET" class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="search" name="searchValue" class="form-control"
                                   placeholder="{{ translate('Search_by_Product_Name_or_Code') }}..."
                                   value="{{ $searchValue }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="fi fi-rr-search"></i> {{ translate('Search') }}
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="category_id" class="form-select js-select2-custom" onchange="this.form.submit()">
                            <option value="all">{{ translate('All_Categories') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }} ({{ $cat->markup_percentage ?? 10 }}% Markup)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 text-md-end">
                        <button type="button" class="btn btn-success d-none" id="batch-approve-btn" onclick="submitBatchApproval()">
                            <i class="fi fi-sr-check"></i> {{ translate('Approve_Selected') }} (<span id="selected-count">0</span>)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pending Products List -->
        <div class="card pricing-gateway-card">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" id="select-all" onclick="toggleSelectAll(this)">
                            </th>
                            <th>{{ translate('Product') }}</th>
                            <th>{{ translate('Vendor_Shop') }}</th>
                            <th>{{ translate('Category_&_Markup') }}</th>
                            <th>{{ translate('Vendor_Cost_(Payout)') }}</th>
                            <th>{{ translate('Customer_Selling_Price') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($products as $product)
                        <tr id="row-product-{{ $product->id }}">
                            <td>
                                <input type="checkbox" class="product-select" value="{{ $product->id }}" onchange="updateSelectedCount()">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ getValidImage(path: 'storage/app/public/product/thumbnail/' . $product->thumbnail, type: 'backend-product') }}"
                                         class="avatar avatar-lg rounded border" alt="{{ $product->name }}">
                                    <div>
                                        <a href="{{ route('admin.products.view', [$product->id]) }}" class="text-dark fw-bold text-hover-primary text-truncate d-inline-block" style="max-width: 250px;">
                                            {{ $product->name }}
                                        </a>
                                        <div class="fs-12 text-muted">
                                            <span>SKU: {{ $product->code }}</span> • <span>Stock: {{ $product->current_stock }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fi fi-rr-shop me-1"></i> {{ $product->seller?->shop?->name ?? translate('Unknown_Vendor') }}
                                </span>
                            </td>
                            <td>
                                <div>{{ $product->category?->name ?? translate('Uncategorized') }}</div>
                                <span class="badge badge-markup">
                                    +{{ $product->markup_rate }}% {{ translate('Markup') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-vendor-cost fs-14">
                                    {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $product->purchase_price > 0 ? $product->purchase_price : $product->unit_price)) }}
                                </span>
                            </td>
                            <td>
                                <form id="form-approve-{{ $product->id }}" action="{{ route('admin.products.approve-price') }}" method="POST" class="d-flex align-items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <div class="input-group input-group-sm price-input-container">
                                        <span class="input-group-text bg-light">₦</span>
                                        <input type="number" step="0.01" min="1" name="unit_price" id="unit_price_{{ $product->id }}"
                                               class="form-control fw-bold text-success text-end"
                                               value="{{ round($product->suggested_unit_price, 2) }}" required>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-success" title="{{ translate('Approve_&_Go_Live') }}">
                                        <i class="fi fi-sr-check"></i> {{ translate('Publish') }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('admin.products.view', [$product->id]) }}" class="btn btn-outline-info btn-sm" title="{{ translate('Full_Product_Specs') }}">
                                        <i class="fi fi-rr-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="openDenyModal({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            title="{{ translate('Deny_Request') }}">
                                        <i class="fi fi-rr-cross"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                @include('layouts.admin.partials._empty-state', ['text' => 'No_Pending_Products_for_Approval', 'image' => 'default'])
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="card-footer py-3">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Deny Note Modal -->
    <div class="modal fade" id="denyModal" tabindex="-1" aria-labelledby="denyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('admin.products.deny-request') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" id="deny-product-id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="denyModalLabel">{{ translate('Deny_Product_Submission') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted fs-13 mb-2" id="deny-product-title"></p>
                        <div class="mb-3">
                            <label for="denied_note" class="form-label fw-bold">{{ translate('Reason_for_Rejection') }} <span class="text-danger">*</span></label>
                            <textarea name="denied_note" id="denied_note" class="form-control" rows="4"
                                      placeholder="{{ translate('e.g._Please_provide_clearer_product_photos_or_verify_the_payout_amount.') }}" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ translate('Confirm_Denial') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Batch Approval Form (Hidden) -->
    <form id="batch-approve-form" action="{{ route('admin.products.batch-approve') }}" method="POST" class="d-none">
        @csrf
        <div id="batch-inputs"></div>
    </form>
@endsection

@push('script')
<script>
    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.product-select');
        checkboxes.forEach(cb => cb.checked = master.checked);
        updateSelectedCount();
    }

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.product-select:checked');
        const countSpan = document.getElementById('selected-count');
        const batchBtn = document.getElementById('batch-approve-btn');
        if (countSpan && batchBtn) {
            countSpan.textContent = checked.length;
            if (checked.length > 0) {
                batchBtn.classList.remove('d-none');
            } else {
                batchBtn.classList.add('d-none');
            }
        }
    }

    function submitBatchApproval() {
        const checked = document.querySelectorAll('.product-select:checked');
        if (checked.length === 0) return;

        if (!confirm('{{ translate("Approve_and_publish_all_selected_products_with_their_configured_prices?") }}')) {
            return;
        }

        const batchInputs = document.getElementById('batch-inputs');
        batchInputs.innerHTML = '';

        checked.forEach(cb => {
            const id = cb.value;
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_ids[]';
            input.value = id;
            batchInputs.appendChild(input);

            const priceField = document.getElementById('unit_price_' + id);
            if (priceField) {
                const priceInput = document.createElement('input');
                priceInput.type = 'hidden';
                priceInput.name = 'custom_price_' + id;
                priceInput.value = priceField.value;
                batchInputs.appendChild(priceInput);
            }
        });

        document.getElementById('batch-approve-form').submit();
    }

    function openDenyModal(productId, productName) {
        document.getElementById('deny-product-id').value = productId;
        document.getElementById('deny-product-title').textContent = '{{ translate("Product:") }} ' + productName;
        document.getElementById('denied_note').value = '';
        const modal = new bootstrap.Modal(document.getElementById('denyModal'));
        modal.show();
    }
</script>
@endpush
