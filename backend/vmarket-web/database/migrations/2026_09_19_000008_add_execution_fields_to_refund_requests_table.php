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
        Schema::table('refund_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('refund_requests', 'execution_ref')) {
                $table->string('execution_ref')->nullable()->index()->after('payment_info');
            }
            if (!Schema::hasColumn('refund_requests', 'execution_status')) {
                $table->string('execution_status')->default('idle')->index()->after('execution_ref');
            }
            if (!Schema::hasColumn('refund_requests', 'paystack_refund_id')) {
                $table->string('paystack_refund_id')->nullable()->index()->after('execution_status');
            }
            if (!Schema::hasColumn('refund_requests', 'paystack_processed_at')) {
                $table->timestamp('paystack_processed_at')->nullable()->after('paystack_refund_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn([
                'execution_ref',
                'execution_status',
                'paystack_refund_id',
                'paystack_processed_at',
            ]);
        });
    }
};
