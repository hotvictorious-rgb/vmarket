<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Ensure unique constraint on order_id in customer_cashback_ledgers
     * Prevents race conditions and guarantees exactly-once cashback issuance at the database engine level.
     */
    public function up(): void
    {
        if (!Schema::hasTable('customer_cashback_ledgers')) {
            return;
        }

        // [AI] Step 1: Reconcile any existing duplicate order_id records before creating unique index
        // Identify any order_id that appears more than once with non-null values
        $duplicateOrderIds = \Illuminate\Support\Facades\DB::table('customer_cashback_ledgers')
            ->select('order_id')
            ->whereNotNull('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('order_id');

        foreach ($duplicateOrderIds as $orderId) {
            // Retain the earliest authoritative row (lowest id) as the valid reward lot
            $earliestId = \Illuminate\Support\Facades\DB::table('customer_cashback_ledgers')
                ->where('order_id', $orderId)
                ->orderBy('id', 'asc')
                ->value('id');

            // Reconcile secondary/duplicate rows without deleting financial history:
            // Disassociate order_id (set to null) and document reconciliation audit trail
            $duplicates = \Illuminate\Support\Facades\DB::table('customer_cashback_ledgers')
                ->where('order_id', $orderId)
                ->where('id', '!=', $earliestId)
                ->get();

            foreach ($duplicates as $duplicate) {
                \Illuminate\Support\Facades\DB::table('customer_cashback_ledgers')
                    ->where('id', $duplicate->id)
                    ->update([
                        'order_id' => null,
                        'redeemed_order_id' => $duplicate->redeemed_order_id ?? $orderId,
                        'description' => trim(($duplicate->description ?? '') . " [RECONCILED_DUPLICATE_ORDER_{$orderId}]"),
                        'updated_at' => now(),
                    ]);
            }
        }

        // [AI] Step 2: Check if unique index already exists on this connection
        $indexName = 'customer_cashback_ledgers_order_id_unique';
        $conn = Schema::getConnection();
        $driver = $conn->getDriverName();
        $hasUniqueIndex = false;

        if ($driver === 'sqlite') {
            $indexes = \Illuminate\Support\Facades\DB::select("PRAGMA index_list('customer_cashback_ledgers')");
            foreach ($indexes as $idx) {
                if ($idx->name === $indexName || ($idx->unique && str_contains($idx->name, 'order_id'))) {
                    $hasUniqueIndex = true;
                    break;
                }
            }
        } elseif ($driver === 'mysql') {
            $indexes = \Illuminate\Support\Facades\DB::select("SHOW INDEXES FROM `customer_cashback_ledgers` WHERE `Key_name` = ?", [$indexName]);
            if (!empty($indexes)) {
                $hasUniqueIndex = true;
            }
        }

        // [AI] Step 3: Create unique index FAIL-LOUD (no try-catch suppression)
        if (!$hasUniqueIndex) {
            Schema::table('customer_cashback_ledgers', function (Blueprint $table) use ($indexName) {
                $table->unique('order_id', $indexName);
            });
        }

        // [AI] Step 4: Strict post-migration schema verification
        $isVerified = false;
        if ($driver === 'sqlite') {
            $indexesAfter = \Illuminate\Support\Facades\DB::select("PRAGMA index_list('customer_cashback_ledgers')");
            foreach ($indexesAfter as $idx) {
                if ($idx->name === $indexName || ($idx->unique && str_contains($idx->name, 'order_id'))) {
                    $isVerified = true;
                    break;
                }
            }
        } elseif ($driver === 'mysql') {
            $indexesAfter = \Illuminate\Support\Facades\DB::select("SHOW INDEXES FROM `customer_cashback_ledgers` WHERE `Key_name` = ?", [$indexName]);
            $isVerified = !empty($indexesAfter);
        } else {
            $isVerified = true; // Fallback for other test/mock drivers
        }

        if (!$isVerified) {
            throw new \RuntimeException("Database migration failed: unique constraint '{$indexName}' could not be verified on 'customer_cashback_ledgers'.");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_cashback_ledgers')) {
            Schema::table('customer_cashback_ledgers', function (Blueprint $table) {
                $table->dropUnique('customer_cashback_ledgers_order_id_unique');
            });
        }
    }
};
