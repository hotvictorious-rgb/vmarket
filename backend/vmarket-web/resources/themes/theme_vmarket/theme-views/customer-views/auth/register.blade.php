@extends('theme-views.layouts.app')

@section('title', translate('Create an Account') . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 48px 16px 80px; max-width: 620px; margin: 0 auto;">
    <div style="background: var(--vm-surface); border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-xl); padding: 36px 32px; box-shadow: var(--vm-shadow-md);">
        
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 50%; background: var(--vm-primary-light); color: var(--vm-primary); margin-bottom: 14px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
            </div>
            <h1 style="font-size: 24px; font-weight: 800; color: var(--vm-dark); margin-bottom: 6px;">{{ translate('Create Your Account') }}</h1>
            <p style="font-size: 14px; color: var(--vm-text-muted);">
                {{ translate('Join Victorious MARKET for verified shopping, fast logistics, and exclusive merchant offers.') }}
            </p>
        </div>

        @if($errors->any())
            <div style="background: #FEE2E2; border: 1px solid #FCA5A5; border-radius: var(--vm-radius-md); padding: 12px 16px; margin-bottom: 20px; color: #991B1B; font-size: 13.5px;">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('customer.auth.sign-up') }}" method="POST" id="vmRegisterForm">
            @csrf
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: var(--vm-dark); margin-bottom: 6px;">
                        {{ translate('First Name') }} <span style="color: var(--vm-danger);">*</span>
                    </label>
                    <input type="text" name="f_name" value="{{ old('f_name') }}" required placeholder="{{ translate('e.g. Victor') }}"
                           style="width: 100%; padding: 11px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 14px; outline: none; transition: border-color 0.2s;"
                           onfocus="this.style.borderColor='var(--vm-primary)'" onblur="this.style.borderColor='var(--vm-border)'">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: var(--vm-dark); margin-bottom: 6px;">
                        {{ translate('Last Name') }} <span style="color: var(--vm-danger);">*</span>
                    </label>
                    <input type="text" name="l_name" value="{{ old('l_name') }}" required placeholder="{{ translate('e.g. Edet') }}"
                           style="width: 100%; padding: 11px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 14px; outline: none; transition: border-color 0.2s;"
                           onfocus="this.style.borderColor='var(--vm-primary)'" onblur="this.style.borderColor='var(--vm-border)'">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--vm-dark); margin-bottom: 6px;">
                    {{ translate('Email Address') }} <span style="color: var(--vm-danger);">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="{{ translate('e.g. customer@example.com') }}"
                       style="width: 100%; padding: 11px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 14px; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='var(--vm-primary)'" onblur="this.style.borderColor='var(--vm-border)'">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--vm-dark); margin-bottom: 6px;">
                    {{ translate('Phone Number') }} <span style="color: var(--vm-danger);">*</span>
                </label>
                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="{{ translate('e.g. 08012345678') }}"
                       style="width: 100%; padding: 11px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 14px; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='var(--vm-primary)'" onblur="this.style.borderColor='var(--vm-border)'">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: var(--vm-dark); margin-bottom: 6px;">
                        {{ translate('Password') }} <span style="color: var(--vm-danger);">*</span>
                    </label>
                    <input type="password" name="password" required placeholder="{{ translate('Minimum 8 chars') }}" minlength="8"
                           style="width: 100%; padding: 11px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 14px; outline: none; transition: border-color 0.2s;"
                           onfocus="this.style.borderColor='var(--vm-primary)'" onblur="this.style.borderColor='var(--vm-border)'">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: var(--vm-dark); margin-bottom: 6px;">
                        {{ translate('Confirm Password') }} <span style="color: var(--vm-danger);">*</span>
                    </label>
                    <input type="password" name="con_password" required placeholder="{{ translate('Repeat password') }}" minlength="8"
                           style="width: 100%; padding: 11px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 14px; outline: none; transition: border-color 0.2s;"
                           onfocus="this.style.borderColor='var(--vm-primary)'" onblur="this.style.borderColor='var(--vm-border)'">
                </div>
            </div>

            @php($refEarning = getWebConfig(name: 'ref_earning_status'))
            @if($refEarning)
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: var(--vm-text-muted); margin-bottom: 6px;">
                        {{ translate('Referral Code (Optional)') }}
                    </label>
                    <input type="text" name="referral_code" value="{{ old('referral_code', request('referral_code')) }}" placeholder="{{ translate('Enter code if referred by a friend') }}"
                           style="width: 100%; padding: 11px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 14px; outline: none;">
                </div>
            @endif

            <button type="submit" class="vm-btn-primary" style="width: 100%; padding: 14px; font-size: 15px; font-weight: 800; justify-content: center; margin-top: 8px;">
                <span>{{ translate('Register Account') }}</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </form>

        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--vm-border-light); font-size: 14px; color: var(--vm-text-muted);">
            {{ translate('Already have an account?') }}
            <a href="javascript:" data-bs-toggle="modal" data-bs-target="#loginModal" style="color: var(--vm-primary); font-weight: 700; text-decoration: none;">
                {{ translate('Sign In') }}
            </a>
        </div>

    </div>
</div>
@endsection
