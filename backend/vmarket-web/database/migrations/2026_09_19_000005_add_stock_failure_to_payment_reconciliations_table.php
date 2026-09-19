<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * [AI] Add 'post_payment_stock_failure' to payment_reconciliations.initial_anomaly_type ENUM.
     * Preserves all existing values and historical rows without data loss.
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
                'other'
            ) NOT NULL
        ");
    }
};
