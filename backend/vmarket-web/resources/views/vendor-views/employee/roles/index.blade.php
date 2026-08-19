@extends('layouts.vendor.app')

@section('title', translate('Employee_Role_Setup'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="tio-user-big"></i>
            {{ translate('Employee_Role_Setup') }}
        </h2>
    </div>

    <!-- Role Form Card -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('vendor.employee-role.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="title-color font-weight-bold" for="name">{{ translate('Role_Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" id="name" placeholder="{{ translate('e.g. Store Attendant, Inventory Clerk') }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="title-color font-weight-bold mb-2">{{ translate('Module_Permissions') }} <span class="text-danger">*</span></label>
                    <div class="row g-3">
                        @foreach($modules as $key => $label)
                            <div class="col-sm-6 col-md-3">
                                <div class="custom-control custom-checkbox bg-light p-3 rounded border">
                                    <input type="checkbox" class="custom-control-input" id="perm_{{ $key }}" name="modules[]" value="{{ $key }}">
                                    <label class="custom-control-label font-weight-bold text-dark cursor-pointer" for="perm_{{ $key }}">
                                        {{ translate($label) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <button type="reset" class="btn btn-secondary px-4">{{ translate('reset') }}</button>
                    <button type="submit" class="btn btn--primary px-4">{{ translate('submit') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Role List Table -->
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h5 class="card-title">{{ translate('Role_List') }} <span class="badge badge-soft-dark radius-50">{{ $roles->total() }}</span></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap align-middle">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('SL') }}</th>
                        <th>{{ translate('Role_Name') }}</th>
                        <th>{{ translate('Allowed_Modules') }}</th>
                        <th class="text-center">{{ translate('Status') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $key => $role)
                        <tr>
                            <td>{{ $roles->firstItem() + $key }}</td>
                            <td class="font-weight-bold text-primary">{{ $role->name }}</td>
                            <td>
                                @if(!empty($role->module_access))
                                    @foreach($role->module_access as $m)
                                        <span class="badge badge-soft-info mr-1 mb-1">{{ $modules[$m] ?? $m }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">{{ translate('No modules assigned') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form action="{{ route('vendor.employee-role.status') }}" method="post" id="role-status{{ $role->id }}-form">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $role->id }}">
                                    <label class="switcher mx-auto">
                                        <input type="checkbox" class="switcher_input toggle-switch-message" name="status"
                                               id="role-status{{ $role->id }}" value="1" {{ $role->status ? 'checked' : '' }}
                                               data-modal-id="toggle-status-modal"
                                               data-toggle-id="role-status{{ $role->id }}"
                                               data-title="{{ translate('Are you sure you want to change status?') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </form>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('vendor.employee-role.edit', [$role->id]) }}" class="btn btn-outline--primary btn-sm square-btn" title="{{ translate('edit') }}">
                                    <i class="tio-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">{{ translate('No roles found. Create your first shop role above.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $roles->links() }}
        </div>
    </div>
</div>
@endsection
