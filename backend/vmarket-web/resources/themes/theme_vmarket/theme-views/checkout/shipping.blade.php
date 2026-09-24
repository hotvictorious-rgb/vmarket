@extends('theme-views.layouts.app')

@section('title', translate('shopping_Details').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <h4 class="text-center mb-3 text-capitalize">{{ translate('shipping_details') }}</h4>
            <div class="row">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <div class="card h-100">
                        <div class="card-body  px-sm-4">
                            <div class="d-flex justify-content-center mb-30">
                                <ul class="cart-step-list">
                                    <li class="done cursor-pointer get-view-by-onclick"
                                        data-link="{{route('shop-cart')}}"><span><i
                                                class="bi bi-check2"></i></span> {{ translate('cart') }}</li>
                                    <li class="current cursor-pointer get-view-by-onclick text-capitalize"
                                        data-link="{{ route('checkout-details') }}"><span><i
                                                class="bi bi-check2"></i></span> {{ translate('shipping_details') }}
                                    </li>
                                    <li><span><i class="bi bi-check2"></i></span> {{ translate('payment') }}</li>
                                </ul>
                            </div>
                            <input type="hidden" id="physical-product" name="physical_product"
                                   value="{{ $physical_product_view ? 'yes':'no'}}">
                            <input type="hidden" id="billing-input-enable" name="billing_input_enable"
                                   value="{{ $billing_input_by_customer }}">
                            @if($physical_product_view)
                                @php
                                    $cartItems = \App\Utils\CartManager::getCartListQuery(type: 'checked');
                                    $uniqueShops = [];
                                    foreach ($cartItems as $item) {
                                        $sellerId = $item['seller_id'];
                                        $sellerIs = $item['seller_is'];
                                        if ($sellerIs == 'admin') {
                                            $uniqueShops['admin'] = [
                                                'name' => getWebConfig(name: 'company_name') ?? 'Victorious Central Store',
                                                'address' => getWebConfig(name: 'shop_address') ?? 'Victorious Central Hub, Nigeria',
                                            ];
                                        } else {
                                            $shop = \App\Models\Shop::where('seller_id', $sellerId)->first();
                                            if ($shop && !isset($uniqueShops['seller_' . $sellerId])) {
                                                $uniqueShops['seller_' . $sellerId] = [
                                                    'name' => $shop->name ?? 'Vendor Store',
                                                    'address' => $shop->address ?? 'Store Location',
                                                ];
                                            }
                                        }
                                    }
                                @endphp

                                <div class="fulfillment-selector d-flex p-1 bg-light rounded-3 mb-4" style="border: 1.5px solid rgba(114, 50, 187, 0.15);">
                                    <button type="button" class="btn w-50 py-2 fw-bold text-capitalize rounded-3 active btn-primary text-white" id="fulfillment-tab-delivery" onclick="switchFulfillment('delivery')">
                                        <i class="bi bi-truck me-1"></i> {{ translate('doorstep_delivery') ?? 'Doorstep Delivery' }}
                                    </button>
                                    <button type="button" class="btn w-50 py-2 fw-bold text-capitalize rounded-3 text-dark" id="fulfillment-tab-pickup" onclick="switchFulfillment('pickup')">
                                        <i class="bi bi-shop me-1"></i> {{ translate('in_shop_pickup') ?? 'In-Shop Pickup' }}
                                        <span class="badge bg-success ms-1 font-size-10">{{ translate('pay_zero_now') ?? 'Pay ₦0 Now' }}</span>
                                    </button>
                                </div>

                                <div id="in-shop-pickup-container" class="d-none mb-4">
                                    <h5 class="mb-3 text-capitalize"><i class="bi bi-shop text-primary me-2"></i>{{ translate('store_pickup_locations') ?? 'Store Pickup Locations' }}</h5>
                                    <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2 rounded-3" style="background-color: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.25); color: #065F46;">
                                        <i class="bi bi-clock-history fs-18"></i>
                                        <span class="fs-13"><strong>{{ translate('24_hour_hold') ?? '24-Hour Stock Hold' }}:</strong> {{ translate('inspect_in_person_pay_at_store') ?? 'Pay ₦0.00 right now. Inspect items in person at the vendor counter before final payment.' }}</span>
                                    </div>

                                    @foreach($uniqueShops as $shop)
                                        <div class="card mb-3 border rounded-3 shadow-sm">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <i class="bi bi-building fs-18 text-primary"></i>
                                                    <h6 class="mb-0 fw-bold">{{ $shop['name'] }}</h6>
                                                </div>
                                                <div class="d-flex align-items-start gap-2 text-muted fs-13 mb-3">
                                                    <i class="bi bi-geo-alt-fill text-danger mt-1"></i>
                                                    <div>{{ $shop['address'] }}</div>
                                                </div>

                                                {{-- [AI] Direction Guidance Box with Message Support button (STRICTLY NO PHONE NUMBER) --}}
                                                <div class="p-2 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: rgba(114, 50, 187, 0.05); border: 1px dashed rgba(114, 50, 187, 0.25);">
                                                    <div class="d-flex align-items-center gap-2 fs-12 text-dark">
                                                        <i class="bi bi-compass text-primary fs-15"></i>
                                                        <span>{{ translate('need_help_finding_this_store') ?? 'Need help finding this store? Message Customer Support for step-by-step guidance.' }}</span>
                                                    </div>
                                                    <a href="{{ route('support-ticket') }}" class="btn btn-sm btn-primary rounded-2 text-nowrap py-1 px-2 fs-12">
                                                        <i class="bi bi-chat-dots me-1"></i>{{ translate('message_support') ?? 'Message Support' }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    <div class="mt-4">
                                        @if(auth('customer')->check())
                                            <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-16 rounded-3" id="btn-reserve-pickup" onclick="submitPickupReservation()">
                                                <i class="bi bi-bag-check me-2"></i>{{ translate('reserve_store_pickup_pay_zero') ?? 'Reserve for Store Pickup (Pay ₦0.00 Now)' }}
                                            </button>
                                        @else
                                            <a href="{{ route('customer.auth.login') }}" class="btn btn-primary w-100 py-3 fw-bold fs-16 rounded-3">
                                                <i class="bi bi-box-arrow-in-right me-2"></i>{{ translate('login_to_reserve_pickup') ?? 'Log in to Reserve for Store Pickup' }}
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <form method="post" id="address-form">
                                    <h5 class="mb-3 text-capitalize">{{ translate('delivery_information_details') }}</h5>

                                    <div class="card">
                                        <div class="card-body" id="collapseThree">
                                            <div class="bg-light p-3 rounded d-flex flex-wrap justify-content-between gap-3 mb-3">
                                                <h6 class="text-capitalize">{{ translate('Shipping_Address') }}</h6>
                                                @if(auth('customer')->check())
                                                    <a href="javascript:" type="button" data-bs-toggle="modal"
                                                       data-bs-target="#shippingSavedAddressModal"
                                                       class="btn-link text-primary text-capitalize">{{ translate('Saved_Address') }}</a>
                                                @endif
                                            </div>

                                            @if(auth('customer')->check())
                                                <div class="modal fade" id="shippingSavedAddressModal" data-bs-backdrop="static"
                                                     data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered justify-content-center">
                                                        <div class="modal-content border-0">
                                                            <div class="modal-header">
                                                                <h5 class="text-capitalize"
                                                                    id="contact_sellerModalLabel">{{translate('saved_addresses')}}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                        aria-label="Close"></button>
                                                            </div>

                                                            <div class="modal-body custom-scrollbar">
                                                                <div class="product-quickview">
                                                                    <div
                                                                        class="shipping-saved-addresses {{ $shipping_addresses->count()<1 ? 'd--none':'' }}">
                                                                        <div class="row gy-3 text-dark py-4">
                                                                            @foreach($shipping_addresses as $key=>$address)
                                                                                <div class="col-md-12">
                                                                                    <div class="card border-0">
                                                                                        <div
                                                                                            class="card-header bg-transparent gap-2 align-items-center d-flex flex-wrap justify-content-between">
                                                                                            <label
                                                                                                class="d-flex align-items-center gap-3 cursor-pointer mb-0">
                                                                                                <input type="radio"
                                                                                                       name="shipping_method_id"
                                                                                                       value="{{$address['id']}}" {{$key==0?'checked':''}}>
                                                                                                <h6>{{$address['address_type']}}</h6>
                                                                                            </label>
                                                                                            <div
                                                                                                class="d-flex align-items-center gap-3">
                                                                                                <button type="button"
                                                                                                        onclick="location.href='{{ route('address-edit', ['id' => $address->id]) }}'"
                                                                                                        class="p-0 bg-transparent border-0">
                                                                                                    <img
                                                                                                        src="{{ theme_asset('assets/img/svg/location-edit.svg') }}"
                                                                                                        alt="" class="svg">
                                                                                                </button>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="card-body">
                                                                                            <address>
                                                                                                <dl class="mb-0 flexible-grid sm-down-1 width--5rem">
                                                                                                    <dt>{{ translate('name') }}</dt>
                                                                                                    <dd class="shipping-contact-person">{{$address['contact_person_name']}}</dd>

                                                                                                    <dt>{{ translate('phone') }}</dt>
                                                                                                    <dd class="">
                                                                                                        <a href="tel:{{$address['phone']}}"
                                                                                                           class="text-dark shipping-contact-phone">{{$address['phone']}}</a>
                                                                                                    </dd>

                                                                                                    <dt>{{ translate('address') }}</dt>
                                                                                                    <dd>{{$address['address']}}
                                                                                                        , {{$address['city']}}
                                                                                                        , {{$address['zip']}}</dd>
                                                                                                    <span
                                                                                                        class="shipping-contact-address d-none">{{ $address['address'] }}</span>
                                                                                                    <span
                                                                                                        class="shipping-contact-city d-none">{{ $address['city'] }}</span>
                                                                                                    <span
                                                                                                        class="shipping-contact-zip d-none">{{ $address['zip'] }}</span>
                                                                                                    <span class="shipping-contact-country d-none">{{ $address['country'] }}</span>
                                                                                                    <span class="shipping-contact-address-type d-none">{{ $address['address_type'] }}</span>
                                                                                                    <span class="shipping-contact-latitude-type d-none">{{ $address['latitude'] }}</span>
                                                                                                    <span class="shipping-contact-longitude-type d-none">{{ $address['longitude'] }}</span>
                                                                                                </dl>
                                                                                            </address>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        class="text-center {{ $shipping_addresses->count()>0 ? 'd--none':'' }}">
                                                                        <img src="{{theme_asset('assets/img/svg/address.svg')}}"
                                                                             alt="address" class="w-25">
                                                                        <h5 class="my-3 pt-1 text-muted">
                                                                            {{translate('no_address_is_saved')}}!
                                                                        </h5>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">{{ translate('close') }}</button>
                                                                <button type="button" class="btn btn-primary"
                                                                        data-bs-dismiss="modal">{{ translate('save') }}</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-sm-{{ auth('customer')->check() ? 6 : 12 }}">
                                                            <div class="form-group mb-4">
                                                                <label for="name"
                                                                       class="text-capitalize">{{ translate('contact_person_name')}} <span class="text-danger">*</span></label>
                                                                <input type="text" name="contact_person_name" id="name"
                                                                       class="form-control"
                                                                       placeholder="{{ translate('ex') }}: {{translate('Jhon_Doe')}}" {{$shipping_addresses->count()==0?'required':''}}>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label for="phone">{{ translate('phone') }} <span class="text-danger">*</span></label>
                                                                <input type="tel" id="phoneNumber" name="phone"
                                                                       class="form-control"
                                                                       placeholder="{{ translate('ex') }}: {{translate('+8801000000000')}}" {{$shipping_addresses->count()==0?'required':''}}>
                                                            </div>
                                                        </div>
                                                        @if(!auth('customer')->check())
                                                            <div class="col-sm-6">
                                                                <div class="form-group mb-4">
                                                                    <label for="email">{{ translate('email') }} <span class="text-danger">*</span></label>
                                                                    <input type="email" name="email" id="email"
                                                                           class="form-control"
                                                                           placeholder="{{ translate('ex') }}: {{translate('email@domain.com')}}"
                                                                           required>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label for="address-type"
                                                                       class="text-capitalize">{{ translate('address_type')}}</label>
                                                                <select name="address_type" id="address-type"
                                                                        class="form-select">
                                                                    <option value="permanent">{{ translate('permanent')}}</option>
                                                                    <option value="home">{{ translate('home')}}</option>
                                                                    <option value="office">{{ translate('office')}}</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label for="country">{{ translate('country') }} <span class="text-danger">*</span></label>
                                                                <select name="country" id="country"
                                                                        class="form-control select_picker select2">
                                                                    @forelse($countries as $country)
                                                                        <option
                                                                            value="{{ $country['name'] }}">{{ $country['name'] }}</option>
                                                                    @empty
                                                                        <option
                                                                            value="">{{ translate('no_country_to_deliver') }}</option>
                                                                    @endforelse
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label for="city">{{ translate('city') }} <span class="text-danger">*</span></label>
                                                                <input type="text" name="city" id="city"
                                                                       placeholder="{{ translate('ex') }}: {{translate('dhaka')}}"
                                                                       class="form-control" {{$shipping_addresses->count()==0?'required':''}}>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label for="city"
                                                                       class="text-capitalize">{{ translate('zip_code') }} <span class="text-danger">*</span></label>
                                                                @if($zip_restrict_status == 1)
                                                                    <select name="zip" id="zip"
                                                                            class="form-control select2 select_picker"
                                                                            data-live-search="true" required>
                                                                        @forelse($zip_codes as $code)
                                                                            <option
                                                                                value="{{ $code->zipcode }}">{{ $code->zipcode }}</option>
                                                                        @empty
                                                                            <option
                                                                                value="">{{ translate('no_zip_to_deliver') }}</option>
                                                                        @endforelse
                                                                    </select>
                                                                @else
                                                                    <input type="text" class="form-control" id="zip"
                                                                           name="zip"
                                                                           placeholder="{{ translate('ex') }}: {{translate('1216')}}" {{$shipping_addresses->count()==0?'required':''}}>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-12">
                                                            <div class="form-group mb-4">
                                                                <div class="d-flex gap-2 align-items-center justify-content-between mb-2">
                                                                    <label for="address" class="mb-0">{{ translate('address') }} <span class="text-danger">*</span></label>
                                                                    @if(getWebConfig('map_api_status') == 1)
                                                                        <a href="javascript:" type="button" data-bs-toggle="modal"
                                                                        data-bs-target="#shippingMapModal"
                                                                        class="btn-link text-primary text-capitalize">{{ translate('Set_Precise_Location') }}
                                                                            <i class="fi fi-sr-land-layer-location d-flex"></i>
                                                                        </a>
                                                                        <div class="modal fade" id="shippingMapModal" tabindex="-1"
                                                                            aria-hidden="true">
                                                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-body">
                                                                                        <div class="product-quickview">
                                                                                            <button type="button" class="btn-close outside"
                                                                                                    data-bs-dismiss="modal"
                                                                                                    aria-label="Close"></button>
                                                                                            <input id="pac-input"
                                                                                                class="controls rounded __inline-46"
                                                                                                title="{{translate('search_your_location_here')}}"
                                                                                                type="text"
                                                                                                placeholder="{{translate('search_here')}}"/>
                                                                                            <div class="dark-support rounded w-100 __h-14rem"
                                                                                                id="location_map_canvas"></div>
                                                                                            <input type="hidden" id="latitude"
                                                                                                name="latitude" class="form-control d-inline"
                                                                                                placeholder="{{ translate('ex') }} : {{ translate('-94.22213') }}"
                                                                                                value="{{$default_location?$default_location['lat']:0}}"
                                                                                                required readonly>
                                                                                            <input type="hidden"
                                                                                                name="longitude" class="form-control"
                                                                                                placeholder="{{ translate('ex') }} : {{ translate('103.344322') }}"
                                                                                                id="longitude"
                                                                                                value="{{$default_location?$default_location['lng']:0}}"
                                                                                                required>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <input type="text" name="address" id="address"
                                                                       class="form-control"
                                                                       placeholder="{{ translate('your_address') }}" {{$shipping_addresses->count()==0?'required':''}}>
                                                            </div>
                                                        </div>

                                                        <div class="col-sm-12">
                                                            <label class="custom-checkbox align-items-center fw-bold"
                                                                   id="save-address-label">
                                                                <input type="hidden" name="shipping_method_id"
                                                                       id="shipping-method-id" value="0">
                                                                @if(auth('customer')->check())
                                                                    <input type="checkbox" name="save_address"
                                                                           id="saveAddress">
                                                                    {{ translate('save_this_address') }}
                                                                @endif
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </form>

                                @if(!Auth::guard('customer')->check() && $web_config['guest_checkout_status'])
                                    <div class="card __card mt-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center flex-wrap justify-content-between gap-3">
                                                <div class="min-h-45 d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                                    <input type="checkbox" id="is_check_create_account" name="is_check_create_account">
                                                    <label class="fw-bold fs-13 mb-0" for="is_check_create_account">
                                                        {{translate('Create_an_account_with_the_above_info')}}
                                                    </label>
                                                </div>

                                                <div class="is_check_create_account_password_group d--none">
                                                    <div class="d-flex gap-3 flex-wrap flex-sm-nowrap">
                                                        <div class="">
                                                            <div class="input-inner-end-ele">
                                                                <input name="customer_password" type="password" id="customer_password" class="form-control" placeholder="{{ translate('new_Password') }}" required="">
                                                                <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                            </div>
                                                        </div>
                                                        <div class="">
                                                            <div class="input-inner-end-ele">
                                                                <input name="customer_confirm_password" type="password" id="customer_confirm_password" class="form-control" placeholder="{{ translate('confirm_Password') }}" required="">
                                                                <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                            @endif

                            @if($billing_input_by_customer)
                                <div class="card card-body mt-3 {{ $billing_input_by_customer ? '':'d-none' }}">
                                    <div class="bg-light rounded p-3">
                                        <div class="d-flex flex-wrap justify-content-between gap-3">
                                            <h6 class="text-capitalize">{{ translate('billing_address') }}</h6>
                                            <div class="d-flex gap-3 align-items-center flex-wrap">
                                                @if(auth('customer')->check())
                                                    <a href="javascript:" type="button" data-bs-toggle="modal"
                                                        data-bs-target="#billingSavedAddressModal"
                                                        class="btn-link text-primary text-capitalize">{{ translate('Saved_Address') }}</a>
                                                @endif
                                                @if($physical_product_view)
                                                    <label class="custom-checkbox" class="text-capitalize">
                                                        {{ translate('same_as_delivery_address') }}
                                                        <input type="checkbox" id="same-as-shipping-address"
                                                               name="same_as_shipping_address"
                                                               class="billing-address-checkbox" checked>
                                                    </label>
                                                @endif
                                            </div>

                                        </div>
                                    </div>

                                    @if(!$physical_product_view)
                                        <div class="mt-3 alert--info">
                                            <div class="d-flex align-items-center gap-2">
                                                <img class="mb-1" src="{{ theme_asset('assets/img/icons/info-light.svg') }}" alt="Info">
                                                <span>{{ translate('When_you_input_all_the_required_information_for_this_billing_address_it_will_be_stored_for_future_purchases') }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    @if(auth('customer')->check())
                                        <div class="modal fade" id="billingSavedAddressModal"
                                             data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                                             aria-hidden="true">
                                            <div
                                                class="modal-dialog modal-lg modal-dialog-centered justify-content-center">
                                                <div class="modal-content border-0 max-width-500">
                                                    <div class="modal-header">
                                                        <h5 class="text-capitalize"
                                                            id="contact_sellerModalLabel">{{translate('saved_addresses')}}</h5>
                                                        <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                    </div>

                                                    <div class="modal-body custom-scrollbar">
                                                        <div class="product-quickview">
                                                            <div
                                                                class="billing-saved-addresses {{ $billing_addresses->count()<1 ? 'd--none':'' }}">
                                                                <div class="row gy-3 text-dark py-4">
                                                                    @foreach($billing_addresses as $key=>$address)
                                                                        <div class="col-md-12">
                                                                            <div class="card border-0 ">
                                                                                <div
                                                                                    class="card-header bg-transparent gap-2 align-items-center d-flex flex-wrap justify-content-between">
                                                                                    <label
                                                                                        class="d-flex align-items-center gap-3 cursor-pointer mb-0">
                                                                                        <input type="radio"
                                                                                               value="{{$address['id']}}"
                                                                                               name="billing_method_id" {{$key==0?'checked':''}}>
                                                                                        <h6>{{$address['address_type']}}</h6>
                                                                                    </label>
                                                                                    <div
                                                                                        class="d-flex align-items-center gap-3">
                                                                                        <button type="button"
                                                                                                onclick="location.href='{{ route('address-edit', ['id' => $address->id]) }}'"
                                                                                                class="p-0 bg-transparent border-0">
                                                                                            <img
                                                                                                src="{{ theme_asset('assets/img/svg/location-edit.svg') }}"
                                                                                                alt=""
                                                                                                class="svg">
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="card-body pb-0">
                                                                                    <address>
                                                                                        <dl class="mb-0 flexible-grid sm-down-1 width--5rem">
                                                                                            <dt>{{ translate('name') }}</dt>
                                                                                            <dd class="billing-contact-name">{{$address['contact_person_name']}}</dd>

                                                                                            <dt>{{ translate('phone') }}</dt>
                                                                                            <dd class="">
                                                                                                <a href="tel:{{$address['phone']}}"
                                                                                                   class="text-dark billing-contact-phone">{{$address['phone']}}</a>
                                                                                            </dd>

                                                                                            <dt>{{ translate('address') }}</dt>
                                                                                            <dd>{{$address['address']}}
                                                                                                , {{$address['city']}}
                                                                                                , {{$address['zip']}}</dd>
                                                                                            <span
                                                                                                class="billing-contact-address d-none">{{ $address['address'] }}</span>
                                                                                            <span
                                                                                                class="billing-contact-city d-none">{{ $address['city'] }}</span>
                                                                                            <span
                                                                                                class="billing-contact-zip d-none">{{ $address['zip'] }}</span>
                                                                                            <span
                                                                                                class="billing-contact-country d-none">{{ $address['country'] }}</span>
                                                                                            <span
                                                                                                class="billing-contact-address-type d-none">{{ $address['address_type'] }}</span>
                                                                                            <span class="billing-contact-latitude-type d-none">{{ $address['latitude'] }}</span>
                                                                                            <span class="billing-contact-longitude-type d-none">{{ $address['longitude'] }}</span>
                                                                                        </dl>
                                                                                    </address>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="text-center {{ $billing_addresses->count()>0 ? 'd--none':'' }}">
                                                                <img
                                                                    src="{{theme_asset('assets/img/svg/address.svg')}}"
                                                                    alt="address" class="w-25">
                                                                <h5 class="my-3 pt-1 text-muted">
                                                                    {{translate('no_address_is_saved')}}!
                                                                </h5>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">{{ translate('close') }}</button>
                                                        <button type="button" class="btn btn-primary"
                                                                data-bs-dismiss="modal">{{ translate('save') }}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    <form method="post" id="billing-address-form">
                                        <div class="toggle-billing-address mt-3 d--none" id="hide-billing-address">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div
                                                            class="col-sm-{{ auth('customer')->check() ? 6 : 12 }}">
                                                            <div class="form-group mb-4">
                                                                <label for="billing-contact-person-name"
                                                                       class="text-capitalize">{{ translate('contact_person_name')}} <span class="text-danger">*</span></label>
                                                                <input type="text"
                                                                       name="billing_contact_person_name"
                                                                       id="billing-contact-person-name"
                                                                       class="form-control"
                                                                       placeholder="{{ translate('ex') }}: {{translate('Jhon_Doe')}}" {{$billing_addresses->count()==0?'required':''}}>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label for="billing-phone">
                                                                    {{ translate('phone') }} <span class="text-danger">*</span>
                                                                </label>
                                                                <input type="tel" name="billing_phone"
                                                                       id="billing-phone" class="form-control"
                                                                       placeholder="{{ translate('ex') }}: {{translate('+88 01000000000')}}" {{$billing_addresses->count()==0?'required':''}}>
                                                            </div>
                                                        </div>
                                                        @if(!auth('customer')->check())
                                                            <div class="col-sm-6">
                                                                <div class="form-group mb-4">
                                                                    <label for="billing_contact_email">
                                                                        {{ translate('email') }} <span class="text-danger">*</span>
                                                                    </label>
                                                                    <input type="email"
                                                                           name="billing_contact_email"
                                                                           id="billing-contact-email"
                                                                           class="form-control"
                                                                           placeholder="{{ translate('ex') }}: {{translate('email@domain.com')}}"
                                                                           required>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label for="billing_address_type"
                                                                       class="text-capitalize">{{ translate('address_type')}}</label>
                                                                <select name="billing_address_type"
                                                                        id="billing-address-type"
                                                                        class="form-select">
                                                                    <option
                                                                        value="permanent">{{ translate('permanent')}}</option>
                                                                    <option
                                                                        value="home">{{ translate('home')}}</option>
                                                                    <option
                                                                        value="office">{{ translate('office')}}</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label
                                                                    for="billing-country">{{ translate('country') }} <span class="text-danger">*</span></label>
                                                                <select name="billing_country"
                                                                        id="billing-country"
                                                                        class="form-control select_picker select2">
                                                                    @forelse($countries as $country)
                                                                        <option
                                                                            value="{{ $country['name'] }}">{{ $country['name'] }}</option>
                                                                    @empty
                                                                        <option
                                                                            value="">{{ translate('no_country_to_deliver') }}</option>
                                                                    @endforelse
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label
                                                                    for="billing-city">{{ translate('city') }} <span class="text-danger">*</span></label>
                                                                <input type="text" name="billing_city"
                                                                       id="billing-city"
                                                                       placeholder="{{ translate('ex') }}: {{translate('Dhaka')}}"
                                                                       class="form-control" {{$billing_addresses->count()==0?'required':''}}>
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <div class="form-group mb-4">
                                                                <label
                                                                    for="billing-zip">{{ translate('Zip_Code') }} <span class="text-danger">*</span></label>
                                                                @if($zip_restrict_status == 1)
                                                                    <select name="billing_zip" id="billing-zip"
                                                                            class="form-control select2 select_picker"
                                                                            data-live-search="true" required>
                                                                        @forelse($zip_codes as $code)
                                                                            <option
                                                                                value="{{ $code->zipcode }}">{{ $code->zipcode }}</option>
                                                                        @empty
                                                                            <option
                                                                                value="">{{ translate('no_zip_to_deliver') }}</option>
                                                                        @endforelse
                                                                    </select>
                                                                @else
                                                                    <input type="text" class="form-control"
                                                                        id="billing-zip" name="billing_zip"
                                                                        placeholder="{{ translate('ex') }}: {{translate('1216')}}" {{$billing_addresses->count()==0?'required':''}}>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-12">
                                                            <div class="form-group mb-4">
                                                                <div class="d-flex gap-2 align-items-center justify-content-between mb-2">
                                                                    <label class="mb-0"
                                                                        for="billing_address">{{ translate('address') }} <span class="text-danger">*</span></label>
                                                                    @if(getWebConfig('map_api_status') == 1)
                                                                        <a href="javascript:" data-bs-toggle="modal"
                                                                        data-bs-target="#billingMapModal"
                                                                        class="btn-link text-primary text-capitalize">
                                                                            {{ translate('Set_Precise_Location') }}
                                                                            <i class="fi fi-sr-land-layer-location d-flex"></i>
                                                                        </a>
                                                                        <div class="modal fade" id="billingMapModal" tabindex="-1"
                                                                            aria-hidden="true">
                                                                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                                                                <div class="modal-content">
                                                                                    <div class="modal-body">
                                                                                        <div class="product-quickview">
                                                                                            <button type="button" class="btn-close outside"
                                                                                                    data-bs-dismiss="modal"
                                                                                                    aria-label="Close"></button>
                                                                                            <input id="pac-input-billing"
                                                                                                class="controls rounded __inline-46"
                                                                                                title="{{translate('search_your_location_here')}}"
                                                                                                type="text"
                                                                                                placeholder="{{translate('search_here')}}"/>
                                                                                            <div
                                                                                                class="dark-support rounded w-100 __h-14rem"
                                                                                                id="billing-location-map-canvas"></div>
                                                                                            <input type="hidden" id="billing-latitude"
                                                                                                name="billing_latitude"
                                                                                                class="form-control d-inline"
                                                                                                placeholder="{{translate('ex')}} : {{translate('-94.22213')}}"
                                                                                                value="{{$default_location?$default_location['lat']:0}}"
                                                                                                required readonly>
                                                                                            <input type="hidden"
                                                                                                name="billing_longitude"
                                                                                                class="form-control"
                                                                                                placeholder="{{ translate('ex') }} : {{translate('103.344322')}}"
                                                                                                id="billing-longitude"
                                                                                                value="{{$default_location?$default_location['lng']:0}}"
                                                                                                required>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <input type="text" name="billing_address"
                                                                    id="billing_address"
                                                                    class="form-control"
                                                                    placeholder="{{ translate('your_address') }}" {{$shipping_addresses->count()==0?'required':''}}>
                                                            </div>
                                                        </div>

                                                        <input type="hidden" name="billing_method_id"
                                                               id="billing-method-id" value="0">
                                                        @if(auth('customer')->check())
                                                            <div class="col-sm-12">
                                                                <label
                                                                    class="custom-checkbox save-billing-address fw-bold"
                                                                    id="save-billing-address-label">
                                                                    <input type="checkbox"
                                                                           name="save_address_billing"
                                                                           id="save_address_billing">
                                                                    {{ translate('save_this_address') }}
                                                                </label>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </form>
                                </div>

                                @if(!Auth::guard('customer')->check() && $web_config['guest_checkout_status'] && !$physical_product_view)
                                    <div class="card __card mt-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center flex-wrap justify-content-between gap-3">
                                                <div class="d-flex gap-2 align-items-center cursor-pointer user-select-none">
                                                    <input type="checkbox" id="is_check_create_account" name="is_check_create_account">
                                                    <label class="fw-bold fs-13 text-capitalize mb-0" for="is_check_create_account">
                                                        {{ translate('Create_an_account_with_the_above_info') }}
                                                    </label>
                                                </div>

                                                <div class="is_check_create_account_password_group d--none">
                                                    <div class="d-flex gap-3 flex-wrap flex-sm-nowrap">
                                                        <div class="">
                                                            <div class="input-inner-end-ele">
                                                                <input name="customer_password" type="password" id="customer_password" class="form-control" placeholder="{{ translate('new_Password') }}" required="">
                                                                <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                            </div>
                                                        </div>
                                                        <div class="">
                                                            <div class="input-inner-end-ele">
                                                                <input name="customer_confirm_password" type="password" id="customer_confirm_password" class="form-control" placeholder="{{ translate('confirm_Password') }}" required="">
                                                                <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                @include('theme-views.partials._order-summery')
            </div>
        </div>
    </main>

    <span id="shipping-address-location"
          data-latitude="{{ $default_location ? $default_location['lat'] : '' }}"
          data-longitude="{{ $default_location ? $default_location['lng'] : '' }}">
</span>
@endsection
@push('script')
    <script src="{{ theme_asset('assets/js/shipping-page.js') }}"></script>

    @if(getWebConfig('map_api_status') ==1 )
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{getWebConfig('map_api_key')}}&callback=mapsLoading&loading=async&libraries=places&v=3.56"
            defer>
        </script>
    @endif

    <script>
        function switchFulfillment(type) {
            if (type === 'pickup') {
                $('#address-form').addClass('d-none');
                $('#in-shop-pickup-container').removeClass('d-none');
                $('#fulfillment-tab-delivery').removeClass('active btn-primary text-white').addClass('text-dark');
                $('#fulfillment-tab-pickup').addClass('active btn-primary text-white').removeClass('text-dark');
                $('#proceed-to-next-action').addClass('d-none');
            } else {
                $('#address-form').removeClass('d-none');
                $('#in-shop-pickup-container').addClass('d-none');
                $('#fulfillment-tab-delivery').addClass('active btn-primary text-white').removeClass('text-dark');
                $('#fulfillment-tab-pickup').removeClass('active btn-primary text-white').addClass('text-dark');
                $('#proceed-to-next-action').removeClass('d-none');
            }
        }

        function submitPickupReservation() {
            var $btn = $('#btn-reserve-pickup');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>{{ translate("reserving") ?? "Reserving..." }}');
            $.ajax({
                url: "{{ route('pickup-reservations.create') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    idempotency_key: "prc_web_" + Date.now() + "_" + Math.floor(Math.random() * 10000),
                    checked_only: 1
                },
                success: function(response) {
                    if (response.status) {
                        toastr.success(response.message || "{{ translate('reservation_created_successfully') ?? 'Reservation created successfully!' }}");
                        window.location.href = "{{ route('account-oder') }}";
                    } else {
                        toastr.error(response.message || "{{ translate('unable_to_create_reservation') ?? 'Unable to create reservation.' }}");
                        $btn.prop('disabled', false).html('<i class="bi bi-bag-check me-2"></i>{{ translate("reserve_store_pickup_pay_zero") ?? "Reserve for Store Pickup (Pay ₦0.00 Now)" }}');
                    }
                },
                error: function(xhr) {
                    var msg = "{{ translate('unable_to_process_pickup_reservation') ?? 'Unable to process pickup reservation.' }}";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg);
                    $btn.prop('disabled', false).html('<i class="bi bi-bag-check me-2"></i>{{ translate("reserve_store_pickup_pay_zero") ?? "Reserve for Store Pickup (Pay ₦0.00 Now)" }}');
                }
            });
        }
    </script>
@endpush
