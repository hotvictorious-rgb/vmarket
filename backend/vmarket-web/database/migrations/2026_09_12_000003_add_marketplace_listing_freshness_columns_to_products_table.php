<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'marketplace_listing_status')) {
                $table->string('marketplace_listing_status', 20)->default('unlisted')->after('status');
                $table->index('marketplace_listing_status', 'idx_products_mkt_listing_status');
            }
            if (!Schema::hasColumn('products', 'marketplace_availability')) {
                $table->string('marketplace_availability', 20)->default('in_stock')->after('marketplace_listing_status');
                $table->index('marketplace_availability', 'idx_products_mkt_availability');
            }
            if (!Schema::hasColumn('products', 'marketplace_confirmed_at')) {
                $table->timestamp('marketplace_confirmed_at')->nullable()->after('marketplace_availability');
                $table->index('marketplace_confirmed_at', 'idx_products_mkt_confirmed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'marketplace_confirmed_at')) {
                $table->dropIndex('idx_products_mkt_confirmed_at');
                $table->dropColumn('marketplace_confirmed_at');
            }
            if (Schema::hasColumn('products', 'marketplace_availability')) {
                $table->dropIndex('idx_products_mkt_availability');
                $table->dropColumn('marketplace_availability');
            }
            if (Schema::hasColumn('products', 'marketplace_listing_status')) {
                $table->dropIndex('idx_products_mkt_listing_status');
                $table->dropColumn('marketplace_listing_status');
            }
        });
    }
};
