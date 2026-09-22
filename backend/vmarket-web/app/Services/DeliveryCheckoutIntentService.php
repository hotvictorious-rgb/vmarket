<?php

namespace App\Services;

use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\InvalidCartException;
use App\Exceptions\ProductUnavailableException;
use App\Models\Cart;
use App\Models\CartShipping;
use App\Models\CashbackRedemption;
use App\Models\CheckoutIntent;
use App\Models\Product;
use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * [AI] Service DeliveryCheckoutIntentService
 * 
 * Constructs durable, immutable CheckoutIntents for delivery orders.
 * Invariants:
 * 1. Authenticated customers only.
 * 2. Strict IDOR ownership validation on carts and addresses.
 * 3. Exact marketplace eligibility validation via Product::isMarketplacePurchasable().
 * 4. Zero float arithmetic: all monetary values computed using BCMath and serialized as 2-decimal strings.
 * 5. Deterministic canonical SHA-256 fingerprint representing the entire checkout agreement.
 * 6. Concurrency and duplicate protection: database-enforced unique constraints,
 *    graceful replay on same key + same payload, 409 conflict on same key + different payload.
 * 7. Lazy expiration of stale active intents releasing active_cart_token under row lock.
 * 8. Zero Order, PaymentRequest, or Cart mutation in this phase.
 * 9. Victorious Points (Cashback) is the single authoritative order-reduction mechanism.
 *    Coupon codes and referral discounts are strictly decommissioned.
 */
