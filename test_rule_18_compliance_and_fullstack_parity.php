<?php

/**
 * ═════════════════════════════════════════════════════════════════════════════
 * 🧪 TEST SUITE: RULE 18 FULL-STACK DUAL DELIVERY, ROLE-AWARENESS & SECURITY PROOF
 * ═════════════════════════════════════════════════════════════════════════════
 *
 * Verifies:
 *  1. Physical branch stock sync on Stock-In & Physical Custody Guard.
 *  2. Physical branch stock sync on Stock Adjustments & Custody Guard (Lekki vs Ikeja).
 *  3. Unsupplied order customer pickup dispatch confirmation & audit logging.
 *  4. Worker management role-awareness (cashier blocked from staff management; seller saves assigned_branch_id).
 *  5. Mathematical Invariant Proof: Total Branch Stocks == Master Product Stock (Delta = 0.00).
 */

require __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Shop;
use App\Models\Seller;
use App\Models\VendorEmployee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Modules\Pos\app\Http\Controllers\StockController;
use Modules\Pos\app\Http\Controllers\UserController;
use Modules\Pos\app\Http\Controllers\PosController;

echo "\n========================================================================================\n";
echo "🧪 RUNNING RULE 18 FULL-STACK COMPLIANCE & 9-TIER ROLE AWARENESS SUITE\n";
echo "========================================================================================\n\n";

$passed = 0;
$total = 0;

