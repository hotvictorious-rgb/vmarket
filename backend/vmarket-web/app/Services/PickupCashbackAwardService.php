<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PickupReservation;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Service PickupCashbackAwardService
 *
 * [AI] Returns an indicative reward preview after pickup payment, with no ledger/balance mutation.
 * CustomerCashbackLedger alone issues rewards after physical receipt and owns the return-window maturity.
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
 * [AI] The result is an estimate; no balance is credited at payment.
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
        if ($loyaltyStatus !== 1 || (int)(getWebConfig(name: 'loyalty_point_for_each_order') ?? 1) !== 1) {
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
        $cashbackRatePercent = \App\Models\CustomerCashbackLedger::configuredEarnRate();

        if ($cashbackRatePercent <= 0) {
            $cashbackRatePercent = 0.0;
        }
        if ($exchangeRate <= 0) {
            $exchangeRate = 1.0;
        }

        // [AI] BCMath Zero-Drift Calculation: Rewards apply ONLY to NEW MONEY paid!
        // Subtract any redeemed cashback applied to this pickup order
        // [AI] Estimate only eligible merchandise, excluding tax; receipt remains authoritative.
        $totalAmount = '0.00';
        foreach ($order->details as $detail) {
            $line = bcsub(bcmul((string)$detail->getRawOriginal('price'), (string)$detail->qty, 2), (string)$detail->getRawOriginal('discount'), 2);
            $totalAmount = bcadd($totalAmount, $line, 2);
        }
        if (bccomp($totalAmount, '0', 2) <= 0) {
            $totalAmount = bcsub((string)$reservation->total_amount, (string)($order->getRawOriginal('total_tax_amount') ?? '0'), 2);
        }
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

        // [AI] Preview only: cashback_redemptions records spending, never reward issuance.
        // CustomerCashbackLedger creates the sole earning lot after physical receipt.

        return [
            'awarded'               => false,
            'status'                => 'pending_handover',
            'is_estimate'           => true,
            'points'                => $points,
            'cashback_amount'       => $cashbackNaira,
            'cashback_rate_percent' => (string) $cashbackRatePercent,
            'exchange_rate'         => (string) $exchangeRate,
            'cashback_redemption_id'=> null,
        ];
    }
}
