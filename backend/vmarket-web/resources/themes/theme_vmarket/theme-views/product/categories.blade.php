@extends('theme-views.layouts.app')

@section('title', translate('All Product Categories') . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 24px 16px 48px;">
    
    <div class="vm-section-header">
        <div>
            <h1 class="vm-section-title">{{ translate('Product Categories') }}</h1>
            <p style="font-size: 13px; color: var(--vm-text-muted); margin-top: 4px;">
                {{ translate('Browse marketplace departments and product groups.') }}
            </p>
        </div>
    </div>

    @if(isset($categories) && count($categories) > 0)
        <div class="vm-categories-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
            @foreach($categories as $category)
                <a href="{{ route('category-products', $category->slug) }}" class="vm-category-card" style="padding: 20px 12px;">
                    <img src="{{ getStorageImages(path: $category->icon_full_url, type: 'category') }}" 
                         alt="{{ $category->name }}" 
                         class="vm-category-icon" 
                         style="width: 56px; height: 56px;"
                         loading="lazy">
                    <span class="vm-category-name" style="font-size: 14px; font-weight: 700;">{{ $category->name }}</span>
                    @if(isset($category->product_count))
                        <span style="font-size: 11px; color: var(--vm-text-muted);">{{ $category->product_count }} {{ translate('items') }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 60px 20px; background: #FFFFFF; border-radius: var(--vm-radius-md); border: 1px solid var(--vm-border);">
            <p style="font-size: 15px; color: var(--vm-text-muted);">
                {{ translate('No categories found.') }}
            </p>
        </div>
    @endif

</div>
@endsection
