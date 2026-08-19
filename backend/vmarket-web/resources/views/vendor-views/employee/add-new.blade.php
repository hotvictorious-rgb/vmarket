@extends('layouts.vendor.app')

@section('title', translate('Add_New_Employee'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="tio-add-circle"></i>
            {{ translate('Add_New_Shop_Employee') }}
        </h2>
    </div>

    <!-- Form Card -->
    <div class="card">
        <div class="card-body">
            <form action="{{ route('vendor.employee.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <!-- General Information -->
                    <div class="col-md-6 mb-3">
                        <label class="title-color font-weight-bold" for="name">{{ translate('Full_Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="name" placeholder="{{ translate('e.g. John Doe') }}" value="{{ old('name') }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="title-color font-weight-bold" for="phone">{{ translate('Phone_Number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" id="phone" placeholder="{{ translate('e.g. 08012345678') }}" value="{{ old('phone') }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="title-color font-weight-bold" for="email">{{ translate('Email_Address') }} <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" id="email" placeholder="{{ translate('staff@example.com') }}" value="{{ old('email') }}" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="title-color font-weight-bold" for="vendor_role_id">{{ translate('Employee_Role') }} <span class="text-danger">*</span></label>
                        <select name="vendor_role_id" id="vendor_role_id" class="form-control js-select2-custom" required>
                            <option value="" disabled selected>{{ translate('Select_Role') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('vendor_role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="title-color font-weight-bold" for="password">{{ translate('Password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" id="password" placeholder="{{ translate('At least 8 characters') }}" required minlength="8">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="title-color font-weight-bold" for="confirm_password">{{ translate('Confirm_Password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="{{ translate('Confirm password') }}" required minlength="8">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="title-color font-weight-bold">{{ translate('Employee_Image') }}</label>
                        <div class="custom-file">
                            <input type="file" name="image" id="customFileUpload" class="custom-file-input" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff, .webp">
                            <label class="custom-file-label" for="customFileUpload">{{ translate('choose_file') }}</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <a href="{{ route('vendor.employee.list') }}" class="btn btn-secondary px-4">{{ translate('cancel') }}</a>
                    <button type="submit" class="btn btn--primary px-4">{{ translate('submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
