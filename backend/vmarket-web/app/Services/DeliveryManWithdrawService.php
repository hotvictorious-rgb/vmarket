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
            // [AI] Approval confirms one manual payout to the beneficiary frozen when funds were reserved.
            $beneficiary = $withdraw->withdrawal_method_fields;
            if (!is_array($beneficiary) || ($beneficiary['currency'] ?? null) !== 'NGN'
                || empty($beneficiary['bank_name']) || empty($beneficiary['account_no']) || empty($beneficiary['holder_name'])) {
                throw new \RuntimeException('Historical payout requires beneficiary reconciliation before approval.');
            }
            if (!$request->hasFile('proof_of_payment')) {
                throw new \InvalidArgumentException('Proof of manual payment is required.');
            }
            $proof = $request->file('proof_of_payment');
            if (!$proof->isValid() || !in_array($proof->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'], true)
                || $proof->getSize() > 5 * 1024 * 1024) {
                throw new \InvalidArgumentException('Proof must be a valid PNG, JPEG or PDF of at most 5 MB.');
            }
            if (bccomp($currentBalance, $amount, 2) < 0) {
                throw new \RuntimeException('Withdrawal balance is insufficient.');
            }
            $withdrawData['proof_of_payment'] = \App\Utils\ImageManager::file_upload('withdraw_requests/', $proof->guessExtension(), $proof);
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