class DeliveryCheckoutIntentService
{
    /**
     * Creates or replays an immutable CheckoutIntent for a customer's delivery checkout.
     *
     * @param int|User $customer
     * @param string $idempotencyKey
     * @param int|ShippingAddress|array $shippingAddress
     * @param int|ShippingAddress|array|null $billingAddress
     * @param bool $useCashback
     * @param array|null $cartItemIds
     * @return CheckoutIntent
     *
     * @throws InvalidCartException
     * @throws ProductUnavailableException
     * @throws IdempotencyConflictException
     * @throws InvalidArgumentException
     */
    public function createCheckoutIntent(
        int|User $customer,
        string $idempotencyKey,
        int|ShippingAddress|array $shippingAddress,
        int|ShippingAddress|array|null $billingAddress = null,
        bool $useCashback = false,
        ?array $cartItemIds = null
    ): CheckoutIntent {
        // 1. Resolve and Validate Authenticated Customer
        $customerId = $customer instanceof User ? (int) $customer->id : (int) $customer;
        if ($customerId <= 0) {
            throw new InvalidCartException("Authentication required: invalid customer ID [{$customerId}].");
        }

        $customerRecord = User::find($customerId);
        if (!$customerRecord) {
            throw new InvalidCartException("Customer #{$customerId} does not exist.");
        }

        // Validate Idempotency Key Format
        $idempotencyKey = trim($idempotencyKey);
        if (empty($idempotencyKey) || !preg_match('/^[A-Za-z0-9_\-\:]{8,64}$/', $idempotencyKey)) {
            throw new InvalidArgumentException("Invalid idempotency key format. Must be 8-64 alphanumeric/dash/underscore/colon characters.");
        }

        // 2. Fetch and Validate Checked Cart Items (IDOR Protected: authenticated customer only)
        $cartQuery = Cart::where('customer_id', $customerId)
            ->where('is_guest', 0)
            ->where('is_checked', 1)
            ->with(['product']);

        if (!empty($cartItemIds)) {
            $cartQuery->whereIn('id', $cartItemIds);
        }

        $cartItems = $cartQuery->get();
        if ($cartItems->isEmpty()) {
            throw new InvalidCartException("No checked cart items found for customer #{$customerId}.");
        }

        // 3. Validate Marketplace Purchasability for Every Cart Item
        foreach ($cartItems as $item) {
            $product = $item->product;
            if (!$product) {
                throw new ProductUnavailableException("Product for cart item #{$item->id} no longer exists.");
            }

            if (!$product->isMarketplacePurchasable()) {
                throw new ProductUnavailableException("Product [{$product->name}] (ID: {$product->id}) is not currently available for purchase on Victorious MARKET.");
            }
        }

        // 4. Resolve Canonical Shipping & Billing Addresses
        $canonicalShippingAddress = $this->resolveCanonicalAddress($shippingAddress, $customerId, 'shipping');
        $canonicalBillingAddress = !empty($billingAddress)
            ? $this->resolveCanonicalAddress($billingAddress, $customerId, 'billing')
            : $canonicalShippingAddress;

        // 5. Group by Vendor and Calculate Exact Amounts (BCMath only)
        $vendorGroups = [];
        $grossAmount = '0.00';

        // Group cart items by seller
        $groupedBySeller = $cartItems->groupBy(function ($item) {
            return ($item->seller_is === 'admin' ? 'admin' : 'seller') . '_' . ($item->seller_id ?? 1);
        });

        foreach ($groupedBySeller as $groupKey => $items) {
            $first = $items->first();
            $sellerId = (int) ($first->seller_id ?? 1);
            $sellerIs = (string) ($first->seller_is ?? 'seller');
            $cartGroupId = (string) ($first->cart_group_id ?? $groupKey);

            $groupItems = [];
            $groupItemsSubtotal = '0.00';

            // Sort items deterministically by product_id and variant
            $sortedItems = $items->sortBy(function ($item) {
                return $item->product_id . '_' . $item->variant;
            });

            foreach ($sortedItems as $item) {
                $unitPrice = $this->toDecimalString($item->price);
                $quantity = max(1, (int) $item->quantity);
                $linePrice = bcmul($unitPrice, (string) $quantity, 2);
                $lineDiscount = $this->toDecimalString($item->discount ?? '0.00');
                $lineTax = $this->toDecimalString($item->tax ?? '0.00');

                // line_total = (unit_price * qty) - discount + tax
                $lineTotal = bcadd(bcsub($linePrice, $lineDiscount, 2), $lineTax, 2);
                $groupItemsSubtotal = bcadd($groupItemsSubtotal, $lineTotal, 2);

                $groupItems[] = [
                    'cart_id' => (int) $item->id,
                    'product_id' => (int) $item->product_id,
                    'product_name' => (string) ($item->product->name ?? $item->name ?? ''),
                    'product_type' => (string) ($item->product_type ?? 'physical'),
                    'variant' => (string) ($item->variant ?? ''),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $lineDiscount,
                    'tax' => $lineTax,
                    'line_total' => $lineTotal,
                ];
            }

            // Resolve shipping method and cost for this vendor group
            $cartShipping = CartShipping::where('cart_group_id', $cartGroupId)->first();
            $shippingMethodId = $cartShipping ? (int) $cartShipping->shipping_method_id : 0;
            $shippingCost = $cartShipping 
                ? $this->toDecimalString($cartShipping->shipping_cost ?? '0.00') 
                : $this->toDecimalString($first->shipping_cost ?? '0.00');

            // Group total = items subtotal + shipping cost
            $groupTotal = bcadd($groupItemsSubtotal, $shippingCost, 2);

            $vendorGroups[] = [
                'seller_id' => $sellerId,
                'seller_is' => $sellerIs,
                'cart_group_id' => $cartGroupId,
                'shipping_method_id' => $shippingMethodId,
                'shipping_cost' => $shippingCost,
                'subtotal' => $groupItemsSubtotal,
                'total' => $groupTotal,
                'items' => $groupItems,
            ];
        }

        // Sort vendor groups deterministically by seller_id
        usort($vendorGroups, function ($a, $b) {
            if ($a['seller_is'] === $b['seller_is']) {
                return $a['seller_id'] <=> $b['seller_id'];
            }
            return strcmp($a['seller_is'], $b['seller_is']);
        });

        // Calculate gross total across all vendor groups
        foreach ($vendorGroups as $vg) {
            $grossAmount = bcadd($grossAmount, $vg['total'], 2);
        }

        // 6. Generate Canonical Fingerprint Payload
        $fingerprintPayload = [
            'customer_id' => $customerId,
            'currency' => 'NGN',
            'gross_amount' => $grossAmount,
            'use_cashback' => (bool) $useCashback,
            'shipping_address' => $canonicalShippingAddress,
            'billing_address' => $canonicalBillingAddress,
            'vendors' => $vendorGroups,
        ];
        $cartFingerprint = hash('sha256', json_encode($fingerprintPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // 7. Atomic Intent Creation / Idempotency Replay under DB Transaction
        return DB::transaction(function () use (
            $customerId,
            $idempotencyKey,
            $cartFingerprint,
            $grossAmount,
            $useCashback,
            $canonicalShippingAddress,
            $canonicalBillingAddress,
            $vendorGroups
        ) {
            // Check for existing intent with the SAME idempotency key
            $existingByKey = CheckoutIntent::where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existingByKey) {
                // Same key + identical payload -> Graceful Replay (200 OK)
                if ($existingByKey->cart_fingerprint === $cartFingerprint) {
                    return $existingByKey;
                }
                // Same key + different payload -> HTTP 409 Idempotency Conflict
                throw new IdempotencyConflictException(
                    "Idempotency conflict: key '{$idempotencyKey}' was previously used with different checkout parameters."
                );
            }

            // Lazy Expiry & Active Token Management
            $existingActive = CheckoutIntent::where('customer_id', $customerId)
                ->whereNotNull('active_cart_token')
                ->lockForUpdate()
                ->first();

            if ($existingActive) {
                if (now()->greaterThanOrEqualTo($existingActive->expires_at)) {
                    // Stale active intent expired -> Release active token and any reserved cashback under lock
                    $existingActive->update([
                        'status' => 'expired',
                        'active_cart_token' => null,
                    ]);
                    CashbackRedemption::where('checkout_intent_id', $existingActive->id)
                        ->where('status', 'reserved')
                        ->update(['status' => 'released', 'released_at' => now()]);
                } elseif ($existingActive->cart_fingerprint === $cartFingerprint) {
                    // Active intent for identical cart state already exists
                    return $existingActive;
                } else {
                    // Customer updated cart items or shipping address -> Supersede previous pending intent
                    $existingActive->update([
                        'status' => 'canceled',
                        'active_cart_token' => null,
                    ]);
                    CashbackRedemption::where('checkout_intent_id', $existingActive->id)
                        ->where('status', 'reserved')
                        ->update(['status' => 'released', 'released_at' => now()]);
                }
            }

            // Authoritative Cashback Calculation & Reservation under Row Lock
            $cashbackAmount = '0.00';
            $pointsToReserve = '0.0000';
            $exchangeRate = (float) (getWebConfig(name: 'loyalty_point_exchange_rate') ?: 1);
            $maxCapPercentage = (float) (getWebConfig(name: 'loyalty_point_max_order_redemption_percentage') ?: 10);
            $loyaltyStatus = (int) (getWebConfig(name: 'loyalty_point_status') ?: 0);
            $minPoint = (float) (getWebConfig(name: 'loyalty_point_minimum_point') ?: 0);

            if ($useCashback && $loyaltyStatus === 1) {
                $lockedCustomer = User::where('id', $customerId)->lockForUpdate()->first();
                $activeReservedPoints = CashbackRedemption::where('customer_id', $customerId)
                    ->where('status', 'reserved')
                    ->lockForUpdate()
                    ->sum('points') ?: '0.0000';

                $userPoints = (string) ($lockedCustomer->loyalty_point ?? '0.0000');
                $effectiveAvailable = bcsub($userPoints, (string) $activeReservedPoints, 4);
                if (bccomp($effectiveAvailable, '0.0000', 4) < 0) {
                    $effectiveAvailable = '0.0000';
                }

                if (bccomp($effectiveAvailable, (string) $minPoint, 4) >= 0) {
                    // Maximum Naira discount allowed on this order (e.g. 10% cap)
                    $maxNairaDiscount = bcmul($grossAmount, bcdiv((string) $maxCapPercentage, '100', 4), 2);
                    // Value of customer's effective points in Naira
                    $pointsInNaira = bcmul($effectiveAvailable, (string) $exchangeRate, 2);
                    // Actual cashback discount is min(pointsInNaira, maxNairaDiscount)
                    $cashbackAmount = (bccomp($pointsInNaira, $maxNairaDiscount, 2) > 0) ? $maxNairaDiscount : $pointsInNaira;
                    // Exact points corresponding to the cashback amount
                    $pointsToReserve = bcdiv($cashbackAmount, (string) $exchangeRate, 4);
                }
            }

            // Final net payable amount (Gross - Cashback)
            $totalAmount = bcsub($grossAmount, $cashbackAmount, 2);
            if (bccomp($totalAmount, '0.00', 2) < 0) {
                $totalAmount = '0.00';
            }

            // Generate Strong Unique Order Group ID
            $orderGroupId = 'OG_' . Str::orderedUuid()->toString();

            // Build Immutable Snapshot JSON
            $snapshot = [
                'version' => 1,
                'customer_id' => $customerId,
                'order_group_id' => $orderGroupId,
                'currency' => 'NGN',
                'gross_amount' => $grossAmount,
                'total_amount' => $totalAmount,
                'shipping_address' => $canonicalShippingAddress,
                'billing_address' => $canonicalBillingAddress,
                'cashback' => [
                    'enabled' => bccomp($cashbackAmount, '0.00', 2) > 0,
                    'points_reserved' => $pointsToReserve,
                    'cashback_amount' => $cashbackAmount,
                    'exchange_rate' => (string) $exchangeRate,
                    'redemption_cap_percentage' => (string) $maxCapPercentage,
                ],
                'vendors' => $vendorGroups,
                'fingerprint' => $cartFingerprint,
                'created_at' => now()->toIso8601String(),
            ];

            try {
                $intent = CheckoutIntent::create([
                    'order_group_id' => $orderGroupId,
                    'customer_id' => $customerId,
                    'idempotency_key' => $idempotencyKey,
                    'cart_fingerprint' => $cartFingerprint,
                    'active_cart_token' => $cartFingerprint,
                    'status' => 'pending',
                    'total_amount' => $totalAmount,
                    'currency' => 'NGN',
                    'checkout_snapshot' => $snapshot,
                    'expires_at' => now()->addHours(24),
                ]);

                // Atomically create CashbackRedemption reservation record if cashback points reserved
                if (bccomp($pointsToReserve, '0.0000', 4) > 0) {
                    CashbackRedemption::create([
                        'customer_id' => $customerId,
                        'checkout_intent_id' => $intent->id,
                        'order_group_id' => $orderGroupId,
                        'points' => $pointsToReserve,
                        'cashback_amount' => $cashbackAmount,
                        'status' => 'reserved',
                    ]);
                }

                return $intent;
            } catch (QueryException $e) {
                // Race condition guard: concurrent identical request inserted first
                if ($e->getCode() == 23000 || str_contains($e->getMessage(), '1062')) {
                    $racing = CheckoutIntent::where('idempotency_key', $idempotencyKey)->first()
                        ?? CheckoutIntent::where('customer_id', $customerId)
                            ->where('active_cart_token', $cartFingerprint)
                            ->first();

                    if ($racing) {
                        return $racing;
                    }
                }
                throw $e;
            }
        });
    }

    /**
     * Resolves a shipping or billing address into a canonical array structure.
     * Enforces IDOR authorization: address must belong to the customer.
     */
    protected function resolveCanonicalAddress(int|ShippingAddress|array $addressInput, int $customerId, string $type): array
    {
        if (is_int($addressInput)) {
            $addr = ShippingAddress::where('id', $addressInput)
                ->where('customer_id', $customerId)
                ->first();

            if (!$addr) {
                throw new InvalidCartException(
                    "Invalid {$type} address ID #{$addressInput}: address not found or does not belong to customer #{$customerId}."
                );
            }

            return [
                'id' => (int) $addr->id,
                'address_type' => (string) ($addr->address_type ?? 'home'),
                'contact_person_name' => (string) ($addr->contact_person_name ?? ''),
                'address' => (string) ($addr->address ?? ''),
                'city' => (string) ($addr->city ?? ''),
                'zip' => (string) ($addr->zip ?? ''),
                'phone' => (string) ($addr->phone ?? ''),
                'state' => (string) ($addr->state ?? ''),
                'country' => (string) ($addr->country ?? 'Nigeria'),
                'latitude' => (string) ($addr->latitude ?? ''),
                'longitude' => (string) ($addr->longitude ?? ''),
            ];
        }

        if ($addressInput instanceof ShippingAddress) {
            if ((int) $addressInput->customer_id !== $customerId) {
                throw new InvalidCartException("IDOR Violation: address does not belong to customer #{$customerId}.");
            }

            return [
                'id' => (int) $addressInput->id,
                'address_type' => (string) ($addressInput->address_type ?? 'home'),
                'contact_person_name' => (string) ($addressInput->contact_person_name ?? ''),
                'address' => (string) ($addressInput->address ?? ''),
                'city' => (string) ($addressInput->city ?? ''),
                'zip' => (string) ($addressInput->zip ?? ''),
                'phone' => (string) ($addressInput->phone ?? ''),
                'state' => (string) ($addressInput->state ?? ''),
                'country' => (string) ($addressInput->country ?? 'Nigeria'),
                'latitude' => (string) ($addressInput->latitude ?? ''),
                'longitude' => (string) ($addressInput->longitude ?? ''),
            ];
        }

        if (is_array($addressInput)) {
            $required = ['address', 'city', 'phone', 'contact_person_name'];
            foreach ($required as $f) {
                if (empty($addressInput[$f])) {
                    throw new InvalidCartException("Missing required {$type} address field: '{$f}'.");
                }
            }

            return [
                'id' => isset($addressInput['id']) ? (int) $addressInput['id'] : null,
                'address_type' => (string) ($addressInput['address_type'] ?? 'home'),
                'contact_person_name' => (string) $addressInput['contact_person_name'],
                'address' => (string) $addressInput['address'],
                'city' => (string) $addressInput['city'],
                'zip' => (string) ($addressInput['zip'] ?? ''),
                'phone' => (string) $addressInput['phone'],
                'state' => (string) ($addressInput['state'] ?? ''),
                'country' => (string) ($addressInput['country'] ?? 'Nigeria'),
                'latitude' => (string) ($addressInput['latitude'] ?? ''),
                'longitude' => (string) ($addressInput['longitude'] ?? ''),
            ];
        }

        throw new InvalidCartException("Unsupported {$type} address input type.");
    }

    /**
     * Converts any monetary input into a canonical 2-decimal string.
     * Enforces non-negative values and at most 2 decimal places.
     * Zero float / zero round().
     */
    public function toDecimalString(string|int|float|null $amount): string
    {
        if ($amount === null) {
            return '0.00';
        }

        $sanitized = trim((string) $amount);

        // Strict format check: non-negative integer or decimal with up to 2 decimal places
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $sanitized)) {
            throw new InvalidArgumentException(
                "Invalid monetary format: '{$sanitized}'. Amount must be a non-negative decimal string with at most 2 decimal places."
            );
        }

        // Normalize to exactly 2 decimal places using BCMath
        return bcadd($sanitized, '0', 2);
    }
}
