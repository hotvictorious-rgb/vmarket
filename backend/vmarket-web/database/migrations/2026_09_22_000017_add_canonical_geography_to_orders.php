<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Phase 11: Delivery Order Settlement Integration
     *
     * Add canonical geography snapshot fields to orders table.
     * Stores immutable origin/destination LGA references captured at checkout time.
     *
     * Part of: VMarket Geography & Fulfillment Architecture
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Canonical geography snapshot from CheckoutIntent
            $table->unsignedBigInteger('origin_lga_id')->nullable()->after('shipping_address_data');
            $table->string('origin_lga_name', 100)->nullable()->after('origin_lga_id');
            $table->string('origin_state_name', 100)->nullable()->after('origin_lga_name');

            $table->unsignedBigInteger('destination_lga_id')->nullable()->after('origin_state_name');
            $table->string('destination_lga_name', 100)->nullable()->after('destination_lga_id');
            $table->string('destination_state_name', 100)->nullable()->after('destination_lga_name');

            // Authoritative delivery fee captured at checkout (for audit/verification)
            $table->decimal('authoritative_delivery_fee', 24, 4)->nullable()->after('destination_state_name')
                ->comment('Frozen delivery fee from DeliveryLane at checkout time');

            // Estimated delivery time from lane snapshot
            $table->string('estimated_delivery_time', 50)->nullable()->after('authoritative_delivery_fee');

            // Indexes for logistics queries
            $table->index('origin_lga_id', 'idx_orders_origin_lga');
            $table->index('destination_lga_id', 'idx_orders_destination_lga');
            $table->index(['origin_lga_id', 'destination_lga_id'], 'idx_orders_delivery_lane');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_delivery_lane');
            $table->dropIndex('idx_orders_destination_lga');
            $table->dropIndex('idx_orders_origin_lga');

            $table->dropColumn([
                'origin_lga_id',
                'origin_lga_name',
                'origin_state_name',
                'destination_lga_id',
                'destination_lga_name',
                'destination_state_name',
                'authoritative_delivery_fee',
                'estimated_delivery_time',
            ]);
        });
    }
};
