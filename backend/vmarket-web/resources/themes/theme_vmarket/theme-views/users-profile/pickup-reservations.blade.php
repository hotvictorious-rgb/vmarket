@extends('theme-views.layouts.app')
@section('title', translate('pickup_reservations'))
@section('content')
<main class="main-content py-3 mb-5"><div class="container"><div class="row g-3">
@include('theme-views.partials._profile-aside')
<div class="col-lg-9"><div class="card"><div class="card-body">
<h1 class="h4">{{ translate('pickup_reservations') }}</h1>
<p>Review your final quote before paying. Check payment status after returning from payment or closing the page.</p>
@forelse($reservations as $reservation)
<section class="border rounded p-3 mb-3 pickup-payment"
 data-quote-url="{{ route('pickup-reservations.quote', ['code' => $reservation->reservation_code]) }}"
 data-pay-url="{{ route('pickup-reservations.pay', ['code' => $reservation->reservation_code]) }}"
 data-status-url="{{ route('pickup-reservations.status', ['code' => $reservation->reservation_code]) }}">
<h2 class="h6">{{ $reservation->reservation_code }}</h2>
<p>{{ translate('status') }}: {{ $reservation->status }} · Expires: {{ $reservation->expires_at }}</p>
<label><input class="pickup-cashback" type="checkbox"> Use available Victorious Cashback</label>
<div class="my-2">
@if($reservation->status === 'inspected_accepted')
<button type="button" class="btn btn-primary pickup-review">Review payment</button>
@endif
<button type="button" class="btn btn-outline-primary pickup-check">Check payment status</button>
</div>
<div class="pickup-quote d-none border rounded p-3 mb-2"></div>
<button type="button" class="btn btn-primary pickup-confirm d-none">Confirm payment</button>
<button type="button" class="btn btn-outline-secondary pickup-cancel d-none">Cancel</button>
<p class="pickup-message mt-2" role="status" aria-live="polite"></p>
<a class="pickup-continue btn btn-primary d-none">Continue existing payment</a>
</section>
@empty
<p>{{ translate('no_data_found') }}</p>
@endforelse
</div></div></div></div></div></main>
@endsection
@push('script')
<script src="{{ asset('assets/front-end/js/pickup-payment.js') }}"></script>
@endpush
