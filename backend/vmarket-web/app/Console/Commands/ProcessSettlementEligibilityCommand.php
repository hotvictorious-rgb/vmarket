<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\VendorSettlementService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Process Vendor Order Settlement Eligibility Command.
 *
 * Evaluates delivered third-party vendor orders where the 24-hour customer return
 * window has elapsed without open disputes, transitioning them from 'held' or 'disputed' to 'eligible'.
 *
 * Schedule: Hourly in app/Console/Kernel.php.
 */
class ProcessSettlementEligibilityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process-settlement-eligibility';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[AI] Automatically transition eligible third-party vendor orders from held/disputed to eligible after the 24-hour return inspection window';

    /**
     * Execute the console command.
     */
    public function handle(VendorSettlementService $settlementService): int
    {
        $this->info('Starting vendor order settlement eligibility evaluation...');

        $now = Carbon::now();

        // 1. Fetch delivered third-party orders currently held or disputed whose window has expired
        $candidateOrders = Order::where('seller_is', 'seller')
            ->where('order_status', 'delivered')
            ->whereIn('vendor_settlement_status', ['held', 'disputed'])
            ->whereNotNull('received_at')
            ->whereNotNull('refund_window_expires_at')
            ->where('refund_window_expires_at', '<=', $now)
            ->get();

        $count = $candidateOrders->count();

        if ($count === 0) {
            $this->info('No third-party vendor orders are currently due for settlement eligibility.');
            return Command::SUCCESS;
        }

        $promotedCount = 0;

        foreach ($candidateOrders as $order) {
            try {
                $isEligible = $settlementService->evaluateOrderSettlementEligibility($order);
                if ($isEligible && $order->vendor_settlement_status === 'eligible') {
                    $promotedCount++;
                    Log::info("[AUDIT] Order #{$order->id} promoted to 'eligible' for manual vendor settlement.", [
                        'order_id' => $order->id,
                        'seller_id' => $order->seller_id,
                        'received_at' => $order->received_at,
                        'window_expires_at' => $order->refund_window_expires_at,
                        'timestamp' => $now->toIso8601String(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("[ERROR] Failed to evaluate settlement eligibility for order #{$order->id}: " . $e->getMessage(), [
                    'order_id' => $order->id,
                    'exception' => $e,
                ]);
            }
        }

        $message = "[AI] Vendor Settlement Eligibility: Processed {$count} orders; promoted {$promotedCount} to 'eligible'.";
        $this->info($message);
        Log::info($message);

        return Command::SUCCESS;
    }
}
