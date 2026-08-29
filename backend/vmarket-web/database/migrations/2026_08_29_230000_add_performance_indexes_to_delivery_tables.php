<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Performance Migration: Delivery Hub Composite Indexes
 *
 * Adds composite and single-column indexes to the delivery tables to eliminate
 * full/partial table scans on the dominant query patterns:
 *   - delivery_hubs: WHERE city_id = ? AND is_active = 1 [AND type = ?]
 *   - delivery_cities: WHERE state_id = ? AND is_active = 1
 *   - delivery_states: WHERE is_active = 1
 *
 * These indexes drastically reduce page-load and API response times for the
 * Delivery Hub admin page and the customer checkout delivery dropdown flow.
 */
return new class extends Migration
{
    public function up(): void
    {
        // [AI] delivery_hubs — composite indexes for the two dominant query patterns.
        // try/catch used for idempotency (compatible with Laravel 10+ without doctrine/dbal).
        Schema::table('delivery_hubs', function (Blueprint $table) {
            // Most common admin/API pattern: WHERE city_id = ? AND is_active = 1
            try { $table->index(['city_id', 'is_active'], 'idx_hubs_city_active'); } catch (\Exception $e) {}
            // Filter-by-type pattern: WHERE city_id = ? AND type = ? AND is_active = 1
            try { $table->index(['city_id', 'type', 'is_active'], 'idx_hubs_city_type_active'); } catch (\Exception $e) {}
            // Global status filter used in admin hub listing
            try { $table->index('is_active', 'idx_hubs_active'); } catch (\Exception $e) {}
        });

        // [AI] delivery_cities — composite index for city-by-state filtering
        Schema::table('delivery_cities', function (Blueprint $table) {
            // Most common pattern: WHERE state_id = ? AND is_active = 1
            try { $table->index(['state_id', 'is_active'], 'idx_cities_state_active'); } catch (\Exception $e) {}
            // Global active-only city dropdowns
            try { $table->index('is_active', 'idx_cities_active'); } catch (\Exception $e) {}
        });

        // [AI] delivery_states — index on is_active used in every dropdown/API call
        Schema::table('delivery_states', function (Blueprint $table) {
            try { $table->index('is_active', 'idx_states_active'); } catch (\Exception $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('delivery_hubs', function (Blueprint $table) {
            try { $table->dropIndex('idx_hubs_city_active'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_hubs_city_type_active'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_hubs_active'); } catch (\Exception $e) {}
        });

        Schema::table('delivery_cities', function (Blueprint $table) {
            try { $table->dropIndex('idx_cities_state_active'); } catch (\Exception $e) {}
            try { $table->dropIndex('idx_cities_active'); } catch (\Exception $e) {}
        });

        Schema::table('delivery_states', function (Blueprint $table) {
            try { $table->dropIndex('idx_states_active'); } catch (\Exception $e) {}
        });
    }
};
