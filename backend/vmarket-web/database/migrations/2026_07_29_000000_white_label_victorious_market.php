<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add Nigerian Naira (NGN) if not exists
        $nairaId = DB::table('currencies')->insertGetId([
            'name' => 'Nigerian Naira',
            'symbol' => '₦',
            'code' => 'NGN',
            'exchange_rate' => 1500, // standard exchange rate fallback to USD
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Set NGN as the default currency in business_settings
        DB::table('business_settings')->updateOrInsert(
            ['type' => 'system_default_currency'],
            ['value' => $nairaId, 'updated_at' => now()]
        );

        // 2. White-label general settings
        $brandName = 'Victorious MARKET';
        $contactEmail = 'support@victoriousmarket.com';
        $contactPhone = '+2348000000000';
        $contactAddress = 'Lagos, Nigeria';

        DB::table('business_settings')->updateOrInsert(['type' => 'company_name'], ['value' => $brandName, 'updated_at' => now()]);
        DB::table('business_settings')->updateOrInsert(['type' => 'company_email'], ['value' => $contactEmail, 'updated_at' => now()]);
        DB::table('business_settings')->updateOrInsert(['type' => 'company_phone'], ['value' => $contactPhone, 'updated_at' => now()]);
        DB::table('business_settings')->updateOrInsert(['type' => 'company_address'], ['value' => $contactAddress, 'updated_at' => now()]);

        // 3. Dynamic search & replace for "6Valley" references in business_settings
        $settings = DB::table('business_settings')->get();
        foreach ($settings as $setting) {
            if ($setting->value && (
                stripos($setting->value, '6valley') !== false || 
                stripos($setting->value, 'sixvalley') !== false
            )) {
                $newValue = str_ireplace(
                    ['6Valley Multi-Vendor Marketplace', '6Valley Multi Vendor Marketplace', '6Valley', '6valley', 'sixvalley', 'sixValley'],
                    $brandName,
                    $setting->value
                );
                DB::table('business_settings')->where('id', $setting->id)->update(['value' => $newValue]);
            }
        }

        // 4. White-label email templates
        if (Schema::hasTable('email_templates')) {
            $templates = DB::table('email_templates')->get();
            foreach ($templates as $template) {
                $updates = [];
                if ($template->title && (stripos($template->title, '6valley') !== false || stripos($template->title, 'sixvalley') !== false)) {
                    $updates['title'] = str_ireplace(
                        ['6Valley Multi-Vendor Marketplace', '6Valley Multi Vendor Marketplace', '6Valley', '6valley', 'sixvalley', 'sixValley'],
                        $brandName,
                        $template->title
                    );
                }
                if ($template->body && (stripos($template->body, '6valley') !== false || stripos($template->body, 'sixvalley') !== false)) {
                    $updates['body'] = str_ireplace(
                        ['6Valley Multi-Vendor Marketplace', '6Valley Multi Vendor Marketplace', '6Valley', '6valley', 'sixvalley', 'sixValley'],
                        $brandName,
                        $template->body
                    );
                }
                if ($template->footer_text && (stripos($template->footer_text, '6valley') !== false || stripos($template->footer_text, 'sixvalley') !== false)) {
                    $updates['footer_text'] = str_ireplace(
                        ['6Valley Multi-Vendor Marketplace', '6Valley Multi Vendor Marketplace', '6Valley', '6valley', 'sixvalley', 'sixValley'],
                        $brandName,
                        $template->footer_text
                    );
                }
                if ($template->copyright_text && (stripos($template->copyright_text, '6valley') !== false || stripos($template->copyright_text, 'sixvalley') !== false)) {
                    $updates['copyright_text'] = str_ireplace(
                        ['6Valley Multi-Vendor Marketplace', '6Valley Multi Vendor Marketplace', '6Valley', '6valley', 'sixvalley', 'sixValley'],
                        $brandName,
                        $template->copyright_text
                    );
                }
                if (!empty($updates)) {
                    DB::table('email_templates')->where('id', $template->id)->update($updates);
                }
            }
        }

        // 5. White-label push notifications templates
        if (Schema::hasTable('notification_messages')) {
            $notifications = DB::table('notification_messages')->get();
            foreach ($notifications as $notif) {
                if ($notif->message && (stripos($notif->message, '6valley') !== false || stripos($notif->message, 'sixvalley') !== false)) {
                    $newMessage = str_ireplace(
                        ['6Valley Multi-Vendor Marketplace', '6Valley Multi Vendor Marketplace', '6Valley', '6valley', 'sixvalley', 'sixValley'],
                        $brandName,
                        $notif->message
                    );
                    DB::table('notification_messages')->where('id', $notif->id)->update(['message' => $newMessage]);
                }
            }
        }

        // 6. Add "Store Pickup" method in shipping_methods table
        if (Schema::hasTable('shipping_methods')) {
            DB::table('shipping_methods')->insertOrIgnore([
                'creator_id' => 1, // Admin creator ID
                'creator_type' => 'admin',
                'title' => 'Store Pickup',
                'cost' => 0.000000000000,
                'duration' => 'Same day / 1 day',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert default currency and delete NGN if needed
        DB::table('business_settings')->where('type', 'system_default_currency')->update(['value' => 1]); // reset to USD
        DB::table('currencies')->where('code', 'NGN')->delete();
        DB::table('shipping_methods')->where('title', 'Store Pickup')->delete();
    }
};
