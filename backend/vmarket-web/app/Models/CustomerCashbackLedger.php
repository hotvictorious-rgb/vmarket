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
     * [AI] Helper to credit 5% cashback reward on eligible order merchandise.
     * Enforces strict lifetime order uniqueness across ALL statuses (pending, available, redeemed, cancelled).
     * Strictly requires authoritative customer receipt (received_at and refund_window_expires_at).
     */
    public static function creditRewardForOrder(Order $order): ?self
    {
        // Must have authenticated registered customer
        if (!$order->customer_id || $order->is_guest) {
            return null;
        }

        // Must have verified customer receipt and active return window
        if (
            $order->order_status !== 'delivered'
            || empty($order->received_at)
            || empty($order->refund_window_expires_at)
        ) {
            return null;
        }

        // Lifetime Order Uniqueness Guard:
        // A single child order can have only ONE base cashback issuance across its entire lifecycle.
        $existing = self::where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing; // Idempotent discovery: never create a duplicate row
        }

        // Calculate merchandise net amount using exact BCMath string arithmetic.
        // getRawOriginal() bypasses float casts on Order.order_amount, Order.shipping_cost, Order.total_tax_amount
        $totalOrderAmount = bcadd((string)($order->getRawOriginal('order_amount') ?? '0.00'), '0', 2);
        $shippingCost = bcadd((string)($order->getRawOriginal('shipping_cost') ?? '0.00'), '0', 2);
        $taxAmount = bcadd((string)($order->getRawOriginal('total_tax_amount') ?? '0.00'), '0', 2);
        
        $merchandiseAmountStr = bcsub(bcsub($totalOrderAmount, $shippingCost, 2), $taxAmount, 2);
        $merchandiseAmount = (bccomp($merchandiseAmountStr, '0.00', 2) < 0) ? '0.00' : $merchandiseAmountStr;

        if (bccomp($merchandiseAmount, '0.00', 2) <= 0) {
            return null;
        }

        // 5% Cashback Reward calculated via BCMath string arithmetic
        $cashbackRate = '5.00';
        $cashbackAmount = bcmul($merchandiseAmount, '0.05', 2);

        return self::create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'merchandise_amount' => $merchandiseAmount,
            'cashback_rate' => $cashbackRate,
            'cashback_amount' => $cashbackAmount,
            'status' => 'pending',
            'available_at' => $order->refund_window_expires_at,
            'description' => "5% Victorious Cashback Reward for Order #{$order->id}",
        ]);
    }

    /**
     * [AI] Adjust pending cashback proportionally upon partial refund
     */
    public function adjustForPartialRefund(string $remainingMerchandise): void
    {
        if ($this->status !== 'pending') {
            return;
        }

        $remainingMerchandise = bcadd($remainingMerchandise, '0', 2);
        if (bccomp($remainingMerchandise, '0.00', 2) <= 0) {
            $this->status = 'cancelled';
            $this->description = "Cancelled due to full merchandise refund for Order #{$this->order_id}";
            $this->save();
            return;
        }

        $adjustedCashback = bcmul($remainingMerchandise, '0.05', 2);
        $this->merchandise_amount = $remainingMerchandise;
        $this->cashback_amount = $adjustedCashback;
        $this->description = "5% Victorious Cashback Reward for Order #{$this->order_id} (Adjusted for partial refund)";
        $this->save();
    }
}
