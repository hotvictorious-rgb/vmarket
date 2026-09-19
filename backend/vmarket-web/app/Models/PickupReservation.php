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
     * [AI] Atomically checks and applies lazy expiry to this reservation if past expires_at.
     *
     * @return bool True if expired
     */
    public function checkAndApplyLazyExpiry(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->expires_at && now()->greaterThanOrEqualTo($this->expires_at)) {
            if (in_array($this->status, ['pending_inspection', 'inspected_accepted'], true)) {
                $this->update([
                    'status' => 'expired',
                    'active_reservation_token' => null,
                ]);
            }
            return true;
        }

        return false;
    }
}
