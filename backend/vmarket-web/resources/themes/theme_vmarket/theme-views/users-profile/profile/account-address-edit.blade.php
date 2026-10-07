@extends('theme-views.layouts.app')

@section('title', translate('edit_Address').' | '.$web_config['company_name'].' '.translate('ecommerce'))

@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body p-lg-4">
                            <div class="mt-4">
                                <form action="{{route('address-update')}}" method="post">
                                    @csrf
                                    <div class="row gy-4">
                                        <div class="col-md-6">
                                            <input type="hidden" name="id" value="{{ $shippingAddress->id}}">
                                            <div class="">
                                                <h6 class="fw-semibold text-muted mb-3 text-capitalize">{{translate('choose_label')}}</h6>
                                                <ul class="option-select-btn flex-wrap style--two gap-4 mb-4">
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="address_type" hidden
                                                                   value="home" {{ Str::lower($shippingAddress->address_type) == 'home' ? 'checked':''}}>
                                                            <span><i class="bi bi-house"></i></span>
                                                        </label>
                                                        {{translate('home')}}
                                                    </li>
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="address_type" hidden
                                                                   value="permanent" {{ Str::lower($shippingAddress->address_type) == 'permanent' ? 'checked':''}}>
                                                            <span><i class="bi bi-paperclip"></i></span>
                                                        </label>
                                                        {{translate('permanent')}}
                                                    </li>
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="address_type" hidden=""
                                                                   value="office" {{ Str::lower($shippingAddress->address_type) == 'office' ? 'checked':''}}>
                                                            <span><i class="bi bi-briefcase"></i></span>
                                                        </label>
                                                        {{ translate('office') }}
                                                    </li>
                                                </ul>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="name"
                                                       class="text-capitalize">{{translate('contact_person')}}</label>
                                                <input type="text" id="name" name="name" class="form-control"
                                                       value="{{ $shippingAddress['contact_person_name']}}"
                                                       placeholder="{{translate('ex').':'.translate('jhon').translate('doe')}}">
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="phone2">{{translate('phone')}}</label>
                                                <div
                                                    class="select-wrap focus-border form-control rounded d-flex align-items-center px-0">
                                                    <input type="tel" id="phone"
                                                           class="form-control bg-transparent focus-input phone-input-with-country-picker"
                                                           name="phone" value="{{ $shippingAddress['phone'] }}"
                                                           placeholder="{{translate('ex').':'.('01xxxxxxxxx')}}">
                                                </div>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="country">{{translate('country')}}</label>
                                                <select name="country" id="country"
                                                        class="form-select select2 select_picker">
                                                    <option value="" disabled selected>
                                                        {{ translate('select_country') }}
                                                    </option>
                                                    @if($country_restrict_status)
                                                        @foreach($delivery_countries as $country)
                                                            <option
                                                                value="{{ $country['name']}}" {{ $country['name'] == $shippingAddress['country']?'selected' : ''}}>{{ $country['name']}}</option>
                                                        @endforeach
                                                    @else
                                                        @foreach(COUNTRIES as $country)
                                                            <option
                                                                value="{{ $country['name'] }}" {{ $shippingAddress['country'] == $country['name']? 'selected' : '' }}>{{ $country['name'] }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="address-state">{{translate('state_or_region') ?? 'State / Region'}} <span class="text-danger">*</span></label>
                                                <select name="state_id" id="address-state" class="form-select" data-selected-state-id="{{ $shippingAddress->state_id ?? '' }}" data-selected-state-name="{{ $shippingAddress->state ?? '' }}" required>
                                                    <option value="">{{translate('loading_states') ?? 'Loading states...'}}</option>
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="address-lga">{{translate('lga') ?? 'LGA (Local Government Area)'}} <span class="text-danger">*</span></label>
                                                <select name="lga_id" id="address-lga" class="form-select" data-selected-lga-id="{{ $shippingAddress->lga_id ?? '' }}" data-selected-lga-name="{{ $shippingAddress->city ?? '' }}" required>
                                                    <option value="">{{translate('select_state_first') ?? 'Select State first'}}</option>
                                                </select>
                                                <input type="hidden" name="city" id="address-city" value="{{ $shippingAddress->city }}">
                                            </div>

                                            <div class="form-group">
                                                <label class="text-capitalize"
                                                       for="zip-code">{{translate('zip_code')}}</label>
                                                @if($zip_restrict_status)
                                                    <select name="zip" class="form-control select2 select-picker"
                                                            data-live-search="true" id="" required>
                                                        @foreach($delivery_zipcodes as $zip)
                                                            <option
                                                                value="{{ $zip->zipcode }}" {{ $zip->zipcode == $shippingAddress->zip? 'selected' : ''}}>{{ $zip->zipcode }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input class="form-control" type="text" id="zip_code" name="zip"
                                                           value="{{ $shippingAddress->zip }}" required  placeholder="{{ translate('Ex') }}: 520211">
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6 mt-5 mt-md-0">
                                            <div class="d-flex justify-content-end mb-5">
                                                <a href="{{ route('user-profile') }}"
                                                   class="btn-link text-secondary d-flex align-items-baseline">
                                                    <i class="bi bi-chevron-left fs-12"></i> {{translate('go_back')}}
                                                </a>
                                            </div>
                                            <h6 class="fw-semibold text-muted mb-3 text-capitalize">{{translate('choose_address_type')}}</h6>
                                            <div class="d-flex flex-wrap style--two gap-4 mb-3">
                                                <div>
                                                    <label class="d-flex align-items-center gap-2 cursor-pointer">
                                                        <input type="radio" name="is_billing"
                                                               value="1" {{ $shippingAddress->is_billing == 1 ? 'checked' : ''}} >
                                                        {{translate('Billing_Address')}}
                                                    </label>
                                                </div>
                                                <div>
                                                    <label class="d-flex align-items-center gap-2 cursor-pointer">
                                                        <input type="radio" name="is_billing"
                                                               value="0" {{ $shippingAddress->is_billing == 0 ? 'checked' : ''}} >
                                                        {{translate('Shipping_Address')}}
                                                    </label>
                                                </div>
                                            </div>
                                            @if(getWebConfig('map_api_status') ==1 )
                                                <div class="mb-3 ">
                                                    <input id="pac-input" class="controls rounded __inline-46"
                                                           title="{{translate('search_your_location_here')}}"
                                                           type="text"
                                                           placeholder="{{translate('search_here')}}"/>
                                                    <div class="dark-support rounded w-100 __h-14rem"
                                                         id="location_map_canvas"></div>
                                                </div>
                                            @endif
                                            <div class="form-group">
                                                <label for="address">{{translate('Address')}}</label>
                                                <textarea name="address" id="address" rows="5" class="form-control"
                                                          placeholder="{{translate('ex').': 14 Ikot Ekpene Road, Uyo' }}">{{ $shippingAddress['address']}}</textarea>
                                            </div>
                                        </div>
                                        <input type="hidden" id="latitude"
                                               name="latitude" class="form-control d-inline"
                                               placeholder="{{ translate('ex').':'.'-94.22213' }}"
                                               value="{{ $shippingAddress->latitude??0}}" required readonly>
                                        <input type="hidden"
                                               name="longitude" class="form-control"
                                               placeholder="{{ translate('ex').':'.'103.344322' }}" id="longitude"
                                               value="{{ $shippingAddress->longitude??0}}" required readonly>
                                        <div class="col-12">
                                            <div
                                                class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                                                <label class="custom-checkbox"></label>

                                                <div class="d-flex justify-content-end gap-3">
                                                    <button type="reset"
                                                            class="btn btn-secondary">{{translate('reset')}}</button>
                                                    <button type="submit"
                                                            class="btn btn-primary text-capitalize">{{translate('update_address')}}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <span id="address-latitude" data-latitude="{{ $shippingAddress['latitude']??'-33.8688'}}" hidden></span>
    <span id="address-longitude" data-longitude="{{ $shippingAddress['longitude']??'151.2195'}}" hidden></span>
@endsection
@push('script')
    <script src="{{ theme_asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ theme_asset('assets/js/address.js') }}"></script>
    @if(getWebConfig('map_api_status') ==1 )
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{getWebConfig('map_api_key')}}&callback=callBackFunction&loading=async&libraries=places&v=3.56"
            defer>
        </script>
    @endif
    <script>
        $(document).ready(function() {
            var $state = $('#address-state');
            var $lga = $('#address-lga');
            var targetStateId = $state.data('selected-state-id');
            var targetStateName = ($state.data('selected-state-name') || '').toLowerCase();
            var targetLgaId = $lga.data('selected-lga-id');
            var targetLgaName = ($lga.data('selected-lga-name') || '').toLowerCase();

            // 1. Fetch Nigerian states
            $.get("{{ url('geography/states') }}", function(res) {
                if (res && res.status && res.data) {
                    $state.empty().append('<option value="">{{ translate("select_state") ?? "Select State" }}</option>');
                    res.data.forEach(function(s) {
                        var isSel = (targetStateId && parseInt(targetStateId) === parseInt(s.id)) ||
                                    (!targetStateId && targetStateName && targetStateName === s.name.toLowerCase()) ||
                                    (!targetStateId && !targetStateName && s.name.toLowerCase() === 'akwa ibom');
                        $state.append('<option value="' + s.id + '" ' + (isSel ? 'selected' : '') + '>' + s.name + '</option>');
                    });
                    if ($state.val()) {
                        $state.trigger('change');
                    }
                }
            });

            // 2. Cascade state change to fetch LGAs
            $state.on('change', function() {
                var stateId = $(this).val();
                $lga.empty().append('<option value="">{{ translate("loading_lgas") ?? "Loading LGAs..." }}</option>');
                if (!stateId) {
                    $lga.html('<option value="">{{ translate("select_state_first") ?? "Select State first" }}</option>');
                    return;
                }
                $.get("{{ url('geography/lgas') }}/" + stateId, function(res) {
                    if (res && res.status && res.data) {
                        $lga.empty().append('<option value="">{{ translate("select_lga") ?? "Select LGA (e.g. Uyo, Eket)" }}</option>');
                        res.data.forEach(function(l) {
                            var isLgaSel = (targetLgaId && parseInt(targetLgaId) === parseInt(l.id)) ||
                                           (targetLgaName && targetLgaName === l.name.toLowerCase());
                            $lga.append('<option value="' + l.id + '" ' + (isLgaSel ? 'selected' : '') + '>' + l.name + '</option>');
                        });
                        // Once initial preselection finishes, reset so future user clicks work naturally
                        targetLgaId = null;
                        targetLgaName = '';
                    }
                });
            });

            // 3. Set city name when LGA is chosen
            $lga.on('change', function() {
                var lgaName = $(this).find('option:selected').text();
                if (lgaName && lgaName.indexOf('Select') === -1) {
                    $('#address-city').val(lgaName);
                }
            });
        });
    </script>
@endpush
