@extends('theme-views.layouts.app')

@section('title', translate('all_Categories').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@push('css_or_js')
    <meta property="og:image" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="og:title" content="Categories of {{$web_config['company_name']}} "/>
    <meta property="og:url" content="{{env('APP_URL')}}">
    <meta property="og:description" content="{{ $web_config['meta_description'] }}">
    <meta property="twitter:card" content="{{$web_config['web_logo']['path']}}"/>
    <meta property="twitter:title" content="Categories of {{$web_config['company_name']}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}">
    <meta property="twitter:description" content="{{ $web_config['meta_description'] }}">
@endpush

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-30">
        <div class="container">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row gy-2 align-items-center">
                        <div class="col-md-6">
                            <h3 class="mb-1 text-capitalize">{{ translate('all_categories') }}</h3>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb fs-12 mb-0">
                                    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ translate('home') }}</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">{{ translate('categories') }}</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('categories') }}" method="GET">
                                <div class="d-flex align-items-center gap-2 position-relative">
                                    <input class="form-control" type="search" autocomplete="off"
                                        placeholder="{{ translate('Search_Categories') }}" name="search" value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if(count($categories) > 0)
                <div class="row g-3">
                    @foreach($categories as $category)
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                            <a href="{{ route('category-products', ['slug' => $category['slug']]) }}" class="card text-center h-100 p-3 text-decoration-none text-dark hover-shadow transition">
                                <div class="mb-2 d-flex justify-content-center align-items-center" style="height: 80px;">
                                    <img src="{{ getStorageImages(path: $category->icon_full_url, type: 'category') }}"
                                         alt="{{ $category['name'] }}"
                                         class="img-fluid rounded"
                                         style="max-height: 70px; object-fit: contain;">
                                </div>
                                <h6 class="fs-14 fw-semibold text-truncate mb-1">{{ $category['name'] }}</h6>
                                <span class="fs-12 text-muted">{{ $category->product_count ?? 0 }} {{ translate('products') }}</span>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="d-flex flex-column justify-content-center align-items-center gap-3 text-center">
                        <img src="{{ theme_asset('assets/img/icons/empty-category.svg') }}"
                             onerror="this.src='{{ dynamicAsset(path: 'public/assets/front-end/img/empty-icons/empty-category.svg') }}'"
                             alt="{{ translate('category') }}" class="img-fluid" width="100">
                        <h6 class="text-muted">{{ translate('no_category_found') }}</h6>
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection
