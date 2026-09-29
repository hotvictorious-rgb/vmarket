@extends('layouts.admin.app')

@section('title', translate('banner'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2 flex-wrap">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/banner.png') }}" alt="">
                {{ translate('banner_Setup') }}
                <small>
                    <strong class="text-primary text-capitalize">
                        ({{ str_replace("_", " ", (theme_root_path() == "theme_fashion" ? "theme_lifestyle" : theme_root_path())) }}
                        )
                    </strong>
                </small>
            </h2>
        </div>

        <div class="row pb-4 d--none text-start" id="main-banner">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.banner.store') }}" method="post" enctype="multipart/form-data"
                              class="banner_form form-advance-validation form-advance-file-validation non-ajax-form-validate" novalidate="novalidate">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="h-100">
                                        <input type="hidden" id="id" name="id">
                                        <div class="form-group">
                                            <label for="name" class="form-label">
                                                {{ translate('banner_type') }}  <span class="text-danger">*</span>
                                            </label>
                                            <select class="custom-select" name="banner_type" required id="banner_type_select">
                                                <option value="" disabled>{{ translate('select_banner_type') }}</option>
                                                @foreach($bannerTypes as $key => $banner)
                                                    <option value="{{ $key }}">{{ $banner }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group" id="banner_resource_type">
                                            <label for="resource_id" class="form-label">
                                                {{ translate('resource_type') }}  <span class="text-danger">*</span>
                                            </label>
                                            <select class="custom-select action-display-data" name="resource_type" required>
                                                <option value="" disabled>{{ translate('select_resource_type') }}</option>
                                                <option value="product">{{ translate('product') }}</option>
                                                <option value="category">{{ translate('category') }}</option>
                                                <option value="shop">{{ translate('shop') }}</option>
                                                <option value="brand">{{ translate('brand') }}</option>
                                                <option value="custom">{{ translate('custom') }}</option>
                                            </select>
                                        </div>

                                        <div class="form-group mb-0" id="resource-product">
                                            <label for="product_id" class="form-label">
                                                {{ translate('product') }}
                                            </label>
                                            <select class="custom-select" name="product_id">
                                                @foreach($products as $product)
                                                    <option value="{{ $product['id'] }}">
                                                        {{ $product['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group mb-0 d--none" id="resource-category">
                                            <label for="name" class="form-label">
                                                {{ translate('category') }}
                                            </label>
                                            <select class="custom-select" name="category_id">
                                                @foreach($categories as $category)
                                                    <option value="{{ $category['id'] }}">
                                                        {{ $category['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group mb-0 d--none" id="resource-shop">
                                            <label for="shop_id" class="form-label">{{ translate('shop') }}</label>
                                            <select class="w-100 custom-select form-control" name="shop_id">
                                                @foreach($shops as $shop)
                                                    <option value="{{ $shop['id'] }}">{{ $shop['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group mb-0 d--none" id="resource-brand">
                                            <label for="brand_id" class="form-label">
                                                {{ translate('brand') }}
                                            </label>
                                            <select class="custom-select" name="brand_id">
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand['id'] }}">{{ $brand['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="form-group mb-0 d--none" id="resource-custom-url">
                                            <label for="name" class="form-label">{{ translate('banner_URL') }} <span class="text-danger">*</span> </label>
                                            <input type="url" name="url" class="form-control" id="url"
                                                   placeholder="{{ translate('Enter_url') }}">
                                        </div>

                                        @if(in_array(theme_root_path(), ['theme_fashion', 'theme_vmarket']))
                                            <div class="form-group mt-4 input-field-for-main-banner">
                                                <label for="title" class="form-label">
                                                    {{ translate('Title') }}
                                                </label>
                                                <input type="text" name="title" class="form-control" id="title"
                                                    placeholder="{{ translate('Enter_banner_title') }}">
                                            </div>
                                            <div class="form-group mb-0 input-field-for-main-banner">
                                                <label for="sub_title" class="form-label">
                                                    {{ translate('Sub_Title') }}
                                                </label>
                                                <input type="text" name="sub_title" class="form-control"
                                                    id="sub_title" placeholder="{{ translate('Enter_banner_sub_title') }}">
                                            </div>
                                            <div class="form-group mt-4 input-field-for-main-banner">
                                                <label for="button_text" class="form-label">
                                                    {{ translate('Button_Text') }}
                                                </label>
                                                <input type="text" name="button_text" class="form-control" id="button_text"
                                                    placeholder="{{ translate('Enter_button_text') }}">
                                            </div>
                                            <div class="form-group mt-4 mb-0 input-field-for-main-banner">
                                                <label for="background_color" class="form-label">
                                                    {{ translate('background_color') }}
                                                </label>
                                                <input type="color" name="background_color"
                                                    class="form-control h-80px px-2 py-2"
                                                    id="background_color" value="#fee440">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                 <div class="col-md-6 d-flex flex-column justify-content-center">
                                    <div class="d-flex justify-content-center align-items-center bg-section rounded-8 p-20 w-100 h-100">
                                        <div class="d-flex flex-column gap-30 w-100">
                                            <div class="text-center">
                                                <label for="" class="form-label fw-semibold mb-1">
                                                    {{ translate('banner_image') }}
                                                    @if(theme_root_path() == 'theme_vmarket')
                                                        <span class="badge badge-soft-info">{{ translate('optional_for_generative_designs') }}</span>
                                                    @else
                                                        <span class="text-danger">*</span>
                                                    @endif
                                                </label>
                                                <h4 class="mb-0"><span class="text-info-dark" id="theme_ratio"> ( {{ translate('ratio') }} 4:1 )</span></h4>
                                            </div>
                                            <div class="upload-file">
                                                <input type="file" name="image" class="upload-file__input single_file_input"
                                                       id="banner" accept="{{ getFileUploadFormats(skip: '.svg') }}"
                                                       {{ theme_root_path() == 'theme_vmarket' ? '' : 'required' }}
                                                       data-max-size="{{ getFileUploadMaxSize() }}"
                                                       data-required-msg="{{ translate('banner_image_is_required') }}"
                                                       value="">
                                                <div class="upload-file__wrapper ratio-4-1">
                                                    <div class="upload-file-textbox text-center">
                                                        <img width="34" height="34" class="svg"
                                                             src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                             alt="image upload">
                                                        <h6 class="mt-1 fw-medium lh-base text-center">
                                                            <span class="text-info">
                                                                {{ translate('Click to upload') }}
                                                            </span>
                                                            <br>
                                                            {{ translate('or_drag_and_drop') }}
                                                        </h6>
                                                    </div>
                                                    <img class="upload-file-img" loading="lazy" src="" data-default-src=""
                                                         alt="">
                                                </div>
                                                <div class="overlay">
                                                    <div
                                                        class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                        <button type="button" class="btn btn-outline-info icon-btn view_btn">
                                                            <i class="fi fi-sr-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                            <i class="fi fi-rr-camera"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            @if(theme_root_path() == 'theme_vmarket')
                                                <div class="p-2 rounded bg-soft-info border border-info fs-12 text-center">
                                                    ✨ <strong>{{ translate('Generative Design Engine Active') }}:</strong>
                                                    {{ translate('If no photo is uploaded, Victorious MARKET will automatically generate dynamic luxury typography, trust badges, and brand color moods on the fly with zero graphic design needed!') }}
                                                </div>
                                            @endif
                                            <p class="fs-12 text-center max-w-360 m-auto">
                                                {{ getFileUploadFormats(skip: '.svg', asBladeMessage: true).' '. translate('Image_size'). ' : '. translate('Max').' '. getFileUploadMaxSize() . 'MB' }}
                                            </p>
                                            <p class="fs-12 text-center max-w-360 m-auto">
                                                {{ translate('banner_Image_ratio_is_not_same_for_all_sections_in_website.') }}
                                                {{ translate('please_review_the_ratio_before_upload') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-flex justify-content-end flex-wrap gap-10">
                                    <button class="btn btn-secondary cancel px-4" type="reset">
                                        {{ translate('reset') }}
                                    </button>
                                    <button id="add" type="submit" class="btn btn-primary px-4">
                                        {{ translate('save') }}
                                    </button>
                                    <button id="update" class="btn btn-primary d--none text-white">
                                        {{ translate('update') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="banner-table">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body d-flex flex-column gap-20">
                        <div class="row g-2 align-items-center">
                            <div class="col-xl-4 mb-2 mb-md-0">
                                <h3 class="mb-0">
                                    {{ translate('banner_table') }}
                                    <span
                                        class="badge text-dark bg-body-secondary fw-semibold rounded-50">{{ $banners->total() }}</span>
                                </h3>
                            </div>
                            <div class="col-xl-8">
                                <form action="{{ url()->current() }}" method="GET">
                                    <div class="d-flex gap-2 justify-content-end align-items-center flex-wrap">
                                        <div class="select-wrapper flex-grow-1 max-w-360">
                                            <select class="form-control" name="searchValue" id="date_type">
                                                <option value="">{{ translate('all') }}</option>
                                                @foreach($bannerTypes as $key => $banner)
                                                    <option
                                                        value="{{ $key }}" {{ request('searchValue') == $key ? 'selected':'' }}>{{ $banner }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit"
                                                class="btn btn-primary px-4 text-nowrap min-w-120 flex-grow-1 flex-sm-grow-0">
                                            {{ translate('filter') }}
                                        </button>
                                        <div id="banner-btn" class="flex-grow-1 flex-sm-grow-0">
                                            <button type="button" id="main-banner-add"
                                                class="btn btn-primary text-nowrap text-capitalize w-100">
                                                <i class="fi fi-sr-plus"></i>
                                                {{ translate('add_banner') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="columnSearchDatatable"
                                   class="table table-hover table-borderless table-thead-bordered align-middle">
                                <thead class="text-capitalize">
                                <tr>
                                    <th class="pl-xl-5">{{ translate('SL') }}</th>
                                    <th>{{ translate('image') }}</th>
                                    <th>{{ translate('banner_type') }}</th>
                                    <th>{{ translate('resource_type') }}</th>
                                    <th>{{ translate('published') }}</th>
                                    <th class="text-center">{{ translate('action') }}</th>
                                </tr>
                                </thead>
                                @foreach($banners as $key=>$banner)
                                    <tbody>
                                    <tr id="data-{{ $banner->id}}">
                                        <td class="pl-xl-5">{{ $banners->firstItem()+$key}}</td>
                                        <td>
                                            <img class="ratio-4-2 object-fit-cover border rounded" width="80" alt=""
                                                 src="{{ getStorageImages(path: $banner->photo_full_url , type: 'backend-banner') }}">
                                        </td>
                                        <td>{{ translate(str_replace('_',' ',$banner->banner_type)) }}</td>
                                        <td>{{ translate(str_replace('_',' ',$banner->resource_type)) }}</td>
                                        <td>
                                            <form action="{{ route('admin.banner.status') }}" method="post"
                                                  id="banner-status{{ $banner['id'] }}-form" class="no-reload-form reload-true">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $banner['id'] }}">
                                                <label class="switcher " for="banner-status{{ $banner['id'] }}">
                                                    <input
                                                        class="switcher_input custom-modal-plugin"
                                                        type="checkbox" value="1" name="status"
                                                        id="banner-status{{ $banner['id'] }}"
                                                        {{ $banner['published'] == 1 ? 'checked' : '' }}
                                                        data-modal-type="input-change-form"
                                                        data-modal-form="#banner-status{{ $banner['id'] }}-form"
                                                        data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/banner-status-on.png') }}"
                                                        data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/banner-status-off.png') }}"
                                                        data-on-title="{{ translate('Want_to_Turn_ON').' '.translate(str_replace('_',' ',$banner->banner_type)).' '.translate('status') }}"
                                                        data-off-title="{{ translate('Want_to_Turn_OFF').' '.translate(str_replace('_',' ',$banner->banner_type)).' '.translate('status') }}"
                                                        data-on-message="<p>{{ translate('if_enabled_this_banner_will_be_available_on_the_website_and_customer_app') }}</p>"
                                                        data-off-message="<p>{{ translate('if_disabled_this_banner_will_be_hidden_from_the_website_and_customer_app') }}</p>"
                                                        data-on-button-text="{{ translate('turn_on') }}"
                                                        data-off-button-text="{{ translate('turn_off') }}">
                                                    <span class="switcher_control"></span>
                                                </label>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-10 justify-content-center">
                                                <a class="btn btn-outline-primary icon-btn edit"
                                                   title="{{ translate('edit') }}"
                                                   href="{{ route('admin.banner.update',[$banner['id']]) }}">
                                                    <i class="fi fi-sr-pencil"></i>
                                                </a>
                                                <a class="btn btn-outline-danger icon-btn banner-delete-button"
                                                   title="{{ translate('delete') }}"
                                                   id="{{ $banner['id'] }}">
                                                    <i class="fi fi-rr-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                @endforeach
                            </table>
                        </div>

                        <div class="table-responsive">
                            <div class="px-4 d-flex justify-content-lg-end">
                                {{ $banners->links() }}
                            </div>
                        </div>

                        @if(count($banners)==0)
                            @include('layouts.admin.partials._empty-state',['text'=>'no_banner_found'],['image'=>'default'])
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <span id="route-admin-banner-store" data-url="{{ route('admin.banner.store') }}"></span>
    <span id="route-admin-banner-delete" data-url="{{ route('admin.banner.delete') }}"></span>
    @include('admin-views.banner.off-canvas')


@endsection

@push('script')
    <script src="{{ dynamicAsset(path: 'public/assets/backend/admin/js/promotion/banner.js') }}"></script>
    <script>
        "use strict";

        $(document).on('ready', function () {
            getThemeWiseRatio();
        });
        let elementBannerTypeSelect = $('#banner_type_select');

        function getThemeWiseRatio() {
            let banner_type = elementBannerTypeSelect.val();
            let theme = '{{ theme_root_path() }}';
            let theme_ratio = {!! json_encode(THEME_RATIO) !!};
            let get_ratio = theme_ratio[theme][banner_type];
            $('#theme_ratio').text(get_ratio);
        }

        elementBannerTypeSelect.on('change', function () {
            getThemeWiseRatio();
            @if(theme_root_path() == 'theme_vmarket')
                setTimeout(function() {
                    $('.input-field-for-main-banner').removeClass('d-none');
                }, 50);
            @endif
        });
        @if(theme_root_path() == 'theme_vmarket')
            $('.input-field-for-main-banner').removeClass('d-none');
            $(document).on('ready', function () {
                $('.input-field-for-main-banner').removeClass('d-none');
            });
            setTimeout(function() {
                $('.input-field-for-main-banner').removeClass('d-none');
            }, 300);
        @endif
    </script>
@endpush
