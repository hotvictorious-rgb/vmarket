@extends('theme-views.layouts.app')
@section('title', translate('add_Address').' | '.$web_config['company_name'].' '.translate('ecommerce'))
@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-5">
        <div class="container">
            <div class="row g-3">
                @include('theme-views.partials._profile-aside')
                <div class="col-lg-9">
                    <div class="card h-100">
                        <div class="card-body p-lg-4">
                            <div class="mt-4">
                                <form action="{{route('address-store')}}" method="post">
                                    @csrf
                                    <div class="row gy-4">
                                        <div class="col-md-6">
                                            <div class="">
                                                <h6 class="fw-semibold text-muted mb-3 text-capitalize">{{translate('choose_label')}}</h6>
                                                <ul class="option-select-btn flex-wrap style--two gap-4 mb-4">
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="address_type" value="home" hidden
                                                                   checked>
                                                            <span><i class="bi bi-house"></i></span>
                                                        </label>
                                                        {{translate('home')}}
                                                    </li>
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="address_type" value="permanent"
                                                                   hidden="">
                                                            <span><i class="bi bi-paperclip"></i></span>
                                                        </label>
                                                        {{translate('permanent')}}
                                                    </li>
                                                    <li>
                                                        <label>
                                                            <input type="radio" name="address_type" value="office"
                                                                   hidden="">
                                                            <span><i class="bi bi-briefcase"></i></span>
                                                        </label>
                                                        {{translate('office')}}
                                                    </li>
                                                </ul>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label class="text-capitalize"
                                                       for="name">{{translate('contact_person')}}</label>
                                                <input type="text" id="name" class="form-control" name="name"
                                                       placeholder="{{translate('ex').':'.translate('jhon').translate('doe')}}"
                                                       required>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="phone">{{translate('phone')}}</label>
                                                <input type="tel" id="phone" class="form-control phone-input-with-country-picker" required
                                                       placeholder="{{translate('ex').':'.translate('01xxxxxxxxx')}}" name="phone">
                                            </div>

                                            <div class="form-group mb-3 ">
                                                <label for="country">{{translate('country')}}</label>
                                                <select name="country" id="country" class="form-control select-picker"
                                                        required>
                                                    <option value="" disabled
                                                            selected>{{translate('select_country')}}</option>
                                                    @foreach($countries as $country)
                                                        <option
                                                            value="{{ $country['name'] }}" {{ $country['name'] == 'Nigeria' ? 'selected' : '' }}>{{ $country['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="address-state">{{translate('state_or_region') ?? 'State / Region'}} <span class="text-danger">*</span></label>
                                                <select name="state_id" id="address-state" class="form-select" required>
                                                    <option value="">{{translate('loading_states') ?? 'Loading states...'}}</option>
                                                </select>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="address-lga">{{translate('lga') ?? 'LGA (Local Government Area)'}} <span class="text-danger">*</span></label>
                                                <select name="lga_id" id="address-lga" class="form-select" required>
                                                    <option value="">{{translate('select_state_first') ?? 'Select State first'}}</option>
                                                </select>
                                                <input type="hidden" name="city" id="address-city" value="">
                                            </div>

                                            <div class="form-group">
                                                <label for="zip-code">{{translate('zip_code')}}</label>
                                                @if($zip_restrict_status)
                                                    <select name="zip" class="form-control select2 select_picker"
                                                            data-live-search="true" required>
                                                        @foreach($zip_codes as $code)
                                                            <option
                                                                value="{{ $code->zipcode }}">{{ $code->zipcode }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input class="form-control" type="text" id="zip" name="zip"
                                                           required placeholder="{{ translate('Ex') }}: 520211" value="520211">
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
                                                    <label
                                                        class="d-flex align-items-center gap-2 cursor-pointer text-capitalize">
                                                        <input type="radio" name="is_billing" checked="" value="1">
                                                        {{translate('billing_address')}}
                                                    </label>
                                                </div>
                                                <div>
                                                    <label
                                                        class="d-flex align-items-center gap-2 cursor-pointer text-capitalize">
                                                        <input type="radio" name="is_billing" value="0">
                                                        {{translate('shipping_address')}}
                                                    </label>
                                                </div>
                                            </div>
                                            @if(getWebConfig('map_api_status') ==1 )
                                            <div class="mb-3 ">
                                                <input id="pac-input" class="controls rounded __inline-46"
                                                       title="{{translate('search_your_location_here')}}" type="text"
                                                       placeholder="{{translate('search_here')}}"/>
                                                <div class="dark-support rounded w-100 __h-14rem"
                                                     id="location_map_canvas"></div>
                                            </div>
                                            @endif
                                            <div class="form-group">
                                                <label for="address">{{translate('address')}}</label>
                                                <textarea name="address" id="address" rows="5" class="form-control"
                                                          placeholder="{{translate('ex').': 14 Ikot Ekpene Road, Uyo' }}"
                                                          required></textarea>
                                            </div>
                                        </div>
                                        <input type="hidden" id="latitude"
                                               name="latitude" class="form-control d-inline"
                                               placeholder="{{ translate('ex').': -94.22213'}}"
                                               value="{{$default_location?$default_location['lat']:0}}" required
                                               readonly>
                                        <input type="hidden"
                                               name="longitude" class="form-control"
                                               placeholder="{{ translate('ex').': 103.344322' }}" id="longitude"
                                               value="{{$default_location?$default_location['lng']:0}}" required>
                                        <div class="col-12">
                                            <div
                                                class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
                                                <label class="custom-checkbox"></label>
                                                <div class="d-flex justify-content-end gap-3">
                                                    <button type="reset"
                                                            class="btn btn-secondary">{{translate('reset')}}</button>
                                                    <button type="submit"
                                                            class="btn btn-primary text-capitalize">{{translate('add_address')}}</button>
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
    <span id="address-latitude" data-latitude="{{$default_location?$default_location['lat']:'-33.8688'}}" hidden></span>
    <span id="address-longitude" data-longitude="{{$default_location?$default_location['lng']:'151.2195'}}" hidden></span>
@endsection
@push('script')
    <script src="{{ theme_asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ theme_asset('assets/js/address.js') }}"></script>
    @if(getWebConfig('map_api_status') ==1 )
        <script
            src="https://maps.googleapis.com/maps/api/js?key={{getWebConfig('map_api_key')}}&callback=callBackFunction&loading=async&libraries=places&v=3.56" defer>
        </script>
    @endif
    <script>
        $(document).ready(function() {
            var $state = $('#address-state');
            var $lga = $('#address-lga');

            // 1. Fetch Nigerian states
            $.get("{{ url('geography/states') }}", function(res) {
                if (res && res.status && res.data) {
                    $state.empty().append('<option value="">{{ translate("select_state") ?? "Select State" }}</option>');
                    res.data.forEach(function(s) {
                        var selected = (s.name.toLowerCase() === 'akwa ibom') ? 'selected' : '';
                        $state.append('<option value="' + s.id + '" ' + selected + '>' + s.name + '</option>');
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
                            $lga.append('<option value="' + l.id + '">' + l.name + '</option>');
                        });
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
