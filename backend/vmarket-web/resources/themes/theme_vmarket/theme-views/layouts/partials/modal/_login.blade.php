<?php
$customerManualLogin = $web_config['customer_login_options']['manual_login'] ?? 0;
$customerOTPLogin = $web_config['customer_login_options']['otp_login'] ?? 0;
$customerSocialLogin = $web_config['customer_login_options']['social_login'] ?? 0;

if (!$customerOTPLogin && $customerManualLogin && $customerSocialLogin) {
    $multiColumn = 1;
} elseif ($customerOTPLogin && !$customerManualLogin && $customerSocialLogin) {
    $multiColumn = 1;
} elseif ($customerOTPLogin && $customerManualLogin && !$customerSocialLogin) {
    $multiColumn = 1;
} elseif ($customerOTPLogin && $customerManualLogin && $customerSocialLogin) {
    $multiColumn = 1;
} else {
    $multiColumn = 0;
}
?>
<div class="modal fade max-z-index-for-auth-modal" id="loginModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable {{ $multiColumn ? 'modal-lg' : '' }}">
        {{-- [AI] Premium Victorious auth skin: cosmetic only, all routes/ids/names unchanged.
             Critical styles inline so the premium look renders even before the public CSS mirror syncs. --}}
        <style>
            #loginModal .vm-auth-modal{border-radius:24px!important;overflow:hidden;border:1px solid rgba(255,215,0,.25)!important;box-shadow:0 30px 60px -12px rgba(26,14,42,.45)!important;max-height:calc(100vh - 3rem)!important}
            #loginModal .vm-auth-modal .modal-body{overflow-y:auto!important;-webkit-overflow-scrolling:touch}
            #loginModal .vm-auth-crown{margin:-1rem -1rem 0;padding:28px 24px 22px;background:linear-gradient(135deg,#2A0A5E 0%,#5E17EB 55%,#8B3DFF 100%);color:#fff;text-align:center;position:relative;overflow:hidden;border-radius:24px 24px 0 0}
            #loginModal .vm-auth-crown-badge{display:inline-block;font-size:11px;font-weight:800;letter-spacing:1.5px;text-transform:uppercase;color:#1A0E2A;background:linear-gradient(135deg,#FFD700,#FFB800);padding:5px 14px;border-radius:999px}
            #loginModal .vm-auth-crown h3{margin:12px 0 4px;font-weight:800;font-size:22px;color:#fff}
            #loginModal .vm-auth-crown p{margin:0;font-size:13px;opacity:.85;color:#fff}
            #loginModal .vm-auth-logo-ring{width:64px;height:64px;margin:-32px auto 8px;border-radius:18px;background:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px rgba(26,14,42,.25);border:2px solid rgba(255,215,0,.6);position:relative;z-index:2}
            #loginModal .vm-auth-logo-ring img{max-width:44px;max-height:44px}
            #loginModal .vm-auth-modal .form-control{border-radius:12px!important;padding:12px 14px!important;font-size:16px!important;pointer-events:auto!important}
            #loginModal #customer-login-form button[type="submit"]{background:linear-gradient(135deg,#5E17EB,#8B3DFF)!important;border:none!important;border-radius:14px!important;padding:14px!important;font-size:16px!important;font-weight:800!important;box-shadow:0 8px 20px rgba(94,23,235,.4)!important}
            #loginModal .vm-auth-trust{display:flex;justify-content:center;gap:16px;margin-top:16px;padding-top:14px;border-top:1px dashed rgba(94,23,235,.2);font-size:11.5px;color:#94A3B8;flex-wrap:wrap}
        </style>
        <div class="modal-content vm-auth-modal">
            <div class="vm-auth-crown">
                <span class="vm-auth-crown-badge">👑 Victorious MARKET</span>
                <h3>{{ translate('Welcome Back') }}</h3>
                <p>{{ translate('Seamless Shopping, Swift Logistics') }}</p>
            </div>
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 px-sm-5">
                <div class="vm-auth-logo-ring">
                    <img alt="logo" class="dark-support"
                        src="{{ getStorageImages(path: $web_config['web_logo'], type: 'logo') }}">
                </div>

                <div class="mb-4 text-center">
                    <h2 class="mb-2 fw-bold">{{ translate('login') }}</h2>
                    <p class="text-muted vm-auth-switch" style="text-align:center;">
                        {{ translate('login_to_your_account.') }}
                        @if ($customerManualLogin)
                            {{ translate('do_not_have_account') . '?' }}
                            <span class="text-primary link-hover-base fw-bold text-capitalize" data-bs-toggle="modal"
                                data-bs-target="#registerModal">
                                {{ translate('sign_up') }}
                            </span>
                        @endif
                    </p>
                </div>

                <div class="{{ $multiColumn ? 'row align-items-center or-sign-in-with-row' : '' }}">
                    <div class="{{ $multiColumn ? 'col-md-6' : '' }}">
                        @if ($customerOTPLogin && !$customerManualLogin && !$customerSocialLogin)
                            <form action="{{ route('customer.auth.login') }}" id="customer-login-form" method="post"
                                class="customer-centralize-login-form" autocomplete="off">
                                @csrf
                                <input type="hidden" name="keep_customer_login_redirect_url" value="{{ url()->full() }}">
                                <input type="hidden" name="login_type" value="otp-login">
                                @include('theme-views.layouts.auth-partials._phone')
                                @include('theme-views.layouts.auth-partials._firebase-recaptcha-container')
                                <div class="d-flex justify-content-center mb-3">
                                    <button type="submit" id="customerOtpLogin"
                                        class="fs-16 btn btn-primary px-5 w-100">
                                        {{ translate('Get_OTP') }}
                                    </button>
                                </div>
                            </form>
                        @elseif(!$customerOTPLogin && $customerManualLogin && !$customerSocialLogin)
                            <form action="{{ route('customer.auth.login') }}" id="customer-login-form" method="post"
                                class="customer-centralize-login-form" autocomplete="off">
                                @csrf
                                <input type="hidden" name="keep_customer_login_redirect_url" value="{{ url()->full() }}">
                                <input type="hidden" name="login_type" value="manual-login">
                                @include('theme-views.layouts.auth-partials._email')
                                @include('theme-views.layouts.auth-partials._password')
                                @include('theme-views.layouts.auth-partials._remember-me', [
                                    'forgotPassword' => true,
                                ])
                                @include('theme-views.layouts.auth-partials._recaptcha')
                                <div class="d-flex justify-content-center mb-3">
                                    <button type="submit" id="customerLoginBtn"
                                        class="fs-16 btn btn-primary px-5 w-100">
                                        {{ translate('login') }}
                                    </button>
                                </div>
                                @if (!$multiColumn)
                                    @include('theme-views.layouts.auth-partials._sign-up-instruction')
                                @endif
                            </form>
                        @elseif(!$customerOTPLogin && $customerManualLogin && $customerSocialLogin)
                            <form action="{{ route('customer.auth.login') }}" id="customer-login-form" method="post"
                                class="customer-centralize-login-form" autocomplete="off">
                                @csrf
                                <input type="hidden" name="keep_customer_login_redirect_url" value="{{ url()->full() }}">
                                <input type="hidden" name="login_type" value="manual-login">
                                @include('theme-views.layouts.auth-partials._email')
                                @include('theme-views.layouts.auth-partials._password')
                                @include('theme-views.layouts.auth-partials._remember-me', [
                                    'forgotPassword' => true,
                                ])
                                @include('theme-views.layouts.auth-partials._recaptcha')
                                <div class="d-flex justify-content-center mb-3">
                                    <button type="submit" id="customerLoginBtn"
                                        class="fs-16 btn btn-primary px-5 w-100">
                                        {{ translate('login') }}
                                    </button>
                                </div>
                                @if (!$multiColumn)
                                    @include('theme-views.layouts.auth-partials._sign-up-instruction')
                                @endif

                            </form>
                        @elseif($customerOTPLogin && !$customerManualLogin && $customerSocialLogin)
                            <form action="{{ route('customer.auth.login') }}" id="customer-login-form" method="post"
                                class="customer-centralize-login-form" autocomplete="off">
                                @csrf
                                <input type="hidden" name="keep_customer_login_redirect_url" value="{{ url()->full() }}">
                                <input type="hidden" name="login_type" value="otp-login">
                                @include('theme-views.layouts.auth-partials._phone')
                                @include('theme-views.layouts.auth-partials._firebase-recaptcha-container')
                                @include('theme-views.layouts.auth-partials._recaptcha')
                                <div class="d-flex justify-content-center mb-3">
                                    <button type="submit" id="customerOtpLogin"
                                        class="fs-16 btn btn-primary px-5 w-100">
                                        {{ translate('Get_OTP') }}
                                    </button>
                                </div>
                            </form>
                        @elseif($customerOTPLogin && $customerManualLogin)
                            <div class="manual-login-container">
                                <form action="{{ route('customer.auth.login') }}" id="customer-login-form"
                                    method="post" class="customer-centralize-login-form" autocomplete="off">
                                    @csrf

                                    <input type="hidden" name="keep_customer_login_redirect_url" value="{{ url()->full() }}">
                                    <input type="hidden" name="login_type" class="auth-login-type-input"
                                        value="manual-login">

                                    <div class="manual-login-items">
                                        @include('theme-views.layouts.auth-partials._email')
                                        @include('theme-views.layouts.auth-partials._password')
                                        @include('theme-views.layouts.auth-partials._remember-me', [
                                            'forgotPassword' => true,
                                        ])
                                    </div>

                                    <div class="otp-login-items d-none">
                                        @include('theme-views.layouts.auth-partials._phone')
                                    </div>

                                    @include('theme-views.layouts.auth-partials._recaptcha')

                                    <div class="manual-login-items">
                                        <div class="d-flex justify-content-center mb-3">
                                            <button type="submit" id="customerLoginBtn"
                                                class="fs-16 btn btn-primary px-5 w-100">
                                                {{ translate('login') }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="otp-login-items d-none">
                                        <div class="d-flex justify-content-center mb-3 w-100">
                                            <button type="submit" id=""
                                                class="fs-16 btn btn-primary px-5 w-100">
                                                {{ translate('Get_OTP') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>

                    @if ($multiColumn)
                        <div class="or-sign-in-with"><span>{{ translate('Or_Sign_in_with') }}</span></div>
                    @endif

                    @if ($multiColumn || $customerSocialLogin)
                        <div class="{{ $multiColumn ? 'col-md-6' : '' }}">
                            @if ($multiColumn)
                                <p class="text-center text-muted d-none d-md-block">{{ translate('or_continue_with') }}
                                </p>
                            @endif
                            <div class="d-flex justify-content-center flex-column align-items-center my-3 gap-3">
                                @if ($customerSocialLogin)
                                    @foreach ($web_config['customer_social_login_options'] as $socialLoginServiceKey => $socialLoginService)
                                        @if ($socialLoginService && $socialLoginServiceKey != 'apple')
                                            <a class="social-media-login-btn"
                                                href="{{ route('customer.auth.service-login', $socialLoginServiceKey) }}">
                                                <img alt=""
                                                    src="{{ theme_asset('assets/img/svg/' . $socialLoginServiceKey . '.svg') }}">
                                                <span class="text">
                                                    {{ translate($socialLoginServiceKey) }}
                                                </span>
                                            </a>
                                        @endif
                                    @endforeach
                                @endif
                                @if ($customerOTPLogin && $customerManualLogin)
                                    <a class="social-media-login-btn otp-login-btn" href="javascript:">
                                        <img alt=""
                                            src="{{ theme_asset('assets/img/svg/otp-login-icon.svg') }}">
                                        <span class="text">{{ translate('OTP_Sign_in') }}</span>
                                    </a>

                                    <a class="social-media-login-btn manual-login-btn d-none" href="javascript:">
                                        <img alt=""
                                            src="{{ theme_asset('assets/img/svg/otp-login-icon.svg') }}">
                                        <span class="text">{{ translate('Manual_Login') }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- [AI] Trust row: display only, no logic. --}}
                <div class="vm-auth-trust">
                    <span>🔒 {{ translate('Secure Sign In') }}</span>
                    <span>⚡ {{ translate('Swift Logistics') }}</span>
                    <span>👑 {{ translate('Verified Merchants') }}</span>
                </div>

            </div>
        </div>
    </div>
</div>


@push('script')
    @if ($multiColumn)
        <script>
            "use strict";

            function resizeFunc() {
                $('.or-sign-in-with').css('width', $('.or-sign-in-with-row').height())
            }
            $('#loginModal').on('show.bs.modal', function() {
                resizeFunc();
                const resizeObserver = new ResizeObserver(resizeFunc);
                resizeObserver.observe(document.querySelector('.or-sign-in-with-row'));
            });
        </script>
    @endif
@endpush
