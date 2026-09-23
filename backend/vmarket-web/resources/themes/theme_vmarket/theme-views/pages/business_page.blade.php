@extends('theme-views.layouts.app')

@section('title', ($businessPage['title'] ?? translate('Information')) . ' | ' . getWebConfig(name: 'company_name'))

@section('content')
<div class="vm-container" style="padding: 32px 16px 64px; max-width: 900px;">
    
    <div style="background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-lg); padding: 36px;">
        <h1 style="font-size: 26px; font-weight: 800; color: var(--vm-dark); margin-bottom: 24px; border-bottom: 2px solid var(--vm-primary-light); padding-bottom: 12px;">
            {{ $businessPage['title'] ?? translate('Terms & Policies') }}
        </h1>
        
        <div style="font-size: 15px; line-height: 1.8; color: var(--vm-text);">
            {!! $businessPage['description'] ?? '' !!}
        </div>
    </div>

</div>
@endsection
