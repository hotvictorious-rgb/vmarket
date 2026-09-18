<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * [AI] Thrown when any service, caller, job, or endpoint attempts to execute
 * an active customer wallet operation (credit, debit, top-up, funding, withdrawal,
 * order payment, due payment, or refund to wallet).
 * Customer wallet is permanently decommissioned in Victorious MARKET.
 */
class CustomerWalletDecommissionedException extends RuntimeException
{
    public function __construct(string $operation = '', string $message = '', int $code = 403, ?\Throwable $previous = null)
    {
        if (empty($message)) {
            $opText = $operation ? " for operation '{$operation}'" : '';
            $message = "Customer wallet capability is permanently decommissioned in Victorious MARKET{$opText}. No active wallet mutations or payments are permitted.";
        }
        parent::__construct($message, $code, $previous);
    }
}
