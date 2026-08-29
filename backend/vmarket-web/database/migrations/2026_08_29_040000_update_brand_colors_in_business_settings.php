<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $brandColors = [
            'primary' => '#5E17EB',
            'secondary' => '#FFD700',
            'primary_light' => '#7B39FD',
            'panel-sidebar' => '#5E17EB',
        ];

        // 1. Update or Insert 'colors' in business_settings
        DB::table('business_settings')->updateOrInsert(
            ['type' => 'colors'],
            [
                'value' => json_encode($brandColors),
                'updated_at' => now()
            ]
        );

        // 2. Update or Insert 'primary_color'
        DB::table('business_settings')->updateOrInsert(
            ['type' => 'primary_color'],
            [
                'value' => '#5E17EB',
                'updated_at' => now()
            ]
        );

        // 3. Update or Insert 'secondary_color'
        DB::table('business_settings')->updateOrInsert(
            ['type' => 'secondary_color'],
            [
                'value' => '#FFD700',
                'updated_at' => now()
            ]
        );

        // 4. Update or Insert 'primary_color_light'
        DB::table('business_settings')->updateOrInsert(
            ['type' => 'primary_color_light'],
            [
                'value' => '#7B39FD',
                'updated_at' => now()
            ]
        );

        // 5. Update announcement default color if exists
        $announcement = DB::table('business_settings')->where('type', 'announcement')->first();
        if ($announcement && !empty($announcement->value)) {
            $data = json_decode($announcement->value, true);
            if (is_array($data)) {
                $data['color'] = '#5E17EB';
                $data['text_color'] = '#ffffff';
                DB::table('business_settings')->where('type', 'announcement')->update([
                    'value' => json_encode($data),
                    'updated_at' => now()
                ]);
            }
        }

        // 6. Invalidate caches
        try {
            Cache::forget('colors');
            Cache::forget('primary_color');
            Cache::forget('secondary_color');
            Cache::forget('primary_color_light');
            Cache::forget('announcement');
            Cache::flush();
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
