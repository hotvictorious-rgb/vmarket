<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Creates the checkout_intents table as the durable concurrency anchor
     * and frozen snapshot repository for delivery checkouts prior to order creation.
     */
    public function up(): void
    {
        Schema::create('checkout_intents', function (Blueprint $table) {
            $table->id();
            $table->string('order_group_id', 191)->unique('uq_ci_order_group_id');
            $table->unsignedBigInteger('customer_id');
            $table->string('idempotency_key', 64)->unique('uq_ci_idempotency_key');
            $table->string('cart_fingerprint', 64);
            $table->string('active_cart_token', 64)->nullable();
            $table->enum('status', ['pending', 'converted_to_orders', 'expired', 'canceled'])->default('pending');
            $table->decimal('total_amount', 14, 4);
            $table->string('currency', 10)->default('NGN');
            $table->json('checkout_snapshot');
            $table->timestamp('expires_at');
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            // Unique constraint: At most one active pending intent per customer and cart state
            $table->unique(['customer_id', 'active_cart_token'], 'uq_ci_customer_active_cart');

            $table->index(['customer_id', 'status'], 'idx_ci_customer_status');
            $table->index('expires_at', 'idx_ci_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_intents');
    }
};
