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
        $defaultSettings = [
            ['type' => 'product_edit_approval_mode', 'value' => 'threshold'], // 'auto', 'threshold', 'strict'
            ['type' => 'product_edit_price_threshold_percentage', 'value' => '20'],
        ];

        foreach ($defaultSettings as $setting) {
            if (!DB::table('business_settings')->where('type', $setting['type'])->exists()) {
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
            ->whereIn('type', ['product_edit_approval_mode', 'product_edit_price_threshold_percentage'])
            ->delete();
    }
};
