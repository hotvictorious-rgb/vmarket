<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Creates payment_reconciliations table implementing the Single-Case-per-Payment model
     * for tracking abnormal captures, late captures on superseded attempts, and financial reversals.
     */
    public function up(): void
    {
        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 64)->unique('uq_prec_case_number');
            $table->string('gateway_reference', 191)->unique('uq_prec_gateway_ref');
            $table->char('payment_request_id', 36)->nullable();
            $table->string('payment_domain', 32);
            $table->string('order_group_id', 191)->nullable();
            $table->unsignedBigInteger('pickup_reservation_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->string('gateway_name', 32)->default('paystack');
            $table->decimal('captured_amount', 14, 4);
            $table->decimal('expected_amount', 14, 4);
            $table->string('currency', 10)->default('NGN');
            $table->enum('initial_anomaly_type', [
                'amount_mismatch',
                'currency_mismatch',
                'late_capture_expired',
                'stale_order_group',
                'stale_reservation_state',
                'charge_reversed',
                'duplicate_capture',
                'other',
            ]);
            $table->enum('current_status', [
                'open',
                'investigating',
                'resolved_manual_order',
                'resolved_refunded',
                'resolved_rejected',
            ])->default('open');
            $table->json('audit_events');
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('last_event_at')->useCurrent();
            $table->timestamps();

            $table->index('current_status', 'idx_prec_status');
            $table->index(['payment_domain', 'current_status'], 'idx_prec_domain_status');
            $table->index('order_group_id', 'idx_prec_order_group_id');
            $table->index('pickup_reservation_id', 'idx_prec_pickup_res_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliations');
    }
};
