<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "Seeding SQLite core business settings...\n";

$settings = [
    'company_name' => 'Victorious MARKET',
    'company_email' => 'support@victoriousmarket.com.ng',
    'company_phone' => '+234800000000',
    'company_web_logo' => 'def.png',
    'company_mobile_logo' => 'def.png',
    'company_footer_logo' => 'def.png',
    'company_fav_icon' => 'def.png',
    'currency_symbol_position' => 'left',
    'system_default_currency' => 1,
    'language' => json_encode([['id' => 1, 'name' => 'English', 'code' => 'en', 'status' => 1, 'default' => true, 'direction' => 'ltr']]),
    'colors' => json_encode(['primary' => '#5e2e85', 'secondary' => '#f1c40f']),
    'pagination_limit' => '15',
    'shop_status' => '1',
    'seller_registration' => '1',
    'delivery_boy_registration' => '1',
    'maintenance_mode' => '0',
    'decimal_point_settings' => '2',
    'theme_name' => 'theme_aster',
    'admin_login_url' => 'admin',
    'employee_login_url' => 'admin',
    'mail_config' => json_encode(['status' => 0]),
    'recaptcha' => json_encode(['status' => 0, 'site_key' => '', 'secret_key' => '']),
    'firebase_otp_verification' => json_encode(['status' => 0, 'web_api_key' => '']),
    'announcement' => json_encode(['status' => 0, 'color' => '#5e2e85', 'text_color' => '#ffffff', 'announcement' => '']),
    'social_login' => json_encode([]),
    'apple_login' => json_encode([]),
    'currency_model' => 'single_currency',
    'product_brand' => '1',
    'digital_product' => '1',
];

foreach ($settings as $type => $value) {
    DB::table('business_settings')->updateOrInsert(
        ['type' => $type],
        ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
    );
}

// Ensure at least 1 currency exists
DB::table('currencies')->updateOrInsert(
    ['id' => 1],
    [
        'name' => 'Naira',
        'symbol' => '₦',
        'code' => 'NGN',
        'exchange_rate' => 1,
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]
);

// Ensure default Admin exists
DB::table('admins')->updateOrInsert(
    ['email' => 'admin@admin.com'],
    [
        'name' => 'Super Admin',
        'phone' => '+234800000000',
        'password' => Hash::make('12345678'),
        'admin_role_id' => 1,
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]
);

echo "✅ SQLite core business settings and Super Admin seeded successfully!\n";
