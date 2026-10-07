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
        if (Schema::hasTable('order_delivery_verifications')) {
            Schema::table('order_delivery_verifications', function (Blueprint $table) {
                if (!Schema::hasColumn('order_delivery_verifications', 'handover_type')) {
                    $table->string('handover_type', 50)->default('customer_doorstep')->after('image')->index();
                }
                if (!Schema::hasColumn('order_delivery_verifications', 'verified_by_type')) {
                    $table->string('verified_by_type', 30)->default('customer')->after('handover_type');
                }
                if (!Schema::hasColumn('order_delivery_verifications', 'verified_by_id')) {
                    $table->unsignedBigInteger('verified_by_id')->nullable()->after('verified_by_type')->index();
                }
                if (!Schema::hasColumn('order_delivery_verifications', 'pickup_otp_used')) {
                    $table->string('pickup_otp_used', 20)->nullable()->after('verified_by_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('order_delivery_verifications')) {
            Schema::table('order_delivery_verifications', function (Blueprint $table) {
                $columns = ['handover_type', 'verified_by_type', 'verified_by_id', 'pickup_otp_used'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('order_delivery_verifications', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
