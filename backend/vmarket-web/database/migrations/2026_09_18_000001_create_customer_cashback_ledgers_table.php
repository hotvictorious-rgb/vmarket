<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * [AI] Victorious MARKET Customer Cashback Reward Ledger
     * 5% Reward on eligible physical merchandise value (excludes shipping fees).
     * Non-withdrawable purchase reward ledger: pending -> available (after 7-day return window) -> redeemed.
     */
    public function up(): void
    {
        if (!Schema::hasTable('customer_cashback_ledgers')) {
            Schema::create('customer_cashback_ledgers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('order_id')->index();
                $table->decimal('merchandise_amount', 14, 4)->default(0.0000);
                $table->decimal('cashback_rate', 5, 2)->default(5.00);
                $table->decimal('cashback_amount', 14, 4)->default(0.0000);
                $table->string('status', 20)->default('pending')->index(); // pending, available, redeemed, cancelled
                $table->timestamp('available_at')->nullable();
                $table->timestamp('redeemed_at')->nullable();
                $table->unsignedBigInteger('redeemed_order_id')->nullable();
                $table->string('description', 255)->nullable();
                $table->timestamps();

                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_cashback_ledgers');
    }
};
