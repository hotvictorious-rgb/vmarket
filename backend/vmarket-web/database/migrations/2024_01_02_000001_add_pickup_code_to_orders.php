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
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'pickup_code')) {
                    $table->string('pickup_code')->nullable()->unique()->after('verification_code');
                    $table->boolean('pickup_verified')->default(false)->after('pickup_code');
                    $table->dateTime('pickup_verified_at')->nullable()->after('pickup_verified');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumnIfExists(['pickup_code', 'pickup_verified', 'pickup_verified_at']);
            });
        }
    }
};
