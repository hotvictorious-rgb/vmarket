@extends('theme-views.layouts.app')
@section('title', 'Confirm checkout | ' . $web_config['company_name'])
@section('content')
<main class="container py-4">
    <div class="card mx-auto" style="max-width:760px">
        <div class="card-body">
            <h3>Confirm final checkout amount</h3>
            <p>Review the amounts reserved for this checkout before opening Paystack.</p>
            <dl class="row">
                <dt class="col-7">Merchandise after product discounts</dt><dd class="col-5">{{ $quote['currency'] }} {{ $quote['merchandise_subtotal'] }}</dd>
                <dt class="col-7">Tax</dt><dd class="col-5">{{ $quote['currency'] }} {{ $quote['tax_total'] }}</dd>
                <dt class="col-7">Delivery</dt><dd class="col-5">{{ $quote['currency'] }} {{ $quote['shipping_total'] }}</dd>
                <dt class="col-7">Victorious Points applied</dt><dd class="col-5">{{ $quote['currency'] }} {{ $quote['cashback']['cashback_amount'] ?? '0.00' }}</dd>
                <dt class="col-7">Pay now</dt><dd class="col-5 fw-bold">{{ $quote['currency'] }} {{ $quote['total_amount'] }}</dd>
            </dl>
            @foreach($quote['vendors'] ?? [] as $vendorQuote)
                <div class="border rounded p-3 mb-3">
                    <h5>{{ $vendorQuote['shop_name'] ?? 'Store' }}</h5>
                    <div>Merchandise: {{ $quote['currency'] }} {{ $vendorQuote['merchandise'] }}</div>
                    <div>Tax: {{ $quote['currency'] }} {{ $vendorQuote['tax'] }}</div>
                    <div>Delivery: {{ $quote['currency'] }} {{ $vendorQuote['shipping_cost'] }}</div>
                    <div>Victorious Points: {{ $quote['currency'] }} {{ $vendorQuote['allocated_cashback'] ?? '0.00' }}</div>
                </div>
            @endforeach
            <form method="post" action="{{ route('customer.web-payment-request') }}">
                @csrf
                <input type="hidden" name="payment_method" value="paystack">
                <input type="hidden" name="payment_platform" value="web">
                <input type="hidden" name="quote_confirmed" value="1">
                <input type="hidden" name="order_group_id" value="{{ $order_group_id }}">
                <input type="hidden" name="idempotency_key" value="{{ $idempotency_key }}">
                <input type="hidden" name="address_id" value="{{ $address_id }}">
                <input type="hidden" name="billing_address_id" value="{{ $billing_address_id }}">
                <input type="hidden" name="use_cashback" value="{{ $use_cashback ? 1 : 0 }}">
                <input type="hidden" name="external_redirect_link" value="{{ route('web-payment-success') }}">
                <a href="{{ route('checkout-payment') }}" class="btn btn-outline-primary">Back</a>
                <button type="submit" class="btn btn-primary">Confirm and pay</button>
            </form>
        </div>
    </div>
</main>
@endsection
