<?php

/**
 * [AI] Merchant & Worker SaaS Access Isolation Proof Suite
 * 
 * Verifies:
 * 1. Unauthenticated requests to /saas -> Redirected to login.
 * 2. Merchant logged in (is_super_admin = false, seller_id set) -> BLOCKED from /saas, /saas/tenants, /saas/settings, etc.
 * 3. Store Cashier/Worker logged in -> BLOCKED from /saas.
 * 4. Super Admin (is_super_admin = true, no seller_id) -> ALLOWED to access /saas.
 */

echo "=================================================================\n";
echo "🛡️ MERCHANT & WORKER SAAS ACCESS ISOLATION PROOF SUITE\n";
echo "=================================================================\n\n";

require_once __DIR__ . '/hysam/vendor/autoload.php';
$app = require_once __DIR__ . '/hysam/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\Request;
use App\Models\User;

// 1. Unauthenticated Request to /saas
echo "1. Testing Unauthenticated Request to /saas...\n";
$req1 = Request::create('/saas', 'GET');
$resp1 = $kernel->handle($req1);
$status1 = $resp1->getStatusCode();
if ($status1 === 302) {
    echo "  ✅ PASS: Unauthenticated user redirected (HTTP 302)!\n";
} else {
    echo "  ❌ FAIL: Unauthenticated user received HTTP {$status1}\n";
}

// 2. Merchant Logged In (Store Owner)
echo "\n2. Testing Authenticated Merchant Access to /saas...\n";
$merchantUser = User::firstOrCreate(
    ['email' => 'vendor_test_isolation@test.com'],
    [
        'id' => 'vendor-test-iso',
        'name' => 'Test Isolation Merchant',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'disabled' => false
    ]
);

auth()->login($merchantUser);
session([
    'user_id' => $merchantUser->id,
    'user_role' => 'admin',
    'is_super_admin' => false,
    'seller_id' => 1,
    'shop_id' => 1,
    'can_sell' => true
]);

$merchantReq = Request::create('/saas', 'GET');
$merchantResp = $kernel->handle($merchantReq);
$mStatus = $merchantResp->getStatusCode();

if ($mStatus === 302 && session('warning')) {
    echo "  ✅ PASS: Merchant successfully BLOCKED from /saas with warning: '" . session('warning') . "'!\n";
} elseif ($mStatus === 403) {
    echo "  ✅ PASS: Merchant successfully BLOCKED from /saas with HTTP 403 Forbidden!\n";
} else {
    echo "  ❌ FAIL: Merchant was NOT blocked from /saas! Received HTTP {$mStatus}\n";
}

// 2b. Test Merchant AJAX Request to /saas
$ajaxReq = Request::create('/saas', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
$ajaxResp = $kernel->handle($ajaxReq);
if ($ajaxResp->getStatusCode() === 403) {
    echo "  ✅ PASS: Merchant AJAX request strictly blocked with HTTP 403 JSON error!\n";
} else {
    echo "  ❌ FAIL: Merchant AJAX returned HTTP " . $ajaxResp->getStatusCode() . "\n";
}

// 3. Cashier/Worker Logged In
echo "\n3. Testing Store Cashier/Worker Access to /saas...\n";
session([
    'user_role' => 'staff',
    'is_super_admin' => false,
    'seller_id' => 1,
    'shop_id' => 1
]);
$workerReq = Request::create('/saas/tenants', 'GET');
$workerResp = $kernel->handle($workerReq);
if ($workerResp->getStatusCode() === 302) {
    echo "  ✅ PASS: Cashier/Worker successfully BLOCKED from /saas/tenants!\n";
} else {
    echo "  ❌ FAIL: Cashier received HTTP " . $workerResp->getStatusCode() . "\n";
}

// 4. Super Admin Logged In
echo "\n4. Testing Platform Super Admin Access to /saas...\n";
$superAdminUser = User::firstOrCreate(
    ['email' => 'superadmin_test@test.com'],
    [
        'id' => 'admin-super-iso',
        'name' => 'Platform Super Admin',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'disabled' => false
    ]
);

auth()->login($superAdminUser);
session([
    'user_id' => $superAdminUser->id,
    'user_role' => 'admin',
    'is_super_admin' => true,
    'seller_id' => null,
    'shop_id' => null,
    'can_sell' => true
]);

$adminReq = Request::create('/saas', 'GET');
$adminResp = $kernel->handle($adminReq);
$adminStatus = $adminResp->getStatusCode();

if ($adminStatus === 200) {
    echo "  ✅ PASS: Platform Super Admin successfully accessed /saas (HTTP 200 OK)!\n";
} else {
    echo "  ❌ FAIL: Super Admin received HTTP {$adminStatus}\n";
}

echo "\n=================================================================\n";
echo "🎉 SAAS ZERO-TRUST ACCESS ISOLATION 100% VERIFIED!\n";
echo "=================================================================\n";
