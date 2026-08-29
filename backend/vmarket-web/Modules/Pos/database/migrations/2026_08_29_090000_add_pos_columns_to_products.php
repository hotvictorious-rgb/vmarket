<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Add POS-specific columns to the unified products table.
 * These columns allow products to participate in In-Store POS operations
 * while remaining on the unified Vmarket marketplace catalog.
 * They do NOT affect marketplace listing behavior (that is gated by status/request_status).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'pos_barcode')) {
                $table->string('pos_barcode', 100)->nullable()->after('code')
                    ->comment('[AI] POS barcode/QR code — for barcode scanner lookup at POS terminal');
            }
            if (!Schema::hasColumn('products', 'pos_category')) {
                $table->string('pos_category', 100)->nullable()->after('pos_barcode')
                    ->comment('[AI] POS display category — e.g. "Electronics", "Groceries". Separate from marketplace category_id.');
            }
            if (!Schema::hasColumn('products', 'pos_reorder_level')) {
                $table->unsignedInteger('pos_reorder_level')->default(5)->after('pos_category')
                    ->comment('[AI] Low-stock alert threshold for POS dashboard. Default 5 units.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumnIfExists('pos_barcode');
            $table->dropColumnIfExists('pos_category');
            $table->dropColumnIfExists('pos_reorder_level');
        });
    }
};