function assertCheck($name, $condition, &$passed, &$total) {
    $total++;
    if ($condition) {
        $passed++;
        echo "✅ PASS [{$total}]: {$name}\n";
    } else {
        echo "❌ FAIL [{$total}]: {$name}\n";
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// SETUP TEST DATA
// ─────────────────────────────────────────────────────────────────────────────
$seller = Seller::firstOrCreate(
    ['email' => 'rule18_merchant@vmarket.ng'],
    [
        'f_name' => 'Emeka',
        'l_name' => 'Retail',
        'phone' => '08099881122',
        'password' => bcrypt('password'),
        'status' => 'approved'
    ]
);

$branchLekki = Shop::firstOrCreate(
    ['seller_id' => $seller->id, 'name' => 'Rule18 Lekki Flagship'],
    [
        'address' => 'Admiralty Way, Lekki Phase 1',
        'contact' => '08011112222',
        'image' => 'def.png',
        'banner' => 'def.png'
    ]
);

$branchIkeja = Shop::firstOrCreate(
    ['seller_id' => $seller->id, 'name' => 'Rule18 Ikeja Hub'],
    [
        'address' => 'Allen Avenue, Ikeja',
        'contact' => '08033334444',
        'image' => 'def.png',
        'banner' => 'def.png'
    ]
);

$product = Product::firstOrCreate(
    ['user_id' => $seller->id, 'name' => 'Samsung Galaxy S24 Ultra 256GB'],
    [
        'added_by' => 'seller',
        'code' => 'SAM-S24U-256',
        'unit_price' => 1250000,
        'purchase_price' => 1100000,
        'current_stock' => 0,
        'status' => 1,
        'request_status' => 1,
        'thumbnail' => 'def.png',
        'images' => '[]',
        'color_image' => '[]',
        'colors' => '[]',
        'attributes' => '[]',
        'choice_options' => '[]',
        'variation' => '[]'
    ]
);

// Clear initial branch stock rows for clean deterministic assertions
DB::table('pos_branch_stocks')->where('product_id', $product->id)->delete();
Product::where('id', $product->id)->update(['current_stock' => 0]);

$employeeLekki = VendorEmployee::firstOrCreate(
    ['email' => 'cashier_lekki_rule18@test.com'],
    [
        'seller_id' => $seller->id,
        'name' => 'Chinedu (Lekki Cashier)',
        'phone' => '08099112233',
        'password' => bcrypt('password'),
        'vendor_role_id' => 1,
        'image' => 'def.png',
        'assigned_branch_id' => $branchLekki->id,
        'status' => 1
    ]
);
$employeeLekki->assigned_branch_id = $branchLekki->id;
$employeeLekki->save();

$stockCtrl = app(StockController::class);
$userCtrl  = app(UserController::class);

// ─────────────────────────────────────────────────────────────────────────────
// TEST 1: Physical Branch Stock-In & Custody Guard
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- TEST 1: Physical Branch Stock-In & Custody Guard ---\n";

Auth::guard('seller')->logout();
Auth::guard('vendor_employee')->setUser($employeeLekki);

// Lekki Cashier attempts to stock-in into Ikeja Hub (unassigned branch) -> must be blocked (403)
$blockedStockIn = false;
try {
    $stockInIkejaReq = Request::create('/pos/stock/in', 'POST', [
        'product_id' => $product->id,
        'warehouse_id' => $branchIkeja->id,
        'quantity' => 10,
        'supplier_name' => 'Samsung Direct'
    ]);
    $stockCtrl->stockIn($stockInIkejaReq);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $blockedStockIn = true;
    }
}
assertCheck("Lekki cashier is BLOCKED (403) from stocking-in goods into unassigned Ikeja Hub", $blockedStockIn, $passed, $total);

// Lekki Cashier stocks in 20 units into their OWN assigned branch (Lekki) -> must succeed
$stockInLekkiReq = Request::create('/pos/stock/in', 'POST', [
    'product_id' => $product->id,
    'warehouse_id' => $branchLekki->id,
    'quantity' => 20,
    'supplier_name' => 'Samsung Official NG'
]);
$stockCtrl->stockIn($stockInLekkiReq);

$lekkiBranchStock = (int) DB::table('pos_branch_stocks')->where('branch_id', $branchLekki->id)->where('product_id', $product->id)->value('stock_quantity');
$masterCatalogStock = (int) Product::find($product->id)->current_stock;

assertCheck("Lekki branch physical stock is exactly 20 units", ($lekkiBranchStock === 20), $passed, $total);
assertCheck("Master product catalog stock is exactly 20 units", ($masterCatalogStock === 20), $passed, $total);

// Store owner stocks in 15 units into Ikeja Hub
Auth::guard('vendor_employee')->logout();
Auth::guard('seller')->setUser($seller);
$stockInIkejaOwnerReq = Request::create('/pos/stock/in', 'POST', [
    'product_id' => $product->id,
    'warehouse_id' => $branchIkeja->id,
    'quantity' => 15,
    'supplier_name' => 'Samsung Direct'
]);
$stockCtrl->stockIn($stockInIkejaOwnerReq);

$ikejaBranchStock = (int) DB::table('pos_branch_stocks')->where('branch_id', $branchIkeja->id)->where('product_id', $product->id)->value('stock_quantity');
$masterCatalogStockAfterOwner = (int) Product::find($product->id)->current_stock;

assertCheck("Ikeja branch physical stock is exactly 15 units", ($ikejaBranchStock === 15), $passed, $total);
assertCheck("Master product catalog stock is consolidated to 35 units (20 + 15)", ($masterCatalogStockAfterOwner === 35), $passed, $total);

// ─────────────────────────────────────────────────────────────────────────────
// TEST 2: Stock Adjustment (Damage / Loss) & Physical Custody Guard
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- TEST 2: Stock Adjustment & Physical Custody Guard ---\n";

Auth::guard('seller')->logout();
Auth::guard('vendor_employee')->setUser($employeeLekki);

// Lekki Cashier tries to write off damage in Ikeja Hub -> blocked (403)
$blockedAdjustment = false;
try {
    $adjIkejaReq = Request::create('/pos/stock/adjustments', 'POST', [
        'product_id' => $product->id,
        'warehouse_id' => $branchIkeja->id,
        'quantity' => 2,
        'type' => 'DAMAGE',
        'reason' => 'Broken glass during handling'
    ]);
    $stockCtrl->createAdjustment($adjIkejaReq);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $blockedAdjustment = true;
    }
}
assertCheck("Lekki cashier is BLOCKED (403) from adjusting/writing off stock in Ikeja Hub", $blockedAdjustment, $passed, $total);

// Lekki Cashier writes off 2 damaged units in their assigned Lekki branch
$adjLekkiReq = Request::create('/pos/stock/adjustments', 'POST', [
    'product_id' => $product->id,
    'warehouse_id' => $branchLekki->id,
    'quantity' => 2,
    'type' => 'DAMAGE',
    'reason' => 'Screen cracked during customer inspection'
]);
$stockCtrl->createAdjustment($adjLekkiReq);

$afterAdjLekkiStock = (int) DB::table('pos_branch_stocks')->where('branch_id', $branchLekki->id)->where('product_id', $product->id)->value('stock_quantity');
$afterAdjMasterStock = (int) Product::find($product->id)->current_stock;
$adjustmentLogExists = DB::table('pos_stock_adjustments')->where('seller_id', $seller->id)->where('branch_id', $branchLekki->id)->where('product_id', $product->id)->exists();

assertCheck("Lekki physical branch stock reduced by 2 (20 -> 18)", ($afterAdjLekkiStock === 18), $passed, $total);
assertCheck("Master product catalog stock reduced by 2 (35 -> 33)", ($afterAdjMasterStock === 33), $passed, $total);
assertCheck("pos_stock_adjustments audit log entry created", $adjustmentLogExists, $passed, $total);

