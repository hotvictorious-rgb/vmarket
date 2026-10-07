<form action="{{route('admin.push-notification.update',['type'=>'logistics_company'])}}"
      class="text-start" method="post" enctype="multipart/form-data" id="push-notification-form">
    @csrf
    <div class="row g-4 mb-4">
        @foreach ($logisticsMessages as $key=>$value )
            <div class="col-md-6 col-lg-4">
                <div class="form-group">
                    <div
                        class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-10">
                        <label for="logistics_{{$value['key']}}"
                               class="switcher_content form-label mb-0 text-capitalize">{{ translate($value['key'])}}</label>
                        <label class="switcher" for="logistics_{{$value['key']}}">
                            <input
                                class="switcher_input custom-modal-plugin"
                                type="checkbox" value="1" name="status{{$value['id']}}"
                                id="logistics_{{$value['key']}}"
                                {{$value['status']==1?'checked':''}}
                                data-modal-type="input-change"
                                data-modal-form="#push-notifications-form"
                                data-on-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/notification-on.png') }}"
                                data-off-image="{{ dynamicAsset(path: 'public/assets/new/back-end/img/modal/notification-off.png') }}"
                                data-on-title="{{ translate('Want_to_Turn_ON_Logistics_Notification') }}"
                                data-off-title="{{ translate('Want_to_Turn_OFF_Logistics_Notification') }}"
                                data-on-message="<p>{{ translate('if_enabled_logistics_companies_will_receive_portal_and_dispatch_notifications') }}</p>"
                                data-off-message="<p>{{ translate('if_disabled_logistics_companies_will_not_receive_notifications') }}</p>"
                                data-on-button-text="{{ translate('turn_on') }}"
                                data-off-button-text="{{ translate('turn_off') }}">
                            <span class="switcher_control"></span>
                        </label>
                    </div>
                    @foreach (json_decode($language) as $lang)
                            <?php
                            if (count($value['translations'])) {
                                $translate = [];
                                foreach ($value['translations'] as $t) {
                                    if ($t->locale == $lang && $t->key == $value['key']) {
                                        $translate[$lang][$value['key']] = $t->value;
                                    }
                                }
                            }
                            ?>
                        <input type="hidden" name="lang{{$value['id']}}[]"
                               value="{{ $lang }}">
                        <textarea name="message{{$value['id']}}[]" rows="4"
                                  class="form-control text-area-max-min {{ $lang != $default_lang ? 'd-none' : '' }} lang-form {{ $lang }}-form">{{$translate[$lang][$value['key']]??$value['message']}}</textarea>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    <div class="d-flex gap-3 flex-wrap justify-content-end">
        <button type="reset" class="btn btn-secondary px-4 {{ getDemoModeFormButton(type: 'class') }}">
            {{ translate('reset') }}
        </button>
        <button type="{{ getDemoModeFormButton(type: 'button') }}" class="btn btn-primary px-4 {{ getDemoModeFormButton(type: 'class') }}">
            {{ translate('submit') }}
        </button>
    </div>
</form>
