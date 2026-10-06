@extends('layouts.admin.app')

@section('title', translate('Logistics_Partner_Withdrawal_Requests'))

@section('content')
<div class="content container-fluid">
    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <i class="fi fi-sr-money-bill-wave text-primary"></i>
                {{ translate('Partner_Withdrawal_Requests') }}
            </h2>
            <p class="fs-12 text-muted mb-0">{{ translate('Review_and_process_bank_disbursements_for_logistics_companies') }}</p>
        </div>
        <div>
            <a href="{{ route('admin.logistics-companies.index') }}" class="btn btn-secondary">
                <i class="fi fi-sr-arrow-left"></i>
                {{ translate('Back_to_Partners') }}
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-0 pb-0">
            <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.logistics-companies.withdraw-requests', ['status' => 'all']) }}"
                       class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ translate('All') }}
                    </a>
                    <a href="{{ route('admin.logistics-companies.withdraw-requests', ['status' => 'pending']) }}"
                       class="btn btn-sm {{ $status === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">
                        {{ translate('Pending') }}
                    </a>
                    <a href="{{ route('admin.logistics-companies.withdraw-requests', ['status' => 'approved']) }}"
                       class="btn btn-sm {{ $status === 'approved' ? 'btn-success' : 'btn-outline-success' }}">
                        {{ translate('Approved') }}
                    </a>
                    <a href="{{ route('admin.logistics-companies.withdraw-requests', ['status' => 'denied']) }}"
                       class="btn btn-sm {{ $status === 'denied' ? 'btn-danger' : 'btn-outline-danger' }}">
                        {{ translate('Denied') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body px-0">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap card-table w-100">
                    <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{ translate('SL') }}</th>
                            <th>{{ translate('Company') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Bank_Details') }}</th>
                            <th>{{ translate('Date_Requested') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $key => $req)
                        <tr>
                            <td>{{ $requests->firstItem() + $key }}</td>
                            <td>
                                <a href="{{ route('admin.logistics-companies.show', $req->logistics_company_id) }}" class="fw-semibold title-color hover-c1">
                                    {{ $req->company->name ?? 'Unknown Company' }}
                                </a>
                                <div class="fs-12 text-muted">{{ $req->company->company_phone ?? '' }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-success fs-15">
                                    ₦{{ number_format($req->amount, 2) }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $req->bank_name ?? translate('Not_set') }}</div>
                                <div class="fs-12 text-muted font-monospace">{{ $req->account_number ?? '' }}</div>
                                <div class="fs-12 text-muted">{{ $req->account_name ?? '' }}</div>
                            </td>
                            <td>{{ $req->created_at->format('M d, Y h:i A') }}</td>
                            <td>
                                @if($req->status === 'pending')
                                    <span class="badge badge-soft-warning">{{ translate('Pending') }}</span>
                                @elseif($req->status === 'approved')
                                    <span class="badge badge-soft-success">{{ translate('Approved') }}</span>
                                @else
                                    <span class="badge badge-soft-danger">{{ translate('Denied') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($req->status === 'pending')
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-success btn-sm px-3"
                                                data-bs-toggle="modal" data-bs-target="#actionModal-{{ $req->id }}-approved">
                                            <i class="fi fi-sr-check"></i> {{ translate('Approve') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm px-3"
                                                data-bs-toggle="modal" data-bs-target="#actionModal-{{ $req->id }}-denied">
                                            <i class="fi fi-sr-cross"></i> {{ translate('Deny') }}
                                        </button>
                                    </div>

                                    {{-- Modal Approve/Deny --}}
                                    @foreach(['approved', 'denied'] as $action)
                                        <div class="modal fade" id="actionModal-{{ $req->id }}-{{ $action }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form action="{{ route('admin.logistics-companies.withdraw-status') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $req->id }}">
                                                        <input type="hidden" name="status" value="{{ $action }}">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                {{ $action === 'approved' ? translate('Approve_Disbursement') : translate('Deny_Withdrawal_Request') }}
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <p>
                                                                {{ translate('Are_you_sure_you_want_to') }}
                                                                <strong>{{ $action }}</strong>
                                                                {{ translate('the_withdrawal_of') }}
                                                                <strong class="text-success">₦{{ number_format($req->amount, 2) }}</strong>
                                                                {{ translate('for') }} <strong>{{ $req->company->name ?? 'Company' }}</strong>?
                                                            </p>
                                                            <div class="form-group mb-0">
                                                                <label class="form-label">{{ translate('Admin_Note_(Optional)') }}</label>
                                                                <textarea name="admin_note" rows="2" class="form-control" placeholder="{{ translate('Transaction_reference_or_reason') }}"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ translate('Cancel') }}</button>
                                                            <button type="submit" class="btn {{ $action === 'approved' ? 'btn-success' : 'btn-danger' }}">
                                                                {{ $action === 'approved' ? translate('Confirm_Approval') : translate('Confirm_Denial') }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="fs-12 text-muted">{{ translate('Processed') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                {{ translate('No_withdrawal_requests_found') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 mt-3">
                {!! $requests->links() !!}
            </div>
        </div>
    </div>
</div>
@endsection
