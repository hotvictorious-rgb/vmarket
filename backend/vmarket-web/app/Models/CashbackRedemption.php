<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * [AI] CashbackRedemption Model
 *
 * Tracks pessimistic point reservations for Victorious Points (Cashback) redemptions at checkout.
 * States:
 *   - 'reserved': Points are locked during CheckoutIntent creation; cannot be spent by concurrent checkouts.
 *   - 'captured': Points permanently deducted upon verified Paystack payment settlement.
 *   - 'released': Reservation released back to customer pool due to cancellation, stock failure, or payment failure.
 *   - 'expired': Reservation timed out along with stale CheckoutIntent.
 *
 * @property int $id
 * @property int $customer_id
 * @property int|null $checkout_intent_id
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
