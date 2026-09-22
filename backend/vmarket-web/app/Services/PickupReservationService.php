<?php

namespace App\Services;

use App\Exceptions\IdempotencyConflictException;
use App\Models\Cart;
use App\Models\PickupReservation;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * [AI] Service PickupReservationService
 * 
 * Authoritative lifecycle engine for In-Shop Pay-After-Inspection Pickup Reservations.
 *
 * Core Architecture & Invariants:
 * 1. Authenticated customers only (zero guest reservations in V1).
 * 2. Carts split strictly by seller_id + shop_id (1 reservation = 1 customer + 1 seller + 1 shop).
 * 3. Exactly 1 human-facing unique reservation_code per reservation (separate from Order pickup OTP).
 * 4. Parent idempotency key derives deterministic child keys per seller/shop group.
 * 5. Immutable reservation_items JSON snapshot and canonical SHA-256 fingerprint.
 * 6. Non-Inventory Hold: Reservations do NOT decrement stock or create POS holds.
 * 7. Lazy Expiry: Atomically transitions past-due reservations to 'expired'.
 * 8. Physical Inspection Lifecycle:
 *    pending_inspection -> inspected_accepted (unlocks later payment) OR inspected_rejected.
 * 9. Cart Preservation: Customer cart is NEVER cleared during reservation, inspection, rejection, or expiry.
 * 10. IDOR / Ownership Scoping: Zero cross-tenant data access between customers and vendors.
 */
class PickupReservationService
{
    /**
     * Default TTL for pickup reservations: 24 hours.
     */
    public const DEFAULT_EXPIRY_HOURS = 24;

    /**
     * Creates pickup reservations from the authenticated customer's cart,
     * splitting items strictly by seller_id + shop_id.
     *
     * @param int $customerId Authenticated customer ID
     * @param string $parentRequestIdempotencyKey Client-supplied parent idempotency key
     * @param array $options Optional filters (e.g. cart_ids, checked_only)
     * @return array Array of created/replayed PickupReservation instances
     *
     * @throws \InvalidArgumentException
     * @throws \DomainException
     * @throws IdempotencyConflictException
     */
    public function createReservationsFromCart(
        int $customerId,
        string $parentRequestIdempotencyKey,
        array $options = []
    ): array {
        // Invariant 1: Authenticated customers only
        if ($customerId <= 0) {
            throw new \InvalidArgumentException("Pickup reservations require an authenticated customer.");
        }

        $parentKey = trim($parentRequestIdempotencyKey);
        if (empty($parentKey)) {
            throw new \InvalidArgumentException("A valid parent idempotency key is required.");
        }

        // 1. Fetch eligible cart items for this authenticated customer
        $cartQuery = Cart::with(['product' => function ($q) {
            $q->with(['category', 'clearanceSale']);
        }])
        ->where('customer_id', $customerId)
        ->where('is_guest', 0);

        if (!empty($options['cart_ids'])) {
            $cartQuery->whereIn('id', (array) $options['cart_ids']);
        } elseif (!empty($options['checked_only'])) {
            $cartQuery->where('is_checked', 1);
        }

        $cartItems = $cartQuery->get();

        if ($cartItems->isEmpty()) {
            throw new \InvalidArgumentException("No cart items found for pickup reservation.");
        }

        // 2. Validate Marketplace Purchasability for all items (without inventory holds)
        foreach ($cartItems as $item) {
            $product = $item->product;
            if (!$product) {
                throw new \DomainException("Cart item #{$item->id} references a missing product.");
            }

            if (!$product->isMarketplaceEligible()) {
                throw new \DomainException("Product '{$product->name}' is currently not eligible for marketplace purchase.");
            }
        }

        // 3. Group cart items strictly by seller_id + shop_id
        $groups = [];
        foreach ($cartItems as $item) {
            $sellerId = (int) $item->seller_id;
            $sellerIs = (string) ($item->seller_is ?? 'seller');
            
            // Resolve authoritative shop_id
            $shopId = (int) ($item->product->shop_id ?? 0);
            if ($shopId === 0) {
                if ($sellerIs === 'admin' || $sellerId === 0) {
                    $adminShop = Shop::where('author_type', 'admin')->first();
                    $shopId = $adminShop ? (int) $adminShop->id : 1;
                } else {
                    $sellerShop = Shop::where('seller_id', $sellerId)->first();
                    $shopId = $sellerShop ? (int) $sellerShop->id : 0;
                }
            }

            $groupKey = "{$sellerId}_{$shopId}";
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'seller_id' => $sellerId,
                    'seller_is' => $sellerIs,
                    'shop_id' => $shopId,
                    'items' => [],
                ];
            }

