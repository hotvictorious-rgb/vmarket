@if(isset($products) && count($products) > 0)
    @foreach($products as $product)
        @php
            $price = $product->unit_price;
            $discount = \App\Utils\Helpers::get_product_discount($product, $price);
            $finalPrice = $price - $discount;
            $shop = $product->seller?->shop;
        @endphp
        <div class="vm-product-card">
            <a href="{{ route('product', $product->slug) }}" class="vm-product-img-wrap">
                <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}" 
                     alt="{{ $product->name }}" 
                     class="vm-product-img" 
                     loading="lazy">
                @if($discount > 0)
                    <span class="vm-discount-badge">
                        @if ($product->discount_type == 'percent')
                            -{{ round($product->discount) }}%
                        @else
                            -{{ webCurrencyConverter($discount) }}
                        @endif
                    </span>
                @endif
            </a>
            <div class="vm-product-info">
                @if($shop)
                    <a href="{{ route('vendor-shop', $shop->slug) }}" class="vm-product-shop">
                        <span>🏪</span>
                        <span>{{ Str::limit($shop->name, 18) }}</span>
                    </a>
                @endif
                <a href="{{ route('product', $product->slug) }}" class="vm-product-name" title="{{ $product->name }}">
                    {{ $product->name }}
                </a>
                <div class="vm-product-price-row">
                    <span class="vm-price-current">{{ webCurrencyConverter($finalPrice) }}</span>
                    @if($discount > 0)
                        <span class="vm-price-old">{{ webCurrencyConverter($price) }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
@endif
