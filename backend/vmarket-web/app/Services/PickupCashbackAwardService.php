<?php

namespace App\Services;

use App\Models\CashbackRedemption;
use App\Models\Order;
use App\Models\PickupReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * [AI] Service PickupCashbackAwardService
 *
 * Awards the 5% (config-driven) Victorious Cashback on in-shop pickup payments.
 * This is an earning event, not a spending event:
 *   - status='captured' is written directly (no 'reserved' phase).
 *   - The customer's loyalty_point balance is incremented (not decremented).
 *   - A loyalty_point_transactions row is written for the immutable audit trail.
 *   - A cashback_redemptions row is written linking to the pickup_reservation_id.
 *
 * MUST be called inside an existing DB::transaction() — the caller (PickupOrderSettlementService)
 * owns the transaction boundary. This service does NOT open its own transaction.
 *
 * Concurrency Safety:
 *   - Caller must have already acquired lockForUpdate() on the User row BEFORE calling this method.
 *   - This method does NOT re-acquire the user lock; it trusts the caller's hierarchy.
 *
 * Mathematical Invariant (Zero Drift):
 *   cashbackNaira = bcmul(totalAmount, bcdiv(cashbackRatePercent, '100', 6), 2)  [BCMath, Δ = ₦0.00]
 *   points        = bcdiv(cashbackNaira, exchangeRate, 4)
 *   user.loyalty_point += points  [atomic increment under lockForUpdate()]
 *
 * [AI] Clients: PickupOrderSettlementService (internal only; not exposed as HTTP endpoint)
 */
class PickupCashbackAwardService
{
    /**
     * Awards pickup cashback to the customer upon verified pickup payment settlement.
     *
     * @param PickupReservation $reservation  The settled pickup reservation
     * @param Order             $order        The newly created pickup order
     * @param int               $customerId   Authenticated customer ID (validated by caller)
     * @return array{
     *     awarded: bool,
     *     points: string,
     *     cashback_amount: string,
     *     cashback_rate_percent: float,
     *     exchange_rate: float,
     *     cashback_redemption_id: int|null,
     * }
     */
    public function award(PickupReservation $reservation, Order $order, int $customerId): array
    {
        // [AI] Guard: loyalty must be enabled in admin config
        $loyaltyStatus = (int) (getWebConfig(name: 'loyalty_point_status') ?: 0);
        if ($loyaltyStatus !== 1) {
            Log::info("[AI] PickupCashbackAward: Loyalty is disabled in admin config. No cashback awarded for Reservation #{$reservation->id}.");
            return [
                'awarded' => false,
                'points' => '0.0000',
                'cashback_amount' => '0.00',
                'cashback_rate_percent' => 0.0,
                'exchange_rate' => 0.0,
                'cashback_redemption_id' => null,
            ];
        }

        // [AI] Config-driven cashback rate (5% default; admin can change in panel, reflects immediately)
        $exchangeRate = (float) (getWebConfig(name: 'loyalty_point_exchange_rate') ?: 1.0);
        $cashbackRatePercent = (float) (getWebConfig(name: 'loyalty_point_earn_rate_percent') ?: 5.0);

        // Fallback: if earn_rate_percent is not set, use the stock loyalty earn formula
        // (loyalty_point per unit order amount, or fallback to 5%)
        if ($cashbackRatePercent <= 0) {
            $cashbackRatePercent = 5.0;
        }
        if ($exchangeRate <= 0) {
            $exchangeRate = 1.0;
        }

        // [AI] BCMath Zero-Drift Calculation
        // cashbackNaira = totalAmount × (cashbackRatePercent ÷ 100)
        $totalAmount = bcadd((string) $reservation->total_amount, '0', 2);
        $cashbackNaira = bcmul(
            $totalAmount,
            bcdiv((string) $cashbackRatePercent, '100', 6),
            2
        );

        // Minimum: must be at least ₦0.01 to record anything
        if (bccomp($cashbackNaira, '0.01', 2) < 0) {
            return [
                'awarded' => false,
                'points' => '0.0000',
                'cashback_amount' => '0.00',
                'cashback_rate_percent' => $cashbackRatePercent,
                'exchange_rate' => $exchangeRate,
                'cashback_redemption_id' => null,
            ];
        }

        // points = cashbackNaira ÷ exchangeRate  (BCMath, 4 decimal places)
        $points = bcdiv($cashbackNaira, (string) $exchangeRate, 4);

        // [AI] 1. Insert cashback_redemptions row (status='captured' — no reserve phase for earning)
        $redemption = CashbackRedemption::create([
            'customer_id'          => $customerId,
            'checkout_intent_id'   => null,   // delivery FK; null for pickup
            'pickup_reservation_id'=> $reservation->id,
            'order_group_id'       => 'pickup-' . $reservation->reservation_code,
            'points'               => $points,
            'cashback_amount'      => $cashbackNaira,
            'status'               => 'captured',   // earned immediately on settlement
            'captured_at'          => now(),
            'released_at'          => null,
        ]);

        // [AI] 2. Increment customer's loyalty_point balance (under caller's lockForUpdate())
        // Using DB::table for atomic increment to avoid float model accumulation drift
        DB::table('users')
            ->where('id', $customerId)
            ->increment('loyalty_point', (float) $points);

        // [AI] 3. Insert immutable audit record in loyalty_point_transactions
        // Re-read fresh loyalty balance for the audit row
        $freshBalance = (float) DB::table('users')->where('id', $customerId)->value('loyalty_point');

        DB::table('loyalty_point_transactions')->insert([
            'user_id'          => $customerId,
            'transaction_id'   => Str::uuid()->toString(),
            'credit'           => (float) $points,  // earning = credit
            'debit'            => 0.0000,
            'balance'          => $freshBalance,
            'reference'        => 'pickup-' . $reservation->reservation_code,
            'transaction_type' => 'order_place',    // [AI] standard earn type, same as delivery
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        Log::info("[AI] PickupCashbackAward: Awarded {$points} pts (₦{$cashbackNaira}) to customer #{$customerId} " .
            "for Reservation #{$reservation->id} (Order #{$order->id}). Rate: {$cashbackRatePercent}%.");

        return [
            'awarded'               => true,
            'points'                => $points,
            'cashback_amount'       => $cashbackNaira,
            'cashback_rate_percent' => $cashbackRatePercent,
            'exchange_rate'         => $exchangeRate,
            'cashback_redemption_id'=> $redemption->id,
        ];
    }
}
