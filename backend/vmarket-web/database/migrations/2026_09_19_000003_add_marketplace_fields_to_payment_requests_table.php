<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * [AI] Adds marketplace routing, canonical gateway reference, single-active-attempt
     * concurrency controls, and MySQL 8.4 CHECK constraints to payment_requests.
     * Legacy payment workflows remain 100% untouched.
     */
    public function up(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('payment_domain', 32)->nullable()->after('payment_method');
            $table->string('order_group_id', 191)->nullable()->after('payment_domain');
            $table->unsignedBigInteger('pickup_reservation_id')->nullable()->after('order_group_id');
            $table->string('gateway_reference', 191)->nullable()->after('pickup_reservation_id');
            $table->string('attempt_status', 32)->nullable()->after('gateway_reference');
            $table->string('active_order_group_id', 191)->nullable()->after('attempt_status');
            $table->unsignedBigInteger('active_pickup_reservation_id')->nullable()->after('active_order_group_id');
            $table->timestamp('attempt_expires_at')->nullable()->after('active_pickup_reservation_id');

            // Unique constraints: Canonical gateway reference and exactly one active attempt per group/reservation
            $table->unique('gateway_reference', 'uq_pr_gateway_reference');
            $table->unique('active_order_group_id', 'uq_pr_active_order_group');
            $table->unique('active_pickup_reservation_id', 'uq_pr_active_pickup_res');

            $table->index('order_group_id', 'idx_pr_order_group_id');
            $table->index('pickup_reservation_id', 'idx_pr_pickup_res_id');
            $table->index(['payment_domain', 'attempt_status'], 'idx_pr_domain_status');
        });

        // MySQL 8.4 Enforced Domain Integrity CHECK Constraint
        DB::statement("
            ALTER TABLE `payment_requests`
            ADD CONSTRAINT `chk_pr_domain_integrity` CHECK (
                (
                    payment_domain IS NULL 
                    AND order_group_id IS NULL 
                    AND pickup_reservation_id IS NULL 
                    AND active_order_group_id IS NULL 
                    AND active_pickup_reservation_id IS NULL
                )
                OR
                (
                    payment_domain = 'marketplace_delivery' 
                    AND order_group_id IS NOT NULL 
                    AND pickup_reservation_id IS NULL 
                    AND active_pickup_reservation_id IS NULL
                )
                OR
                (
                    payment_domain = 'marketplace_pickup' 
                    AND pickup_reservation_id IS NOT NULL 
                    AND order_group_id IS NULL 
                    AND active_order_group_id IS NULL
                )
            )
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `payment_requests` DROP CHECK `chk_pr_domain_integrity`");

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropUnique('uq_pr_gateway_reference');
            $table->dropUnique('uq_pr_active_order_group');
            $table->dropUnique('uq_pr_active_pickup_res');
            $table->dropIndex('idx_pr_order_group_id');
            $table->dropIndex('idx_pr_pickup_res_id');
            $table->dropIndex('idx_pr_domain_status');

            $table->dropColumn([
                'payment_domain',
                'order_group_id',
                'pickup_reservation_id',
                'gateway_reference',
                'attempt_status',
                'active_order_group_id',
                'active_pickup_reservation_id',
                'attempt_expires_at',
            ]);
        });
    }
};
