@extends('layouts.admin.app')

@section('title', translate('Logistics_Partners'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            <i class="fi fi-sr-building text-primary"></i>
            {{ translate('Logistics_Partners') }}
            <span class="badge badge-soft-dark radius-50">{{ $companies->total() }}</span>
        </h2>
    </div>

    <div class="card mb-3">
        <div class="card-header border-0 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 w-100">
                <form action="{{ url()->current() }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="input-group input-group-merge input-group-custom">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <i class="fi fi-sr-search"></i>
                            </div>
                        </div>
                        <input type="search" name="searchValue" class="form-control"
                               placeholder="{{ translate('Search_by_company_name,_email,_phone...') }}"
                               value="{{ $searchValue }}">
                        <button type="submit" class="btn btn-primary">{{ translate('search') }}</button>
                    </div>

                    <div class="select-wrapper">
                        <select name="status" class="form-select form-control" onchange="this.form.submit()">
                            <option value="">{{ translate('All_Statuses') }}</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>{{ translate('Active') }}</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>{{ translate('Pending_Review') }}</option>
                            <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>{{ translate('Suspended') }}</option>
                        </select>
                    </div>
                </form>

                <div class="d-flex gap-2">
                    <a href="{{ route('admin.logistics-companies.withdraw-requests') }}" class="btn btn-outline-primary">
                        <i class="fi fi-sr-money-bill-wave"></i>
                        {{ translate('Withdrawal_Requests') }}
                    </a>
                    <a href="{{ route('admin.logistics-companies.create') }}" class="btn btn-primary">
                        <i class="fi fi-sr-plus"></i>
                        {{ translate('Add_New_Partner') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body px-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                    <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('SL') }}</th>
                            <th>{{ translate('Company_Name') }}</th>
                            <th>{{ translate('Contact_Person') }}</th>
                            <th>{{ translate('Operating_Base') }}</th>
                            <th>{{ translate('Fleet_Size') }}</th>
                            <th>{{ translate('Wallet_Balance') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($companies as $key => $company)
                        <tr>
                            <td>{{ $companies->firstItem() + $key }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar avatar-md border rounded overflow-hidden bg-light d-flex align-items-center justify-content-center">
                                        @if($company->logo)
                                            <img src="{{ getStorageImages(path: $company->logo_full_url ?? null, type: 'backend-profile') }}"
                                                 alt="{{ $company->name }}" class="img-fluid">
                                        @else
                                            <span class="fw-bold text-primary fs-16">{{ strtoupper(substr($company->name, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.logistics-companies.show', $company->id) }}" class="title-color hover-c1 fw-semibold">
                                            {{ $company->name }}
                                        </a>
                                        <div class="fs-12 text-muted">{{ $company->company_email }}</div>
                                        <div class="fs-12 text-muted">{{ $company->company_phone }}</div>
                                        <div class="mt-1">
                                            <span class="badge badge-soft-{{ $company->commission_percentage !== null ? 'info' : 'secondary' }} fs-11">
                                                {{ $company->commission_percentage !== null ? $company->commission_percentage . '% cut' : (getWebConfig(name: 'delivery_commission_percentage') ?? 15) . '% (global default)' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $company->contact_person_name ?? translate('N/A') }}</div>
                                <div class="fs-12 text-muted">{{ $company->contact_person_phone ?? '' }}</div>
                            </td>
                            <td>
                                <div>{{ $company->lga->name ?? translate('LGA_Unassigned') }}</div>
                                <div class="fs-12 text-muted">{{ $company->state->name ?? 'Nigeria' }}</div>
                            </td>
                            <td>
                                <span class="badge badge-soft-info px-2 py-1 fs-12">
                                    <i class="fi fi-sr-motorcycle"></i>
                                    {{ $company->delivery_men_count }} {{ translate('Riders') }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-success fs-14">
                                    ₦{{ number_format($company->wallet->current_balance ?? 0, 2) }}
                                </div>
                                <div class="fs-11 text-muted">
                                    {{ translate('Earned:') }} ₦{{ number_format($company->wallet->total_earned ?? 0, 2) }}
                                </div>
                            </td>
                            <td>
                                <label class="switcher" for="status-{{ $company->id }}">
                                    <input type="checkbox" class="switcher_input change-status-ajax"
                                           id="status-{{ $company->id }}"
                                           data-id="{{ $company->id }}"
                                           data-url="{{ route('admin.logistics-companies.status-update') }}"
                                           {{ $company->is_active ? 'checked' : '' }}>
                                    <span class="switcher_control"></span>
                                </label>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('admin.logistics-companies.show', $company->id) }}"
                                       class="btn btn-outline-info btn-sm square-btn" title="{{ translate('View_Details') }}">
                                        <i class="fi fi-sr-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.logistics-companies.edit', $company->id) }}"
                                       class="btn btn-outline-primary btn-sm square-btn" title="{{ translate('Edit') }}">
                                        <i class="fi fi-sr-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">{{ translate('No_logistics_partners_found') }}</div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 mt-3">
                {!! $companies->links() !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $('.change-status-ajax').on('change', function() {
        let id = $(this).data('id');
        let url = $(this).data('url');
        let status = $(this).is(':checked') ? 1 : 0;

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                status: status
            },
            success: function(response) {
                toastr.success(response.message);
            },
            error: function() {
                toastr.error('{{ translate("Status_update_failed") }}');
            }
        });
    });
</script>
@endpush
