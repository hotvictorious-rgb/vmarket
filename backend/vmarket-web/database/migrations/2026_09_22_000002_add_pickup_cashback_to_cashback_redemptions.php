<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Adds pickup_reservation_id FK to cashback_redemptions.
     *
     * Pickup cashback is an earning event (5% awarded at settlement, status='captured' immediately).
     * This column links the cashback ledger row back to the originating pickup_reservations row,
     * enabling audit trails, support tooling, and future release logic if a reservation is voided.
     *
     * Invariants preserved:
     * - checkout_intent_id remains the FK for delivery cashback redemptions (existing).
     * - pickup_reservation_id is the FK for pickup cashback records (new).
     * - Both columns are nullable and mutually exclusive per row.
     */
    public function up(): void
    {
        if (Schema::hasTable('cashback_redemptions') && !Schema::hasColumn('cashback_redemptions', 'pickup_reservation_id')) {
            Schema::table('cashback_redemptions', function (Blueprint $table) {
                $table->unsignedBigInteger('pickup_reservation_id')->nullable()->after('checkout_intent_id');
                $table->index('pickup_reservation_id', 'idx_cbr_pickup_res_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cashback_redemptions') && Schema::hasColumn('cashback_redemptions', 'pickup_reservation_id')) {
            Schema::table('cashback_redemptions', function (Blueprint $table) {
                $table->dropIndex('idx_cbr_pickup_res_id');
                $table->dropColumn('pickup_reservation_id');
            });
        }
    }
};
