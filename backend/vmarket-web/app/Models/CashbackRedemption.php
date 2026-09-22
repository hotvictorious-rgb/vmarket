<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * [AI] CashbackRedemption Model
 *
 * Tracks Victorious Points (Cashback) transactions for both fulfilment channels:
 *
 * DELIVERY (spend/redeem path):
 *   - status='reserved': Points locked during CheckoutIntent creation; cannot be spent concurrently.
 *   - status='captured': Points permanently deducted upon verified Paystack delivery payment.
 *   - status='released': Points returned to pool on cancellation, stock failure, or payment failure.
 *   - status='expired': Reservation expired with its stale CheckoutIntent.
 *   - checkout_intent_id is set; pickup_reservation_id is NULL.
 *
 * PICKUP (earn path):
 *   - status='captured' only: Points awarded immediately upon verified Paystack pickup payment settlement.
 *   - No 'reserved' phase for pickup — cashback is earned, not spent.
 *   - pickup_reservation_id is set; checkout_intent_id is NULL.
 *
 * @property int $id
 * @property int $customer_id
 * @property int|null $checkout_intent_id  FK to checkout_intents (delivery)
 * @property int|null $pickup_reservation_id FK to pickup_reservations (pickup)
 * @property string $order_group_id
 * @property string $points
 * @property string $cashback_amount
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $captured_at
 * @property \Illuminate\Support\Carbon|null $released_at
 */
class CashbackRedemption extends Model
{
    use HasFactory;

    protected $table = 'cashback_redemptions';

    protected $fillable = [
        'customer_id',
        'checkout_intent_id',
        'pickup_reservation_id',
        'order_group_id',
        'points',
        'cashback_amount',
        'status',
        'captured_at',
        'released_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'checkout_intent_id' => 'integer',
        'pickup_reservation_id' => 'integer',
        'order_group_id' => 'string',
        'points' => 'string',
        'cashback_amount' => 'string',
        'status' => 'string',
        'captured_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function checkoutIntent(): BelongsTo
    {
        return $this->belongsTo(CheckoutIntent::class, 'checkout_intent_id');
    }

    /**
     * [AI] Relationship to the originating PickupReservation (pickup cashback earn records only).
     */
    public function pickupReservation(): BelongsTo
    {
        return $this->belongsTo(PickupReservation::class, 'pickup_reservation_id');
    }

    /**
     * [AI] Atomically captures this reservation, marking status captured and recording timestamp.
     */
    public function capture(): void
    {
        $this->update([
            'status' => 'captured',
            'captured_at' => now(),
        ]);
    }

    /**
     * [AI] Releases this reservation, returning the points to the customer's available balance.
     */
    public function release(): void
    {
        if ($this->status === 'reserved') {
            $this->update([
                'status' => 'released',
                'released_at' => now(),
            ]);
        }
    }
}
