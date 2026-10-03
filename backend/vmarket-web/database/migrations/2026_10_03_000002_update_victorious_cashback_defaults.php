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
        ];

        foreach ($settings as $type => $value) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => $type],
                ['value' => $value, 'updated_at' => now()]
            );
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
