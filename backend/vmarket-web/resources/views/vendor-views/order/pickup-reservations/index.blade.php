@extends('layouts.vendor.app')
@section('title', translate('pickup_Reservations'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <div>
                <h2 class="h1 mb-1 text-capitalize d-flex align-items-center gap-2">
                    <img width="24" src="{{ dynamicAsset(path: 'public/assets/back-end/img/all-orders.png') }}" alt="">
                    <span>{{ translate('in_Shop_Pickup_Reservations') }}</span>
                </h2>
                <p class="text-muted mb-0 font-size-sm">
                    {{ translate('manage_pre_payment_customer_in_store_item_inspection_and_handover_queue.') }}
                </p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn--primary" data-toggle="modal" data-target="#verifyReservationModal">
                    <i class="tio-checkmark-circle-outlined mr-1"></i> {{ translate('verify_Reservation_Code') }}
                </button>
            </div>
        </div>

        {{-- Status Filter Cards --}}
        <div class="card card-body mb-3">
            <div class="row g-2">
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="d-flex gap-2 align-items-center justify-content-between p-3 rounded {{ $status === 'all' ? 'bg-primary text-white' : 'bg-section' }}"
                       href="{{ route('vendor.pickup-reservations.index', ['status' => 'all', 'searchValue' => request('searchValue')]) }}">
                        <div class="d-flex flex-column">
                            <span class="font-size-sm font-weight-bold">{{ translate('All') }}</span>
                            <span class="h4 mb-0 {{ $status === 'all' ? 'text-white' : 'text-primary' }}">{{ $statusCounts['all'] ?? 0 }}</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="d-flex gap-2 align-items-center justify-content-between p-3 rounded {{ $status === 'pending_inspection' ? 'bg-warning text-white' : 'bg-section' }}"
                       href="{{ route('vendor.pickup-reservations.index', ['status' => 'pending_inspection', 'searchValue' => request('searchValue')]) }}">
                        <div class="d-flex flex-column">
                            <span class="font-size-sm font-weight-bold">{{ translate('Pending_Inspection') }}</span>
                            <span class="h4 mb-0 {{ $status === 'pending_inspection' ? 'text-white' : 'text-warning' }}">{{ $statusCounts['pending_inspection'] ?? 0 }}</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="d-flex gap-2 align-items-center justify-content-between p-3 rounded {{ $status === 'inspected_accepted' ? 'bg-info text-white' : 'bg-section' }}"
                       href="{{ route('vendor.pickup-reservations.index', ['status' => 'inspected_accepted', 'searchValue' => request('searchValue')]) }}">
                        <div class="d-flex flex-column">
                            <span class="font-size-sm font-weight-bold">{{ translate('Inspection_Passed') }}</span>
                            <span class="h4 mb-0 {{ $status === 'inspected_accepted' ? 'text-white' : 'text-info' }}">{{ $statusCounts['inspected_accepted'] ?? 0 }}</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="d-flex gap-2 align-items-center justify-content-between p-3 rounded {{ $status === 'order_placed' ? 'bg-success text-white' : 'bg-section' }}"
                       href="{{ route('vendor.pickup-reservations.index', ['status' => 'order_placed', 'searchValue' => request('searchValue')]) }}">
                        <div class="d-flex flex-column">
                            <span class="font-size-sm font-weight-bold">{{ translate('Order_Paid') }}</span>
                            <span class="h4 mb-0 {{ $status === 'order_placed' ? 'text-white' : 'text-success' }}">{{ $statusCounts['order_placed'] ?? 0 }}</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="d-flex gap-2 align-items-center justify-content-between p-3 rounded {{ $status === 'expired' ? 'bg-secondary text-white' : 'bg-section' }}"
                       href="{{ route('vendor.pickup-reservations.index', ['status' => 'expired', 'searchValue' => request('searchValue')]) }}">
                        <div class="d-flex flex-column">
                            <span class="font-size-sm font-weight-bold">{{ translate('Expired_24h') }}</span>
                            <span class="h4 mb-0 {{ $status === 'expired' ? 'text-white' : 'text-muted' }}">{{ $statusCounts['expired'] ?? 0 }}</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="d-flex gap-2 align-items-center justify-content-between p-3 rounded {{ $status === 'inspected_rejected' ? 'bg-danger text-white' : 'bg-section' }}"
                       href="{{ route('vendor.pickup-reservations.index', ['status' => 'inspected_rejected', 'searchValue' => request('searchValue')]) }}">
                        <div class="d-flex flex-column">
                            <span class="font-size-sm font-weight-bold">{{ translate('Declined') }}</span>
                            <span class="h4 mb-0 {{ $status === 'inspected_rejected' ? 'text-white' : 'text-danger' }}">{{ $statusCounts['inspected_rejected'] ?? 0 }}</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- Reservations Table Card --}}
        <div class="card">
            <div class="card-header border-0">
                <div class="row justify-content-between align-items-center flex-grow-1">
                    <div class="col-12 col-md-4 mb-2 mb-md-0">
                        <h4 class="d-flex align-items-center gap-2 mb-0">
                            {{ translate('Reservations_List') }}
                            <span class="badge badge-soft-dark radius-50">{{ $reservations->total() }}</span>
                        </h4>
                    </div>
                    <div class="col-12 col-md-6">
                        <form action="{{ route('vendor.pickup-reservations.index') }}" method="GET">
                            <input type="hidden" name="status" value="{{ $status }}">
                            <div class="input-group input-group-merge input-group-custom">
                                <div class="input-group-prepend">
                                    <div class="input-group-text"><i class="tio-search"></i></div>
                                </div>
                                <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                       placeholder="{{ translate('search_by_reservation_code_or_customer_name') }}"
                                       value="{{ $searchValue }}">
                                <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="table-responsive datatable-custom">
                <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 text-start">
                    <thead class="thead-light thead-50 text-capitalize">
                    <tr>
                        <th>{{ translate('SL') }}</th>
                        <th>{{ translate('reservation_Code') }}</th>
                        <th>{{ translate('customer') }}</th>
                        <th>{{ translate('reserved_Items') }}</th>
                        <th>{{ translate('estimated_Total') }}</th>
                        <th>{{ translate('inspection_Status') }}</th>
                        <th>{{ translate('expires_In') }}</th>
                        <th class="text-center">{{ translate('action') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($reservations as $key => $reservation)
                        <tr>
                            <td>{{ $reservations->firstItem() + $key }}</td>
                            <td>
                                <span class="font-weight-bold text-primary font-size-md">
                                    {{ $reservation->reservation_code }}
                                </span>
                            </td>
                            <td>
                                @if($reservation->customer)
                                    <div class="font-weight-bold">{{ $reservation->customer->f_name }} {{ substr($reservation->customer->l_name ?? '', 0, 1) }}.</div>
                                    <span class="badge badge-soft-info font-size-xs">{{ translate('customer') }} #{{ $reservation->customer_id }}</span>
                                @else
                                    <span class="text-muted">{{ translate('customer_not_found') }}</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $items = $reservation->reservation_items ?? [];
                                    $itemCount = count($items);
                                @endphp
                                @if($itemCount > 0)
                                    <div>{{ $items[0]['name'] ?? translate('Item') }} <span class="badge badge-soft-info">x{{ $items[0]['quantity'] ?? 1 }}</span></div>
                                    @if($itemCount > 1)
                                        <div class="text-muted font-size-xs">+{{ $itemCount - 1 }} {{ translate('more_item(s)') }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="font-weight-bold">
                                    ₦{{ number_format((float)$reservation->total_amount, 2) }}
                                </span>
                            </td>
                            <td>
                                @if($reservation->status === 'pending_inspection')
                                    <span class="badge badge-soft-warning font-weight-bold px-2 py-1">
                                        <i class="tio-time mr-1"></i> {{ translate('awaiting_Inspection') }}
                                    </span>
                                @elseif($reservation->status === 'inspected_accepted')
                                    <span class="badge badge-soft-info font-weight-bold px-2 py-1">
                                        <i class="tio-checkmark-circle mr-1"></i> {{ translate('passed_Inspection_Awaiting_Payment') }}
                                    </span>
                                @elseif($reservation->status === 'order_placed')
                                    <span class="badge badge-soft-success font-weight-bold px-2 py-1">
                                        <i class="tio-done-all mr-1"></i> {{ translate('paid_Order_Placed') }}
                                    </span>
                                @elseif($reservation->status === 'expired')
                                    <span class="badge badge-soft-secondary font-weight-bold px-2 py-1">
                                        {{ translate('expired_24h') }}
                                    </span>
                                @elseif($reservation->status === 'inspected_rejected')
                                    <span class="badge badge-soft-danger font-weight-bold px-2 py-1">
                                        {{ translate('declined_By_Customer') }}
                                    </span>
                                @else
                                    <span class="badge badge-soft-dark">{{ $reservation->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($reservation->status === 'pending_inspection' || $reservation->status === 'inspected_accepted')
                                    @if($reservation->expires_at && $reservation->expires_at->isPast())
                                        <span class="text-danger font-size-sm font-weight-bold">{{ translate('Expired') }}</span>
                                    @elseif($reservation->expires_at)
                                        <span class="text-warning font-size-sm font-weight-bold">
                                            {{ $reservation->expires_at->diffForHumans(null, true) }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted font-size-sm">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    @if($reservation->status === 'pending_inspection')
                                        <button type="button" class="btn btn-sm btn--primary inspect-btn"
                                                data-code="{{ $reservation->reservation_code }}"
                                                title="{{ translate('accept_Inspection') }}">
                                            <i class="tio-checkmark-circle"></i> {{ translate('Accept') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger reject-btn"
                                                data-code="{{ $reservation->reservation_code }}"
                                                title="{{ translate('reject_Inspection') }}">
                                            <i class="tio-clear"></i> {{ translate('Reject') }}
                                        </button>
                                    @elseif($reservation->status === 'inspected_accepted')
                                        <span class="text-info font-size-sm font-weight-bold">
                                            {{ translate('Waiting_Customer_Payment') }}
                                        </span>
                                    @elseif($reservation->status === 'order_placed' && $reservation->order_id)
                                        <a href="{{ route('vendor.orders.details', ['id' => $reservation->order_id]) }}"
                                           class="btn btn-sm btn-outline--primary">
                                            <i class="tio-invisible"></i> {{ translate('View_Order') }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="text-center p-4">
                                    <img class="mb-3 w-160"
                                         src="{{ dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg') }}"
                                         alt="">
                                    <p class="mb-0">{{ translate('no_reservations_found_in_this_queue.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-responsive mt-4">
                <div class="d-flex justify-content-lg-end">
                    {!! $reservations->links() !!}
                </div>
            </div>
        </div>
    </div>

    {{-- Counter Verify Reservation Modal --}}
    <div class="modal fade" id="verifyReservationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold d-flex align-items-center gap-2">
                        <i class="tio-verified text-primary"></i>
                        {{ translate('verify_In_Shop_Reservation_Code') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="font-size-sm text-muted">
                        {{ translate('enter_the_customer_reservation_code_presented_at_your_store_counter_to_confirm_item_details_before_physical_inspection.') }}
                    </p>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">{{ translate('Reservation_Code') }} <span class="text-danger">*</span></label>
                        <input type="text" id="verify_code_input" class="form-control form-control-lg text-uppercase font-weight-bold"
                               placeholder="RES-XXXXXXXX" maxlength="32">
                    </div>
                    <div id="verify_result_box" class="d-none p-3 rounded mb-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('close') }}</button>
                    <button type="button" id="btn_verify_code" class="btn btn--primary">{{ translate('verify_Code') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Inspection Modal --}}
    <div class="modal fade" id="rejectReservationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-danger">
                        <i class="tio-warning-outlined mr-1"></i> {{ translate('decline_Reservation_Inspection') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="font-size-sm text-muted">
                        {{ translate('specify_why_this_reservation_is_being_declined._No_payment_occurred_and_zero_inventory_is_held.') }}
                    </p>
                    <input type="hidden" id="reject_code_target">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">{{ translate('Reason') }} <span class="text-danger">*</span></label>
                        <select id="reject_reason_select" class="form-control mb-2">
                            <option value="Customer declined after physical inspection">{{ translate('Customer declined after physical inspection') }}</option>
                            <option value="Item out of physical stock at store">{{ translate('Item out of physical stock at store') }}</option>
                            <option value="Customer requested alternative model/size">{{ translate('Customer requested alternative model/size') }}</option>
                            <option value="other">{{ translate('Other') }}</option>
                        </select>
                        <textarea id="reject_reason_text" class="form-control d-none" rows="2" placeholder="{{ translate('enter_custom_reason') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('cancel') }}</button>
                    <button type="button" id="btn_confirm_reject" class="btn btn-danger">{{ translate('confirm_Decline') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const verifyUrl = "{{ route('vendor.pickup-reservations.verify') }}";
        const acceptUrl = "{{ route('vendor.pickup-reservations.accept') }}";
        const rejectUrl = "{{ route('vendor.pickup-reservations.reject') }}";
        const csrfToken = "{{ csrf_token() }}";

        // Verification modal button
        document.getElementById('btn_verify_code')?.addEventListener('click', function () {
            const code = document.getElementById('verify_code_input').value.trim();
            const resultBox = document.getElementById('verify_result_box');

            if (!code) {
                toastr.warning("{{ translate('please_enter_a_reservation_code') }}");
                return;
            }

            this.disabled = true;
            this.innerText = "{{ translate('verifying') }}...";

            fetch(verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reservation_code: code })
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('btn_verify_code').disabled = false;
                document.getElementById('btn_verify_code').innerText = "{{ translate('verify_Code') }}";

                resultBox.classList.remove('d-none', 'alert-success', 'alert-danger');
                if (data.status) {
                    resultBox.classList.add('alert-success');
                    resultBox.innerHTML = `
                        <div class="font-weight-bold text-success mb-1"><i class="tio-checkmark-circle"></i> ${data.message}</div>
                        <div class="font-size-sm text-dark">${data.data.reservation.items_count} {{ translate('item(s) reserved for inspection.') }}</div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-success" onclick="acceptReservation('${code}')">{{ translate('accept_Inspection_Now') }}</button>
                        </div>
                    `;
                } else {
                    resultBox.classList.add('alert-danger');
                    resultBox.innerHTML = `<i class="tio-clear-circle"></i> ${data.message}`;
                }
            })
            .catch(() => {
                document.getElementById('btn_verify_code').disabled = false;
                document.getElementById('btn_verify_code').innerText = "{{ translate('verify_Code') }}";
                toastr.error("{{ translate('network_error_or_server_unavailable') }}");
            });
        });

        // Accept inspection click
        document.querySelectorAll('.inspect-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const code = this.getAttribute('data-code');
                window.acceptReservation(code);
            });
        });

        window.acceptReservation = function (code) {
            Swal.fire({
                title: "{{ translate('confirm_inspection_acceptance') }}?",
                text: "{{ translate('confirm_that_the_customer_has_physically_inspected_the_items_and_approved_them._this_will_unlock_payment_for_the_customer.') }}",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#00897B',
                cancelButtonColor: '#757575',
                confirmButtonText: "{{ translate('yes_accept_inspection') }}",
                cancelButtonText: "{{ translate('cancel') }}"
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(acceptUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ reservation_code: code })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status) {
                            toastr.success(data.message);
                            location.reload();
                        } else {
                            toastr.error(data.message);
                        }
                    })
                    .catch(() => toastr.error("{{ translate('failed_to_accept_inspection') }}"));
                }
            });
        };

        // Reject modal handling
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const code = this.getAttribute('data-code');
                document.getElementById('reject_code_target').value = code;
                $('#rejectReservationModal').modal('show');
            });
        });

        document.getElementById('reject_reason_select')?.addEventListener('change', function () {
            const textArea = document.getElementById('reject_reason_text');
            if (this.value === 'other') {
                textArea.classList.remove('d-none');
            } else {
                textArea.classList.add('d-none');
            }
        });

        document.getElementById('btn_confirm_reject')?.addEventListener('click', function () {
            const code = document.getElementById('reject_code_target').value;
            const selectVal = document.getElementById('reject_reason_select').value;
            const customVal = document.getElementById('reject_reason_text').value.trim();
            const reason = selectVal === 'other' ? (customVal || selectVal) : selectVal;

            this.disabled = true;

            fetch(rejectUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reservation_code: code, reason: reason })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    toastr.success(data.message);
                    location.reload();
                } else {
                    document.getElementById('btn_confirm_reject').disabled = false;
                    toastr.error(data.message);
                }
            })
            .catch(() => {
                document.getElementById('btn_confirm_reject').disabled = false;
                toastr.error("{{ translate('failed_to_reject_reservation') }}");
            });
        });
    });
</script>
@endpush
