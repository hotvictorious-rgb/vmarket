<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] Model PickupReservation
 * 
 * Represents an authenticated customer's pre-payment reservation of physical items
 * at a specific seller and shop for in-person physical inspection before payment.
 *
 * Invariants:
 * 1. Exactly 1 customer, 1 seller, 1 shop per reservation.
 * 2. Does NOT decrement or hold inventory (physical store stock remains quantity authority).
 * 3. Unique human-friendly reservation_code (separate from eventual Order pickup OTP).
 * 4. Lazy expiry via expires_at.
 * 5. Lifecycle states: pending_inspection -> inspected_accepted / inspected_rejected -> order_placed / expired.
 *
 * @property int $id
 * @property string $reservation_code
 * @property string $idempotency_key
 * @property int $customer_id
 * @property int $seller_id
 * @property int $shop_id
 * @property string $reservation_fingerprint
 * @property string|null $active_reservation_token
 * @property string $status
 * @property string $total_amount
 * @property string $currency
 * @property array $reservation_items
 * @property int|null $order_id
 * @property Carbon $expires_at
 * @property Carbon|null $inspected_at
 * @property Carbon|null $finalized_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PickupReservation extends Model
{
    protected $table = 'pickup_reservations';

    protected $fillable = [
        'reservation_code',
        'idempotency_key',
        'customer_id',
        'seller_id',
        'shop_id',
        'reservation_fingerprint',
        'active_reservation_token',
        'status',
        'en_route_at',
        'en_route_sla_expires_at',
        'en_route_resend_count',
        'last_en_route_ping_at',
        'held_at',
        'held_until',
        'rejection_reason',
        'total_amount',
        'currency',
        'reservation_items',
        'order_id',
        'expires_at',
        'inspected_at',
        'finalized_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'seller_id' => 'integer',
        'shop_id' => 'integer',
        'order_id' => 'integer',
        'reservation_items' => 'array',
        'expires_at' => 'datetime',
        'en_route_at' => 'datetime',
        'en_route_sla_expires_at' => 'datetime',
        'en_route_resend_count' => 'integer',
        'last_en_route_ping_at' => 'datetime',
        'held_at' => 'datetime',
        'held_until' => 'datetime',
        'inspected_at' => 'datetime',
        'finalized_at' => 'datetime',
        'total_amount' => 'string',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function scopePendingInspection(Builder $query): Builder
    {
        return $query->where('status', 'pending_inspection');
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        return $this->expires_at && now()->greaterThanOrEqualTo($this->expires_at);
    }

    /**
     * [AI] Whether customer is currently eligible to initiate or resend "I'm coming".
     */
    public function canCustomerSignalEnRoute(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        return in_array($this->status, ['pending_inspection', 'en_route_timeout'], true);
    }

    /**
     * [AI] Whether enough cooldown has elapsed since last ping to resend "I'm coming".
     */
    public function canResendEnRoute(int $cooldownMinutes = 5): bool
    {
        if (!$this->canCustomerSignalEnRoute()) {
            return false;
        }

        if (!$this->last_en_route_ping_at) {
            return true;
        }

        return abs(now()->diffInMinutes($this->last_en_route_ping_at)) >= $cooldownMinutes;
    }

    /**
     * [AI] Seconds remaining for vendor to acknowledge "I'm coming".
     */
    public function getRemainingSlaSeconds(): int
    {
        if ($this->status !== 'en_route' || !$this->en_route_sla_expires_at) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->en_route_sla_expires_at, false));
    }

    /**
     * [AI] Minutes remaining for physical counter hold.
     */
    public function getRemainingHoldMinutes(): int
    {
        if ($this->status !== 'held_confirmed' || !$this->held_until) {
            return 0;
        }

        return max(0, now()->diffInMinutes($this->held_until, false));
    }

    /**
     * [AI] Atomically checks and applies lazy expiry or timeout to this reservation.
     *
     * @return bool True if overall reservation has expired
     */
    public function checkAndApplyLazyExpiry(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        $now = now();

        // 1. Overall reservation expiration (24h default)
        if ($this->expires_at && $now->greaterThanOrEqualTo($this->expires_at)) {
            if (in_array($this->status, ['pending_inspection', 'en_route', 'en_route_timeout', 'held_confirmed', 'inspected_accepted'], true)) {
                $this->update([
                    'status' => 'expired',
                    'active_reservation_token' => null,
                ]);
            }
            return true;
        }

        // 2. En-Route SLA timeout: Vendor didn't respond within 15 minutes
        if ($this->status === 'en_route' && $this->en_route_sla_expires_at && $now->greaterThanOrEqualTo($this->en_route_sla_expires_at)) {
            $this->update([
                'status' => 'en_route_timeout',
            ]);
            return false;
        }

        // 3. Counter hold expiration: 90-minute counter hold elapsed without arrival
        if ($this->status === 'held_confirmed' && $this->held_until && $now->greaterThanOrEqualTo($this->held_until)) {
            // Revert back to soft reservation if overall 24h window hasn't expired
            $this->update([
                'status' => 'pending_inspection',
                'held_until' => null,
            ]);
            return false;
        }

        return false;
    }
}
