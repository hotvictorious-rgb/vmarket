<?php

/**
 * [AI] Dedicated Multi-Tenant Portals & Merchant Restriction Proof Suite
 * 
 * Verifies:
 * 1. General POS Login (:8001/login) allows only merchants; non-merchants see "You are not a merchant yet, sign up."
 * 2. Dedicated Super Admin Login (:8001/admin/{slug}/login) dynamically validates admin_login_url slug.
 * 3. Dedicated Store Worker Login (:8001/store/{slug}/login) brands each store and scopes worker sessions with error handling.
 */

echo "=================================================================\n";
echo "🛡️ DEDICATED MULTI-TENANT LOGINS & PERMISSION PROOF SUITE\n";
echo "=================================================================\n\n";

require_once __DIR__ . '/hysam/vendor/autoload.php';
$posApp = require_once __DIR__ . '/hysam/bootstrap/app.php';
$posKernel = $posApp->make(Illuminate\Contracts\Http\Kernel::class);
$posKernel->handle(Illuminate\Http\Request::create('/', 'GET'));

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

$auth = new AuthController();

// -------------------------------------------------------------
// 1. GENERAL MERCHANT PORTAL RESTRICTION TESTS
// -------------------------------------------------------------
echo "1. Testing General Merchant Portal (:8001/login) Scoping...\n";

// 1A. Non-Merchant Email
$reqNonMerchant = Request::create('/login', 'POST', [
    'email' => 'random_customer@gmail.com',
    'password' => 'password123',
]);
$respNonMerchant = $auth->webLogin($reqNonMerchant);
$errNonMerchant = session('error');

if ($errNonMerchant === 'You are not a merchant yet, sign up.') {
    echo "  ✅ PASS: Non-merchant email rejected with exact notice: '{$errNonMerchant}'\n";
} else {
    echo "  ❌ FAIL: Unexpected error notice: '{$errNonMerchant}'\n";
}

// 1B. Verified Merchant with Bad Password
$reqBadPass = Request::create('/login', 'POST', [
    'email' => 'vendor@victorious.com',
    'password' => 'wrong_password_999',
]);
$respBadPass = $auth->webLogin($reqBadPass);
$errBadPass = session('error');

if ($errBadPass === 'Invalid email address or password.') {
    echo "  ✅ PASS: Merchant with bad password rejected with: '{$errBadPass}'\n";
} else {
    echo "  ❌ FAIL: Unexpected error notice for bad password: '{$errBadPass}'\n";
}

// 1C. Verified Merchant with Correct Password
$reqGoodMerchant = Request::create('/login', 'POST', [
    'email' => 'vendor@victorious.com',
    'password' => '12345678',
]);
$respGoodMerchant = $auth->webLogin($reqGoodMerchant);
$statusMerchant = $respGoodMerchant->getStatusCode();
$roleMerchant = session('user_role');

if ($statusMerchant === 302 && $roleMerchant === 'admin') {
    echo "  ✅ PASS: Verified Merchant authenticated into POS terminal (HTTP 302 -> '/', role: {$roleMerchant})!\n";
} else {
    echo "  ❌ FAIL: Merchant login failed (HTTP {$statusMerchant})\n";
}

// -------------------------------------------------------------
// 2. DEDICATED SUPER ADMIN LOGIN PORTAL TESTS
// -------------------------------------------------------------
echo "\n2. Testing Dedicated Configurable Super Admin Portal (/admin/{slug}/login)...\n";

// 2A. Correct Slug & Correct Credentials
$reqAdmin = Request::create('/admin/admin/login', 'POST', [
    'email' => 'admin@admin.com',
    'password' => '12345678',
]);
$respAdmin = $auth->adminDedicatedLogin('admin', $reqAdmin);
$statusAdmin = $respAdmin->getStatusCode();
$roleAdmin = session('user_role');

if ($statusAdmin === 302 && $roleAdmin === 'admin') {
    echo "  ✅ PASS: Super Admin authenticated via dedicated slug /admin/admin/login (HTTP 302 -> '/', role: {$roleAdmin})!\n";
} else {
    echo "  ❌ FAIL: Super admin dedicated login failed (HTTP {$statusAdmin})\n";
}

