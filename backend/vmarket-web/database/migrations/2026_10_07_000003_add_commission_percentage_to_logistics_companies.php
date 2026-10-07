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
        if (Schema::hasTable('logistics_companies')) {
            Schema::table('logistics_companies', function (Blueprint $table) {
                if (!Schema::hasColumn('logistics_companies', 'commission_percentage')) {
                    $table->decimal('commission_percentage', 5, 2)->nullable()->default(null)->after('account_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('logistics_companies')) {
            Schema::table('logistics_companies', function (Blueprint $table) {
                if (Schema::hasColumn('logistics_companies', 'commission_percentage')) {
                    $table->dropColumn('commission_percentage');
                }
            });
        }
    }
};
