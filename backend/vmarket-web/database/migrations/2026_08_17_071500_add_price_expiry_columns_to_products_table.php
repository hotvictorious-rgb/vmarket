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
            if (!Schema::hasColumn('products', 'price_updated_at')) {
                $table->timestamp('price_updated_at')->nullable()->useCurrent()->after('unit_price');
            }
            if (!Schema::hasColumn('products', 'price_expiry_notified_at')) {
                $table->timestamp('price_expiry_notified_at')->nullable()->after('price_updated_at');
            }
            if (!Schema::hasColumn('products', 'deactivation_reason')) {
                $table->string('deactivation_reason', 50)->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'price_updated_at')) {
                $table->dropColumn('price_updated_at');
            }
            if (Schema::hasColumn('products', 'price_expiry_notified_at')) {
                $table->dropColumn('price_expiry_notified_at');
            }
            if (Schema::hasColumn('products', 'deactivation_reason')) {
                $table->dropColumn('deactivation_reason');
            }
        });
    }
};
