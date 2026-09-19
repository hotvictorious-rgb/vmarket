<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * [AI] Exception PostPaymentStockFailureException
 * 
 * Thrown when physical inventory is insufficient during post-payment order settlement.
 * Triggers the two-phase rollback of order creation followed by persistent reconciliation logging.
 */
class PostPaymentStockFailureException extends RuntimeException
{
    public function __construct(
        public string $productName,
        public int $productId,
        public int $requestedQty,
        public int $availableStock = 0,
        string $message = ""
    ) {
        $msg = !empty($message) 
            ? $message 
            : "Post-payment stock failure for product '{$productName}' (#{$productId}): requested {$requestedQty}, available {$availableStock}.";
        parent::__construct($msg);
    }
}
