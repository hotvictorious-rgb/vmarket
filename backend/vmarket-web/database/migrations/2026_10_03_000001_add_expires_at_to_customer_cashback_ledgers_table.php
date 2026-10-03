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
        if (Schema::hasTable('customer_cashback_ledgers')) {
            Schema::table('customer_cashback_ledgers', function (Blueprint $table) {
                if (!Schema::hasColumn('customer_cashback_ledgers', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable()->after('available_at')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customer_cashback_ledgers')) {
            Schema::table('customer_cashback_ledgers', function (Blueprint $table) {
                if (Schema::hasColumn('customer_cashback_ledgers', 'expires_at')) {
                    $table->dropColumn('expires_at');
                }
            });
        }
    }
};
