<?php

namespace App\Exceptions;

use DomainException;

/**
 * [AI] Thrown when gateway initialization fails due to confirmed gateway rejection
 * or invalid gateway configuration.
 */
class PaymentInitializationException extends DomainException
{
    public function __construct(string $message = 'Payment initialization failed.', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
