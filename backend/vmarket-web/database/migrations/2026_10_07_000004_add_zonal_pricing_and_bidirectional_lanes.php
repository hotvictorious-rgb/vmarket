<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add is_bidirectional to delivery_lanes
        Schema::table('delivery_lanes', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_lanes', 'is_bidirectional')) {
                $table->boolean('is_bidirectional')->default(true)->after('is_enabled');
            }
        });

        // 2. Seed 3-Tier Zonal Distance Pricing Defaults in business_settings
        $zonalSettings = [
            [
                'type' => 'zone_intra_lga_fee',
                'value' => '1000.00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'zone_intra_lga_eta',
                'value' => '2-4 hours',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'zone_inter_lga_fee',
                'value' => '2500.00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'zone_inter_lga_eta',
                'value' => 'Same day / 24 hours',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'zone_inter_state_fee',
                'value' => '4500.00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'zone_inter_state_eta',
                'value' => '2-4 business days',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($zonalSettings as $setting) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => $setting['type']],
                $setting
            );
        }

        // Set all existing lanes to bidirectional by default
        DB::table('delivery_lanes')->update(['is_bidirectional' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_lanes', function (Blueprint $table) {
            if (Schema::hasColumn('delivery_lanes', 'is_bidirectional')) {
                $table->dropColumn('is_bidirectional');
            }
        });

        DB::table('business_settings')->whereIn('type', [
            'zone_intra_lga_fee',
            'zone_intra_lga_eta',
            'zone_inter_lga_fee',
            'zone_inter_lga_eta',
            'zone_inter_state_fee',
            'zone_inter_state_eta',
        ])->delete();
    }
};
