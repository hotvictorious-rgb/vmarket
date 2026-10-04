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
        $exchangeRate = (string) (getWebConfig(name: 'loyalty_point_exchange_rate') ?: '1');

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
                    // [AI] Serialize receipt/refund accounting before user and reward lot locks.
                    $order = \App\Models\Order::where('id', $ledger->order_id)->lockForUpdate()->first();
                    if (!$order || !$order->received_at || !$order->refund_window_expires_at
                        || Carbon::parse($order->refund_window_expires_at)->isFuture()
                        || $order->hasUnresolvedRefund()) {
                        return;
                    }
                    $customer = DB::table('users')->where('id', $ledger->customer_id)->lockForUpdate()->first();
                    if (!$customer) {
                        return;
                    }
                    // [AI] Re-read the amount and eligibility under lock, never credit the earlier query's snapshot.
                    $ledger = CustomerCashbackLedger::where('id', $ledger->id)->where('status', 'pending')
                        ->whereNotNull('available_at')->where('available_at', '<=', $now)->lockForUpdate()->first();
                    if (!$ledger) {
                        return;
                    }

                    // Preserve existing snapshot expires_at from issuance; fallback to configured validity months if null
                    $rawValidityMonths = getWebConfig(name: 'loyalty_point_validity_months');
                    $validityMonths = (!is_null($rawValidityMonths) && $rawValidityMonths !== '') ? (int)$rawValidityMonths : 6;
                    if ($validityMonths <= 0) {
                        $validityMonths = 6;
                    }
                    $expiresAt = $ledger->expires_at ?? $now->copy()->addMonths($validityMonths);

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
                        $points = bcdiv((string) $ledger->getRawOriginal('cashback_amount'), $exchangeRate, 4);
                        if (bccomp($points, '0', 4) > 0) {
                            DB::table('users')->where('id', $ledger->customer_id)->increment('loyalty_point', $points);
                            $freshBalance = DB::table('users')->where('id', $ledger->customer_id)->value('loyalty_point');

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
        // PART 2: EXPIRATION (Unredeemed Available Rewards > Validity Period)
        // =========================================================================
        $sixMonthsAgo = $now->copy()->subMonths(6);
        $expiredLedgers = CustomerCashbackLedger::where('status', 'available')
            ->where(function ($q) use ($now, $sixMonthsAgo) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('customer_cashback_ledgers', 'expires_at')) {
                    $q->where(function ($sub) use ($now) {
                        $sub->whereNotNull('expires_at')
                            ->where('expires_at', '<=', $now);
                    })->orWhere(function ($sub) use ($sixMonthsAgo) {
                        $sub->whereNull('expires_at')
                            ->where('available_at', '<=', $sixMonthsAgo);
                    });
                } else {
                    $q->where('available_at', '<=', $sixMonthsAgo);
                }
            })
            ->get();

        $expiredCount = 0;
        foreach ($expiredLedgers as $expLedger) {
            DB::transaction(function () use ($expLedger, $exchangeRate, $now, &$expiredCount) {
                // Rule 1: Always lock User FIRST (prevents deadlock with checkout/settlement)
                $customer = DB::table('users')->where('id', $expLedger->customer_id)->lockForUpdate()->first();
                if (!$customer) {
                    return;
                }

                // Rule 2: Lock CustomerCashbackLedger SECOND
                $lockedLedger = CustomerCashbackLedger::where('id', $expLedger->id)
                    ->where('status', 'available')
                    ->lockForUpdate()
                    ->first();
                if (!$lockedLedger) {
                    return;
                }

                $points = bcdiv((string) $lockedLedger->getRawOriginal('cashback_amount'), $exchangeRate, 4);
                $currentPoints = (string) ($customer->loyalty_point ?? '0');

                // Active in-flight checkouts holding reserved points
                $activeReservedPoints = (string) (DB::table('cashback_redemptions')
                    ->where('customer_id', $lockedLedger->customer_id)
                    ->where('status', 'reserved')
                    ->sum('points') ?: '0');
                if (bccomp($activeReservedPoints, '0', 4) <= 0) {
                    $activeReservedAmount = (string) (DB::table('cashback_redemptions')
                        ->where('customer_id', $lockedLedger->customer_id)
                        ->where('status', 'reserved')
                        ->sum('cashback_amount') ?: '0');
                    $activeReservedPoints = bcdiv($activeReservedAmount, $exchangeRate, 4);
                }

                $unreservedPoints = bcsub($currentPoints, $activeReservedPoints, 4);
                if (bccomp($unreservedPoints, '0', 4) < 0) {
                    $unreservedPoints = '0.0000';
                }

                // If this lot is needed to back active in-flight checkouts, DO NOT expire it yet!
                // It remains available to back the in-flight checkout. If the checkout fails/cancels,
                // the reservation is released and the lot will be expired in the next cycle.
                // If the checkout succeeds, the lot is consumed via markRedeemed().
                if (bccomp($points, $unreservedPoints, 4) > 0) {
                    return;
                }

                $updated = $lockedLedger->update([
                    'status' => 'expired',
                    'updated_at' => $now,
                    'description' => $lockedLedger->description . ' (Expired after validity period)',
                ]);

                if ($updated && bccomp($points, '0', 4) > 0) {
                    DB::table('users')->where('id', $lockedLedger->customer_id)->decrement('loyalty_point', $points);
                    $freshBalance = DB::table('users')->where('id', $lockedLedger->customer_id)->value('loyalty_point');

                    DB::table('loyalty_point_transactions')->insert([
                        'user_id' => $lockedLedger->customer_id,
                        'transaction_id' => \Illuminate\Support\Str::uuid()->toString(),
                        'credit' => 0.0000,
                        'debit' => $points,
                        'balance' => $freshBalance,
                        'reference' => 'cashback-expired-' . $lockedLedger->order_id,
                        'transaction_type' => 'point_expired',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
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
