@include('theme-views.product.partials._filter-product-price')
@include('theme-views.product.partials._filter-product-categories', [
    'productCategories' => $categories,
    'dataFrom' => 'shop',
])
@include('theme-views.product.partials._filter-product-brands', [
    'productBrands' => $brands,
    'dataFrom' => 'shop',
])

@include('theme-views.product.partials._filter-product-reviews', [
    'productRatings' => $ratings
])
