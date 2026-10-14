@extends('theme-views.layouts.app')
@section('title', translate('pickup_reservations'))
@section('content')
<main class="main-content py-3 mb-5"><div class="container"><div class="row g-3">
@include('theme-views.partials._profile-aside')
<div class="col-lg-9"><div class="card"><div class="card-body">
<h1 class="h4">{{ translate('pickup_reservations') }}</h1>
<p class="text-muted">{{ translate('Manage your in-store pickup reservations. Present your code at the store counter for physical inspection, or signal when en route to lock items under the counter.') }}</p>
@forelse($reservations as $reservation)
<section class="border rounded p-3 mb-3 pickup-payment"
 data-quote-url="{{ route('pickup-reservations.quote', ['code' => $reservation->reservation_code]) }}"
 data-pay-url="{{ route('pickup-reservations.pay', ['code' => $reservation->reservation_code]) }}"
 data-status-url="{{ route('pickup-reservations.status', ['code' => $reservation->reservation_code]) }}">
<div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
    <h2 class="h6 mb-0 font-weight-bold text-primary">{{ $reservation->reservation_code }}</h2>
    <div>
        @if($reservation->status === 'pending_inspection')
            <span class="badge bg-warning text-dark"><i class="fa fa-clock"></i> {{ translate('Awaiting Inspection') }}</span>
        @elseif($reservation->status === 'en_route')
            <span class="badge bg-primary text-white"><i class="fa fa-car"></i> {{ translate('En Route (Waiting Store SLA)') }}</span>
        @elseif($reservation->status === 'en_route_timeout')
            <span class="badge bg-secondary text-white"><i class="fa fa-history"></i> {{ translate('SLA Timeout (Can Resend)') }}</span>
        @elseif($reservation->status === 'held_confirmed')
            <span class="badge bg-success text-white"><i class="fa fa-lock"></i> {{ translate('Item Held Under Counter (90m)') }}</span>
        @elseif($reservation->status === 'inspected_accepted')
            <span class="badge bg-info text-white"><i class="fa fa-check-circle"></i> {{ translate('Inspection Passed - Ready to Pay') }}</span>
        @elseif($reservation->status === 'order_placed')
            <span class="badge bg-success text-white"><i class="fa fa-check"></i> {{ translate('Paid & Completed') }}</span>
        @elseif($reservation->status === 'inspected_rejected')
            <span class="badge bg-danger text-white"><i class="fa fa-times-circle"></i> {{ translate('Declined / Out of Stock') }}</span>
        @elseif($reservation->status === 'expired')
            <span class="badge bg-secondary text-white">{{ translate('Expired (24h)') }}</span>
        @else
            <span class="badge bg-dark text-white">{{ $reservation->status }}</span>
        @endif
    </div>
</div>

<p class="font-size-sm text-muted mb-2">
    {{ translate('Total Amount') }}: <strong>₦{{ number_format((float)$reservation->total_amount, 2) }}</strong> · 
    {{ translate('Expires') }}: {{ $reservation->expires_at ? \Carbon\Carbon::parse($reservation->expires_at)->diffForHumans() : '24h' }}
</p>

{{-- Transit Handshake Action Controls --}}
<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
    @if(in_array($reservation->status, ['pending_inspection', 'en_route_timeout'], true))
        <button type="button" class="btn btn-warning btn-sm text-dark font-weight-bold btn-signal-en-route"
                data-url="{{ route('pickup-reservations.en-route', ['code' => $reservation->reservation_code]) }}">
            <i class="fa fa-car mr-1"></i> {{ translate('I Am Coming Now') }}
        </button>
    @elseif(in_array($reservation->status, ['en_route', 'held_confirmed'], true))
        <button type="button" class="btn btn-outline-danger btn-sm btn-cancel-en-route"
                data-url="{{ route('pickup-reservations.cancel-en-route', ['code' => $reservation->reservation_code]) }}">
            <i class="fa fa-times mr-1"></i> {{ translate('Cancel Trip') }}
        </button>
    @endif
</div>

<label><input class="pickup-cashback" type="checkbox"> {{ translate('Use available Victorious Cashback') }}</label>
<div class="my-2">
@if($reservation->status === 'inspected_accepted')
<button type="button" class="btn btn-primary pickup-review">{{ translate('Review payment') }}</button>
@endif
<button type="button" class="btn btn-outline-primary pickup-check">{{ translate('Check payment status') }}</button>
</div>
<div class="pickup-quote d-none border rounded p-3 mb-2"></div>
<button type="button" class="btn btn-primary pickup-confirm d-none">{{ translate('Confirm payment') }}</button>
<button type="button" class="btn btn-outline-secondary pickup-cancel d-none">{{ translate('Cancel') }}</button>
<p class="pickup-message mt-2" role="status" aria-live="polite"></p>
<a class="pickup-continue btn btn-primary d-none">{{ translate('Continue existing payment') }}</a>
</section>
@empty
<p>{{ translate('no_data_found') }}</p>
@endforelse
</div></div></div></div></div></main>
@endsection
@push('script')
<script src="{{ asset('assets/front-end/js/pickup-payment.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = "{{ csrf_token() }}";

        // Signal en route
        document.querySelectorAll('.btn-signal-en-route').forEach(btn => {
            btn.addEventListener('click', function () {
                const url = this.getAttribute('data-url');
                if (!confirm("{{ translate('Signal store that you are en route? The store will verify stock and hold it under the counter for 90 minutes.') }}")) return;
                
                this.disabled = true;
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message || 'Unable to signal transit.');
                        this.disabled = false;
                    }
                })
                .catch(() => {
                    alert('Network error. Please try again.');
                    this.disabled = false;
                });
            });
        });

        // Cancel en route
        document.querySelectorAll('.btn-cancel-en-route').forEach(btn => {
            btn.addEventListener('click', function () {
                const url = this.getAttribute('data-url');
                if (!confirm("{{ translate('Cancel your transit trip? Your reservation will remain active, but physical hold will be released.') }}")) return;

                this.disabled = true;
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message || 'Unable to cancel transit trip.');
                        this.disabled = false;
                    }
                })
                .catch(() => {
                    alert('Network error. Please try again.');
                    this.disabled = false;
                });
            });
        });
    });
</script>
@endpush
