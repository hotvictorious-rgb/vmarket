@extends('pos::layouts.app')

@section('title', 'Edit Product')

@section('breadcrumb', 'Products / Edit')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="row mb-3 align-items-center">
            <div class="col-6">
                <h2 class="fs-4 fw-bold text-dark mb-0">📝 Edit Product</h2>
            </div>
            <div class="col-6 text-end">
                <a href="{{ route('pos.products.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back to Catalog
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 18px; background: #fff;">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('pos.products.update', $product->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label fw-bold text-muted text-uppercase" style="font-size: 0.72rem;">Product Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required placeholder="e.g. Peak Milk Powder (900g)">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="code" class="form-label fw-bold text-muted text-uppercase" style="font-size: 0.72rem;">SKU / Barcode *</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $product->code) }}" required readonly style="background: #e9ecef; cursor: not-allowed;">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="pos_category" class="form-label fw-bold text-muted text-uppercase" style="font-size: 0.72rem;">Category</label>
                            <input type="text" class="form-control @error('pos_category') is-invalid @enderror" id="pos_category" name="pos_category" value="{{ old('pos_category', $product->pos_category) }}" list="categoriesList" placeholder="e.g. Provisions">
                            <datalist id="categoriesList">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">
                                @endforeach
                            </datalist>
                            @error('pos_category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="unit_price" class="form-label fw-bold text-muted text-uppercase" style="font-size: 0.72rem;">Selling Price (₦) *</label>
                            <input type="number" step="0.01" class="form-control @error('unit_price') is-invalid @enderror" id="unit_price" name="unit_price" value="{{ old('unit_price', $product->unit_price) }}" required placeholder="e.g. 7200">
                            @error('unit_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="purchase_price" class="form-label fw-bold text-muted text-uppercase" style="font-size: 0.72rem;">Purchase Cost (₦)</label>
                            <input type="number" step="0.01" class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" placeholder="e.g. 6500">
                            @error('purchase_price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="current_stock" class="form-label fw-bold text-muted text-uppercase" style="font-size: 0.72rem;">Current Stock Qty *</label>
                            <input type="number" class="form-control @error('current_stock') is-invalid @enderror" id="current_stock" name="current_stock" value="{{ old('current_stock', $product->current_stock) }}" required placeholder="e.g. 50">
                            @error('current_stock')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="pos_reorder_level" class="form-label fw-bold text-muted text-uppercase" style="font-size: 0.72rem;">Reorder level</label>
                            <input type="number" class="form-control @error('pos_reorder_level') is-invalid @enderror" id="pos_reorder_level" name="pos_reorder_level" value="{{ old('pos_reorder_level', $product->pos_reorder_level) }}" placeholder="e.g. 5">
                            @error('pos_reorder_level')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg fw-bold py-2" style="background: #5E17EB; border-color: #5E17EB;">
                            ✓ Update Product Catalog
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
