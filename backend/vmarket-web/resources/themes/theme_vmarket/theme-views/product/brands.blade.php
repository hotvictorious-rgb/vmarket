@extends('theme-views.layouts.app')

@section('title', translate('Official Brands') . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 24px 16px 48px;">
    
    <div class="vm-section-header">
        <div>
            <h1 class="vm-section-title">{{ translate('Official Brands & Manufacturers') }}</h1>
            <p style="font-size: 13.5px; color: var(--vm-text-muted); margin-top: 4px;">
                {{ translate('Discover verified manufacturer brands and authorized distributors in Akwa Ibom.') }}
            </p>
        </div>

        <form action="{{ route('brands') }}" method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ translate('Search brands...') }}" class="vm-search-input" style="background: #FFFFFF; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-full); padding: 6px 14px; font-size: 13px; width: 200px;">
            <button type="submit" class="vm-search-btn" style="padding: 6px 16px;">{{ translate('Search') }}</button>
        </form>
    </div>

    @if(isset($brands) && $brands->count() > 0)
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px; margin-top: 20px;">
            @foreach($brands as $brand)
                <a href="{{ route('brand-products', ['slug' => $brand['slug']]) }}" 
                   style="background: var(--vm-surface); border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 20px 14px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; transition: var(--vm-transition);"
                   onmouseover="this.style.borderColor='var(--vm-primary)'; this.style.transform='translateY(-3px)'; this.style.boxShadow='var(--vm-shadow-hover)'"
                   onmouseout="this.style.borderColor='var(--vm-border)'; this.style.transform='none'; this.style.boxShadow='none'">
                    <img src="{{ getStorageImages(path: $brand->image_full_url, type: 'brand') }}" 
                         alt="{{ $brand->name }}" 
                         style="width: 72px; height: 72px; object-fit: contain; border-radius: var(--vm-radius-sm);" 
                         loading="lazy">
                    <span style="font-size: 14px; font-weight: 700; color: var(--vm-dark); line-height: 1.25;">
                        {{ $brand->name }}
                    </span>
                    @if(isset($brand->brand_products_count))
                        <span style="font-size: 11.5px; color: var(--vm-text-muted);">
                            {{ $brand->brand_products_count }} {{ translate('Products') }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        <div style="margin-top: 36px; display: flex; justify-content: center;">
            {{ $brands->links() }}
        </div>
    @else
        <div style="text-align: center; padding: 60px 20px; background: #FFFFFF; border-radius: var(--vm-radius-md); border: 1px solid var(--vm-border); margin-top: 20px;">
            <p style="font-size: 15px; color: var(--vm-text-muted);">
                {{ translate('No brands found matching your search.') }}
            </p>
            <a href="{{ route('brands') }}" class="vm-btn-primary" style="display: inline-flex; width: auto; margin-top: 16px; padding: 10px 20px;">
                {{ translate('View All Brands') }}
            </a>
        </div>
    @endif

</div>
@endsection
