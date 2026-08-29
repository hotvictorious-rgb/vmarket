<?php
require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\AdminRole;
use App\Models\Admin;

echo "=== 1. STANDARDIZING PREDEFINED ADMIN ROLES ===\n";

$predefinedAdminRoles = [
    1 => [
        'name' => 'Super Admin',
        'module_access' => json_encode(['all']),
        'status' => 1
    ],
    2 => [
        'name' => 'Operations & Store Manager',
        'module_access' => json_encode(['order_management', 'product_management', 'pos_management', 'deliveryman_management']),
        'status' => 1
    ],
    3 => [
        'name' => 'Product Moderator',
        'module_access' => json_encode(['product_management', 'promotion_management']),
        'status' => 1
    ],
    4 => [
        'name' => 'Finance Controller & Auditor',
        'module_access' => json_encode(['report', 'refund_section', 'user_section']),
        'status' => 1
    ],
    5 => [
        'name' => 'Customer Support Specialist',
        'module_access' => json_encode(['support_section', 'chatting_section']),
        'status' => 1
    ]
];

foreach ($predefinedAdminRoles as $id => $data) {
    DB::table('admin_roles')->updateOrInsert(
        ['id' => $id],
        array_merge($data, [
            'created_at' => now(),
            'updated_at' => now()
        ])
    );
    echo "  -> Predefined Admin Role #{$id}: {$data['name']} (ACTIVE)\n";
}

// Clean up any extra admin roles (> 5)
DB::table('admin_roles')->where('id', '>', 5)->delete();

echo "\n=== 2. ENFORCING STRICTLY 1 SUPER ADMIN INVARIANT ===\n";

// Only Admin ID 1 is allowed to have admin_role_id = 1
$superAdminCount = Admin::where('admin_role_id', 1)->count();
echo "  Current Super Admins count: {$superAdminCount}\n";

// Update any non-primary admin to staff roles
Admin::where('id', '!=', 1)->where('admin_role_id', 1)->update(['admin_role_id' => 2]);

$verifiedSuperAdmins = Admin::where('admin_role_id', 1)->get();
echo "  Verified Super Admin: ID {$verifiedSuperAdmins->first()->id} | {$verifiedSuperAdmins->first()->name} ({$verifiedSuperAdmins->first()->email})\n";
echo "  Total Super Admins remaining: " . $verifiedSuperAdmins->count() . " (STRICTLY 1)\n";

echo "\n=== 3. STANDARDIZING PREDEFINED VENDOR EMPLOYEE ROLES ===\n";

if (\Illuminate\Support\Facades\Schema::hasTable('vendor_roles')) {
    $predefinedVendorRoles = [
        ['name' => 'Store Manager', 'module_access' => json_encode(['order', 'product', 'pos', 'deliveryman', 'report']), 'status' => 1],
        ['name' => 'Counter Cashier', 'module_access' => json_encode(['pos', 'order']), 'status' => 1],
        ['name' => 'Storekeeper & Inventory Clerk', 'module_access' => json_encode(['product', 'pos']), 'status' => 1],
    ];

    $sellers = DB::table('sellers')->get();
    foreach ($sellers as $s) {
        foreach ($predefinedVendorRoles as $vdata) {
            DB::table('vendor_roles')->updateOrInsert(
                [
                    'seller_id' => $s->id,
                    'name' => $vdata['name']
                ],
                array_merge($vdata, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
        echo "  -> Predefined Vendor Roles configured for Seller #{$s->id}\n";
    }
}

echo "\n🎉 PREDEFINED ECOSYSTEM ROLES SYNCHRONIZED SUCCESSFULLY!\n";
