@extends('layouts.vendor.app')

@section('title', translate('Edit_Employee_Role'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="tio-edit"></i>
            {{ translate('Edit_Employee_Role') }}
        </h2>
    </div>

    <!-- Edit Role Card -->
    <div class="card">
        <div class="card-body">
            <form action="{{ route('vendor.employee-role.update', [$role->id]) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="title-color font-weight-bold" for="name">{{ translate('Role_Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="name" value="{{ $role->name }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="title-color font-weight-bold mb-2">{{ translate('Module_Permissions') }} <span class="text-danger">*</span></label>
                    <div class="row g-3">
                        @php($currentAccess = is_array($role->module_access) ? $role->module_access : (json_decode($role->module_access, true) ?? []))
                        @foreach($modules as $key => $label)
                            <div class="col-sm-6 col-md-3">
                                <div class="custom-control custom-checkbox bg-light p-3 rounded border">
                                    <input type="checkbox" class="custom-control-input" id="perm_{{ $key }}" name="modules[]" value="{{ $key }}"
                                           {{ in_array($key, $currentAccess) ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark cursor-pointer" for="perm_{{ $key }}">
                                        {{ translate($label) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <a href="{{ route('vendor.employee-role.index') }}" class="btn btn-secondary px-4">{{ translate('cancel') }}</a>
                    <button type="submit" class="btn btn--primary px-4">{{ translate('update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
