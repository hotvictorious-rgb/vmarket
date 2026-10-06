@extends('layouts.admin.app')

@section('title', translate('Add_New_Logistics_Partner'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="fi fi-sr-building text-primary"></i>
            {{ translate('Register_Logistics_Partner') }}
        </h2>
    </div>

    <form action="{{ route('admin.logistics-companies.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card mb-3">
            <div class="card-header">
                <h4 class="mb-0">{{ translate('Company_Information') }}</h4>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Company_Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ translate('e.g._Akwa_Ibom_Express_Logistics') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Company_Email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="company_email" class="form-control" value="{{ old('company_email') }}" placeholder="dispatch@partner.com" required>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Company_Phone') }} <span class="text-danger">*</span></label>
                            <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone') }}" placeholder="+234 800 000 0000" required>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Login_Password') }} <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('CAC_Registration_Number') }}</label>
                            <input type="text" name="cac_number" class="form-control" value="{{ old('cac_number') }}" placeholder="RC-1234567">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Contact_Person_Name') }}</label>
                            <input type="text" name="contact_person_name" class="form-control" value="{{ old('contact_person_name') }}" placeholder="{{ translate('Fleet_Manager_Name') }}">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Contact_Person_Phone') }}</label>
                            <input type="text" name="contact_person_phone" class="form-control" value="{{ old('contact_person_phone') }}" placeholder="+234 810 000 0000">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('State') }}</label>
                            <select name="state_id" class="form-select form-control">
                                <option value="">{{ translate('Select_State') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ old('state_id') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Base_LGA') }}</label>
                            <select name="lga_id" class="form-select form-control">
                                <option value="">{{ translate('Select_LGA') }}</option>
                                @foreach($lgas as $lga)
                                    <option value="{{ $lga->id }}" {{ old('lga_id') == $lga->id ? 'selected' : '' }}>{{ $lga->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Physical_Address') }}</label>
                            <textarea name="address" rows="2" class="form-control" placeholder="{{ translate('Head_office_address') }}">{{ old('address') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h4 class="mb-0">{{ translate('Bank_Account_Details_(For_Settlement_Payouts)') }}</h4>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Bank_Name') }}</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="{{ translate('e.g._Zenith_Bank') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Account_Number') }}</label>
                            <input type="text" name="account_number" class="form-control" value="{{ old('account_number') }}" placeholder="0123456789">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Account_Name') }}</label>
                            <input type="text" name="account_name" class="form-control" value="{{ old('account_name') }}" placeholder="{{ translate('e.g._Akwa_Ibom_Express_Ltd') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Company_Logo') }}</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-3">
            <a href="{{ route('admin.logistics-companies.index') }}" class="btn btn-secondary px-4">{{ translate('Cancel') }}</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fi fi-sr-disk"></i>
                {{ translate('Register_Company') }}
            </button>
        </div>
    </form>
</div>
@endsection
