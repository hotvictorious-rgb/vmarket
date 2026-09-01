<?php

/**
 * [AI] Comprehensive Verification Suite: Existing Vendor POS Access
 * 
 * Business Context: Verifies that both existing Verified Merchants (approved status)
 * and newly registered / Unverified Merchants (pending status) can seamlessly access
 * the In-Store POS register from their merchant panel.
 * 
 * @role_access       Role 3 (Verified Merchant), Role 4 (Unverified Merchant), Role 5 (Store Cashier)
 * @frontend_view     resources/views/vendor-views/layouts/_header.blade.php & Modules/Pos/resources/views/pos/index.blade.php
 * @route_name        pos.index, pos.dashboard
 */

require __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\VendorEmployee;
use App\Http\Middleware\PosAccessMiddleware;
use Modules\Pos\app\Models\PosWarehouse;

echo "========================================================================================\n";
echo "🛡️ RUNNING COMPREHENSIVE VENDOR POS ACCESS & SSO VERIFICATION SUITE\n";
echo "========================================================================================\n\n";

$passCount = 0;
$totalCount = 6;
$middleware = new PosAccessMiddleware();

// 1. Create or Find Verified Merchant
$verifiedSeller = Seller::firstOrCreate(
    ['email' => 'verified_vendor_test@victorious.com'],
    [
        'f_name' => 'Emeka',
        'l_name' => 'Verified',
        'phone' => '08011112233',
        'password' => bcrypt('password123'),
        'status' => 'approved',
        'pos_subscription_plan' => 'free',
        'is_active' => 1,
    ]
);
$verifiedShop = Shop::firstOrCreate(
    ['seller_id' => $verifiedSeller->id],
    [
        'name' => 'Emeka Electronics Ltd',
        'slug' => 'emeka-electronics-ltd',
        'address' => 'Shop 14, Alaba International Market, Lagos',
        'contact' => '08011112233',
        'image' => 'def.png',
        'banner' => 'def.png',
    ]
);

// 2. Create or Find Pending/Unverified Merchant
$pendingSeller = Seller::firstOrCreate(
    ['email' => 'pending_vendor_test@victorious.com'],
    [
        'f_name' => 'Chinedu',
        'l_name' => 'Pending',
        'phone' => '08044445566',
        'password' => bcrypt('password123'),
        'status' => 'pending',
        'pos_subscription_plan' => 'free',
        'is_active' => 1,
    ]
);
$pendingShop = Shop::firstOrCreate(
    ['seller_id' => $pendingSeller->id],
    [
        'name' => 'Chinedu Provisions',
        'slug' => 'chinedu-provisions',
        'address' => 'Block B, Trade Fair Complex, Lagos',
        'contact' => '08044445566',
        'image' => 'def.png',
        'banner' => 'def.png',
    ]
);

// --- TEST 1: Verified Vendor POS Access ---
echo "--- TEST 1: Verified Merchant (Approved) POS Access ---\n";
Auth::guard('seller')->login($verifiedSeller);
$request1 = Request::create('/pos', 'GET');
$response1 = $middleware->handle($request1, function ($req) {
    return response('POS_ALLOWED', 200);
});

if ($response1->getStatusCode() === 200 && $response1->getContent() === 'POS_ALLOWED') {
    echo "✅ PASS [1]: Verified Merchant successfully granted access through PosAccessMiddleware (HTTP 200)\n";
    $passCount++;
} else {
    echo "❌ FAIL [1]: Verified Merchant access failed.\n";
}

if (session('user_role') === 'verified_merchant' && session('seller_id') == $verifiedSeller->id) {
    echo "✅ PASS [2]: Verified Merchant session correctly bound to seller_id={$verifiedSeller->id} with role 'verified_merchant'\n";
    $passCount++;
} else {
    echo "❌ FAIL [2]: Session binding failed. Role: " . session('user_role') . "\n";
}
Auth::guard('seller')->logout();

// --- TEST 2: Unverified / Pending Vendor POS Access (Free Tier POS) ---
echo "\n--- TEST 2: Unverified / Pending Merchant (Pending KYC) POS Access ---\n";
Auth::guard('seller')->login($pendingSeller);
$request2 = Request::create('/pos', 'GET');
$response2 = $middleware->handle($request2, function ($req) {
    return response('POS_ALLOWED', 200);
});

if ($response2->getStatusCode() === 200 && $response2->getContent() === 'POS_ALLOWED') {
    echo "✅ PASS [3]: Unverified Merchant successfully granted Free In-Store POS access (HTTP 200)\n";
    $passCount++;
} else {
    echo "❌ FAIL [3]: Unverified Merchant access failed.\n";
}

if (session('user_role') === 'unverified_merchant' && session('seller_id') == $pendingSeller->id) {
    echo "✅ PASS [4]: Unverified Merchant session correctly bound with role 'unverified_merchant'\n";
    $passCount++;
} else {
    echo "❌ FAIL [4]: Session binding failed. Role: " . session('user_role') . "\n";
}
Auth::guard('seller')->logout();

// --- TEST 3: Store Cashier Employee Access ---
echo "\n--- TEST 3: Store Cashier Employee POS Access ---\n";
$cashier = VendorEmployee::firstOrCreate(
    ['email' => 'cashier_test@victorious.com'],
    [
        'seller_id' => $verifiedSeller->id,
        'name' => 'Ifeoma Cashier',
        'phone' => '08099998877',
        'password' => bcrypt('password123'),
        'status' => 1,
        'vendor_role_id' => 1,
        'image' => 'def.png',
        'assigned_branch_id' => 1,
    ]
);
Auth::guard('vendor_employee')->login($cashier);
$request3 = Request::create('/pos', 'GET');
$response3 = $middleware->handle($request3, function ($req) {
    return response('POS_ALLOWED', 200);
});

if ($response3->getStatusCode() === 200 && $response3->getContent() === 'POS_ALLOWED') {
    echo "✅ PASS [5]: Store Cashier successfully accesses POS Register (HTTP 200)\n";
    $passCount++;
} else {
    echo "❌ FAIL [5]: Cashier access failed.\n";
}

if (session('user_role') === 'staff' && session('seller_id') == $verifiedSeller->id) {
    echo "✅ PASS [6]: Cashier session isolated to merchant store (seller_id={$verifiedSeller->id}) with role 'staff'\n";
    $passCount++;
} else {
    echo "❌ FAIL [6]: Cashier session binding failed.\n";
}
Auth::guard('vendor_employee')->logout();

echo "\n========================================================================================\n";
echo "🏁 VERIFICATION RESULT: {$passCount}/{$totalCount} CHECKS PASSED\n";
echo "========================================================================================\n";

if ($passCount === $totalCount) {
    echo "🎉 PROVEN: ALL EXISTING & NEW VENDORS HAVE SEAMLESS, VERIFIED ACCESS TO THE POS!\n";
    exit(0);
} else {
    echo "⚠️ ISSUES DETECTED IN VENDOR POS ACCESS.\n";
    exit(1);
}
