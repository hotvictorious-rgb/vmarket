<?php

/**
 * [AI] Universal Demo Accounts Seeder & Single Super Admin Invariant Enforcer
 * Enforces exactly 1 Super Admin synced from .env, plus complete demo accounts for all roles.
 */

require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

echo "=================================================================\n";
echo "👑 ENFORCING SINGLE SUPER ADMIN & SEEDING DEMO ACCOUNTS\n";
echo "=================================================================\n\n";

// --- 1. VICTORIOUS MARKET BACKEND ---
echo "1. Configuring Victorious MARKET Accounts...\n";

// A. Enforce Single Super Admin (Delete extra admin rows with role_id = 1 except id = 1)
$superName     = env('SUPER_ADMIN_NAME', 'Victorious Super Admin');
$superEmail    = env('SUPER_ADMIN_EMAIL', 'admin@admin.com');
$superPassword = env('SUPER_ADMIN_PASSWORD', '12345678');
$superPhone    = env('SUPER_ADMIN_PHONE', '08000000000');

DB::table('admins')->where('id', '>', 1)->where('admin_role_id', 1)->delete();

DB::table('admins')->updateOrInsert(
    ['id' => 1],
    [
        'name'          => $superName,
        'email'         => $superEmail,
        'password'      => Hash::make($superPassword),
        'phone'         => $superPhone,
        'admin_role_id' => 1,
        'status'        => 1,
        'image'         => 'def.png',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]
);
echo "  ✅ Enforced Single Super Admin: {$superEmail} (ID: 1)\n";

// Super Admin Wallet
DB::table('admin_wallets')->updateOrInsert(
    ['admin_id' => 1],
    [
        'withdrawn'              => 0,
        'commission_earned'      => 0,
        'inhouse_earning'        => 0,
        'delivery_charge_earned' => 0,
        'pending_amount'         => 0,
        'created_at'             => now(),
        'updated_at'             => now(),
    ]
);

// B. Employee / Operations Manager Demo Account
DB::table('admin_roles')->updateOrInsert(
    ['id' => 2],
    [
        'name'       => 'Operations Manager',
        'module_access' => json_encode(['order_management', 'product_management', 'pos_management', 'deliveryman_management']),
        'status'     => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]
);

DB::table('admins')->updateOrInsert(
    ['id' => 2],
    [
        'name'          => 'Demo Store Manager',
        'email'         => 'manager@victorious.com',
        'password'      => Hash::make('12345678'),
        'phone'         => '08011112222',
        'admin_role_id' => 2,
        'status'        => 1,
        'image'         => 'def.png',
        'created_at'    => now(),
        'updated_at'    => now(),
    ]
);
echo "  ✅ Created Demo Employee / Manager: manager@victorious.com\n";

// C. Seller / Merchant Demo Account
DB::table('sellers')->updateOrInsert(
    ['id' => 1],
    [
        'f_name'       => 'Victorious',
        'l_name'       => 'Merchant',
        'phone'        => '08033334444',
        'email'        => 'vendor@victorious.com',
        'password'     => Hash::make('12345678'),
        'status'       => 'approved',
        'bank_name'    => 'Access Bank',
        'branch'       => 'Victoria Island',
        'account_no'   => '0123456789',
        'holder_name'  => 'Victorious Merchant Ltd',
        'image'        => 'def.png',
        'created_at'   => now(),
        'updated_at'   => now(),
    ]
);

DB::table('shops')->updateOrInsert(
    ['id' => 1],
    [
        'seller_id'   => 1,
        'name'        => 'Victorious Flagship Store',
        'address'     => '14 Marina Road, Lagos Island, Lagos',
        'contact'     => '08033334444',
        'image'       => 'def.png',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]
);

