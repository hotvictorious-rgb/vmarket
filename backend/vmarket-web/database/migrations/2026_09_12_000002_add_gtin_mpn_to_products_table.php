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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'gtin')) {
                $table->string('gtin', 50)->nullable()->index()->after('code');
            }
            if (!Schema::hasColumn('products', 'mpn')) {
                $table->string('mpn', 50)->nullable()->after('gtin');
            }
            if (!Schema::hasColumn('products', 'google_category_id')) {
                $table->string('google_category_id', 50)->nullable()->after('mpn');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'gtin')) {
                $table->dropColumn('gtin');
            }
            if (Schema::hasColumn('products', 'mpn')) {
                $table->dropColumn('mpn');
            }
            if (Schema::hasColumn('products', 'google_category_id')) {
                $table->dropColumn('google_category_id');
            }
        });
    }
};
