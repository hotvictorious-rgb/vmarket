<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Adds canonical geography fields to shops table.
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 4 - Shop Canonical Geography
 *
 * CRITICAL:
 * - Adds NEW fields: country_id, state_id, lga_id
 * - KEEPS legacy fields: delivery_state_id, delivery_city_id, delivery_hub_id
 * - Legacy fields will be deprecated in Phase 18 after full migration
 *
 * Shop LGA = Origin for delivery routing (Origin LGA → Destination LGA)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Add canonical geography (nullable during migration)
            $table->foreignId('country_id')->nullable()->after('id')->constrained('countries');
            $table->foreignId('state_id')->nullable()->after('country_id')->constrained('states');
            $table->foreignId('lga_id')->nullable()->after('state_id')->constrained('lgas');

            // Index for origin LGA lookups (delivery lane routing)
            $table->index(['country_id', 'state_id', 'lga_id'], 'idx_shop_canonical_geography');

            // DO NOT DROP legacy fields:
            // - delivery_state_id
            // - delivery_city_id
            // - delivery_hub_id
            // These remain for backward compatibility during migration
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['lga_id']);
            $table->dropIndex('idx_shop_canonical_geography');
            $table->dropColumn(['country_id', 'state_id', 'lga_id']);
        });
    }
};
