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
        // 1. Add package_size to products
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'package_size')) {
                    $table->string('package_size', 20)->default('small')->after('unit')->index(); // small, large
                }
            });
        }

        // 2. Add package_tier, logistics_company_id, delivery_commission_amount, bulky_surcharge_amount to orders
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'package_tier')) {
                    $table->string('package_tier', 20)->default('small')->after('delivery_type')->index(); // small, large
                }
                if (!Schema::hasColumn('orders', 'logistics_company_id')) {
                    $table->unsignedBigInteger('logistics_company_id')->nullable()->after('delivery_man_id')->index();
                }
                if (!Schema::hasColumn('orders', 'delivery_commission_amount')) {
                    $table->decimal('delivery_commission_amount', 14, 2)->default(0.00)->after('deliveryman_charge');
                }
                if (!Schema::hasColumn('orders', 'bulky_surcharge_amount')) {
                    $table->decimal('bulky_surcharge_amount', 14, 2)->default(0.00)->after('delivery_commission_amount');
                }
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
                if (Schema::hasColumn('products', 'package_size')) {
                    $table->dropColumn('package_size');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'package_tier')) {
                    $table->dropColumn('package_tier');
                }
                if (Schema::hasColumn('orders', 'logistics_company_id')) {
                    $table->dropColumn('logistics_company_id');
                }
                if (Schema::hasColumn('orders', 'delivery_commission_amount')) {
                    $table->dropColumn('delivery_commission_amount');
                }
                if (Schema::hasColumn('orders', 'bulky_surcharge_amount')) {
                    $table->dropColumn('bulky_surcharge_amount');
                }
            });
        }
    }
};
