<div class="modal fade max-z-index-for-auth-modal" id="registerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        {{-- [AI] Premium Victorious auth skin: cosmetic only, all routes/ids/names/JS hooks unchanged.
             Critical styles inline so the premium look renders even before the public CSS mirror syncs. --}}
        <style>
            #registerModal .vm-auth-modal{border-radius:24px!important;overflow:hidden;border:1px solid rgba(255,215,0,.25)!important;box-shadow:0 30px 60px -12px rgba(26,14,42,.45)!important}
            #registerModal .vm-auth-crown{margin:-1rem -1rem 0;padding:28px 24px 22px;background:linear-gradient(135deg,#2A0A5E 0%,#5E17EB 55%,#8B3DFF 100%);color:#fff;text-align:center;position:relative;overflow:hidden}
            #registerModal .vm-auth-crown-badge{display:inline-block;font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:#1A0E2A;background:linear-gradient(135deg,#FFD700,#FFB800);padding:5px 14px;border-radius:999px}
            #registerModal .vm-auth-crown h3{margin:12px 0 4px;font-weight:800;font-size:22px;color:#fff}
            #registerModal .vm-auth-crown p{margin:0;font-size:13px;opacity:.85;color:#fff}
            #registerModal .vm-auth-logo-ring{width:64px;height:64px;margin:-32px auto 8px;border-radius:18px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(26,14,42,.25);border:2px solid rgba(255,215,0,.6);position:relative;z-index:2}
            #registerModal .vm-auth-logo-ring img{max-width:44px;max-height:44px}
            #registerModal .vm-auth-modal .form-control{border-radius:12px!important;padding:12px 14px!important;font-size:14px!important}
            #registerModal #customer-sign-up-btn{background:linear-gradient(135deg,#5E17EB,#8B3DFF)!important;border:none!important;border-radius:14px!important;padding:14px 48px!important;font-size:16px!important;font-weight:800!important;box-shadow:0 8px 20px rgba(94,23,235,.4)!important}
            #registerModal .vm-auth-trust{display:flex;justify-content:center;gap:16px;margin-top:16px;padding-top:14px;border-top:1px dashed rgba(94,23,235,.2);font-size:11.5px;color:#94A3B8;flex-wrap:wrap}
        </style>
        <div class="modal-content vm-auth-modal">
            <div class="vm-auth-crown">
                <span class="vm-auth-crown-badge">👑 Victorious MARKET</span>
                <h3>{{ translate('Join the Marketplace') }}</h3>
                <p>{{ translate('Seamless Shopping, Swift Logistics') }}</p>
            </div>
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 px-lg-5">
                <div class="vm-auth-logo-ring">
                    <img alt="logo" class="dark-support"
                         src="{{ getStorageImages(path: $web_config['web_logo'], type: 'logo') }}">
                </div>
                <div class="mb-4 text-center">
                    <h2 class="mb-2 fw-bold">{{ translate('sign_up') }}</h2>
                    <p class="text-muted vm-auth-switch" style="text-align:center;">
                        {{ translate('login_to_your_account.') }} {{ translate('Do_n’t_have_account') }}?
                        <span class="text-primary link-hover-base fw-bold" data-bs-toggle="modal" data-bs-target="#loginModal">
                            {{ translate('login') }}
                        </span>
                    </p>
                </div>
                <form action="{{ route('customer.auth.sign-up') }}" method="POST" id="customer-form"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="custom-scrollbar height-45vh">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group mb-4">
                                    <label class="text-capitalize" for="f_name"> {{ translate('first_name') }}</label>
                                    <input type="text" id="f_name" name="f_name" class="form-control"
                                           placeholder="{{ translate('ex') . ':' . translate('Jhone') }}"
                                           value="{{ old('f_name') }}" required/>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group mb-4">
                                    <label class="text-capitalize" for="l_name">{{ translate('last_name') }}</label>
                                    <input type="text" id="l_name" name="l_name" value="{{ old('l_name') }}"
                                           class="form-control"
                                           placeholder="{{ translate('ex') . ':' . translate('doe') }}" required/>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group mb-4">
                                    <label for="r_email">{{ translate('email') }}</label>
                                    <input type="text" id="r_email" value="{{ old('email') }}" name="email"
                                           class="form-control"
                                           placeholder="{{ translate('enter_email_or_phone_number') }}"
                                           autocomplete="off"
                                           required/>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group mb-4">
                                    <label for="phone">{{ translate('phone') }}</label>
                                    <input type="tel" id="phone" value="{{ old('phone') }}"
                                           class="form-control phone-input-with-country-picker" name="phone"
                                           placeholder="{{ translate('enter_phone_number') }}" required/>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-4">
                                    @php($randomLabelId=rand(1111, 9999))
                                    <label for="password-{{ $randomLabelId }}">
                                        {{ translate('password') }}
                                        <span class="text-danger mx-1 password-error"></span>
                                    </label>
                                    <div class="input-inner-end-ele">
                                        <input type="password" id="password-{{ $randomLabelId }}" name="password"
                                               class="form-control"
                                               placeholder="{{ translate('minimum_8_characters_long') }}"
                                               autocomplete="off" required/>
                                        <i class="bi bi-eye-slash-fill togglePassword"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="mb-4">
                                    <label class="text-capitalize"
                                           for="confirm_password-{{ $randomLabelId }}">
                                        {{ translate('confirm_password') }}
                                    </label>
                                    <div class="input-inner-end-ele">
                                        <input type="password" id="confirm_password-{{ $randomLabelId }}"
                                               class="form-control" name="con_password"
                                               placeholder="{{ translate('minimum_8_characters_long') }}"
                                               autocomplete="off" required/>
                                        <i class="bi bi-eye-slash-fill togglePassword"></i>
                                    </div>
                                </div>
                            </div>
                            @if ($web_config['ref_earning_status'])
                                <div class="col-sm-12">
                                    <div class="mb-4">
                                        <div class="form-group">
                                            <label class="form-label form--label text-capitalize"
                                                   for="referral_code">{{ translate('refer_code') }} <small
                                                    class="text-muted">({{ translate('optional') }})</small></label>
                                            <input type="text" id="referral_code" class="form-control"
                                                   name="referral_code"
                                                   placeholder="{{ translate('use_referral_code') }}">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @php($recaptcha = getWebConfig(name: 'recaptcha'))

                        @if (is_array($web_config['firebase_otp_verification'] ?? null) && !empty($web_config['firebase_otp_verification']['status']))
                            <div class="generate-firebase-auth-recaptcha" id="firebase-auth-recaptcha-{{ rand(111, 999) }}"></div>
                        @elseif(is_array($recaptcha ?? null) && !empty($recaptcha['status']) && $recaptcha['status'] == 1)
                            <div class="dynamic-default-and-recaptcha-section">
                                <input type="hidden" name="g-recaptcha-response" class="render-grecaptcha-response"
                                       data-action="customer_auth" data-input="#register-default-captcha-section"
                                       data-default-captcha="#register-default-captcha-section">

                                <div class="default-captcha-container d-none" id="register-default-captcha-section"
                                     data-placeholder="{{ translate('enter_captcha_value') }}"
                                     data-base-url="{{ route('g-recaptcha-session-store') }}"
                                     data-session="{{ 'default_recaptcha_id_customer_auth' }}">
                                </div>
                            </div>
                        @else
                            <div class="default-captcha-container"
                                 data-placeholder="{{ translate('enter_captcha_value') }}"
                                 data-base-url="{{ route('g-recaptcha-session-store') }}"
                                 data-session="{{ 'default_recaptcha_id_customer_auth' }}">
                            </div>
                        @endif

                        <div class="d-flex justify-content-center mt-4">
                            <label for="input-checked" class="d-flex gap-1 align-items-center mb-0 user-select-none">
                                <input type="checkbox" id="input-checked" required/>
                                {{ translate('i_agree_with_the') }}
                                <a href="{{ route('business-page.view', ['slug' => 'terms-and-conditions']) }}"
                                   class="text-info text-capitalize">
                                    {{ translate('terms_&_conditions') }}
                                </a>
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center mt-4 mb-3">
                        <button type="submit" id="customer-sign-up-btn" class="btn btn-primary px-5 text-capitalize vm-auth-submit"
                                disabled>{{ translate('sign_up') }}</button>
                    </div>
                </form>

                {{-- [AI] Trust row: display only, no logic. --}}
                <div class="vm-auth-trust">
                    <span>🔒 {{ translate('Secure Registration') }}</span>
                    <span>⚡ {{ translate('Swift Logistics') }}</span>
                    <span>👑 {{ translate('Verified Merchants') }}</span>
                </div>

                @if ($web_config['social_login_text'])
                    <p class="text-center text-muted">{{ translate('or_continue_with') }}</p>
                @endif

                <div class="d-flex justify-content-center gap-3 align-items-center flex-wrap pb-3">
                    @if (isset($web_config['customer_login_options']['social_login']) &&
                            $web_config['customer_login_options']['social_login']
                    )
                        @foreach ($web_config['customer_social_login_options'] as $socialLoginServiceKey => $socialLoginService)
                            @if ($socialLoginService && $socialLoginServiceKey != 'apple')
                                <a href="{{ route('customer.auth.service-login', $socialLoginServiceKey) }}">
                                    <img width="35"
                                         src="{{ theme_asset('assets/img/svg/' . $socialLoginServiceKey . '.svg') }}"
                                         alt="" class="dark-support"/>
                                </a>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
    <script>
        "use strict";
        $('#input-checked').change(function () {
            if ($(this).is(':checked')) {
                $('#customer-sign-up-btn').removeAttr('disabled');
            } else {
                $('#customer-sign-up-btn').attr('disabled', 'disabled');
            }
        });
        $('#customer-form').submit(function (event) {
            event.preventDefault();
            let formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: formData,
                beforeSend: function () {
                    $("#loading").addClass("d-grid");
                },
                success: function (response) {
                    if (response.errors) {
                        for (let index = 0; index < response.errors.length; index++) {
                            toastr.error(response.errors[index].message);
                        }
                    } else if (response.error) {
                        toastr.error(response.error);
                    } else if (response.status === 1) {
                        toastr.success(response.message);
                    }

                    if (response?.redirect_url) {
                        window.location.href = response.redirect_url;
                    }
                },
                error: function (xhr) {
                    $("#loading").removeClass("d-grid");

                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        toastr.error(xhr.responseJSON.error, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function (key, value) {
                            toastr.error(value, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        });
                    } else {
                        toastr.error('An unexpected error occurred.', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                },
                complete: function () {
                    $("#loading").removeClass("d-grid");
                }
            });
        });
    </script>

    <script src="{{ theme_asset('assets/js/password-strength.js') }}"></script>
@endpush