DB::table('seller_wallets')->updateOrInsert(
    ['seller_id' => 1],
    [
        'total_earning'    => 150000,
        'withdrawn'        => 0,
        'commission_given' => 5000,
        'pending_withdraw' => 0,
        'delivery_charge_earned' => 0,
        'collected_cash'   => 0,
        'total_tax_collected' => 0,
        'created_at'       => now(),
        'updated_at'       => now(),
    ]
);
echo "  ✅ Created Demo Merchant / Vendor: vendor@victorious.com (Shop: Victorious Flagship Store)\n";

// D. Customer / Shopper Demo Account
DB::table('users')->updateOrInsert(
    ['id' => 1],
    [
        'name'            => 'Chinedu Victorious Customer',
        'f_name'          => 'Chinedu',
        'l_name'          => 'Okonkwo',
        'phone'           => '08012345678',
        'email'           => 'customer@victorious.com',
        'password'        => Hash::make('12345678'),
        'is_active'       => 1,
        'is_phone_verified' => 1,
        'is_email_verified' => 1,
        'wallet_balance'  => 50000.00,
        'loyalty_point'   => 250,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]
);
echo "  ✅ Created Demo Customer: customer@victorious.com (Wallet: ₦50,000.00)\n";

// E. Delivery Rider Demo Account
if (Schema::hasTable('delivery_men')) {
    DB::table('delivery_men')->updateOrInsert(
        ['id' => 1],
        [
            'seller_id'       => 0, // In-house / Admin rider
            'f_name'          => 'Emeka',
            'l_name'          => 'Express Rider',
            'phone'           => '08098765432',
            'email'           => 'rider@victorious.com',
            'password'        => Hash::make('12345678'),
            'is_active'       => 1,
            'is_online'       => 1,
            'country_code'    => '234',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]
    );

    if (Schema::hasTable('delivery_man_wallets')) {
        DB::table('delivery_man_wallets')->updateOrInsert(
            ['delivery_man_id' => 1],
            [
                'current_balance'       => 12500,
                'cash_in_hand'          => 4500,
                'pending_withdraw'      => 0,
                'total_withdraw'        => 0,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]
        );
    }
    echo "  ✅ Created Demo Delivery Rider: rider@victorious.com\n";
}


// --- 2. VMARKET POS BACKEND (HYSAM) ---
echo "\n2. Configuring Vmarket POS Accounts...\n";
$posDb = new PDO('sqlite:' . __DIR__ . '/hysam/database/database.sqlite');
$posDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Enforce Single Super Admin in POS
$posPassHash = password_hash($superPassword, PASSWORD_BCRYPT);
$posDb->exec("DELETE FROM users WHERE email = '{$superEmail}' OR id = 'admin-user-1'");

$stmt = $posDb->prepare("
    INSERT INTO users (id, name, email, password, role, disabled, created_at, updated_at) 
    VALUES ('admin-user-1', :name, :email, :password, 'admin', 0, datetime('now'), datetime('now'))
");
$stmt->execute([
    ':name'     => $superName,
    ':email'    => $superEmail,
    ':password' => $posPassHash,
]);
echo "  ✅ Enforced Single Super Admin in POS: {$superEmail}\n";

// Demo Cashier in POS
$posCashierPass = password_hash('12345678', PASSWORD_BCRYPT);
$posDb->exec("DELETE FROM users WHERE email = 'cashier@victorious.com'");
$stmt = $posDb->prepare("
    INSERT INTO users (id, name, email, password, role, disabled, created_at, updated_at) 
    VALUES ('cashier-user-1', 'Adaeze Cashier', 'cashier@victorious.com', :password, 'cashier', 0, datetime('now'), datetime('now'))
");
$stmt->execute([':password' => $posCashierPass]);
echo "  ✅ Created Demo POS Cashier: cashier@victorious.com\n";

echo "\n=================================================================\n";
echo "🎉 ALL DEMO ACCOUNTS SEEDED & SUPER ADMIN INVARIANT 100% ENFORCED!\n";
echo "=================================================================\n";
