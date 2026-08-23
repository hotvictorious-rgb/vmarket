<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Performance Refinement: Add composite indexing on products table for expiry checks and approval portals
     */
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                // Index for daily price expiry worker: where('status', 1)->where('price_updated_at', '<=', $threshold)
                $table->index(['status', 'price_updated_at'], 'products_status_price_updated_at_idx');

                // Index for admin approval portal and seller status queries
                $table->index(['added_by', 'request_status'], 'products_added_by_request_status_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_status_price_updated_at_idx');
                $table->dropIndex('products_added_by_request_status_idx');
            });
        }
    }
};
