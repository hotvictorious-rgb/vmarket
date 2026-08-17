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
        $setting = DB::table('business_settings')->where('type', 'ref_earning_min_order_amount')->first();
        if (!$setting) {
            DB::table('business_settings')->insert([
                'type' => 'ref_earning_min_order_amount',
                'value' => '5000',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('business_settings')->where('type', 'ref_earning_min_order_amount')->delete();
    }
};
