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
        // 1. Immutable In-Shop Order Handover Audit Logs
        if (!Schema::hasTable('order_handover_logs')) {
            Schema::create('order_handover_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('handed_over_by_id')->nullable()->index();
                $table->string('handed_over_by_name');
                $table->unsignedBigInteger('delivery_man_id')->nullable()->index();
                $table->string('delivery_man_name')->nullable();
                $table->string('pickup_otp_used')->nullable();
                $table->timestamp('handed_over_at')->useCurrent();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Extend Orders Table for Direct Handover Attribution
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'handed_over_by_id')) {
                $table->unsignedBigInteger('handed_over_by_id')->nullable()->after('pickup_verification_code');
            }
            if (!Schema::hasColumn('orders', 'handed_over_by_name')) {
                $table->string('handed_over_by_name')->nullable()->after('handed_over_by_id');
            }
            if (!Schema::hasColumn('orders', 'handed_over_at')) {
                $table->timestamp('handed_over_at')->nullable()->after('handed_over_by_name');
            }
            if (!Schema::hasColumn('orders', 'handover_branch_id')) {
                $table->unsignedBigInteger('handover_branch_id')->nullable()->after('handed_over_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_handover_logs');

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'handed_over_by_id')) {
                $table->dropColumn(['handed_over_by_id', 'handed_over_by_name', 'handed_over_at', 'handover_branch_id']);
            }
        });
    }
};
