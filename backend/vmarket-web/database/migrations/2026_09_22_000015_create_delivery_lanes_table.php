<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Creates delivery_lanes table for directional Origin LGA -> Destination LGA routing.
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 6 - Directional Delivery Lanes
 *
 * Invariants:
 * - Directional routing: Origin LGA -> Destination LGA
 * - Uyo -> Eket is independent of Eket -> Uyo
 * - Same-LGA delivery (Uyo -> Uyo) uses the same table
 * - Marketplace service owned by VMarket, not vendors
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_lanes', function (Blueprint $table) {
            $table->id();

            // Origin geography (shop location)
            $table->foreignId('origin_country_id')->constrained('countries')->onDelete('cascade');
            $table->foreignId('origin_state_id')->constrained('states')->onDelete('cascade');
            $table->foreignId('origin_lga_id')->constrained('lgas')->onDelete('cascade');

            // Destination geography (customer delivery location)
            $table->foreignId('destination_country_id')->constrained('countries')->onDelete('cascade');
            $table->foreignId('destination_state_id')->constrained('states')->onDelete('cascade');
            $table->foreignId('destination_lga_id')->constrained('lgas')->onDelete('cascade');

            // Delivery lane service attributes
            $table->boolean('is_enabled')->default(true);
            $table->decimal('delivery_fee', 10, 2)->default(0.00)->comment('Naira');
            $table->string('estimated_delivery_time', 100)->nullable()->comment('e.g. 24-48 hours');

            $table->timestamps();

            // Unique constraint: one lane per origin-destination pair
            $table->unique([
                'origin_country_id',
                'origin_state_id',
                'origin_lga_id',
                'destination_country_id',
                'destination_state_id',
                'destination_lga_id',
            ], 'uniq_delivery_lane');

            // Indexes for lookup performance
            $table->index(['origin_lga_id', 'destination_lga_id', 'is_enabled'], 'idx_lane_origin_dest_enabled');
            $table->index(['destination_lga_id', 'is_enabled'], 'idx_lane_dest_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_lanes');
    }
};
