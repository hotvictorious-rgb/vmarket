<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Marketplace Availability Control — Lifecycle Columns.
 *
 * Adds `availability_confirmed_at` and `availability_expires_at` to products.
 * These two fields are the canonical freshness lifecycle metadata for the
 * Marketplace Availability Control architecture (replacing the old approach of
 * deriving expiry at runtime from `marketplace_confirmed_at`).
 *
 *  - `availability_confirmed_at` = When vendor last confirmed "Still Available: Yes"
 *  - `availability_expires_at`   = Pre-calculated cut-off (confirmed_at + N days)
 *
 * Backfill: existing listed in-stock seller products inherit values from
 * `marketplace_confirmed_at` (default 7-day window) so no active listing breaks.
 * Admin products stay NULL — they are permanently exempt from expiry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // [AI] Canonical vendor confirmation timestamp
            if (!Schema::hasColumn('products', 'availability_confirmed_at')) {
                $table->timestamp('availability_confirmed_at')->nullable()->after('marketplace_confirmed_at');
                $table->index('availability_confirmed_at', 'idx_products_avail_confirmed_at');
            }
            // [AI] Pre-calculated expiry — enables O(1) database-level freshness filtering
            if (!Schema::hasColumn('products', 'availability_expires_at')) {
                $table->timestamp('availability_expires_at')->nullable()->after('availability_confirmed_at');
                $table->index('availability_expires_at', 'idx_products_avail_expires_at');
            }
        });

        // [AI] Backfill: Seed lifecycle columns from existing marketplace_confirmed_at
        // for listed in-stock seller products. Default window: 7 days.
        // This ensures zero-disruption — existing vendors keep their listings active.
        DB::statement("
            UPDATE products
            SET
                availability_confirmed_at = marketplace_confirmed_at,
                availability_expires_at   = DATE_ADD(marketplace_confirmed_at, INTERVAL 7 DAY)
            WHERE
                added_by = 'seller'
                AND marketplace_listing_status = 'listed'
                AND marketplace_availability   = 'in_stock'
                AND marketplace_confirmed_at   IS NOT NULL
                AND availability_confirmed_at  IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'availability_expires_at')) {
                $table->dropIndex('idx_products_avail_expires_at');
                $table->dropColumn('availability_expires_at');
            }
            if (Schema::hasColumn('products', 'availability_confirmed_at')) {
                $table->dropIndex('idx_products_avail_confirmed_at');
                $table->dropColumn('availability_confirmed_at');
            }
        });
    }
};
