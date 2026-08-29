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
        Schema::table('shops', function (Blueprint $table) {
            $table->string('country')->default('Nigeria')->nullable()->after('contact');
            $table->unsignedBigInteger('state_id')->nullable()->after('country');
            $table->unsignedBigInteger('lga_id')->nullable()->after('state_id');
            $table->unsignedBigInteger('hub_id')->nullable()->after('lga_id');

            // [AI] Setup foreign key constraints referencing the delivery location tables
            $table->foreign('state_id')->references('id')->on('delivery_states')->onDelete('set null');
            $table->foreign('lga_id')->references('id')->on('delivery_cities')->onDelete('set null');
            $table->foreign('hub_id')->references('id')->on('delivery_hubs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // [AI] Safely drop foreign key constraints first
            try {
                $table->dropForeign(['state_id']);
                $table->dropForeign(['lga_id']);
                $table->dropForeign(['hub_id']);
            } catch (\Exception $e) {}

            $table->dropColumn(['country', 'state_id', 'lga_id', 'hub_id']);
        });
    }
};
