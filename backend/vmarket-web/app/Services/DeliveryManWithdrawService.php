<?php

namespace App\Services;

class DeliveryManWithdrawService
{
    /**
     * @param object $request
     * @return array
     */
    public function getDeliveryManWithdrawData(object $request) : array
    {
        // [AI] Vendor/Admin portals may approve or deny a pending payout only.
        if (!in_array((string)$request['approved'], ['1', '2'], true)) {
            throw new \InvalidArgumentException('Invalid withdrawal decision.');
        }
        return  [
            'approved' => $request['approved'],
            'transaction_note' => $request['note']
        ];
    }

    /**
     * @param object $request
     * @param object $wallet
     * @param object $withdraw
     * @return array[]
     */
    public function getUpdateData(object $request, ?object $wallet, object $withdraw): array
    {
        $this->getDeliveryManWithdrawData($request);
        if ((int)$withdraw['approved'] !== 0 || !$wallet) {
            throw new \RuntimeException('Withdrawal has already been processed or wallet is missing.');
        }
        $withdrawData = [
            'approved' => $request['approved'],
            'transaction_note' => $request['note'],
        ];
        $walletData = [];
        $amount = (string)$withdraw->getRawOriginal('amount');
        $totalWithdraw = (string)$wallet->getRawOriginal('total_withdraw');
        $pendingWithdraw = (string)$wallet->getRawOriginal('pending_withdraw');
        $currentBalance = (string)$wallet->getRawOriginal('current_balance');
        if (bccomp($amount, '0', 2) <= 0 || bccomp($pendingWithdraw, $amount, 2) < 0) {
            throw new \RuntimeException('Withdrawal reservation is insufficient.');
        }

        if ($request['approved'] == 1) {
            if (bccomp($currentBalance, $amount, 2) < 0) {
                throw new \RuntimeException('Withdrawal balance is insufficient.');
            }
            $walletData['total_withdraw'] = bcadd($totalWithdraw, $amount, 2);
            $walletData['pending_withdraw'] = bcsub($pendingWithdraw, $amount, 2);
            $walletData['current_balance'] = bcsub($currentBalance, $amount, 2);
        } else {
            $walletData['pending_withdraw'] = bcsub($pendingWithdraw, $amount, 2);
        }

        return [
            'wallet' => $walletData,
            'withdraw' => $withdrawData,
        ];
    }
}
