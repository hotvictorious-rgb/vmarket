<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $nigerianGateways = [
            [
                'key_name' => 'termii',
                'live_values' => [
                    'gateway' => 'termii',
                    'mode' => 'live',
                    'status' => '0',
                    'api_key' => '',
                    'from' => 'Vmarket',
                    'channel' => 'dnd',
                    'otp_template' => 'Your Victorious MARKET verification code is #OTP#',
                ],
            ],
            [
                'key_name' => 'ebulksms',
                'live_values' => [
                    'gateway' => 'ebulksms',
                    'mode' => 'live',
                    'status' => '0',
                    'username' => '',
                    'api_key' => '',
                    'sender' => 'Vmarket',
                ],
            ],
            [
                'key_name' => 'smart_sms',
                'live_values' => [
                    'gateway' => 'smart_sms',
                    'mode' => 'live',
                    'status' => '0',
                    'api_key' => '',
                    'sender_id' => 'Vmarket',
                    'otp_template' => 'Your Victorious MARKET verification code is #OTP#',
                ],
            ],
            [
                'key_name' => 'kudisms',
                'live_values' => [
                    'gateway' => 'kudisms',
                    'mode' => 'live',
                    'status' => '0',
                    'token' => '',
                    'sender' => 'Vmarket',
                    'otp_template' => 'Your Victorious MARKET verification code is #OTP#',
                ],
            ],
            [
                'key_name' => 'sendchamp',
                'live_values' => [
                    'gateway' => 'sendchamp',
                    'mode' => 'live',
                    'status' => '0',
                    'public_key' => '',
                    'sender_name' => 'Vmarket',
                    'otp_template' => 'Your Victorious MARKET verification code is #OTP#',
                ],
            ],
        ];

        foreach ($nigerianGateways as $gateway) {
            $exists = DB::table('addon_settings')
                ->where('key_name', $gateway['key_name'])
                ->where('settings_type', 'sms_config')
                ->exists();

            if (!$exists) {
                DB::table('addon_settings')->insert([
                    'id' => (string) Str::uuid(),
                    'key_name' => $gateway['key_name'],
                    'live_values' => json_encode($gateway['live_values']),
                    'test_values' => json_encode($gateway['live_values']),
                    'settings_type' => 'sms_config',
                    'mode' => 'live',
                    'is_active' => 0,
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
        DB::table('addon_settings')
            ->whereIn('key_name', ['termii', 'ebulksms', 'smart_sms', 'kudisms', 'sendchamp'])
            ->where('settings_type', 'sms_config')
            ->delete();
    }
};
