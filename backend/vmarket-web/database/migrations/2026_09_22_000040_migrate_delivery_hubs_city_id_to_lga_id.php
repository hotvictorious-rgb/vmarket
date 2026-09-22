<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Phase A8 — Logistics Hub Decoupling
 *
 * Migrates delivery_hubs.city_id (→ delivery_cities, legacy) to
 * delivery_hubs.lga_id (→ lgas, canonical geography).
 *
 * After this migration the DeliveryHub model links exclusively to
 * the canonical Lga model; the legacy DeliveryCity/DeliveryState
 * tables are no longer referenced by any hub record.
 *
 * Data migration: existing city_id values cannot be automatically
 * mapped to lga_id because delivery_cities and lgas are independent
 * seeded datasets. Hubs are operational config managed by admin;
 * existing rows have their lga_id set to NULL — the admin must
 * re-assign them via the redesigned hub management UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_hubs', function (Blueprint $table) {
            // Add the new canonical column alongside city_id
            if (!Schema::hasColumn('delivery_hubs', 'lga_id')) {
                $table->unsignedBigInteger('lga_id')->nullable()->index()->after('id');
            }
        });

        // Null out city_id so it holds no dangling legacy references
        // lga_id stays NULL; admin re-assigns via the updated hub UI
        DB::table('delivery_hubs')->update(['lga_id' => null]);

        Schema::table('delivery_hubs', function (Blueprint $table) {
            // Drop the old city_id column
            if (Schema::hasColumn('delivery_hubs', 'city_id')) {
                $table->dropColumn('city_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_hubs', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_hubs', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->index()->after('id');
            }
        });

        Schema::table('delivery_hubs', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_hubs', 'lga_id')) {
                $table->dropColumn('lga_id');
            }
        });
    }
};
