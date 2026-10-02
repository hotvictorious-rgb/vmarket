<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_men', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable()->after('seller_id');
            }
            if (!Schema::hasColumn('delivery_men', 'state_id')) {
                $table->unsignedBigInteger('state_id')->nullable()->after('country_id');
            }
            if (!Schema::hasColumn('delivery_men', 'lga_id')) {
                $table->unsignedBigInteger('lga_id')->nullable()->after('state_id');
            }

            if (Schema::hasTable('countries')) {
                $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            }
            if (Schema::hasTable('states')) {
                $table->foreign('state_id')->references('id')->on('states')->nullOnDelete();
            }
            if (Schema::hasTable('lgas')) {
                $table->foreign('lga_id')->references('id')->on('lgas')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['lga_id']);
            
            $table->dropColumn(['country_id', 'state_id', 'lga_id']);
        });
    }
};
