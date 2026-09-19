<?php

namespace App\Exceptions;

use DomainException;

/**
 * [AI] Thrown when a payment initialization or attempt lifecycle transition is invalid,
 * such as an expired intent, superseded attempt, or unverified order group.
 */
class InvalidPaymentStateException extends DomainException
{
    public function __construct(string $message = 'Invalid payment state.', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
