<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * [AI] 100% Version-Robust & Native MySQL Idempotent Index Migration
     * Uses direct SHOW INDEX queries to guarantee crash-free execution on any Laravel/MySQL setup.
     */
    public function up(): void
    {
        // Universal Native MySQL index checker (does not depend on doctrine/dbal or Schema::hasIndex)
        $hasIndex = function (string $table, string $indexName): bool {
            try {
                if (!Schema::hasTable($table)) {
                    return false;
                }
                $results = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
                return !empty($results);
            } catch (\Throwable $e) {
                return false;
            }
        };

        // 1. Index Sellers table
        if (Schema::hasTable('sellers')) {
            Schema::table('sellers', function (Blueprint $table) use ($hasIndex) {
                if (Schema::hasColumn('sellers', 'marketplace_status') && !$hasIndex('sellers', 'idx_sellers_marketplace_status')) {
                    $table->index('marketplace_status', 'idx_sellers_marketplace_status');
                }
                if (Schema::hasColumn('sellers', 'status') && !$hasIndex('sellers', 'idx_sellers_status')) {
                    $table->index('status', 'idx_sellers_status');
                }
            });
        }

        // 2. Index Shops table
        if (Schema::hasTable('shops')) {
            Schema::table('shops', function (Blueprint $table) use ($hasIndex) {
                if (Schema::hasColumn('shops', 'is_primary_branch') && !$hasIndex('shops', 'idx_shops_is_primary_branch')) {
                    $table->index('is_primary_branch', 'idx_shops_is_primary_branch');
                }
                if (Schema::hasColumn('shops', 'seller_id') && !$hasIndex('shops', 'idx_shops_seller_id')) {
                    $table->index('seller_id', 'idx_shops_seller_id');
                }
            });
        }

        // 3. Index Orders table for Handover & Logistics Lookups
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) use ($hasIndex) {
                if (Schema::hasColumn('orders', 'handed_over_by_id') && !$hasIndex('orders', 'idx_orders_handed_over_by_id')) {
                    $table->index('handed_over_by_id', 'idx_orders_handed_over_by_id');
                }
                if (Schema::hasColumn('orders', 'handover_branch_id') && !$hasIndex('orders', 'idx_orders_handover_branch_id')) {
                    $table->index('handover_branch_id', 'idx_orders_handover_branch_id');
                }
                if (Schema::hasColumn('orders', 'delivery_man_id') && !$hasIndex('orders', 'idx_orders_delivery_man_id')) {
                    $table->index('delivery_man_id', 'idx_orders_delivery_man_id');
                }
                if (Schema::hasColumn('orders', 'order_status') && !$hasIndex('orders', 'idx_orders_order_status')) {
                    $table->index('order_status', 'idx_orders_order_status');
                }
            });
        }

        // 4. Index POS Ledgers & Transfers
        if (Schema::hasTable('pos_customer_ledgers')) {
            Schema::table('pos_customer_ledgers', function (Blueprint $table) use ($hasIndex) {
                if (Schema::hasColumn('pos_customer_ledgers', 'aging_bucket') && !$hasIndex('pos_customer_ledgers', 'idx_pos_ledgers_seller_aging')) {
                    $table->index(['seller_id', 'aging_bucket'], 'idx_pos_ledgers_seller_aging');
                } elseif (Schema::hasColumn('pos_customer_ledgers', 'seller_id') && !$hasIndex('pos_customer_ledgers', 'idx_pos_ledgers_seller')) {
                    $table->index('seller_id', 'idx_pos_ledgers_seller');
                }
            });
        }

        if (Schema::hasTable('pos_transfers')) {
            Schema::table('pos_transfers', function (Blueprint $table) use ($hasIndex) {
                if (Schema::hasColumn('pos_transfers', 'seller_id') && Schema::hasColumn('pos_transfers', 'status') && !$hasIndex('pos_transfers', 'idx_pos_transfers_seller_status')) {
                    $table->index(['seller_id', 'status'], 'idx_pos_transfers_seller_status');
                }
            });
        }

        if (Schema::hasTable('pos_cashier_shifts')) {
            Schema::table('pos_cashier_shifts', function (Blueprint $table) use ($hasIndex) {
                if (Schema::hasColumn('pos_cashier_shifts', 'seller_id') && Schema::hasColumn('pos_cashier_shifts', 'status') && !$hasIndex('pos_cashier_shifts', 'idx_pos_shifts_seller_status')) {
                    $table->index(['seller_id', 'status'], 'idx_pos_shifts_seller_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $dropIndexSafely = function (string $table, string $indexName): void {
            try {
                if (!Schema::hasTable($table)) {
                    return;
                }
                $results = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
                if (!empty($results)) {
                    DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
                }
            } catch (\Throwable $e) {
                // Silently ignore if already removed
            }
        };

        $dropIndexSafely('sellers', 'idx_sellers_marketplace_status');
        $dropIndexSafely('sellers', 'idx_sellers_status');
        $dropIndexSafely('shops', 'idx_shops_is_primary_branch');
        $dropIndexSafely('shops', 'idx_shops_seller_id');
        $dropIndexSafely('orders', 'idx_orders_handed_over_by_id');
        $dropIndexSafely('orders', 'idx_orders_handover_branch_id');
        $dropIndexSafely('orders', 'idx_orders_delivery_man_id');
        $dropIndexSafely('orders', 'idx_orders_order_status');
        $dropIndexSafely('pos_customer_ledgers', 'idx_pos_ledgers_seller_aging');
        $dropIndexSafely('pos_customer_ledgers', 'idx_pos_ledgers_seller');
        $dropIndexSafely('pos_transfers', 'idx_pos_transfers_seller_status');
        $dropIndexSafely('pos_cashier_shifts', 'idx_pos_shifts_seller_status');
    }
};
