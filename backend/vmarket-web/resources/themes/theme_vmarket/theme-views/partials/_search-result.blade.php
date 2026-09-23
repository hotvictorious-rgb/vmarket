@if(isset($products) && count($products) > 0)
    <ul style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); box-shadow: var(--vm-shadow-lg); max-height: 380px; overflow-y: auto; padding: 8px 0;">
        @foreach($products as $product)
            @php
                $price = $product->unit_price;
                $discount = \App\Utils\Helpers::get_product_discount($product, $price);
                $finalPrice = $price - $discount;
            @endphp
            <li style="border-bottom: 1px solid var(--vm-border-light);">
                <a href="{{ route('product', $product->slug) }}" style="display: flex; align-items: center; gap: 12px; padding: 10px 16px; transition: var(--vm-transition);" onmouseover="this.style.background='var(--vm-primary-light)'" onmouseout="this.style.background='transparent'">
                    <img src="{{ getStorageImages(path: $product->thumbnail_full_url, type: 'product') }}" alt="{{ $product->name }}" style="width: 40px; height: 40px; border-radius: 4px; object-fit: contain;">
                    <div style="flex-grow: 1;">
                        <span style="font-size: 13.5px; font-weight: 600; color: var(--vm-dark); display: block;">{{ $product->name }}</span>
                        <span style="font-size: 12.5px; font-weight: 700; color: var(--vm-primary);">{{ webCurrencyConverter($finalPrice) }}</span>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
@else
    <div style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-md); padding: 16px; text-align: center; font-size: 13px; color: var(--vm-text-muted);">
        {{ translate('No matching products found') }}
    </div>
@endif
