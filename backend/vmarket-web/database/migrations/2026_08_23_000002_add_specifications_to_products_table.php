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
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'specifications')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('specifications')->nullable()->after('choice_options');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'specifications')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('specifications');
            });
        }
    }
};
