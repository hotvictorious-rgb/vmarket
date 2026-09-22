<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Adds shop_id (branch isolation) to vendor_employees.
     */
    public function up(): void
    {
        if (Schema::hasTable('vendor_employees') && !Schema::hasColumn('vendor_employees', 'shop_id')) {
            Schema::table('vendor_employees', function (Blueprint $table) {
                $table->unsignedBigInteger('shop_id')->nullable()->after('seller_id')->index('idx_vendor_emp_shop');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vendor_employees') && Schema::hasColumn('vendor_employees', 'shop_id')) {
            Schema::table('vendor_employees', function (Blueprint $table) {
                $table->dropColumn('shop_id');
            });
        }
    }
};