            $groups[$groupKey]['items'][] = $item;
        }

        // 4. Create or replay one reservation per seller/shop group under DB transactions
        $results = [];
        foreach ($groups as $groupKey => $group) {
            $sellerId = $group['seller_id'];
            $sellerIs = $group['seller_is'];
            $shopId = $group['shop_id'];
            $items = $group['items'];

            // Deterministic child idempotency key: PRC_ + 58 chars hash
            $childKey = 'PRC_' . substr(hash('sha256', $parentKey . ':' . $sellerId . ':' . $shopId), 0, 58);

            // Compute exact financial amounts with BCMath (Shipping is strictly 0.00 for pickup)
            $groupSubtotal = '0.00';
            $groupTax = '0.00';
            $groupDiscount = '0.00';
            $groupItemsData = [];

            foreach ($items as $item) {
                $unitPrice = number_format((float) $item->price, 2, '.', '');
                $discount = number_format((float) ($item->discount ?? 0.00), 2, '.', '');
                $tax = number_format((float) ($item->tax ?? 0.00), 2, '.', '');
                $qty = (int) $item->quantity;

                $netUnit = bcsub($unitPrice, $discount, 2);
                $lineSubtotal = bcmul($netUnit, (string) $qty, 2);
                $lineTax = bcmul($tax, (string) $qty, 2);
                $lineTotal = bcadd($lineSubtotal, $lineTax, 2);

                $groupSubtotal = bcadd($groupSubtotal, $lineSubtotal, 2);
                $groupTax = bcadd($groupTax, $lineTax, 2);
                $groupDiscount = bcadd($groupDiscount, bcmul($discount, (string) $qty, 2), 2);

                $groupItemsData[] = [
                    'cart_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => (string) ($item->product->name ?? $item->name ?? ''),
                    'product_type' => (string) ($item->product_type ?? 'physical'),
                    'variant' => (string) ($item->variant ?? ''),
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'tax' => $tax,
                    'line_total' => $lineTotal,
                ];
            }

            // Total = Subtotal + Tax (Shipping = 0.00 for in-store pickup)
            $groupTotal = bcadd($groupSubtotal, $groupTax, 2);

            // Resolve Shop details for snapshot
            $shop = Shop::find($shopId);
            $shopSnapshot = [
                'shop_id' => $shopId,
                'seller_id' => $sellerId,
                'name' => $shop ? (string) $shop->name : 'Shop #' . $shopId,
                'address' => $shop ? (string) $shop->address : '',
                'contact' => null, // [AI] Strictly redacted for vendor privacy
                'direction_guidance' => 'Need help finding this store? Please message Customer Support for step-by-step guidance.',
            ];

            // Immutable reservation snapshot
            $reservationSnapshot = [
                'customer_id' => $customerId,
                'seller_id' => $sellerId,
                'seller_is' => $sellerIs,
                'shop' => $shopSnapshot,
                'items' => $groupItemsData,
                'subtotal' => $groupSubtotal,
                'tax' => $groupTax,
                'discount' => $groupDiscount,
                'shipping_cost' => '0.00',
                'total_amount' => $groupTotal,
                'currency' => 'NGN',
                'created_at' => now()->toIso8601String(),
            ];

            // Canonical SHA-256 fingerprint
            $fingerprintPayload = [
                'customer_id' => $customerId,
                'seller_id' => $sellerId,
                'shop_id' => $shopId,
                'currency' => 'NGN',
                'total_amount' => $groupTotal,
                'items' => $groupItemsData,
            ];
            $fingerprint = hash('sha256', json_encode($fingerprintPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            // Atomic creation / idempotency replay under DB transaction
            $reservation = DB::transaction(function () use (
                $customerId,
                $sellerId,
                $shopId,
                $childKey,
                $fingerprint,
                $groupTotal,
                $reservationSnapshot
            ) {
                // Check existing reservation with this child idempotency key
                $existing = PickupReservation::where('idempotency_key', $childKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $existing->checkAndApplyLazyExpiry();

                    // Same key + identical payload -> Graceful Replay (200 OK)
                    if ($existing->reservation_fingerprint === $fingerprint) {
                        return $existing;
                    }

                    // Same key + different payload -> HTTP 409 Conflict
                    throw new IdempotencyConflictException(
                        "Idempotency conflict: child key '{$childKey}' was previously used with different reservation parameters."
                    );
                }

                // Lazy-expire any past-due pending reservations for this customer
                $expiredReservations = PickupReservation::where('customer_id', $customerId)
                    ->where('status', 'pending_inspection')
                    ->where('expires_at', '<=', now())
                    ->lockForUpdate()
                    ->get();

                foreach ($expiredReservations as $exp) {
                    $exp->update([
                        'status' => 'expired',
                        'active_reservation_token' => null,
                    ]);
                }

                // Generate unique, collision-safe human-friendly reservation code: RES-XXXXXXXX
                $reservationCode = $this->generateUniqueReservationCode();
                $activeToken = Str::uuid()->toString();

                return PickupReservation::create([
                    'reservation_code' => $reservationCode,
                    'idempotency_key' => $childKey,
                    'customer_id' => $customerId,
                    'seller_id' => $sellerId,
                    'shop_id' => $shopId,
                    'reservation_fingerprint' => $fingerprint,
                    'active_reservation_token' => $activeToken,
                    'status' => 'pending_inspection',
                    'total_amount' => $groupTotal,
                    'currency' => 'NGN',
                    'reservation_items' => $reservationSnapshot,
                    'expires_at' => Carbon::now()->addHours(self::DEFAULT_EXPIRY_HOURS),
                ]);
            });

            $results[] = $reservation;
        }

        return $results;
    }

    /**
     * Customer lookup: Retrieves a single reservation scoped strictly to customer_id (IDOR protection).
     */
    public function getReservationForCustomer(int $customerId, string $reservationCode): ?PickupReservation
    {
        $reservation = PickupReservation::where('customer_id', $customerId)
            ->where('reservation_code', $reservationCode)
            ->first();

        if ($reservation) {
            $reservation->checkAndApplyLazyExpiry();
        }

        return $reservation;
    }

    /**
     * Customer lookup: Lists reservations belonging to the authenticated customer.
     */
    public function listReservationsForCustomer(int $customerId): Collection
    {
        $reservations = PickupReservation::where('customer_id', $customerId)
            ->orderByDesc('id')
            ->get();

        foreach ($reservations as $res) {
            $res->checkAndApplyLazyExpiry();
        }

        return $reservations;
    }

    /**
     * Vendor lookup: Verifies a reservation code for physical inspection.
     * Enforces vendor and shop authorization scoping.
     *
     * @param string $reservationCode Human-facing reservation code
     * @param int $sellerId Authenticated vendor seller_id
     * @param int|null $shopId Authenticated vendor shop_id
     * @return array Sanitized inspection details or error status
     */
    public function verifyReservationForVendor(string $reservationCode, int $sellerId, ?int $shopId = null): array
    {
        $reservation = PickupReservation::where('reservation_code', $reservationCode)->first();

        if (!$reservation) {
            return [
                'status' => 'NOT_FOUND',
                'message' => 'Reservation code not found.',
            ];
        }

        if ($reservation->checkAndApplyLazyExpiry()) {
            return [
                'status' => 'EXPIRED',
                'message' => 'This reservation has expired.',
                'reservation_code' => $reservation->reservation_code,
            ];
        }

        // IDOR Protection: Must match authenticated vendor
        if ((int) $reservation->seller_id !== (int) $sellerId) {
            return [
                'status' => 'FORBIDDEN',
                'message' => 'Unauthorized: This reservation belongs to a different vendor.',
            ];
        }

        // IDOR Protection: Must match authenticated shop if specified
        if ($shopId !== null && (int) $reservation->shop_id !== (int) $shopId) {
            return [
                'status' => 'FORBIDDEN',
                'message' => 'Unauthorized: This reservation is assigned to a different shop location.',
            ];
        }

        $snapshot = $reservation->reservation_items;
        $customer = $reservation->customer;

        return [
            'status' => 'VERIFIED',
            'reservation_code' => $reservation->reservation_code,
            'current_status' => $reservation->status,
            'shop_id' => $reservation->shop_id,
            'total_amount' => $reservation->total_amount,
            'currency' => $reservation->currency,
            'expires_at' => $reservation->expires_at->toIso8601String(),
            'items' => $snapshot['items'] ?? [],
            'customer_summary' => [
                'customer_id' => $reservation->customer_id,
                'name' => $customer ? trim($customer->f_name . ' ' . $customer->l_name) : 'Customer',
                'phone_masked' => $customer && $customer->phone ? substr($customer->phone, 0, 4) . '****' . substr($customer->phone, -3) : '',
            ],
        ];
    }

    /**
     * Vendor inspection acceptance: Marks reservation as inspected_accepted.
     *
     * Invariants:
     * - Vendor must own the reservation.
     * - Shop must match.
     * - Status must be 'pending_inspection'.
     * - Must not be expired.
     * - Does NOT create PaymentRequest, Order, or wallet transaction.
     * - Does NOT decrement stock.
     */
    public function acceptInspection(
        string $reservationCode,
        int $sellerId,
        ?int $shopId = null,
        array $notes = []
    ): array {
        return DB::transaction(function () use ($reservationCode, $sellerId, $shopId, $notes) {
            $reservation = PickupReservation::where('reservation_code', $reservationCode)
                ->lockForUpdate()
                ->first();

            if (!$reservation) {
                return ['status' => 'NOT_FOUND', 'message' => 'Reservation not found.'];
            }

            if ($reservation->checkAndApplyLazyExpiry()) {
                return ['status' => 'EXPIRED', 'message' => 'Cannot accept an expired reservation.'];
            }

            // IDOR Protection
            if ((int) $reservation->seller_id !== (int) $sellerId) {
                return ['status' => 'FORBIDDEN', 'message' => 'Unauthorized: Reservation belongs to another vendor.'];
            }

            if ($shopId !== null && (int) $reservation->shop_id !== (int) $shopId) {
                return ['status' => 'FORBIDDEN', 'message' => 'Unauthorized: Reservation is for a different shop.'];
            }

            // State Validation
            if ($reservation->status === 'inspected_accepted') {
                return [
                    'status' => 'ALREADY_ACCEPTED',
                    'is_replayed' => true,
                    'message' => 'Reservation has already been accepted during physical inspection.',
                    'reservation' => $reservation,
                ];
            }

            if ($reservation->status === 'inspected_rejected') {
                return [
                    'status' => 'INVALID_TRANSITION',
                    'message' => 'Cannot accept a reservation that was previously rejected.',
                ];
            }

            if ($reservation->status !== 'pending_inspection') {
                return [
                    'status' => 'INVALID_TRANSITION',
                    'message' => "Cannot accept reservation in state '{$reservation->status}'.",
                ];
            }

            $reservation->update([
                'status' => 'inspected_accepted',
                'inspected_at' => now(),
            ]);

            return [
                'status' => 'SUCCESS',
                'message' => 'Physical inspection passed and reservation accepted. Customer may proceed to payment.',
                'reservation' => $reservation->fresh(),
            ];
        });
    }

    /**
     * Vendor inspection rejection: Marks reservation as inspected_rejected.
     *
     * Invariants:
     * - Vendor must own the reservation.
     * - Shop must match.
     * - Status must be 'pending_inspection'.
     * - Does NOT create PaymentRequest, Order, or wallet transaction.
     * - Does NOT perform refund (no payment ever occurred).
     * - Customer cart remains 100% untouched.
     */
    public function rejectInspection(
        string $reservationCode,
        int $sellerId,
        ?int $shopId = null,
        string $rejectionReason = ''
    ): array {
        return DB::transaction(function () use ($reservationCode, $sellerId, $shopId, $rejectionReason) {
            $reservation = PickupReservation::where('reservation_code', $reservationCode)
                ->lockForUpdate()
                ->first();

            if (!$reservation) {
                return ['status' => 'NOT_FOUND', 'message' => 'Reservation not found.'];
            }

            if ($reservation->checkAndApplyLazyExpiry()) {
                return ['status' => 'EXPIRED', 'message' => 'Cannot reject an expired reservation.'];
            }

            // IDOR Protection
            if ((int) $reservation->seller_id !== (int) $sellerId) {
                return ['status' => 'FORBIDDEN', 'message' => 'Unauthorized: Reservation belongs to another vendor.'];
            }

            if ($shopId !== null && (int) $reservation->shop_id !== (int) $shopId) {
                return ['status' => 'FORBIDDEN', 'message' => 'Unauthorized: Reservation is for a different shop.'];
            }

            // State Validation
            if ($reservation->status === 'inspected_rejected') {
                return [
                    'status' => 'ALREADY_REJECTED',
                    'is_replayed' => true,
                    'message' => 'Reservation has already been rejected.',
                    'reservation' => $reservation,
                ];
            }

            if ($reservation->status === 'inspected_accepted') {
                return [
                    'status' => 'INVALID_TRANSITION',
                    'message' => 'Cannot reject a reservation that was already accepted during physical inspection.',
                ];
            }

            if ($reservation->status !== 'pending_inspection') {
                return [
                    'status' => 'INVALID_TRANSITION',
                    'message' => "Cannot reject reservation in state '{$reservation->status}'.",
                ];
            }

            $reservation->update([
                'status' => 'inspected_rejected',
                'active_reservation_token' => null,
                'inspected_at' => now(),
            ]);

            return [
                'status' => 'SUCCESS',
                'message' => 'Reservation physically rejected.',
                'reason' => $rejectionReason,
                'reservation' => $reservation->fresh(),
            ];
        });
    }

    /**
     * Generates a collision-safe human-friendly reservation code (e.g. RES-9B4X2K1L).
     */
    protected function generateUniqueReservationCode(): string
    {
        do {
            $randomHex = strtoupper(bin2hex(random_bytes(4))); // 8 hex chars
            $code = 'RES-' . $randomHex;
        } while (PickupReservation::where('reservation_code', $code)->exists());

        return $code;
    }
}
