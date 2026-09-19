<?php

namespace App\Console\Commands;

use App\Models\CustomerCashbackLedger;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Mature Customer Cashback Reward Ledgers Command.
 *
 * Automatically matures 5% customer cashback reward records from 'pending' to 'available'
 * once the 7-day return inspection period has elapsed (available_at <= now()).
 *
 * Schedule: Daily in bootstrap/app.php or Kernel.php.
 */
class MatureCustomerCashbackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashback:mature';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[AI] Automatically transition eligible customer cashback rewards from pending to available after the 7-day inspection window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting customer cashback reward ledger maturation process...');

        $now = Carbon::now();

        // 1. Fetch unresolved refund order IDs
        $unresolvedOrderIds = \App\Models\RefundRequest::whereNotIn('status', ['rejected', 'refunded'])
            ->pluck('order_id')
            ->unique()
            ->toArray();

        // 2. Fetch pending rewards ready for maturity (verified customer receipt + no open disputes)
        $eligibleLedgerIds = CustomerCashbackLedger::where('status', 'pending')
            ->whereNotNull('available_at')
            ->where('available_at', '<=', $now)
            ->whereNotIn('order_id', $unresolvedOrderIds)
            ->whereHas('order', function ($q) {
                $q->whereNotNull('received_at')
                  ->whereNotNull('refund_window_expires_at');
            })
            ->pluck('id')
            ->toArray();

        $count = count($eligibleLedgerIds);

        if ($count === 0) {
            $this->info('No pending cashback rewards are currently due for maturity.');
            return Command::SUCCESS;
        }

        // 3. Perform atomic batch update
        $affected = CustomerCashbackLedger::whereIn('id', $eligibleLedgerIds)
            ->where('status', 'pending')
            ->update([
                'status' => 'available',
                'updated_at' => $now,
            ]);

        $message = "[AI] Customer Cashback Maturation: Successfully transitioned {$affected} reward ledger records to 'available'.";
        $this->info($message);
        Log::info($message, [
            'affected_records' => $affected,
            'timestamp' => $now->toIso8601String(),
        ]);

        return Command::SUCCESS;
    }
}
