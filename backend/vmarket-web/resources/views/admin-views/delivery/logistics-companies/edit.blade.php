@extends('layouts.admin.app')

@section('title', translate('Edit_Logistics_Partner'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="fi fi-sr-building text-primary"></i>
            {{ translate('Edit_Logistics_Partner:') }} {{ $company->name }}
        </h2>
    </div>

    <form action="{{ route('admin.logistics-companies.update', $company->id) }}" method="POST" enctype="multipart/form-data">
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
                            <input type="text" name="name" class="form-control" value="{{ old('name', $company->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Company_Email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $company->company_email) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Company_Phone') }} <span class="text-danger">*</span></label>
                            <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone', $company->company_phone) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Change_Password') }} <small class="text-muted">({{ translate('Leave_blank_to_keep_current') }})</small></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('CAC_Registration_Number') }}</label>
                            <input type="text" name="cac_number" class="form-control" value="{{ old('cac_number', $company->cac_number) }}">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Account_Status') }}</label>
                            <select name="status" class="form-select form-control">
                                <option value="active" {{ $company->status === 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                                <option value="pending" {{ $company->status === 'pending' ? 'selected' : '' }}>{{ translate('Pending_Review') }}</option>
                                <option value="suspended" {{ $company->status === 'suspended' ? 'selected' : '' }}>{{ translate('Suspended') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Contact_Person_Name') }}</label>
                            <input type="text" name="contact_person_name" class="form-control" value="{{ old('contact_person_name', $company->contact_person_name) }}">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Contact_Person_Phone') }}</label>
                            <input type="text" name="contact_person_phone" class="form-control" value="{{ old('contact_person_phone', $company->contact_person_phone) }}">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('State') }}</label>
                            <select name="state_id" class="form-select form-control">
                                <option value="">{{ translate('Select_State') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" {{ old('state_id', $company->state_id) == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
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
                                    <option value="{{ $lga->id }}" {{ old('lga_id', $company->lga_id) == $lga->id ? 'selected' : '' }}>{{ $lga->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Physical_Address') }}</label>
                            <textarea name="address" rows="2" class="form-control">{{ old('address', $company->address) }}</textarea>
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
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $company->bank_name) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Account_Number') }}</label>
                            <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $company->account_number) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="form-label">{{ translate('Account_Name') }}</label>
                            <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $company->account_name) }}">
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
            <a href="{{ route('admin.logistics-companies.show', $company->id) }}" class="btn btn-secondary px-4">{{ translate('Cancel') }}</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fi fi-sr-disk"></i>
                {{ translate('Update_Company') }}
            </button>
        </div>
    </form>
</div>
@endsection
