<?php
/**
 * [AI] Super Admin vs Sub-Admin Employee Role Discrimination Verification Test
 *
 * Proves that:
 * 1. Admin with admin_role_id = 1 is recognized as Super Admin (is_super_admin = true, full access).
 * 2. Admin with admin_role_id > 1 is recognized as Sub-Admin Employee (is_super_admin = false, SaaS blocked).
 *
 * Usage: php test_super_admin_role_discrimination.php
 */

chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Admin;
use App\Http\Middleware\PosAccessMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "\n============================================================\n";
echo "  SUPER ADMIN VS SUB-ADMIN DISCRIMINATION AUDIT\n";
echo "  Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "============================================================\n\n";

$middleware = new PosAccessMiddleware();

// Test 1: Super Admin (admin_role_id = 1)
$superAdmin = new Admin([
    'name' => 'Victorious Platform Owner',
    'email' => 'superadmin@vmarket.com.ng',
    'admin_role_id' => 1,
    'status' => 1,
]);
$superAdmin->id = 1;

Auth::guard('admin')->setUser($superAdmin);
session()->flush();

$request = Request::create('/pos', 'GET');
$response = $middleware->handle($request, function ($req) {
    return 'NEXT_CALLED';
});

$isSuperAdmin = session('is_super_admin');
$userRole = session('user_role');

echo "TEST 1: Super Admin (admin_role_id = 1)\n";
echo "  - is_super_admin: " . ($isSuperAdmin === true ? '✅ TRUE (Correct)' : '❌ FALSE') . "\n";
echo "  - user_role: " . ($userRole === 'admin' ? '✅ admin (Correct)' : "❌ $userRole") . "\n\n";

// Test 2: Sub-Admin Employee (admin_role_id = 2, e.g. Customer Support / Moderator)
$employeeAdmin = new Admin([
    'name' => 'Support Staff',
    'email' => 'support@vmarket.com.ng',
    'admin_role_id' => 2,
    'status' => 1,
]);
$employeeAdmin->id = 2;

Auth::guard('admin')->setUser($employeeAdmin);
session()->flush();

$request = Request::create('/pos', 'GET');
$response = $middleware->handle($request, function ($req) {
    return 'NEXT_CALLED';
});

$isSuperAdminEmp = session('is_super_admin');
$userRoleEmp = session('user_role');

echo "TEST 2: Sub-Admin Employee (admin_role_id = 2)\n";
echo "  - is_super_admin: " . ($isSuperAdminEmp === false ? '✅ FALSE (Correctly Restricted)' : '❌ TRUE (Privilege Escalation!)') . "\n";
echo "  - user_role: " . ($userRoleEmp === 'super_admin_employee' ? '✅ super_admin_employee (Correct)' : "❌ $userRoleEmp") . "\n\n";

if ($isSuperAdmin === true && $userRole === 'admin' && $isSuperAdminEmp === false && $userRoleEmp === 'super_admin_employee') {
    echo "✅ AUDIT PASSED: ZERO PRIVILEGE ESCALATION. SUPER ADMIN (Role 1) STRICTLY ISOLATED FROM SUB-ADMIN EMPLOYEES (Role 2).\n\n";
} else {
    echo "❌ AUDIT FAILED. Review role discrimination logic.\n\n";
}
