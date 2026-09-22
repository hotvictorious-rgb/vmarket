<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * [AI] Victorious MARKET V1 Cashback Redemptions Table
     * Concurrency-safe reservation engine preventing double-spending of Victorious Points (Cashback).
     */
    public function up(): void
    {
        if (!Schema::hasTable('cashback_redemptions')) {
            Schema::create('cashback_redemptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('customer_id')->index();
                $table->unsignedBigInteger('checkout_intent_id')->nullable()->index();
                $table->string('order_group_id', 64)->index();
                $table->decimal('points', 18, 4)->default(0.0000);
                $table->decimal('cashback_amount', 14, 4)->default(0.0000);
                $table->string('status', 20)->default('reserved')->index(); // reserved, captured, released, expired
                $table->timestamp('captured_at')->nullable();
                $table->timestamp('released_at')->nullable();
                $table->timestamps();

                $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashback_redemptions');
    }
};
