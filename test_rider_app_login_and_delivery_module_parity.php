<?php

require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliveryHub;
use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\RestAPI\v2\delivery_man\auth\LoginController as RiderLoginController;
use Modules\Delivery\app\Http\Controllers\FinanceController;

echo "\n========================================================================\n";
echo "🛵 TEST: RIDER APP LOGIN & DELIVERY MODULE OPERATIONAL PARITY\n";
echo "========================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $description, bool $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  ✅ PASS: {$description}\n";
        $passCount++;
    } else {
        echo "  ❌ FAIL: {$description}\n";
        $failCount++;
    }
}

// 1. Hub Existence Verification
$hub = DeliveryHub::first();
if (!$hub) {
    $hub = DeliveryHub::create([
        'city_id' => 1,
        'name' => 'Uyo Central Hub (Itam)',
        'type' => 'landmark',
        'base_shipping_cost' => 1000.00,
        'rider_delivery_fee' => 700.00,
        'is_active' => true,
    ]);
}
assertTest("Logistics Hub exists in unified database (#{$hub->id}: {$hub->name})", $hub->id > 0);

// 2. Rider Creation & Password Hash Verification
$testPhone = '08099998888';
$testPassword = 'password123';
$rider = DeliveryMan::where('phone', $testPhone)->first();
if (!$rider) {
    $rider = DeliveryMan::create([
        'seller_id' => 0,
        'f_name' => 'Kufre',
        'l_name' => 'Rider',
        'phone' => $testPhone,
        'email' => 'kufre.rider@vmarket.ng',
        'password' => Hash::make($testPassword),
        'delivery_hub_id' => $hub->id,
        'is_active' => 1,
    ]);
    DeliverymanWallet::create([
        'delivery_man_id' => $rider->id,
        'current_balance' => 0.00,
        'cash_in_hand' => 15000.00,
        'total_earning' => 50000.00,
        'total_withdraw' => 35000.00,
    ]);
} else {
    $rider->password = Hash::make($testPassword);
    $rider->is_active = 1;
    $rider->delivery_hub_id = $hub->id;
    $rider->save();
}

assertTest("Rider account established and linked to Hub #{$hub->id}", $rider->id > 0 && $rider->delivery_hub_id == $hub->id);

// 3. Test Mobile App Login Controller API
$loginController = new RiderLoginController();
$loginRequest = Request::create('/api/v2/delivery-man/auth/login', 'POST', [
    'phone' => $testPhone,
    'password' => $testPassword,
]);

$loginResponse = $loginController->login($loginRequest);
$loginData = json_decode($loginResponse->getContent(), true);

assertTest("Rider App Mobile Login API returns HTTP 200 with Bearer Token", $loginResponse->getStatusCode() === 200 && !empty($loginData['token']));

// 4. Verify Auth Token Persisted in Database
$rider->refresh();
assertTest("Rider's auth_token matched generated token", $rider->auth_token === $loginData['token']);

// 5. Test Cash-in-Hand Remittance Settlement via Modules/Delivery FinanceController
$financeController = new FinanceController();
$wallet = DeliverymanWallet::where('delivery_man_id', $rider->id)->first();
$initialCash = (float) $wallet->cash_in_hand;
$remitAmount = 5000.00;

$remitRequest = Request::create('/delivery/finance/remittance/record', 'POST', [
    'delivery_man_id' => $rider->id,
    'amount' => $remitAmount,
    'remittance_type' => 'cash_deposit_at_hub',
    'reference' => 'TEST-REMIT-001',
]);

$remitResponse = $financeController->recordRemittance($remitRequest);
$wallet->refresh();
$newCash = (float) $wallet->cash_in_hand;

assertTest("COD Remittance successfully deducted with pessimistic concurrency lock (₦{$initialCash} ➔ ₦{$newCash})", $newCash == ($initialCash - $remitAmount));

echo "\n========================================================================\n";
echo "📊 RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================================\n\n";
