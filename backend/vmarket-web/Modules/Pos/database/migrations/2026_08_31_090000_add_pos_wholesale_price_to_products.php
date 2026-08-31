<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Add pos_wholesale_price to unified products table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'pos_wholesale_price')) {
                $table->decimal('pos_wholesale_price', 16, 2)->nullable()->after('purchase_price')
                    ->comment('[AI] POS Bulk / Wholesale tier selling price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumnIfExists('pos_wholesale_price');
        });
    }
};
