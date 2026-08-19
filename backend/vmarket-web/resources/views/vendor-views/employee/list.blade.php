@extends('layouts.vendor.app')

@section('title', translate('Shop_Employees'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="tio-user-big"></i>
            {{ translate('Shop_Employees') }}
            <span class="badge badge-soft-dark radius-50">{{ $employees->total() }}</span>
        </h2>
        <a href="{{ route('vendor.employee.add-new') }}" class="btn btn--primary">
            <i class="tio-add"></i>
            {{ translate('Add_New_Employee') }}
        </a>
    </div>

    <!-- Filter & Search Card -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('vendor.employee.list') }}" method="GET">
                <div class="input-group input-group-merge">
                    <input type="search" name="searchValue" class="form-control" placeholder="{{ translate('Search by name, email or phone...') }}" value="{{ $searchValue }}">
                    <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Employee Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap align-middle">
                <thead class="thead-light">
                    <tr>
                        <th>{{ translate('SL') }}</th>
                        <th>{{ translate('Name') }}</th>
                        <th>{{ translate('Role') }}</th>
                        <th>{{ translate('Contact_Info') }}</th>
                        <th class="text-center">{{ translate('Status') }}</th>
                        <th class="text-center">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $key => $emp)
                        <tr>
                            <td>{{ $employees->firstItem() + $key }}</td>
                            <td>
                                <div class="media align-items-center gap-3">
                                    <img class="avatar avatar-lg rounded-circle"
                                         src="{{ getStorageImages(path: $emp->image, type: 'backend-profile') }}"
                                         alt="{{ $emp->name }}">
                                    <div class="media-body">
                                        <h5 class="text-hover-primary mb-0 font-weight-bold">{{ $emp->name }}</h5>
                                        <small class="text-muted">{{ translate('Added:') }} {{ date('d M, Y', strtotime($emp->created_at)) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-soft-info font-weight-bold px-2 py-1">
                                    {{ $emp->role->name ?? translate('Staff') }}
                                </span>
                            </td>
                            <td>
                                <div class="font-weight-bold text-dark">{{ $emp->email }}</div>
                                <div class="fs-12 text-muted">{{ $emp->phone }}</div>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('vendor.employee.status') }}" method="post" id="emp-status{{ $emp->id }}-form">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $emp->id }}">
                                    <label class="switcher mx-auto">
                                        <input type="checkbox" class="switcher_input toggle-switch-message" name="status"
                                               id="emp-status{{ $emp->id }}" value="1" {{ $emp->status ? 'checked' : '' }}
                                               data-modal-id="toggle-status-modal"
                                               data-toggle-id="emp-status{{ $emp->id }}"
                                               data-title="{{ translate('Are you sure you want to change employee status?') }}">
                                        <span class="switcher_control"></span>
                                    </label>
                                </form>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('vendor.employee.edit', [$emp->id]) }}" class="btn btn-outline--primary btn-sm square-btn" title="{{ translate('edit') }}">
                                    <i class="tio-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">{{ translate('No employees found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $employees->links() }}
        </div>
    </div>
</div>
@endsection
