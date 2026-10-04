<?php

namespace App\Services;

class DeliveryManWalletService
{
    public function getDeliveryManData(string|int $id, float $deliverymanCharge, float $cashInHand): array
    {
        return [
            'delivery_man_id' => $id,
            'current_balance' => $deliverymanCharge,
            'cash_in_hand' => $cashInHand,
            'pending_withdraw' => 0,
            'total_withdraw' => 0,
        ];
    }
    public function getDeliveryManWalletData(object $request, object $wallet, object $withdraw):array
    {
       // [AI] Vendor rider payouts share the same terminal decision and exact-money validation as Admin.
       return (new DeliveryManWithdrawService())->getUpdateData($request, $wallet, $withdraw)['wallet'];
    }
}
