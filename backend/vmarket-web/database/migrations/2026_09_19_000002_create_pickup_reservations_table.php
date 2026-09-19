<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Creates the pickup_reservations table for Pay-After-Inspection pickup workflows.
     * Reuses existing sellers and shops tables; handover OTP belongs to orders.pickup_verification_code.
     */
    public function up(): void
    {
        Schema::create('pickup_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_code', 32)->unique('uq_pr_reservation_code');
            $table->string('idempotency_key', 64)->unique('uq_pr_idempotency_key');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('shop_id');
            $table->string('reservation_fingerprint', 64);
            $table->string('active_reservation_token', 64)->nullable();
            $table->enum('status', [
                'pending_inspection',
                'inspected_accepted',
                'inspected_rejected',
                'order_placed',
                'canceled',
                'expired',
            ])->default('pending_inspection');
            $table->decimal('total_amount', 14, 4);
            $table->string('currency', 10)->default('NGN');
            $table->json('reservation_items');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('inspected_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            // Unique constraint: At most one active pending reservation per customer and reservation state
            $table->unique(['customer_id', 'active_reservation_token'], 'uq_pr_customer_active_res');

            $table->index(['customer_id', 'status'], 'idx_pr_customer_status');
            $table->index(['seller_id', 'status'], 'idx_pr_seller_status');
            $table->index('shop_id', 'idx_pr_shop_id');
            $table->index('order_id', 'idx_pr_order_id');
            $table->index('expires_at', 'idx_pr_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_reservations');
    }
};