// ─────────────────────────────────────────────────────────────────────────────
// TEST 3: Unsupplied Order Dispatch Confirmation
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- TEST 3: Unsupplied Order Dispatch Confirmation ---\n";

$receiptNumber = 'REC-TEST-' . strtoupper(uniqid());
$saleId = DB::table('pos_sales')->insertGetId([
    'seller_id' => $seller->id,
    'branch_id' => $branchLekki->id,
    'customer_id' => 1,
    'customer_name' => 'Mrs. Folake Adeyemi',
    'customer_phone' => '08022334455',
    'receipt_number' => $receiptNumber,
    'total_amount' => 1250000,
    'paid_amount' => 1250000,
    'debt_amount' => 0,
    'status' => 'completed',
    'delivery_status' => 'UNSUPPLIED',
    'created_at' => now(),
    'updated_at' => now(),
]);

// Dispatch confirmation by Lekki cashier
$stockCtrl->dispatchConfirm($saleId);

$updatedSale = DB::table('pos_sales')->where('id', $saleId)->first();
$activityLog = DB::table('pos_activities')->where('seller_id', $seller->id)->where('type', 'ORDER_DISPATCHED')->first();

assertCheck("Sale delivery_status transitioned to DELIVERED", ($updatedSale->delivery_status === 'DELIVERED'), $passed, $total);
assertCheck("pos_activities audit entry logged for dispatch handover", ($activityLog !== null), $passed, $total);

// ─────────────────────────────────────────────────────────────────────────────
// TEST 4: Worker Management Role Gates & Branch Assignment
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- TEST 4: Worker Management Role Gates & Branch Assignment ---\n";

// Cashier attempts to create worker -> blocked (403)
Auth::guard('seller')->logout();
Auth::guard('vendor_employee')->setUser($employeeLekki);

$cashierBlockedFromWorkerCreate = false;
try {
    $illegalWorkerReq = Request::create('/pos/users', 'POST', [
        'name' => 'Hacker Staff',
        'email' => 'hacker@test.com',
        'phone' => '08011223344',
        'password' => 'password123',
        'role' => 'cashier'
    ]);
    $userCtrl->store($illegalWorkerReq);
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    if ($e->getStatusCode() === 403) {
        $cashierBlockedFromWorkerCreate = true;
    }
}
assertCheck("Cashier is BLOCKED (403) from creating other staff accounts", $cashierBlockedFromWorkerCreate, $passed, $total);

// Seller creates worker with assigned branch -> succeeds
Auth::guard('vendor_employee')->logout();
Auth::guard('seller')->setUser($seller);

$newWorkerEmail = 'ikeja_cashier_' . time() . '@test.com';
$legalWorkerReq = Request::create('/pos/users', 'POST', [
    'name' => 'Ngozi (Ikeja Staff)',
    'email' => $newWorkerEmail,
    'phone' => '08077665544',
    'password' => 'password123',
    'warehouse_id' => $branchIkeja->id
]);
$userCtrl->store($legalWorkerReq);

$createdWorker = VendorEmployee::where('email', $newWorkerEmail)->first();
assertCheck("Seller successfully creates staff account", ($createdWorker !== null), $passed, $total);
assertCheck("Staff account correctly has assigned_branch_id set to Ikeja Hub", ($createdWorker && $createdWorker->assigned_branch_id == $branchIkeja->id), $passed, $total);

// ─────────────────────────────────────────────────────────────────────────────
// TEST 5: Mathematical Invariant Proof: Zero Drift
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- TEST 5: Mathematical Invariant Proof: Zero Drift ---\n";

$sumBranchStocks = (int) DB::table('pos_branch_stocks')->where('product_id', $product->id)->sum('stock_quantity');
$finalMasterStock = (int) Product::find($product->id)->current_stock;
$delta = abs($sumBranchStocks - $finalMasterStock);

assertCheck("Mathematical Invariant Proof: Sum(pos_branch_stocks: {$sumBranchStocks}) == products.current_stock ({$finalMasterStock}) with Delta = 0.00", ($delta === 0), $passed, $total);

// ─────────────────────────────────────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────────────────────────────────────
echo "\n========================================================================================\n";
echo "🏁 VERIFICATION SUMMARY: {$passed}/{$total} CHECKS PASSED\n";
echo "========================================================================================\n";

if ($passed === $total) {
    echo "🎉 ALL RULE 18 FULL-STACK COMPLIANCE & ROLE-AWARENESS CHECKS PASSED WITH 100% SUCCESS!\n";
} else {
    echo "⚠️ SOME TESTS FAILED.\n";
    exit(1);
}
