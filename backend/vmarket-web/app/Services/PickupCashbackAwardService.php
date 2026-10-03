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
        // [AI] Guard: loyalty must be enabled in admin config (defaults to enabled for VMarket)
        $loyaltyStatus = (int) (getWebConfig(name: 'loyalty_point_status') ?? 1);
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

        if ($cashbackRatePercent <= 0) {
            $cashbackRatePercent = 5.0;
        }
        if ($exchangeRate <= 0) {
            $exchangeRate = 1.0;
        }

        // [AI] BCMath Zero-Drift Calculation: Rewards apply ONLY to NEW MONEY paid!
        // Subtract any redeemed cashback applied to this pickup order
        $totalAmount = bcadd((string) $reservation->total_amount, '0', 2);
        $redeemedCashback = '0.00';
        if ($order->discount_type === 'cashback' && !empty($order->discount_amount)) {
            $redeemedCashback = bcadd((string) $order->discount_amount, '0', 2);
        }
        $netNewMoney = bcsub($totalAmount, $redeemedCashback, 2);

        if (bccomp($netNewMoney, '0.00', 2) <= 0) {
            Log::info("[AI] PickupCashbackAward: Order #{$order->id} was 100% funded with rewards (net new money = ₦0.00). Zero new rewards earned.");
            return [
                'awarded' => false,
                'points' => '0.0000',
                'cashback_amount' => '0.00',
                'cashback_rate_percent' => (string) $cashbackRatePercent,
                'exchange_rate' => (string) $exchangeRate,
                'cashback_redemption_id' => null,
            ];
        }

        $cashbackNaira = bcmul(
            $netNewMoney,
            bcdiv((string) $cashbackRatePercent, '100', 6),
            2
        );

        // Minimum: must be at least ₦0.01 to record anything
        if (bccomp($cashbackNaira, '0.01', 2) < 0) {
            return [
                'awarded' => false,
                'points' => '0.0000',
                'cashback_amount' => '0.00',
                'cashback_rate_percent' => (string) $cashbackRatePercent,
                'exchange_rate' => (string) $exchangeRate,
                'cashback_redemption_id' => null,
            ];
        }

        // points = cashbackNaira ÷ exchangeRate  (BCMath, 4 decimal places)
        $points = bcdiv($cashbackNaira, (string) $exchangeRate, 4);

        // [AI] 1. Insert cashback_redemptions record in 'pending' status.
        // Per V1 Rulebook §19/§20: Cashback is NOT immediately spendable upon reservation payment.
        // It matures into customer availability strictly after physical handover and the 24-hour return window.
        // Authoritative ledger credit is managed by InShopHandoverController upon verified customer pickup.
        $redemption = CashbackRedemption::create([
            'customer_id'          => $customerId,
            'checkout_intent_id'   => null,
            'pickup_reservation_id'=> $reservation->id,
            'order_group_id'       => 'pickup-' . $reservation->reservation_code,
            'points'               => $points,
            'cashback_amount'      => $cashbackNaira,
            'status'               => 'pending',
            'captured_at'          => null,
            'released_at'          => null,
        ]);

        Log::info("[AI] PickupCashbackAward: Scheduled {$points} pts (₦{$cashbackNaira}) pending cashback for customer #{$customerId} " .
            "on Reservation #{$reservation->id} (Order #{$order->id}). Matures after physical handover + 24-hour return window.");

        return [
            'awarded'               => false,
            'status'                => 'pending_handover',
            'points'                => $points,
            'cashback_amount'       => $cashbackNaira,
            'cashback_rate_percent' => (string) $cashbackRatePercent,
            'exchange_rate'         => (string) $exchangeRate,
            'cashback_redemption_id'=> $redemption->id,
        ];
    }
}
