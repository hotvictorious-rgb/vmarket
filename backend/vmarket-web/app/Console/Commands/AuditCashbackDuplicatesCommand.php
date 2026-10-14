<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Audit Cashback Duplicates Command
 * Inspects customer_cashback_ledgers for duplicate order_ids and outputs
 * an operator audit report. Supports dry-run analysis and operator-confirmed reconciliation.
 */
class AuditCashbackDuplicatesCommand extends Command
{
    protected $signature = 'cashback:audit-duplicates {--reconcile : Interactively reconcile verified duplicate rows} {--dry-run : Only display duplicate records without mutations}';
    protected $description = '[AI] Audit and reconcile duplicate order cashback rewards in customer_cashback_ledgers';

    public function handle(): int
    {
        $this->info("Scanning customer_cashback_ledgers for duplicate order_id records...");

        if (!Schema::hasTable('customer_cashback_ledgers')) {
            $this->error("Table customer_cashback_ledgers does not exist.");
            return self::FAILURE;
        }

        $duplicates = DB::table('customer_cashback_ledgers')
            ->select('order_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info("✅ No duplicate order_id records found. The table conforms to 1:1 financial uniqueness.");
            return self::SUCCESS;
        }

        $this->warn("⚠️ Found " . $duplicates->count() . " orders with multiple cashback records:");

        $tableRows = [];
        foreach ($duplicates as $duplicate) {
            $records = DB::table('customer_cashback_ledgers')
                ->where('order_id', $duplicate->order_id)
                ->orderBy('id', 'asc')
                ->get();

            foreach ($records as $index => $rec) {
                $tableRows[] = [
                    'Order ID' => $rec->order_id,
                    'Ledger ID' => $rec->id,
                    'Customer ID' => $rec->customer_id,
                    'Merchandise (₦)' => number_format((float)$rec->merchandise_amount, 2),
                    'Cashback (₦)' => number_format((float)$rec->cashback_amount, 2),
                    'Status' => $rec->status,
                    'Created At' => $rec->created_at,
                    'Role' => ($index === 0 ? 'Authoritative (Lowest ID)' : 'Duplicate Candidate'),
                ];
            }
        }

        $this->table(['Order ID', 'Ledger ID', 'Customer ID', 'Merchandise (₦)', 'Cashback (₦)', 'Status', 'Created At', 'Role'], $tableRows);

        if ($this->option('dry-run') || !$this->option('reconcile')) {
            $this->comment("Run with --reconcile to execute safe operator-guided reconciliation with full audit trail.");
            return self::SUCCESS;
        }

        if (!$this->confirm("Are you sure you want to reconcile duplicate rows? Secondary rows will have order_id cleared and audit notes appended.")) {
            $this->info("Reconciliation aborted by operator.");
            return self::SUCCESS;
        }

        DB::beginTransaction();
        try {
            $reconciledCount = 0;
            foreach ($duplicates as $duplicate) {
                $earliestId = DB::table('customer_cashback_ledgers')
                    ->where('order_id', $duplicate->order_id)
                    ->orderBy('id', 'asc')
                    ->value('id');

                $excessRows = DB::table('customer_cashback_ledgers')
                    ->where('order_id', $duplicate->order_id)
                    ->where('id', '!=', $earliestId)
                    ->get();

                foreach ($excessRows as $row) {
                    DB::table('customer_cashback_ledgers')
                        ->where('id', $row->id)
                        ->update([
                            'order_id' => null,
                            'redeemed_order_id' => $row->redeemed_order_id ?? $duplicate->order_id,
                            'description' => trim(($row->description ?? '') . " [OPERATOR_RECONCILED_DUPLICATE_ORDER_{$duplicate->order_id}]"),
                            'updated_at' => now(),
                        ]);
                    $reconciledCount++;
                }
            }
            DB::commit();
            $this->info("✅ Successfully reconciled {$reconciledCount} duplicate records across {$duplicates->count()} orders.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Reconciliation failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
