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
        // 1. Hub-to-Hub Route Corridors & Pricing Matrix
        if (!Schema::hasTable('delivery_routes')) {
            Schema::create('delivery_routes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('origin_hub_id')->index();
                $table->unsignedBigInteger('destination_hub_id')->index();
                $table->decimal('customer_fee', 10, 2)->default(1000.00); // Charged to buyer
                $table->decimal('rider_payout', 10, 2)->default(700.00);  // Paid to dispatch rider
                $table->decimal('logistics_partner_margin', 10, 2)->default(100.00); // 3PL company cut
                $table->decimal('estimated_hours', 5, 2)->default(2.00); // Transit time in hours
                $table->enum('transit_type', ['intra_city', 'inter_city_linehaul', 'regional'])->default('intra_city');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('origin_hub_id')->references('id')->on('delivery_hubs')->onDelete('cascade');
                $table->foreign('destination_hub_id')->references('id')->on('delivery_hubs')->onDelete('cascade');
                $table->unique(['origin_hub_id', 'destination_hub_id'], 'origin_dest_unique');
            });
        }

        // 2. 3rd-Party Logistics (3PL) Companies & Fleets
        if (!Schema::hasTable('delivery_3pl_companies')) {
            Schema::create('delivery_3pl_companies', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('code', 50)->unique();
                $table->string('contact_person', 100)->nullable();
                $table->string('phone', 50)->unique();
                $table->string('email', 150)->nullable()->unique();
                $table->string('password')->nullable();
                $table->string('address', 255)->nullable();
                $table->decimal('commission_rate', 5, 2)->default(10.00); // Percentage or flat
                $table->enum('status', ['pending', 'approved', 'suspended'])->default('approved');
                $table->string('auth_token')->nullable();
                $table->timestamps();
            });
        }

        // Add 3PL Company ID to delivery_men if not present
        if (Schema::hasTable('delivery_men') && !Schema::hasColumn('delivery_men', 'company_id')) {
            Schema::table('delivery_men', function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->after('seller_id')->index();
                $table->foreign('company_id')->references('id')->on('delivery_3pl_companies')->onDelete('set null');
            });
        }

        // 3. Consolidated Shipment Batches (Linehauls)
        if (!Schema::hasTable('delivery_batches')) {
            Schema::create('delivery_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_no', 100)->unique()->index();
                $table->unsignedBigInteger('origin_hub_id')->index();
                $table->unsignedBigInteger('destination_hub_id')->index();
                $table->unsignedBigInteger('driver_id')->nullable()->index();
                $table->string('vehicle_no', 50)->nullable();
                $table->integer('package_count')->default(0);
                $table->decimal('total_weight_kg', 8, 2)->default(0.00);
                $table->string('transit_otp', 10)->nullable(); // 6-digit transit handshake
                $table->enum('status', ['sorting', 'dispatched', 'in_transit', 'received_at_hub', 'completed'])->default('sorting');
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();

                $table->foreign('origin_hub_id')->references('id')->on('delivery_hubs')->onDelete('cascade');
                $table->foreign('destination_hub_id')->references('id')->on('delivery_hubs')->onDelete('cascade');
            });
        }

        // 4. Batch Orders Pivot Table
        if (!Schema::hasTable('delivery_batch_orders')) {
            Schema::create('delivery_batch_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('batch_id')->index();
                $table->unsignedBigInteger('order_id')->index();
                $table->enum('status', ['queued', 'loaded', 'delivered_to_hub', 'returned'])->default('queued');
                $table->timestamps();

                $table->foreign('batch_id')->references('id')->on('delivery_batches')->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->unique(['batch_id', 'order_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_batch_orders');
        Schema::dropIfExists('delivery_batches');
        if (Schema::hasTable('delivery_men') && Schema::hasColumn('delivery_men', 'company_id')) {
            Schema::table('delivery_men', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
        Schema::dropIfExists('delivery_3pl_companies');
        Schema::dropIfExists('delivery_routes');
    }
};