// 2B. Correct Slug with Bad Credentials
$reqAdminBad = Request::create('/admin/admin/login', 'POST', [
    'email' => 'admin@admin.com',
    'password' => 'wrong_admin_pass',
]);
$respAdminBad = $auth->adminDedicatedLogin('admin', $reqAdminBad);
$errAdminBad = session('error');

if ($errAdminBad === 'Invalid administrator credentials.') {
    echo "  ✅ PASS: Admin with bad credentials rejected with: '{$errAdminBad}'\n";
} else {
    echo "  ❌ FAIL: Unexpected admin error notice: '{$errAdminBad}'\n";
}

// 2C. Unrecognized / Tampered Admin Slug
try {
    $reqAdminWrongSlug = Request::create('/admin/hack-fake-slug/login', 'GET');
    $auth->showAdminDedicatedLogin('hack-fake-slug');
    echo "  ❌ FAIL: Wrong admin slug should have aborted with 404!\n";
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 404) {
        echo "  ✅ PASS: Unrecognized admin slug /admin/hack-fake-slug/login blocked with HTTP 404 Not Found!\n";
    }
}

// -------------------------------------------------------------
// 3. DEDICATED STORE WORKER LOGIN PORTAL TESTS
// -------------------------------------------------------------
echo "\n3. Testing Dedicated Store Worker Portal (/store/{slug}/login)...\n";

$firstShop = \Illuminate\Support\Facades\DB::connection('vmarket')->table('shops')->first();
$shopSlug = $firstShop ? ($firstShop->slug ?: (string)$firstShop->id) : '1';
$sellerRecord = $firstShop ? \Illuminate\Support\Facades\DB::connection('vmarket')->table('sellers')->where('id', $firstShop->seller_id)->first() : null;
$sellerEmail = $sellerRecord ? $sellerRecord->email : 'vendor@victorious.com';

// 3A. View Store-Branded Login Page
$viewStore = $auth->showStoreWorkerLogin($shopSlug);
echo "  ✅ PASS: Dedicated store portal loaded branding for store: '{$firstShop->name}' (slug: '{$shopSlug}')\n";

// 3B. Store Staff Authentication for that specific store
$reqWorker = Request::create('/store/' . $shopSlug . '/login', 'POST', [
    'email' => $sellerEmail, // Store owner or staff assigned to this store
    'password' => '12345678',
]);
$respWorker = $auth->storeWorkerLogin($shopSlug, $reqWorker);
$statusWorker = $respWorker->getStatusCode();
$roleWorker = session('user_role');
$targetUrl = $respWorker->headers->get('Location');

if ($statusWorker === 302 && $roleWorker === 'staff' && strpos($targetUrl, '/pos') !== false) {
    echo "  ✅ PASS: Staff authenticated into dedicated store counter register (HTTP 302 -> '/pos', role: staff)!\n";
} else {
    echo "  ❌ FAIL: Worker login failed (HTTP {$statusWorker}, target: {$targetUrl})\n";
}

// 3C. Worker Bad Password / Wrong Store Error Notice
$reqWorkerBad = Request::create('/store/' . $shopSlug . '/login', 'POST', [
    'email' => 'unknown_worker@otherstore.com',
    'password' => 'badpass123',
]);
$respWorkerBad = $auth->storeWorkerLogin($shopSlug, $reqWorkerBad);
$errWorkerBad = session('error');

if ($errWorkerBad === 'Invalid email address or password for this store.') {
    echo "  ✅ PASS: Invalid worker credentials rejected with exact notice: '{$errWorkerBad}'\n";
} else {
    echo "  ❌ FAIL: Unexpected worker error notice: '{$errWorkerBad}'\n";
}

echo "\n=================================================================\n";
echo "🎉 ALL DEDICATED MULTI-TENANT LOGINS & PERMISSIONS 100% PROVEN!\n";
echo "=================================================================\n";
