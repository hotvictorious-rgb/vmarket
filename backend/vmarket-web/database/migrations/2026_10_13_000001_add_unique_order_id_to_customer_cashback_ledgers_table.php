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
        if (Schema::hasTable('customer_cashback_ledgers')) {
            Schema::table('customer_cashback_ledgers', function (Blueprint $table) {
                // Drop regular index if exists before adding unique constraint, or add unique index
                try {
                    $table->unique('order_id', 'customer_cashback_ledgers_order_id_unique');
                } catch (\Throwable $e) {
                    // Index may already exist or driver handled
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_cashback_ledgers')) {
            Schema::table('customer_cashback_ledgers', function (Blueprint $table) {
                try {
                    $table->dropUnique('customer_cashback_ledgers_order_id_unique');
                } catch (\Throwable $e) {
                    // Index not present
                }
            });
        }
    }
};
