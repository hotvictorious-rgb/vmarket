<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * [AI] Ensure all payment reconciliation anomaly types are present in ENUM,
     * including legacy types, pickup/delivery lifecycle anomalies, and post-payment stock failure.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE payment_reconciliations 
            MODIFY COLUMN initial_anomaly_type ENUM(
                'amount_mismatch',
                'currency_mismatch',
                'late_capture_expired',
                'stale_order_group',
                'stale_reservation_state',
                'charge_reversed',
                'duplicate_capture',
                'stale_superseded_attempt',
                'invalid_snapshot',
                'post_payment_stock_failure',
                'other'
            ) NOT NULL
        ");
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE payment_reconciliations 
            MODIFY COLUMN initial_anomaly_type ENUM(
                'amount_mismatch',
                'currency_mismatch',
                'late_capture_expired',
                'stale_order_group',
                'stale_reservation_state',
                'charge_reversed',
                'duplicate_capture',
                'post_payment_stock_failure',
                'other'
            ) NOT NULL
        ");
    }
};
