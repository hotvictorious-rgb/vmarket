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
        'expires_at',
        'redeemed_at',
        'redeemed_order_id',
        'description',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'merchandise_amount' => 'decimal:2',
        'cashback_rate' => 'decimal:2',
        'cashback_amount' => 'decimal:2',
        'status' => 'string',
        'available_at' => 'datetime',
        'expires_at' => 'datetime',
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

        // [AI] Strictly enforce: New rewards apply ONLY to NEW MONEY paid for merchandise!
        // Subtract any redeemed cashback applied to this order.
        $redeemedCashbackOnOrder = '0.00';
        if ($order->discount_type === 'cashback' && !empty($order->discount_amount)) {
            $redeemedCashbackOnOrder = bcadd((string)($order->getRawOriginal('discount_amount') ?? '0.00'), '0', 2);
        } else {
            if (!empty($order->order_group_id)) {
                $redemption = CashbackRedemption::where('order_group_id', $order->order_group_id)
                    ->where('status', 'captured')
                    ->first();
                if ($redemption) {
                    $groupOrdersCount = Order::where('order_group_id', $order->order_group_id)->count();
                    if ($groupOrdersCount <= 1) {
                        $redeemedCashbackOnOrder = bcadd((string)$redemption->cashback_amount, '0', 2);
                    } else {
                        $groupSubtotal = Order::where('order_group_id', $order->order_group_id)->sum('order_amount');
                        if (bccomp((string)$groupSubtotal, '0.00', 2) > 0) {
                            $ratio = bcdiv((string)$order->order_amount, (string)$groupSubtotal, 4);
                            $redeemedCashbackOnOrder = bcmul((string)$redemption->cashback_amount, $ratio, 2);
                        }
                    }
                }
            }

            $pickupRes = PickupReservation::where('order_id', $order->id)->first();
            if ($pickupRes) {
                $pRedemption = CashbackRedemption::where('pickup_reservation_id', $pickupRes->id)
                    ->where('status', 'captured')
                    ->first();
                if ($pRedemption) {
                    $redeemedCashbackOnOrder = bcadd((string)$pRedemption->cashback_amount, '0', 2);
                }
            }
        }

        $rawOrderAmount = bcadd((string)($order->getRawOriginal('order_amount') ?? '0.00'), '0', 2);
        $rawInitAmount = bcadd((string)($order->getRawOriginal('init_order_amount') ?? '0.00'), '0', 2);
        $shippingCost = bcadd((string)($order->getRawOriginal('shipping_cost') ?? '0.00'), '0', 2);
        $taxAmount = bcadd((string)($order->getRawOriginal('total_tax_amount') ?? '0.00'), '0', 2);

        // Check if order_amount was already discounted or represents gross total
        if (bccomp($rawInitAmount, $rawOrderAmount, 2) > 0) {
            // order_amount is already net of cashback discount
            $netNewMoney = bcsub(bcsub($rawOrderAmount, $shippingCost, 2), $taxAmount, 2);
        } else {
            // order_amount is gross total; explicitly subtract redeemed cashback
            $merchandiseGross = bcsub(bcsub($rawOrderAmount, $shippingCost, 2), $taxAmount, 2);
            $netNewMoney = bcsub($merchandiseGross, $redeemedCashbackOnOrder, 2);
        }

        if (bccomp($netNewMoney, '0.00', 2) <= 0) {
            return null; // Entire merchandise was paid using rewards; zero new reward earned
        }

        // [AI] Configurable Cashback Reward calculated strictly on new money via BCMath string arithmetic
        $rawRate = (string) (getWebConfig(name: 'loyalty_point_earn_rate_percent') ?: (getWebConfig(name: 'cashback_earn_rate_percent') ?: '5.00'));
        $cashbackRate = bcadd($rawRate, '0', 2);
        $rateMultiplier = bcdiv($cashbackRate, '100', 4);
        $cashbackAmount = bcmul($netNewMoney, $rateMultiplier, 2);
        $validityMonths = (int) (getWebConfig(name: 'loyalty_point_validity_months') ?: 6);
        $expiresAt = $order->refund_window_expires_at ? \Carbon\Carbon::parse($order->refund_window_expires_at)->addMonths($validityMonths) : null;

        return self::create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'merchandise_amount' => $netNewMoney,
            'cashback_rate' => $cashbackRate,
            'cashback_amount' => $cashbackAmount,
            'status' => 'pending',
            'available_at' => $order->refund_window_expires_at,
            'expires_at' => $expiresAt,
            'description' => "{$cashbackRate}% Victorious Cashback Reward for Order #{$order->id} (Earned on ₦{$netNewMoney} new money paid)",
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

        $rateMultiplier = bcdiv((string) ($this->cashback_rate ?: '5.00'), '100', 4);
        $adjustedCashback = bcmul($remainingMerchandise, $rateMultiplier, 2);
        $this->merchandise_amount = $remainingMerchandise;
        $this->cashback_amount = $adjustedCashback;
        $this->description = "{$this->cashback_rate}% Victorious Cashback Reward for Order #{$this->order_id} (Adjusted for partial refund)";
        $this->save();
    }

    /**
     * [AI] Transitions customer's available cashback ledger entries to 'redeemed' up to $redeemedNaira.
     * Enforces FIFO by earliest expiration date and strictly ignores expired records.
     */
    public static function markRedeemed(int $customerId, string $redeemedNaira, ?int $redeemedOrderId = null): void
    {
        $remainingToRedeem = bcadd($redeemedNaira, '0', 2);
        if (bccomp($remainingToRedeem, '0.00', 2) <= 0) {
            return;
        }

        $availableLedgers = self::where('customer_id', $customerId)
            ->where('status', 'available')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END, expires_at ASC, available_at ASC')
            ->lockForUpdate()
            ->get();

        foreach ($availableLedgers as $ledger) {
            $ledgerAmount = bcadd((string) $ledger->cashback_amount, '0', 2);
            if (bccomp($remainingToRedeem, $ledgerAmount, 2) >= 0) {
                $ledger->update([
                    'status' => 'redeemed',
                    'redeemed_at' => now(),
                    'redeemed_order_id' => $redeemedOrderId,
                ]);
                $remainingToRedeem = bcsub($remainingToRedeem, $ledgerAmount, 2);
            } else {
                $leftover = bcsub($ledgerAmount, $remainingToRedeem, 2);
                $ledger->update([
                    'cashback_amount' => $leftover,
                ]);
                self::create([
                    'customer_id' => $customerId,
                    'order_id' => $ledger->order_id,
                    'merchandise_amount' => '0.00',
                    'cashback_rate' => $ledger->cashback_rate,
                    'cashback_amount' => $remainingToRedeem,
                    'status' => 'redeemed',
                    'available_at' => $ledger->available_at,
                    'redeemed_at' => now(),
                    'redeemed_order_id' => $redeemedOrderId,
                    'description' => "Redeemed for Order #{$redeemedOrderId}",
                ]);
                $remainingToRedeem = '0.00';
                break;
            }

            if (bccomp($remainingToRedeem, '0.00', 2) <= 0) {
                break;
            }
        }
    }
}
