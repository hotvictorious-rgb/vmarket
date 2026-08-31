@extends('layouts.app')

@section('title', 'Edit Product – ' . $product->name)

@push('styles')
<style>
    .form-section-card {
        background: var(--card-bg, #1f2937);
        border: 1px solid var(--border, #374151);
        border-radius: 18px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border, #374151);
    }
    .form-label {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted, #9ca3af);
        margin-bottom: 0.35rem;
        display: block;
    }
    .form-control, .form-select {
        width: 100%;
        padding: 0.65rem 0.85rem;
        background: rgba(11, 15, 25, 0.7);
        border: 1px solid var(--border, #374151);
        border-radius: 10px;
        color: #fff;
        font-size: 0.92rem;
        font-family: inherit;
        transition: all 0.2s;
    }
    .form-control:focus, .form-select:focus {
        outline: none;
        border-color: var(--primary, #5E17EB);
        box-shadow: 0 0 0 3px rgba(94, 23, 235, 0.25);
    }
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .role-badge-verified {
        background: linear-gradient(135deg, rgba(5, 150, 105, 0.2), rgba(13, 148, 136, 0.2));
        border: 1px solid rgba(52, 211, 153, 0.5);
        color: #6ee7b7;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .role-badge-free {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(217, 119, 6, 0.15));
        border: 1px dashed rgba(245, 158, 11, 0.5);
        color: #fde047;
        padding: 0.85rem 1rem;
        border-radius: 12px;
        font-size: 0.85rem;
        line-height: 1.4;
        margin-bottom: 1.25rem;
    }
    .image-preview-box {
        width: 120px;
        height: 120px;
        border: 2px dashed var(--border, #374151);
        border-radius: 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        background: rgba(11, 15, 25, 0.5);
        overflow: hidden;
        transition: all 0.2s;
    }
    .image-preview-box:hover {
        border-color: var(--primary, #5E17EB);
    }
    .image-preview-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
@endpush

@section('content')
<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.75rem;">📝</span>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: #fff;">Edit Product</h2>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted, #9ca3af);">
                Manage single-source inventory specifications and marketplace availability.
            </p>
        </div>
        <a href="{{ route('pos.products.index') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem; border-radius: 10px; font-weight: 700; text-decoration: none;">
            ← Back to Catalog
        </a>
    </div>

    <form method="POST" action="{{ route('pos.products.update', $product->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- 1. Core Inventory & POS Details -->
        <div class="form-section-card">
            <div class="form-section-title">
                <span>🏷️</span> Core Product & In-Store POS Details
            </div>

            <div class="form-row">
                <div style="grid-column: 1 / -1;">
                    <label class="form-label">Product Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label class="form-label">SKU / Barcode *</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $product->code) }}" required>
                </div>
                <div>
                    <label class="form-label">Official Marketplace Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Select Marketplace Category --</option>
                        @foreach($officialCategories as $cat)
                            <option value="{{ $cat->id }}" style="font-weight: 800; color: #60a5fa;" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                📁 {{ $cat->name }}
                            </option>
                            @if(!empty($cat->childes))
                                @foreach($cat->childes as $sub)
                                    <option value="{{ $sub->id }}" {{ old('category_id', $product->category_id) == $sub->id ? 'selected' : '' }}>
                                        &nbsp;&nbsp;&nbsp;&nbsp;↳ {{ $sub->name }}
                                    </option>
                                @endforeach
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Unit of Measure</label>
                    <select name="unit" class="form-select">
                        @foreach($units as $u)
                            <option value="{{ $u }}" {{ old('unit', $product->unit ?? 'pc') == $u ? 'selected' : '' }}>{{ strtoupper($u) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label class="form-label">Retail Selling Price (₦) *</label>
                    <input type="number" step="0.01" name="unit_price" class="form-control" style="color: #4ade80; font-weight: 800; font-size: 1.05rem;" value="{{ old('unit_price', $product->unit_price) }}" required>
                </div>
                <div>
                    <label class="form-label">Purchase Cost Price (₦)</label>
                    <input type="number" step="0.01" name="purchase_price" class="form-control" value="{{ old('purchase_price', $product->purchase_price) }}">
                </div>
                <div>
                    <label class="form-label">Wholesale Price (₦)</label>
                    <input type="number" step="0.01" name="pos_wholesale_price" class="form-control" style="color: #c084fc; font-weight: 700;" value="{{ old('pos_wholesale_price', $product->pos_wholesale_price ?? $product->unit_price) }}">
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label class="form-label">Physical Stock *</label>
                    <input type="number" name="current_stock" class="form-control" value="{{ old('current_stock', $product->current_stock) }}" required>
                </div>
                <div>
                    <label class="form-label">Low Stock Alert Level</label>
                    <input type="number" name="pos_reorder_level" class="form-control" value="{{ old('pos_reorder_level', $product->pos_reorder_level ?? 5) }}">
                </div>
                <div>
                    <label class="form-label">Min Order Qty</label>
                    <input type="number" name="minimum_order_qty" class="form-control" value="{{ old('minimum_order_qty', $product->minimum_order_qty ?? 1) }}">
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" step="0.01" name="tax" class="form-control" value="{{ old('tax', $product->tax ?? 0) }}">
                    <input type="hidden" name="tax_type" value="percent">
                </div>
                <div>
                    <label class="form-label">Discount Amount</label>
                    <input type="number" step="0.01" name="discount" class="form-control" value="{{ old('discount', $product->discount ?? 0) }}">
                </div>
                <div>
                    <label class="form-label">Discount Type</label>
                    <select name="discount_type" class="form-select">
                        <option value="flat" {{ old('discount_type', $product->discount_type ?? 'flat') == 'flat' ? 'selected' : '' }}>Flat (₦)</option>
                        <option value="percent" {{ old('discount_type', $product->discount_type ?? 'flat') == 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                    </select>
                </div>
            </div>

            <div style="margin-top: 0.5rem;">
                <label class="form-label">Product Description / Notes</label>
                <textarea name="details" class="form-control" rows="2">{{ old('details', $product->details) }}</textarea>
            </div>
        </div>

        <!-- 2. Omnichannel Marketplace Integration (Role-Aware) -->
        <div class="form-section-card">
            <div class="form-section-title">
                <span>🌐</span> Victorious MARKET Online Synchronization
            </div>

            @if($isVerified)
                <div class="role-badge-verified">
                    <span>✓</span>
                    <span><strong>Verified Merchant Store:</strong> This product can be listed live for online shoppers on Victorious MARKET and mobile apps.</span>
                </div>

                <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(15,23,42,0.6); padding: 1rem 1.25rem; border-radius: 14px; border: 1px solid var(--border, #374151); margin-bottom: 1.25rem;">
                    <div>
                        <div style="font-weight: 800; color: #fff; font-size: 0.95rem;">Publish to Online Marketplace</div>
                        <div style="font-size: 0.78rem; color: var(--text-muted, #9ca3af);">Make available for online search, customer checkout, and dispatch delivery.</div>
                    </div>
                    <label style="position: relative; display: inline-block; width: 50px; height: 26px; margin: 0; cursor: pointer;">
                        <input type="checkbox" name="is_published" value="1" {{ $product->status == 1 ? 'checked' : '' }} style="opacity: 0; width: 0; height: 0;">
                        <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: {{ $product->status == 1 ? '#5E17EB' : '#4b5563' }}; border-radius: 34px; transition: .4s;"></span>
                    </label>
                </div>
            @else
                <div class="role-badge-free">
                    <div style="display: flex; align-items: center; gap: 0.4rem; font-weight: 800; margin-bottom: 0.25rem;">
                        <span>🔒</span> <span>Free In-Store POS Active (Marketplace Locked Pending KYC)</span>
                    </div>
                    <span>Product is active at your counter POS register. Photos and online settings saved here will automatically go live on Victorious MARKET once Super Admin completes your verification.</span>
                </div>
            @endif

            <!-- Product Photo (Used for Online Marketplace Storefront) -->
            <div>
                <label class="form-label">Product Photo (Optional for Online Marketplace)</label>
                <div style="display: flex; gap: 1.25rem; align-items: center;">
                    <div class="image-preview-box" onclick="document.getElementById('imageInput').click()" id="previewContainer">
                        @if($product->thumbnail && $product->thumbnail !== 'def.png')
                            <img id="imagePreview" src="{{ asset('storage/app/public/product/thumbnail/' . $product->thumbnail) }}" onerror="this.src='{{ asset('storage/app/public/product/' . $product->thumbnail) }}'">
                        @else
                            <span id="uploadIcon" style="font-size: 1.75rem;">📷</span>
                            <span id="uploadText" style="font-size: 0.68rem; color: var(--text-muted, #9ca3af); font-weight: 700; margin-top: 0.25rem;">Upload Photo</span>
                            <img id="imagePreview" style="display: none;">
                        @endif
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted, #9ca3af); line-height: 1.4;">
                        Upload high-resolution image for your online storefront listing.<br>
                        <span style="color: #60a5fa; font-weight: 600;">Note: Product images do not appear on counter POS registers to keep barcode checkout ultra-fast.</span>
                        <input type="file" id="imageInput" name="image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem; margin-bottom: 3rem;">
            <a href="{{ route('pos.products.index') }}" class="btn btn-secondary" style="flex: 1; padding: 0.85rem; border-radius: 12px; font-weight: 800; text-align: center; text-decoration: none;">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary" style="flex: 2; padding: 0.85rem; border-radius: 12px; font-weight: 800; font-size: 1.05rem; background: #5E17EB; border-color: #5E17EB; box-shadow: 0 4px 14px rgba(94, 23, 235, 0.4);">
                ✓ Update & Sync Product
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            let img = document.getElementById('imagePreview');
            img.src = e.target.result;
            img.style.display = 'block';
            let icon = document.getElementById('uploadIcon');
            let text = document.getElementById('uploadText');
            if (icon) icon.style.display = 'none';
            if (text) text.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
