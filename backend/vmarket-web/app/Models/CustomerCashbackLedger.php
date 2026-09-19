<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * [AI] CustomerCashbackLedger Model
 * Represents the 5% Victorious Cashback Reward Ledger.
 * Non-withdrawable purchase reward ledger (not a cash wallet).
 *
 * @property int $id
 * @property int $customer_id
 * @property int $order_id
 * @property float $merchandise_amount
 * @property float $cashback_rate
 * @property float $cashback_amount
 * @property string $status
 * @property string|null $available_at
 * @property string|null $redeemed_at
 * @property int|null $redeemed_order_id
 * @property string|null $description
 */
class CustomerCashbackLedger extends Model
{
    use HasFactory;

    protected $table = 'customer_cashback_ledgers';

    protected $fillable = [
        'customer_id',
        'order_id',
        'merchandise_amount',
        'cashback_rate',
        'cashback_amount',
        'status',
        'available_at',
        'redeemed_at',
        'redeemed_order_id',
        'description',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'merchandise_amount' => 'float',
        'cashback_rate' => 'float',
        'cashback_amount' => 'float',
        'status' => 'string',
        'available_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'redeemed_order_id' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * [AI] Helper to credit 5% cashback reward on eligible order merchandise
     */
    public static function creditRewardForOrder(Order $order): ?self
    {
        // Must have customer
        if (!$order->customer_id || $order->is_guest) {
            return null;
        }

        // Check if ledger entry already exists (Idempotency)
        $existing = self::where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        // Calculate merchandise net amount (excluding shipping cost)
        $shippingCost = (float)($order->shipping_cost ?? 0.00);
        $totalOrderAmount = (float)($order->order_amount ?? 0.00);
        $taxAmount = (float)($order->total_tax_amount ?? 0.00);
        $merchandiseAmount = max(0.00, $totalOrderAmount - $shippingCost - $taxAmount);

        if ($merchandiseAmount <= 0) {
            return null;
        }

        $cashbackRate = 5.00; // 5%
        $cashbackAmount = round($merchandiseAmount * ($cashbackRate / 100.0), 2);

        return self::create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'merchandise_amount' => $merchandiseAmount,
            'cashback_rate' => $cashbackRate,
            'cashback_amount' => $cashbackAmount,
            'status' => 'pending',
            'available_at' => $order->refund_window_expires_at ?? now()->addHours(24), // 24-hour return inspection window
            'description' => "5% Victorious Cashback Reward for Order #{$order->id}",
        ]);
    }
}
