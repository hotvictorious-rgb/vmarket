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
        Schema::table('delivery_men', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_men', 'delivery_hub_id')) {
                $table->unsignedBigInteger('delivery_hub_id')->nullable()->after('seller_id')->index();
                $table->foreign('delivery_hub_id')->references('id')->on('delivery_hubs')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_men', 'delivery_hub_id')) {
                $table->dropForeign(['delivery_hub_id']);
                $table->dropColumn('delivery_hub_id');
            }
        });
    }
};
