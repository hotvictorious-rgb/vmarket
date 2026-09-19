<?php

namespace App\Exceptions;

use DomainException;

/**
 * [AI] Thrown when a request provides an existing idempotency key
 * with different parameters or payload (HTTP 409 Conflict).
 */
class IdempotencyConflictException extends DomainException
{
    public function __construct(string $message = 'Idempotency conflict: key was previously used with different checkout parameters.', int $code = 409, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
