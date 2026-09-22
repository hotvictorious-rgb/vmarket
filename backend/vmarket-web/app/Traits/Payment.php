<?php

namespace App\Traits;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use App\Models\PaymentRequest;

trait Payment
{
    /**
     * Creates a PaymentRequest record and returns a redirect URL to the Paystack payment page.
     *
     * [AI] V1 Victorious MARKET is Paystack-only. All legacy gateway routes
     * (ssl_commerz, stripe, paymob, flutterwave, paytm, paypal, paytabs, liqpay,
     * razor_pay, senang_pay, mercadopago, bkash, fatoorah, xendit, amazon_pay,
     * iyzi_pay, hyper_pay, foloosi, ccavenue, pvit, moncash, thawani, tap,
     * viva_wallet, hubtel, maxicash, esewa, swish, momo, payfast, worldpay,
     * sixcash, phonepe, cashfree, instamojo, mercadopago_pix) have been
     * decommissioned. Only Paystack is authorized for NGN marketplace payments.
     *
     * NOTE: For e-commerce delivery/pickup checkout, use DeliveryPaymentInitializationService
     * or PickupPaymentInitializationService directly — those bypass this trait entirely and
     * route through the secure CheckoutIntent / PickupReservation canonical engine.
     * This method is retained only for non-checkout payment flows (e.g. wallet top-ups)
     * that still use the legacy PaymentRequest path.
     */
    public static function generate_link(object $payer, object $payment_info, object $receiver): Application|bool|string|UrlGenerator|\Illuminate\Contracts\Foundation\Application
    {
        if ($payment_info->getPaymentAmount() <= 0) {
            throw new InvalidArgumentException(translate('Payment amount can not be 0'));
        }

        if (!is_array($payment_info->getAdditionalData())) {
            throw new InvalidArgumentException(translate('Additional data should be in a valid array'));
        }

        $payment = new PaymentRequest();
        $payment->payment_amount = $payment_info->getPaymentAmount();
        $payment->success_hook = $payment_info->getSuccessHook();
        $payment->failure_hook = $payment_info->getFailureHook();
        $payment->payer_id = $payment_info->getPayerId();
        $payment->receiver_id = $payment_info->getReceiverId();
        $payment->currency_code = strtoupper($payment_info->getCurrencyCode());
        $payment->payment_method = $payment_info->getPaymentMethod();
        $payment->additional_data = json_encode($payment_info->getAdditionalData());
        $payment->payer_information = json_encode($payer->information());
        $payment->receiver_information = json_encode($receiver->information());
        $payment->external_redirect_link = $payment_info->getExternalRedirectLink();
        $payment->attribute = $payment_info->getAttribute();
        $payment->attribute_id = $payment_info->getAttributeId();
        $payment->payment_platform = $payment_info->getPaymentPlatForm();
        $payment->save();

        // [AI] Paystack is the only authorized payment gateway for V1.
        $routes = [
            'paystack' => 'payment/paystack/pay',
        ];

        if (array_key_exists($payment->payment_method, $routes)) {
            return url("{$routes[$payment->payment_method]}/?payment_id={$payment->id}");
        } else {
            return false;
        }
    }
}
