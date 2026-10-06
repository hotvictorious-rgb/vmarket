@extends('logistics-views.layouts.app')

@section('title', translate('Edit_Rider'))

@section('content')
<div class="container-fluid" style="max-width: 800px;">
    <div class="mb-4">
        <h2 class="h3 fw-bold mb-1">{{ translate('Edit_Rider:') }} {{ $rider->f_name }} {{ $rider->l_name }}</h2>
    </div>

    <div class="card border-0 shadow-sm rounded-12">
        <div class="card-body p-4">
            <form action="{{ route('logistics.riders.update', $rider->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('First_Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="f_name" class="form-control" value="{{ old('f_name', $rider->f_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Last_Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="l_name" class="form-control" value="{{ old('l_name', $rider->l_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Phone_Number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $rider->phone) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Email_Address') }} <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $rider->email) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Vehicle_Type') }} <span class="text-danger">*</span></label>
                        <select name="vehicle_type" class="form-select" required>
                            <option value="motorbike" {{ $rider->vehicle_type === 'motorbike' ? 'selected' : '' }}>🏍️ {{ translate('Motorbike_(Small_Parcels_<8kg)') }}</option>
                            <option value="tricycle" {{ $rider->vehicle_type === 'tricycle' ? 'selected' : '' }}>🛺 {{ translate('Tricycle_/_Keke') }}</option>
                            <option value="van" {{ $rider->vehicle_type === 'van' ? 'selected' : '' }}>🚐 {{ translate('Delivery_Van_(Large_Bulky_Cargo)') }}</option>
                            <option value="truck" {{ $rider->vehicle_type === 'truck' ? 'selected' : '' }}>🚚 {{ translate('Cargo_Truck_(Heavy_Cargo)') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Change_Password') }} <small class="text-muted">({{ translate('Leave_blank_to_keep_current') }})</small></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Identity_Type') }}</label>
                        <select name="identity_type" class="form-select">
                            <option value="nin" {{ $rider->identity_type === 'nin' ? 'selected' : '' }}>National Identity Number (NIN)</option>
                            <option value="driving_license" {{ $rider->identity_type === 'driving_license' ? 'selected' : '' }}>Driver's License</option>
                            <option value="voters_card" {{ $rider->identity_type === 'voters_card' ? 'selected' : '' }}>Voter's Card</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Identity_Number') }}</label>
                        <input type="text" name="identity_number" class="form-control" value="{{ old('identity_number', $rider->identity_number) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Residential_Address') }}</label>
                        <textarea name="address" rows="2" class="form-control">{{ old('address', $rider->address) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13 fw-semibold">{{ translate('Rider_Photo') }}</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <a href="{{ route('logistics.riders.index') }}" class="btn btn-secondary px-4">{{ translate('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fi fi-sr-disk me-1"></i> {{ translate('Update_Rider') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
