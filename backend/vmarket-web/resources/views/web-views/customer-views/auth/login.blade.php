@extends('theme-views.layouts.app')

@section('title', translate('Sign In') . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 48px 16px 80px; max-width: 500px; margin: 0 auto;">
    <div style="background: var(--vm-surface); border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-xl); padding: 36px 32px; box-shadow: var(--vm-shadow-md);">
        
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 50%; background: var(--vm-primary-light); color: var(--vm-primary); margin-bottom: 14px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
            </div>
            <h1 style="font-size: 24px; font-weight: 800; color: var(--vm-dark); margin-bottom: 6px;">{{ translate('Welcome Back') }}</h1>
            <p style="font-size: 14px; color: var(--vm-text-muted);">
                {{ translate('Sign in to manage your orders, deliveries, and merchant interactions.') }}
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

        <form action="{{ route('customer.auth.login') }}" method="POST" id="vmLoginForm">
            @csrf
            <input type="hidden" name="login_type" value="manual-login">
            <input type="hidden" name="keep_customer_login_redirect_url" value="{{ session('keep_customer_login_redirect_url', route('home')) }}">

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: var(--vm-dark); margin-bottom: 6px;">
                    {{ translate('Email or Phone Number') }} <span style="color: var(--vm-danger);">*</span>
                </label>
                <input type="text" name="user_id" value="{{ old('user_id') }}" required placeholder="{{ translate('Enter email or phone') }}"
                       style="width: 100%; padding: 12px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 14px; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='var(--vm-primary)'" onblur="this.style.borderColor='var(--vm-border)'">
            </div>

            <div style="margin-bottom: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label style="font-size: 13px; font-weight: 700; color: var(--vm-dark); margin: 0;">
                        {{ translate('Password') }} <span style="color: var(--vm-danger);">*</span>
                    </label>
                    <a href="{{ route('customer.auth.recover-password') }}" style="font-size: 12.5px; color: var(--vm-primary); font-weight: 600; text-decoration: none;">
                        {{ translate('Forgot Password?') }}
                    </a>
                </div>
                <input type="password" name="password" required placeholder="{{ translate('Enter your password') }}"
                       style="width: 100%; padding: 12px 14px; border: 1.5px solid var(--vm-border); border-radius: var(--vm-radius-md); font-size: 14px; outline: none; transition: border-color 0.2s;"
                       onfocus="this.style.borderColor='var(--vm-primary)'" onblur="this.style.borderColor='var(--vm-border)'">
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--vm-text-muted); cursor: pointer;">
                    <input type="checkbox" name="remember" id="remember" style="accent-color: var(--vm-primary);">
                    <span>{{ translate('Remember me') }}</span>
                </label>
            </div>

            <button type="submit" class="vm-btn-primary" style="width: 100%; padding: 14px; font-size: 15px; font-weight: 800; justify-content: center;">
                <span>{{ translate('Sign In') }}</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </form>

        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--vm-border-light); font-size: 14px; color: var(--vm-text-muted);">
            {{ translate('Do not have an account?') }}
            <a href="{{ route('customer.auth.sign-up') }}" style="color: var(--vm-primary); font-weight: 700; text-decoration: none;">
                {{ translate('Create Account') }}
            </a>
        </div>

    </div>
</div>
@endsection
