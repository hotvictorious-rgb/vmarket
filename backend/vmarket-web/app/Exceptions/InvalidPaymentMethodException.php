<?php

namespace App\Exceptions;

use DomainException;

/**
 * [AI] Thrown when an order or payment request attempts to use a payment method
 * not authorized under Victorious MARKET's authoritative payment model:
 * Allowed: paystack (Doorstep Delivery), pay_at_pickup (Customer Pickup — Pay After Inspection).
 * Disallowed: wallet, pay_by_wallet, cash_on_delivery, cod, customer_wallet, offline_payment.
 */
class InvalidPaymentMethodException extends DomainException
{
    protected string $invalidMethod;

    public function __construct(string $method = '', string $message = '', int $code = 422, ?\Throwable $previous = null)
    {
        $this->invalidMethod = $method;
        if (empty($message)) {
            $message = "Payment method '{$method}' is not authorized on Victorious MARKET. Authorized methods: paystack (Doorstep Delivery), pay_at_pickup (Customer Pickup — Pay After Inspection).";
        }
        parent::__construct($message, $code, $previous);
    }

    public function getInvalidMethod(): string
    {
        return $this->invalidMethod;
    }
}
