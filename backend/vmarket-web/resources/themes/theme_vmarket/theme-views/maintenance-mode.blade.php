<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('Scheduled Maintenance') }} | {{ getWebConfig(name: 'company_name') }}</title>
    <link rel="stylesheet" href="{{ theme_asset('assets/css/vmarket.css') }}">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--vm-bg); padding: 20px;">

    <div style="text-align: center; max-width: 520px; background: #FFFFFF; border: 1px solid var(--vm-border); border-radius: var(--vm-radius-xl); padding: 48px 32px; box-shadow: var(--vm-shadow-lg);">
        <div style="font-size: 54px; margin-bottom: 20px;">🛠️</div>
        <h1 style="font-size: 24px; font-weight: 800; color: var(--vm-dark); margin-bottom: 12px;">
            {{ translate('We’ll Be Right Back!') }}
        </h1>
        <p style="font-size: 14.5px; line-height: 1.6; color: var(--vm-text-muted); margin-bottom: 28px;">
            {{ translate('Victorious MARKET is currently performing routine platform enhancements to serve you better. We will be back online shortly.') }}
        </p>
        <span class="vm-verified-badge" style="font-size: 13px; padding: 6px 16px;">
            Victorious MARKET
        </span>
    </div>

</body>
</html>
