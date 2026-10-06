<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ translate('Register_Logistics_Fleet') }} | Victorious Market</title>
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
            padding: 40px 20px;
        }
        .register-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .register-header {
            background: #5f1376;
            padding: 30px;
            text-align: center;
            color: #ffffff;
        }
        .register-body {
            padding: 35px;
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
    <div class="register-card">
        <div class="register-header">
            <i class="fi fi-sr-truck-side fs-36 text-warning mb-2"></i>
            <h3 class="fw-bold mb-1">Victorious <span style="color: #e5a93c;">MARKET</span></h3>
            <p class="fs-14 text-white-50 mb-0">{{ translate('Partner_With_Us_As_A_Logistics_Company') }}</p>
        </div>
        <div class="register-body">
            @if($errors->any())
                <div class="alert alert-danger py-2 fs-13">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('logistics.auth.register.post') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <h5 class="fw-bold mb-3 text-primary">{{ translate('1._Company_&_Fleet_Details') }}</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Company_Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ translate('e.g._Akwa_Ibom_Speed_Logistics') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Corporate_Email') }} <span class="text-danger">*</span></label>
                        <input type="email" name="company_email" class="form-control" value="{{ old('company_email') }}" placeholder="dispatch@company.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Corporate_Phone') }} <span class="text-danger">*</span></label>
                        <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone') }}" placeholder="+234 800 000 0000" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('CAC_Number') }}</label>
                        <input type="text" name="cac_number" class="form-control" value="{{ old('cac_number') }}" placeholder="RC-1234567">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Confirm_Password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Contact_Person_Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="contact_person_name" class="form-control" value="{{ old('contact_person_name') }}" placeholder="{{ translate('Manager_Name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Contact_Person_Phone') }} <span class="text-danger">*</span></label>
                        <input type="text" name="contact_person_phone" class="form-control" value="{{ old('contact_person_phone') }}" placeholder="+234 810 000 0000" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('State') }} <span class="text-danger">*</span></label>
                        <select name="state_id" class="form-select" required>
                            <option value="">{{ translate('Select_State') }}</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}" {{ old('state_id') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Base_LGA') }} <span class="text-danger">*</span></label>
                        <select name="lga_id" class="form-select" required>
                            <option value="">{{ translate('Select_LGA') }}</option>
                            @foreach($lgas as $lga)
                                <option value="{{ $lga->id }}" {{ old('lga_id') == $lga->id ? 'selected' : '' }}>{{ $lga->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Office_Address') }} <span class="text-danger">*</span></label>
                        <input type="text" name="address" class="form-control" value="{{ old('address') }}" placeholder="{{ translate('Head_office_address') }}" required>
                    </div>
                </div>

                <h5 class="fw-bold mb-3 text-primary">{{ translate('2._Bank_Details_(For_Automated_Weekly_Payouts)') }}</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Bank_Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="{{ translate('e.g._Zenith_Bank') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Account_Number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}" placeholder="0123456789" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Account_Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="account_name" class="form-control" value="{{ old('account_name') }}" placeholder="{{ translate('e.g._Akwa_Ibom_Speed_Ltd') }}" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fs-16 py-2">
                    <i class="fi fi-sr-disk me-1"></i>
                    {{ translate('Submit_Fleet_Registration') }}
                </button>
            </form>

            <div class="text-center mt-3">
                <span class="fs-13 text-muted">{{ translate('Already_registered?') }}</span>
                <a href="{{ route('logistics.auth.login') }}" class="fs-13 fw-semibold text-primary ms-1">
                    {{ translate('Sign_In_Here') }}
                </a>
            </div>
        </div>
    </div>
</body>
</html>
