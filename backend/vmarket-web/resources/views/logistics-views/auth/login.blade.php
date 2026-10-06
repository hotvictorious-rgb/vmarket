<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('Logistics_Partner_Login') }} | Victorious Market</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/uicons-solid-rounded.css') }}">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e092b 0%, #3a0d4c 50%, #5f1376 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            max-width: 440px;
            width: 100%;
            overflow: hidden;
        }
        .login-header {
            background: #5f1376;
            padding: 30px 24px;
            text-align: center;
            color: #ffffff;
        }
        .login-body {
            padding: 30px 28px;
        }
        .btn-primary {
            background-color: #5f1376;
            border-color: #5f1376;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-primary:hover {
            background-color: #480d5b;
            border-color: #480d5b;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fi fi-sr-truck-side fs-36 text-warning mb-2"></i>
            <h3 class="fw-bold mb-1">Victorious <span style="color: #e5a93c;">MARKET</span></h3>
            <p class="fs-13 text-white-50 mb-0">{{ translate('Logistics_Partner_Fleet_Portal') }}</p>
        </div>
        <div class="login-body">
            @if(session('error'))
                <div class="alert alert-danger py-2 fs-13">{{ session('error') }}</div>
            @endif

            <form action="{{ route('logistics.auth.login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold fs-13">{{ translate('Company_Email') }}</label>
                    <input type="email" name="email" class="form-control form-control-lg fs-14" placeholder="dispatch@company.com" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold fs-13 mb-0">{{ translate('Password') }}</label>
                    </div>
                    <input type="password" name="password" class="form-control form-control-lg fs-14" placeholder="••••••••" required>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label fs-13" for="remember">{{ translate('Remember_me') }}</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 fs-15">{{ translate('Sign_In_to_Fleet_Portal') }}</button>
            </form>

            <div class="text-center mt-4 pt-2 border-top">
                <span class="fs-13 text-muted">{{ translate('New_logistics_company?') }}</span>
                <a href="{{ route('logistics.auth.register') }}" class="fs-13 fw-semibold text-primary ms-1">
                    {{ translate('Register_Your_Fleet') }}
                </a>
            </div>
        </div>
    </div>
</body>
</html>
