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
            'loyalty_point_status' => '1',
            'loyalty_point_max_order_redemption_percentage' => '100',
            'loyalty_point_exchange_rate' => '1',
            'loyalty_point_minimum_point' => '0',
            'loyalty_point_earn_rate_percent' => '5.00',
            'loyalty_point_validity_months' => '6',
        ];

        foreach ($settings as $type => $value) {
            $existing = DB::table('business_settings')->where('type', $type)->first();
            if (!$existing) {
                DB::table('business_settings')->insert([
                    'type' => $type,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($type === 'loyalty_point_max_order_redemption_percentage' && (string)$existing->value === '10') {
                DB::table('business_settings')->where('type', $type)->update(['value' => '100', 'updated_at' => now()]);
            } elseif ($type === 'loyalty_point_exchange_rate' && (empty($existing->value) || (string)$existing->value === '0')) {
                // Only initialize if exchange rate was unset or zero, preserving existing valuations
                DB::table('business_settings')->where('type', $type)->update(['value' => '1', 'updated_at' => now()]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to stock defaults if needed
        DB::table('business_settings')->where('type', 'loyalty_point_max_order_redemption_percentage')->update(['value' => '10']);
    }
};
