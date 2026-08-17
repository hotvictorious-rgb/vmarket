<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $settings = [
            [
                'type' => 'loyalty_point_max_order_redemption_percentage',
                'value' => '10',
            ],
            [
                'type' => 'ref_earning_min_order_amount',
                'value' => '5000',
            ]
        ];

        foreach ($settings as $setting) {
            $exists = DB::table('business_settings')->where('type', $setting['type'])->exists();
            if (!$exists) {
                DB::table('business_settings')->insert([
                    'type' => $setting['type'],
                    'value' => $setting['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('business_settings')
            ->whereIn('type', ['loyalty_point_max_order_redemption_percentage', 'ref_earning_min_order_amount'])
            ->delete();
    }
};
