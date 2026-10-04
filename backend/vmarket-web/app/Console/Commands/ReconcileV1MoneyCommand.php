<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconcileV1MoneyCommand extends Command
{
    protected $signature = 'finance:reconcile-v1 {--json : Emit a machine-readable report}';
    protected $description = 'Read-only V1 order escrow, refund and payout accounting diagnostics; never adjusts balances';

    public function handle(): int
    {
        // [AI] Operators run this after migration, then verify discrepancies against provider/bank evidence.
        if (!Schema::hasColumn('order_transactions', 'escrow_remaining')) {
            $this->error('Order escrow migration is missing. No balances were changed.');
            return self::FAILURE;
        }
        $issues = [];
        $knownHoldTotal = '0.00';
        foreach (DB::table('order_transactions')->where('status', 'hold')->orderBy('id')->cursor() as $hold) {
            if ($hold->escrow_remaining === null) {
                $issues[] = ['type' => 'unknown_historical_hold', 'order_id' => $hold->order_id];
            } else {
                $knownHoldTotal = bcadd($knownHoldTotal, (string)$hold->escrow_remaining, 2);
                if (bccomp((string)$hold->escrow_remaining, '0', 2) < 0) $issues[] = ['type' => 'negative_hold', 'order_id' => $hold->order_id];
            }
        }
        $platformPending = (string)(DB::table('admin_wallets')->where('admin_id', 1)->value('pending_amount') ?? '0');
        if (bccomp($knownHoldTotal, $platformPending, 2) !== 0) {
            $issues[] = ['type' => 'platform_pending_vs_known_holds', 'pending' => bcadd($platformPending, '0', 2), 'known_holds' => $knownHoldTotal];
        }
        foreach (DB::table('seller_wallets')->orderBy('id')->cursor() as $wallet) {
            $pending = $this->pendingWithdrawals('seller_id', $wallet->seller_id, true);
            if (bccomp((string)$wallet->pending_withdraw, $pending, 2) !== 0) {
                $issues[] = ['type' => 'vendor_withdrawal_reservation', 'seller_id' => $wallet->seller_id,
                    'wallet_reserved' => bcadd((string)$wallet->pending_withdraw, '0', 2), 'requests' => $pending];
            }
            if (bccomp((string)$wallet->collected_cash, '0', 2) > 0) {
                $issues[] = ['type' => 'vendor_debt_or_historical_cash', 'seller_id' => $wallet->seller_id, 'amount' => bcadd((string)$wallet->collected_cash, '0', 2)];
            }
        }
        foreach (DB::table((new \App\Models\DeliveryManWallet())->getTable())->orderBy('id')->cursor() as $wallet) {
            $pending = $this->pendingWithdrawals('delivery_man_id', $wallet->delivery_man_id, false);
            if (bccomp((string)$wallet->pending_withdraw, $pending, 2) !== 0) {
                $issues[] = ['type' => 'rider_withdrawal_reservation', 'delivery_man_id' => $wallet->delivery_man_id,
                    'wallet_reserved' => bcadd((string)$wallet->pending_withdraw, '0', 2), 'requests' => $pending];
            }
        }
        $missingRefundProof = DB::table('refund_requests as r')->where('r.status', 'refunded')
            ->whereNotExists(function ($q) { $q->selectRaw('1')->from('refund_transactions as t')->whereColumn('t.refund_id', 'r.id')->where('t.payment_status', 'paid'); })
            ->orderBy('r.id')->pluck('r.id');
        foreach ($missingRefundProof as $id) $issues[] = ['type' => 'terminal_refund_without_accounting_proof', 'refund_id' => $id];
        foreach (DB::table('transactions')->where('payment_for', 'vendor_settlement')->select('order_id')
            ->selectRaw('COUNT(*) as event_count')->groupBy('order_id')->havingRaw('COUNT(*) > 1')->get() as $duplicate) {
            $issues[] = ['type' => 'duplicate_vendor_release', 'order_id' => $duplicate->order_id, 'count' => $duplicate->event_count];
        }
        $report = ['read_only' => true, 'currency' => 'NGN', 'known_hold_total' => $knownHoldTotal,
            'platform_pending' => bcadd($platformPending, '0', 2), 'issue_count' => count($issues), 'issues' => $issues,
            'external_evidence_required' => 'Verify historical rows and discrepancies against gateway/refund/bank statements; no adjustment is automatic.'];
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $issues ? self::FAILURE : self::SUCCESS;
    }

    private function pendingWithdrawals(string $column, int $id, bool $vendor): string
    {
        $total = '0.00';
        $query = DB::table('withdraw_requests')->where($column, $id)->where('approved', 0);
        if ($vendor) $query->whereNull('delivery_man_id');
        foreach ($query->orderBy('id')->cursor() as $request) $total = bcadd($total, (string)$request->amount, 2);
        return $total;
    }
}
