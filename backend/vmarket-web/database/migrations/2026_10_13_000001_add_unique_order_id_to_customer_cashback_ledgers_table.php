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

        // [AI] Step 1: Strict Financial Integrity Check for Existing Duplicates.
        // In strict accordance with financial audit standards, this migration NEVER silently
        // mutates or reclassifies historical ledger rows. If unresolved duplicate order_ids exist,
        // the migration MUST fail loudly and block deployment until an operator audits the entries.
        $duplicates = \Illuminate\Support\Facades\DB::table('customer_cashback_ledgers')
            ->select('order_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as duplicate_count'))
            ->whereNotNull('order_id')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $details = $duplicates->map(fn($d) => "Order #{$d->order_id} ({$d->duplicate_count} records)")->implode(', ');
            throw new \RuntimeException(
                "CRITICAL FINANCIAL INTEGRITY GATE: Migration aborted. The 'customer_cashback_ledgers' table contains " .
                "unreconciled duplicate reward rows for orders: [{$details}]. " .
                "Automatic rewriting of financial history is strictly prohibited. " .
                "Operators must run 'php artisan cashback:audit-duplicates' to inspect, verify, and resolve duplicates " .
                "before the database UNIQUE constraint can be applied."
            );
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
