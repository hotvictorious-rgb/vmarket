<?php

namespace App\Exceptions;

use DomainException;

/**
 * [AI] Thrown when a cart item references a product that is not currently
 * eligible or purchasable on Victorious MARKET according to isMarketplacePurchasable().
 */
class ProductUnavailableException extends DomainException
{
    public function __construct(string $message = 'One or more items in your cart are no longer available for purchase.', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
