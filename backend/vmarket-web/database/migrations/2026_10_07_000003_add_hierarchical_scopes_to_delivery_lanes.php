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
        Schema::table('delivery_lanes', function (Blueprint $table) {
            // Make LGA columns nullable to support State-to-State nationwide lanes
            if (Schema::hasColumn('delivery_lanes', 'origin_lga_id')) {
                $table->unsignedBigInteger('origin_lga_id')->nullable()->change();
            }
            if (Schema::hasColumn('delivery_lanes', 'destination_lga_id')) {
                $table->unsignedBigInteger('destination_lga_id')->nullable()->change();
            }

            // Add lane_type: intra_state (LGA to LGA) vs inter_state (State to State)
            if (!Schema::hasColumn('delivery_lanes', 'lane_type')) {
                $table->string('lane_type', 30)->default('intra_state')->index()->after('destination_lga_id');
            }

            // Add compound index for fast State-to-State routing resolution
            $table->index(['origin_state_id', 'destination_state_id', 'lane_type'], 'lanes_state_routing_idx');
        });

        // Seed default fallback rate settings in business_settings
        $defaultSettings = [
            [
                'type' => 'default_intrastate_delivery_fee',
                'value' => '2000.00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'default_interstate_delivery_fee',
                'value' => '5000.00',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($defaultSettings as $setting) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => $setting['type']],
                $setting
            );
        }

        // Seed initial major Interstate corridors from Akwa Ibom to key hubs
        $akwaIbom = DB::table('states')->where('name', 'Akwa Ibom')->first();
        $nigeria = DB::table('countries')->where('iso_code', 'NGA')->first();

        if ($akwaIbom && $nigeria) {
            $destStates = [
                ['name' => 'Lagos', 'fee' => 4500.00, 'eta' => '2-3 business days'],
                ['name' => 'Rivers', 'fee' => 3500.00, 'eta' => '1-2 business days'],
                ['name' => 'Federal Capital Territory', 'fee' => 5000.00, 'eta' => '2-4 business days'],
                ['name' => 'Cross River', 'fee' => 3000.00, 'eta' => '1-2 business days'],
                ['name' => 'Abia', 'fee' => 3000.00, 'eta' => '1-2 business days'],
                ['name' => 'Enugu', 'fee' => 3500.00, 'eta' => '2-3 business days'],
                ['name' => 'Oyo', 'fee' => 5000.00, 'eta' => '3-5 business days'],
                ['name' => 'Kano', 'fee' => 6000.00, 'eta' => '3-5 business days'],
            ];

            foreach ($destStates as $target) {
                $targetState = DB::table('states')->where('name', $target['name'])->first();
                if ($targetState) {
                    DB::table('delivery_lanes')->updateOrInsert(
                        [
                            'origin_state_id' => $akwaIbom->id,
                            'destination_state_id' => $targetState->id,
                            'lane_type' => 'inter_state',
                        ],
                        [
                            'origin_country_id' => $nigeria->id,
                            'origin_lga_id' => null,
                            'destination_country_id' => $nigeria->id,
                            'destination_lga_id' => null,
                            'lane_type' => 'inter_state',
                            'delivery_fee' => $target['fee'],
                            'estimated_delivery_time' => $target['eta'],
                            'is_enabled' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_lanes', function (Blueprint $table) {
            $table->dropIndex('lanes_state_routing_idx');
            if (Schema::hasColumn('delivery_lanes', 'lane_type')) {
                $table->dropColumn('lane_type');
            }
        });

        DB::table('business_settings')->whereIn('type', [
            'default_intrastate_delivery_fee',
            'default_interstate_delivery_fee',
        ])->delete();
    }
};
