@extends('layouts.vendor.app')

@section('title', translate('shop_Edit'))

@section('content')
    <div class="content container-fluid">

        <h1 class="mb-3">{{ translate('shop_Setup') }}</h1>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap flex-grow-1">
                    <div class="flex-grow-1">
                        <h3 class="text-capitalize">{{ translate('edit_Shop') }}</h3>
                        <p class="fs-12 mb-0">{{ translate('here_you_setup_your_all_business_information.') }}</p>
                    </div>
                    <a href="{{ route('vendor.shop.index') }}" class="d-flex gap-2 align-items-center">
                        <i class="fi fi-rr-arrow-small-left mt-1"></i>
                        {{ translate('Back_to_Shop_Settings') }}
                    </a>
                </div>
            </div>

            <div class="card-body">
                <div class="d-flex gap-2 alert alert-soft-warning mb-3" role="alert">
                    <i class="fi fi-sr-info"></i>
                    <p class="fs-12 mb-0 text-dark">
                        {{ translate('after_changes_all_information,_make_sure_you_click_save_button.') }}
                        {{ translate('this_setup_not_related_or_overwrite_to_your_business_information.') }}
                    </p>
                </div>

                @php($storeUrl = route('shopView', ['id' => $shop->id]))
                <div class="card bg-white border border-primary shadow-sm mb-4">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <span class="badge bg-primary text-white mb-1">🔗 {{ translate('Your_Online_Storefront_Link') }}</span>
                            <h5 class="fw-bold text-dark mb-1 text-break">{{ $storeUrl }}</h5>
                            <small class="text-muted">{{ translate('Share_this_link_with_customers_on_WhatsApp,_Instagram,_and_Facebook_so_they_can_order_from_your_catalog_online.') }}</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="navigator.clipboard.writeText('{{ $storeUrl }}'); ToastMagic.success('{{ translate('Store_link_copied_to_clipboard!') }}');">
                                <i class="tio-copy"></i> {{ translate('Copy_Link') }}
                            </button>
                            <a href="https://api.whatsapp.com/send?text={{ urlencode('Check out our online store catalog on Victorious Market: ' . $storeUrl) }}" target="_blank" class="btn btn-success btn-sm">
                                <i class="tio-whatsapp"></i> {{ translate('Share_to_WhatsApp') }}
                            </a>
                        </div>
                    </div>
                </div>

                <form action="{{ route('vendor.shop.update', [$shop->id]) }}" method="post" class="text-start form-advance-validation non-ajax-form-validate" novalidate="novalidate"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="card-sm-body mb-4">
                        <div class="mb-3">
                            <h3 class="text-capitalize">{{ translate('Shop_Name') }}</h3>
                            <p class="fs-12 mb-0">{{ translate('here_you_can_set_your_brand_logo_for_website_and_app.') }}
                            </p>
                        </div>

                        <div class="bg-light p-3 rounded">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="">
                                        <label for="name" class="text-capitalize">{{ translate('shop_name') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="name" value="{{ $shop->name }}"  data-required-msg="{{ translate('shop_name_is_required') }}"
                                            class="form-control" id="name" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="">
                                        <label for="name">{{ translate('contact') }} <span
                                                class="text-danger">*</span></label>
                                        <div class="">
                                            <input class="form-control" type="tel" name="company_phone" data-required-msg="{{ translate('contact_number_is_required') }}"
                                                value="{{ $shop->contact ?? old('phone') }}"
                                                placeholder="{{ translate('enter_phone_number') }}" required>
                                        </div>

                                        <div class="form-group">

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="">
                                        <label for="address">{{ translate('address') }} <span
                                                class="text-danger">*</span></label>
                                        <textarea type="text" rows="1" name="address" class="form-control" id="address" required data-required-msg="{{ translate('address_is_required') }}">{{ $shop->address }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-2 pt-3 border-top">
                                <div class="col-md-4">
                                    <div>
                                        <label for="delivery_state_id" class="text-capitalize">{{ translate('Operational_State') }} <span class="text-info fs-11">({{ translate('Origin_Region') }})</span></label>
                                        <select name="delivery_state_id" id="delivery_state_id" class="form-control">
                                            <option value="">{{ translate('Select_State') }}</option>
                                            @if(isset($states))
                                                @foreach($states as $state)
                                                    <option value="{{ $state->id }}" {{ (isset($shop->delivery_state_id) && $shop->delivery_state_id == $state->id) ? 'selected' : '' }}>
                                                        {{ $state->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div>
                                        <label for="delivery_city_id" class="text-capitalize">{{ translate('Dispatch_City_/_Zone') }} <span class="text-info fs-11">({{ translate('Storefront_Origin_Badge') }})</span></label>
                                        <select name="delivery_city_id" id="delivery_city_id" class="form-control">
                                            <option value="">{{ translate('Select_City') }}</option>
                                            @if(isset($cities))
                                                @foreach($cities as $city)
                                                    <option value="{{ $city->id }}" {{ (isset($shop->delivery_city_id) && $shop->delivery_city_id == $city->id) ? 'selected' : '' }}>
                                                        {{ $city->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div>
                                        <label for="delivery_hub_id" class="text-capitalize">{{ translate('Local_Dispatch_Hub_/_Landmark') }}</label>
                                        <select name="delivery_hub_id" id="delivery_hub_id" class="form-control">
                                            <option value="">{{ translate('Select_Landmark_/_Hub') }}</option>
                                            @if(isset($hubs))
                                                @foreach($hubs as $hub)
                                                    <option value="{{ $hub->id }}" {{ (isset($shop->delivery_hub_id) && $shop->delivery_hub_id == $hub->id) ? 'selected' : '' }}>
                                                        {{ $hub->name }} ({{ $hub->type == 'motor_park' ? translate('Motor_Park') : translate('Landmark') }})
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-sm-body">
                        <div class="mb-3">
                            <h3 class="text-capitalize">{{ translate('Logo_and_Cover') }}</h3>
                            <p class="fs-12 mb-0">
                                {{ translate('here_you_can_set_your_brand_logo_and_cover_for_website_and_app.') }}</p>
                        </div>

                        <div class="row gy-2">
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <div class="d-flex flex-column gap-20">
                                        <div>
                                            <label for=""
                                                class="form-label font-weight-bold text-dark mb-1 text-capitalize">
                                                {{ translate('Shop_Logo') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <p class="fs-12 mb-0">
                                                {{ translate('Upload_your_Shop_logo') }}
                                            </p>
                                        </div>
                                        <div class="upload-file">
                                            <input type="file" name="image"
                                                class="upload-file__input single_file_input"   data-max-size="{{ getFileUploadMaxSize() }}"
                                                accept="{{ getFileUploadFormats(skip: '.svg') }}" {{ empty(getStorageImages(path: $shop->image_full_url, type: 'backend-basic')) ? 'required' : '' }}>
                                            <label class="upload-file__wrapper mb-0">
                                                <div class="upload-file-textbox text-center">
                                                    <img width="34" height="34" class="svg img-fluid"
                                                        src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                        alt="image upload">
                                                    <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                                                        <span class="text-info text-capitalize">
                                                            {{ translate('Click_to_upload') }}
                                                        </span>
                                                        <br>
                                                        {{ translate('Or_drag_and_drop') }}
                                                    </h6>
                                                </div>
                                                <img class="upload-file-img" loading="lazy"
                                                    src="{{ getStorageImages(path: $shop->image_full_url, type: 'backend-basic') }}"
                                                    alt="">
                                            </label>
                                            <div class="overlay">
                                                <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                    <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                        <i class="fi fi-rr-camera"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="fs-10 mb-0 text-center">
                                            {{ getFileUploadFormats(skip: '.svg'). translate('_image_size') }}: {{ translate('Max_').getFileUploadMaxSize().'MB' }}
                                            <span class="fw-medium">
                                                ({{ THEME_RATIO[theme_root_path()]['Store cover Image'] }})
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <div class="d-flex flex-column gap-20">
                                        <div>
                                            <label for=""
                                                class="form-label font-weight-bold text-dark mb-1 text-capitalize">
                                                {{ translate('Shop_cover_image') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <p class="fs-12 mb-0">{{ translate('Upload_your_Shop_cover_image') }}</p>
                                        </div>
                                        <div class="upload-file">
                                            <input type="file" name="banner"  data-max-size="{{ getFileUploadMaxSize() }}"
                                                class="upload-file__input single_file_input"
                                                accept="{{getFileUploadFormats(skip: '.svg')}}" {{ empty(getStorageImages(path: $shop->banner_full_url, type: 'backend-banner')) ? 'required' : '' }}>
                                            <label class="upload-file__wrapper w-325 mb-0">
                                                <div class="upload-file-textbox text-center ">
                                                    <img width="34" height="34" class="svg img-fluid"
                                                        src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                        alt="image upload">
                                                    <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                                                        <span
                                                            class="text-info text-capitalize">{{ translate('Click_to_upload') }}</span>
                                                        <br>
                                                        {{ translate('Or_drag_and_drop') }}
                                                    </h6>
                                                </div>
                                                <img class="upload-file-img" loading="lazy"
                                                    src="{{ getStorageImages(path: $shop->banner_full_url, type: 'backend-banner') }}"
                                                    data-default-src="" alt="">
                                            </label>
                                            <div class="overlay">
                                                <div class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                    <button type="button" class="btn btn-outline-info icon-btn edit_btn">
                                                        <i class="fi fi-rr-camera"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="fs-10 mb-0 text-center ">
                                            {{ getFileUploadFormats(skip: '.svg'). translate('_image_size') }}: {{ translate('Max_').getFileUploadMaxSize().'MB' }}
                                            <span class="fw-medium">
                                                ({{ THEME_RATIO[theme_root_path()]['Store cover Image'] }})
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if (theme_root_path() == 'theme_aster')
                                <div class="col-md-12">
                                    <div class="bg-light p-3 rounded">
                                        <div class="d-flex flex-column gap-20">
                                            <div>
                                                <label for=""
                                                    class="form-label font-weight-bold text-dark mb-1 text-capitalize">
                                                    {{ translate('secondary_banner') }}
                                                </label>
                                                <p class="fs-12 mb-0">
                                                    {{ translate('Upload_your_Shop_secondary_banner') }}
                                                </p>
                                            </div>
                                            <div class="upload-file">
                                                <input type="file" name="bottom_banner"  data-max-size="{{ getFileUploadMaxSize() }}"
                                                    class="upload-file__input single_file_input"
                                                    accept="{{getFileUploadFormats(skip: '.svg')}}" {{ empty(getStorageImages(path: $shop->bottom_banner_full_url, type: 'backend-banner')) ? 'required' : '' }}>
                                                <label class="upload-file__wrapper w-325 mb-0">
                                                    <div class="upload-file-textbox text-center ">
                                                        <img width="34" height="34" class="svg img-fluid"
                                                            src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                            alt="image upload">
                                                        <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                                                            <span class="text-info text-capitalize">
                                                                {{ translate('Click_to_upload') }}
                                                            </span>
                                                            <br>
                                                            {{ translate('Or_drag_and_drop') }}
                                                        </h6>
                                                    </div>
                                                    <img class="upload-file-img" loading="lazy"
                                                        src="{{ getStorageImages(path: $shop->bottom_banner_full_url, type: 'backend-banner') }}"
                                                        data-default-src="" alt="">
                                                </label>
                                                <div class="overlay">
                                                    <div
                                                        class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                        <button type="button"
                                                            class="btn btn-outline-info icon-btn edit_btn">
                                                            <i class="fi fi-rr-camera"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="fs-10 mb-0 text-center ">
                                                {{ getFileUploadFormats(skip: '.svg'). translate('_image_size') }}: {{ translate('Max_').getFileUploadMaxSize().'MB' }}
                                                <span class="fw-medium text-dark">
                                                    ({{ THEME_RATIO[theme_root_path()]['Store Banner Image'] ?? 'Ratio 5.3:1 (2000 x 377 px)' }})
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if (theme_root_path() == 'theme_fashion')
                                <div class="col-md-12">
                                    <div class="bg-light p-3 rounded">
                                        <div class="d-flex flex-column gap-20">
                                            <div>
                                                <label for=""
                                                    class="form-label font-weight-bold text-dark mb-1 text-capitalize">
                                                    {{ translate('offer_banner') }}
                                                </label>
                                                <p class="fs-12 mb-0">
                                                    {{ translate('Upload_your_Shop_offer_banner') }}
                                                </p>
                                            </div>
                                            <div class="upload-file">
                                                <input type="file" name="offer_banner"
                                                    class="upload-file__input single_file_input"
                                                    accept="{{getFileUploadFormats(skip: '.svg')}}" {{ empty(getStorageImages(path: $shop->offer_banner_full_url, type: 'backend-banner')) ? 'required' : '' }}>
                                                <label class="upload-file__wrapper w-325 mb-0">
                                                    <div class="upload-file-textbox text-center ">
                                                        <img width="34" height="34" class="svg img-fluid"
                                                            src="{{ dynamicAsset(path: 'public/assets/new/back-end/img/svg/image-upload.svg') }}"
                                                            alt="image upload">
                                                        <h6 class="mt-1 fw-medium lh-base text-center fs-10">
                                                            <span class="text-info text-capitalize">
                                                                {{ translate('Click_to_upload') }}
                                                            </span>
                                                            <br>
                                                            {{ translate('Or_drag_and_drop') }}
                                                        </h6>
                                                    </div>
                                                    <img class="upload-file-img" loading="lazy"
                                                        src="{{ getStorageImages(path: $shop->offer_banner_full_url, type: 'backend-banner') }}"
                                                        data-default-src="" alt="">
                                                </label>
                                                <div class="overlay">
                                                    <div
                                                        class="d-flex gap-10 justify-content-center align-items-center h-100">
                                                        <button type="button"
                                                            class="btn btn-outline-info icon-btn edit_btn">
                                                            <i class="fi fi-rr-camera"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="fs-10 mb-0 text-center">
                                                {{ getFileUploadFormats(skip: '.svg'). translate('_image_size') }}: {{ translate('Max_').getFileUploadMaxSize().'MB' }}
                                                <span class="fw-medium">
                                                    ({{ translate('ratio') . ' ' . '( 7:1 )' }})
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-3">
                        <button type="reset" class="btn btn-secondary">{{ translate('Reset') }}</button>
                        <button type="submit" class="btn btn--primary"><i class="fi fi-sr-disk"></i>
                            {{ translate('Save_Information') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    'use strict';
    $('#delivery_state_id').on('change', function () {
        let stateId = $(this).val();
        let citySelect = $('#delivery_city_id');
        let hubSelect = $('#delivery_hub_id');
        citySelect.empty().append('<option value="">{{ translate("Select_City") }}</option>');
        hubSelect.empty().append('<option value="">{{ translate("Select_Landmark_/_Hub") }}</option>');
        if (stateId) {
            $.ajax({
                url: "{{ url('api/v1/delivery-hubs/cities') }}/" + stateId,
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    $.each(data, function (key, value) {
                        citySelect.append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        }
    });

    $('#delivery_city_id').on('change', function () {
        let cityId = $(this).val();
        let hubSelect = $('#delivery_hub_id');
        hubSelect.empty().append('<option value="">{{ translate("Select_Landmark_/_Hub") }}</option>');
        if (cityId) {
            $.ajax({
                url: "{{ url('api/v1/delivery-hubs/hubs') }}/" + cityId,
                type: 'GET',
                dataType: 'json',
                success: function (data) {
                    $.each(data, function (key, value) {
                        let typeBadge = value.type === 'motor_park' ? '({{ translate("Motor_Park") }})' : '({{ translate("Landmark") }})';
                        hubSelect.append('<option value="' + value.id + '">' + value.name + ' ' + typeBadge + '</option>');
                    });
                }
            });
        }
    });
</script>
@endpush
