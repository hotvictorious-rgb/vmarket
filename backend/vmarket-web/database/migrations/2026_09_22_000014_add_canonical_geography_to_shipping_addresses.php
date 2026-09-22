<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Adds canonical geography fields to shipping_addresses table.
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 5 - Customer Address Canonical Geography
 *
 * CRITICAL:
 * - Adds NEW fields: country_id, state_id, lga_id
 * - KEEPS text fields: country, state, city (for display/history)
 * - Customer address LGA = Destination for delivery routing
 *
 * Address structure:
 * Canonical IDs (country_id, state_id, lga_id) + Text (address) + Coordinates (lat/lng)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_addresses', function (Blueprint $table) {
            // Add canonical geography (nullable during migration)
            $table->foreignId('country_id')->nullable()->after('customer_id')->constrained('countries');
            $table->foreignId('state_id')->nullable()->after('country_id')->constrained('states');
            $table->foreignId('lga_id')->nullable()->after('state_id')->constrained('lgas');

            // Index for destination LGA lookups (delivery lane routing)
            $table->index(['lga_id'], 'idx_address_lga');

            // KEEP existing text fields for display:
            // - country (text)
            // - state (text)
            // - city (text)
            // - address (text)
            // These remain for backward compatibility and display purposes
        });
    }

    public function down(): void
    {
        Schema::table('shipping_addresses', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['lga_id']);
            $table->dropIndex('idx_address_lga');
            $table->dropColumn(['country_id', 'state_id', 'lga_id']);
        });
    }
};
