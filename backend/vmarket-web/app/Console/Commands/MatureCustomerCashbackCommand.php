<?php

namespace App\Console\Commands;

use App\Models\CustomerCashbackLedger;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * [AI] Mature Customer Cashback Reward Ledgers Command.
 *
 * Automatically matures 5% customer cashback reward records from 'pending' to 'available'
 * once the 24-hour return inspection period has elapsed (available_at <= now()).
 * Also expires unredeemed available rewards once the 6-month (180 days) validity window has elapsed.
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
    protected $description = '[AI] Automatically transition eligible customer cashback rewards from pending to available after 24hr window, and expire rewards older than 6 months';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting customer cashback reward ledger maturation and expiration process...');

        $now = Carbon::now();
        $exchangeRate = (float) (getWebConfig(name: 'loyalty_point_exchange_rate') ?: 1.0);

        // =========================================================================
        // PART 1: MATURATION (24-hour Return Window Elasped: pending -> available)
        // =========================================================================
        $unresolvedOrderIds = \App\Models\RefundRequest::whereNotIn('status', ['rejected', 'refunded'])
            ->pluck('order_id')
            ->unique()
            ->toArray();

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

        $maturedCount = 0;
        if (!empty($eligibleLedgerIds)) {
            $eligibleLedgers = CustomerCashbackLedger::whereIn('id', $eligibleLedgerIds)
                ->where('status', 'pending')
                ->get();

            foreach ($eligibleLedgers as $ledger) {
                DB::transaction(function () use ($ledger, $exchangeRate, $now, &$maturedCount) {
                    $expiresAt = $now->copy()->addMonths(6);
                    $updateData = [
                        'status' => 'available',
                        'updated_at' => $now,
                    ];
                    if (\Illuminate\Support\Facades\Schema::hasColumn('customer_cashback_ledgers', 'expires_at')) {
                        $updateData['expires_at'] = $expiresAt;
                    }

                    $updated = CustomerCashbackLedger::where('id', $ledger->id)
                        ->where('status', 'pending')
                        ->update($updateData);

                    if ($updated) {
                        $points = (float) bcdiv((string) $ledger->cashback_amount, (string) $exchangeRate, 4);
                        if ($points > 0) {
                            DB::table('users')->where('id', $ledger->customer_id)->increment('loyalty_point', $points);
                            $freshBalance = (float) DB::table('users')->where('id', $ledger->customer_id)->value('loyalty_point');

                            DB::table('loyalty_point_transactions')->insert([
                                'user_id' => $ledger->customer_id,
                                'transaction_id' => \Illuminate\Support\Str::uuid()->toString(),
                                'credit' => $points,
                                'debit' => 0.0000,
                                'balance' => $freshBalance,
                                'reference' => 'cashback-reward-' . $ledger->order_id,
                                'transaction_type' => 'point_transfer',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                        $maturedCount++;
                    }
                });
            }
        }

        // =========================================================================
        // PART 2: 6-MONTH EXPIRATION (Unredeemed Available Rewards > 6 Months)
        // =========================================================================
        $sixMonthsAgo = $now->copy()->subMonths(6);
        $expiredLedgers = CustomerCashbackLedger::where('status', 'available')
            ->where(function ($q) use ($now, $sixMonthsAgo) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('customer_cashback_ledgers', 'expires_at')) {
                    $q->where('expires_at', '<=', $now)
                      ->orWhere('available_at', '<=', $sixMonthsAgo);
                } else {
                    $q->where('available_at', '<=', $sixMonthsAgo);
                }
            })
            ->get();

        $expiredCount = 0;
        foreach ($expiredLedgers as $expLedger) {
            DB::transaction(function () use ($expLedger, $exchangeRate, $now, &$expiredCount) {
                $updated = CustomerCashbackLedger::where('id', $expLedger->id)
                    ->where('status', 'available')
                    ->update([
                        'status' => 'expired',
                        'updated_at' => $now,
                        'description' => $expLedger->description . ' (Expired after 6 months validity)',
                    ]);

                if ($updated) {
                    $points = (float) bcdiv((string) $expLedger->cashback_amount, (string) $exchangeRate, 4);
                    if ($points > 0) {
                        $currentPoints = (float) DB::table('users')->where('id', $expLedger->customer_id)->value('loyalty_point');
                        $deductPoints = min($points, $currentPoints);
                        if ($deductPoints > 0) {
                            DB::table('users')->where('id', $expLedger->customer_id)->decrement('loyalty_point', $deductPoints);
                            $freshBalance = (float) DB::table('users')->where('id', $expLedger->customer_id)->value('loyalty_point');

                            DB::table('loyalty_point_transactions')->insert([
                                'user_id' => $expLedger->customer_id,
                                'transaction_id' => \Illuminate\Support\Str::uuid()->toString(),
                                'credit' => 0.0000,
                                'debit' => $deductPoints,
                                'balance' => $freshBalance,
                                'reference' => 'cashback-expired-' . $expLedger->order_id,
                                'transaction_type' => 'point_expired',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                    $expiredCount++;
                }
            });
        }

        $message = "[AI] Customer Cashback Process: Matured {$maturedCount} rewards to 'available' and expired {$expiredCount} rewards older than 6 months.";
        $this->info($message);
        Log::info($message, [
            'matured_records' => $maturedCount,
            'expired_records' => $expiredCount,
            'timestamp' => $now->toIso8601String(),
        ]);

        return Command::SUCCESS;
    }
}
