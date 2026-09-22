<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Adds pickup settings to shops table.
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 7 - Shop Pickup Settings
 *
 * In-shop pickup is a shop-level capability independent of delivery lanes.
 * Evaluated via shop pickup settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('pickup_enabled')->default(true)->after('branch_code');
            $table->time('pickup_opening_time')->nullable()->after('pickup_enabled');
            $table->time('pickup_closing_time')->nullable()->after('pickup_opening_time');
            $table->integer('pickup_preparation_time_minutes')->default(30)->after('pickup_closing_time');
            $table->text('pickup_instructions')->nullable()->after('pickup_preparation_time_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_enabled',
                'pickup_opening_time',
                'pickup_closing_time',
                'pickup_preparation_time_minutes',
                'pickup_instructions',
            ]);
        });
    }
};
