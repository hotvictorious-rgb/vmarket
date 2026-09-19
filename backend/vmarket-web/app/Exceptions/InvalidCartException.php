<?php

namespace App\Exceptions;

use DomainException;

/**
 * [AI] Thrown when a cart is empty, malformed, or violates customer ownership/IDOR boundaries.
 */
class InvalidCartException extends DomainException
{
    public function __construct(string $message = 'Invalid cart state or cart ownership mismatch.', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
