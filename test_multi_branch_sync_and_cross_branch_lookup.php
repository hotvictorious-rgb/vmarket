<?php
/**
 * [AI] Automated Verification Suite for Multi-Branch Synchronization & Cross-Branch Stock Lookup
 */

require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Admin;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\PosSubscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Modules\Pos\app\Http\Controllers\PosController;
use Modules\Pos\app\Http\Controllers\StockController;

echo "========================================================================================\n";
echo "🧪 RUNNING MULTI-BRANCH SYNC & CROSS-BRANCH STOCK LOOKUP VERIFICATION SUITE\n";
echo "========================================================================================\n\n";

$passed = 0;
$total = 0;

function assertCondition($name, $condition, &$passed, &$total) {
    $total++;
    if ($condition) {
        $passed++;
        echo "✅ PASS [{$total}]: {$name}\n";
    } else {
        echo "❌ FAIL [{$total}]: {$name}\n";
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Setup: Create 2 Test Sellers with multiple branches and test products
// ─────────────────────────────────────────────────────────────────────────────

$sellerA = Seller::firstOrCreate(
    ['email' => 'merchant_a_multibranch@test.com'],
    [
        'f_name' => 'Alaba',
        'l_name' => 'Merchant A',
        'phone' => '08011111111',
        'password' => bcrypt('password'),
        'status' => 'approved',
        'marketplace_status' => 'approved'
    ]
);

$sellerB = Seller::firstOrCreate(
    ['email' => 'merchant_b_multibranch@test.com'],
    [
        'f_name' => 'Ikeja',
        'l_name' => 'Merchant B',
        'phone' => '08022222222',
        'password' => bcrypt('password'),
        'status' => 'approved',
        'marketplace_status' => 'approved'
    ]
);

// Branches for Seller A
$branchLekki = Shop::firstOrCreate(
    ['seller_id' => $sellerA->id, 'name' => 'Lekki Flagship Store'],
    ['address' => 'Admiralty Way, Lekki', 'contact' => '08011110001', 'image' => 'def.png', 'banner' => 'def.png', 'slug' => 'lekki-flagship-' . rand(100, 999)]
);

$branchIkeja = Shop::firstOrCreate(
    ['seller_id' => $sellerA->id, 'name' => 'Ikeja Hub'],
    ['address' => 'Computer Village, Ikeja', 'contact' => '08011110002', 'image' => 'def.png', 'banner' => 'def.png', 'slug' => 'ikeja-hub-' . rand(100, 999)]
);

$branchAbuja = Shop::firstOrCreate(
    ['seller_id' => $sellerA->id, 'name' => 'Abuja Regional Depot'],
    ['address' => 'Wuse II, Abuja', 'contact' => '08011110003', 'image' => 'def.png', 'banner' => 'def.png', 'slug' => 'abuja-depot-' . rand(100, 999)]
);

// Branch for Seller B (Isolated Tenant)
$branchSellerB = Shop::firstOrCreate(
    ['seller_id' => $sellerB->id, 'name' => 'Seller B Island Store'],
    ['address' => 'Marina, Lagos', 'contact' => '08022220001', 'image' => 'def.png', 'banner' => 'def.png', 'slug' => 'seller-b-island-' . rand(100, 999)]
);

// Create Test Product for Seller A
$productA = Product::firstOrCreate(
    ['user_id' => $sellerA->id, 'code' => 'TEST-MB-001'],
    [
        'name' => 'Multi-Branch Test Smartphone',
        'added_by' => 'seller',
        'unit_price' => 120000,
        'purchase_price' => 95000,
        'current_stock' => 50,
        'status' => 1,
        'unit' => 'pc',
        'images' => '[]',
        'color_image' => '[]',
        'thumbnail' => 'def.png',
        'category_ids' => '[]',
        'pos_barcode' => 'TEST-MB-001-BAR',
        'pos_category' => 'Phones',
        'pos_reorder_level' => 5
    ]
);

// Seed branch stocks for Product A
DB::table('pos_branch_stocks')->updateOrInsert(
    ['branch_id' => $branchLekki->id, 'product_id' => $productA->id],
    ['seller_id' => $sellerA->id, 'stock_quantity' => 15, 'reorder_level' => 5, 'updated_at' => now(), 'created_at' => now()]
);

DB::table('pos_branch_stocks')->updateOrInsert(
    ['branch_id' => $branchIkeja->id, 'product_id' => $productA->id],
    ['seller_id' => $sellerA->id, 'stock_quantity' => 25, 'reorder_level' => 5, 'updated_at' => now(), 'created_at' => now()]
);

DB::table('pos_branch_stocks')->updateOrInsert(
    ['branch_id' => $branchAbuja->id, 'product_id' => $productA->id],
    ['seller_id' => $sellerA->id, 'stock_quantity' => 10, 'reorder_level' => 5, 'updated_at' => now(), 'created_at' => now()]
);

// Re-sync master product current_stock = 15 + 25 + 10 = 50
$productA->current_stock = 50;
$productA->save();

// Product for Seller B
$productB = Product::firstOrCreate(
    ['user_id' => $sellerB->id, 'code' => 'TEST-MB-B001'],
    [
        'name' => 'Seller B Isolated Product',
        'added_by' => 'seller',
        'unit_price' => 30000,
        'current_stock' => 100,
        'status' => 1,
        'unit' => 'pc',
        'images' => '[]',
        'color_image' => '[]',
        'thumbnail' => 'def.png',
        'category_ids' => '[]'
    ]
);

// ─────────────────────────────────────────────────────────────────────────────
// TEST 1: Cross-Branch Stock Lookup API
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- TEST 1: Cross-Branch Stock Lookup API ---\n";
Auth::guard('seller')->setUser($sellerA);
session(['pos_active_warehouse_id' => $branchLekki->id]);

$posCtrl = app(PosController::class);
$request = Request::create("/pos/product/{$productA->id}/branch-stocks", 'GET');
$response = $posCtrl->getBranchStocks($request, $productA->id);
$data = json_decode($response->getContent(), true);

assertCondition("API response status is true", $data['status'] === true, $passed, $total);
assertCondition("API returns exactly 3 branches for Seller A", count($data['branches']) === 3, $passed, $total);

$lekkiData = collect($data['branches'])->firstWhere('id', $branchLekki->id);
$ikejaData = collect($data['branches'])->firstWhere('id', $branchIkeja->id);
$abujaData = collect($data['branches'])->firstWhere('id', $branchAbuja->id);

assertCondition("Lekki branch has 15 units and marked as current counter", ($lekkiData['stock'] === 15 && $lekkiData['is_current'] === true), $passed, $total);
assertCondition("Ikeja branch has 25 units", ($ikejaData['stock'] === 25), $passed, $total);
assertCondition("Abuja branch has 10 units", ($abujaData['stock'] === 10), $passed, $total);

// ─────────────────────────────────────────────────────────────────────────────
// TEST 2: Strict Tenant Isolation (Zero Cross-Tenant Bleed)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- TEST 2: Strict Tenant Isolation (Zero Cross-Tenant Bleed) ---\n";
Auth::guard('seller')->setUser($sellerB);
session(['pos_active_warehouse_id' => $branchSellerB->id]);

// Seller B tries to query Seller A's product
$requestIdor = Request::create("/pos/product/{$productA->id}/branch-stocks", 'GET');
$responseIdor = $posCtrl->getBranchStocks($requestIdor, $productA->id);

assertCondition("Seller B is blocked (404/unauthorized) from Seller A's product", $responseIdor->getStatusCode() === 404, $passed, $total);

// ─────────────────────────────────────────────────────────────────────────────
// TEST 3: POS Checkout & Branch-Specific Stock Decrement
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- TEST 3: POS Checkout & Branch-Specific Stock Decrement ---\n";
Auth::guard('seller')->setUser($sellerA);
session(['pos_active_warehouse_id' => $branchLekki->id]);

$initialLekkiStock = DB::table('pos_branch_stocks')->where('branch_id', $branchLekki->id)->where('product_id', $productA->id)->value('stock_quantity');
$initialIkejaStock = DB::table('pos_branch_stocks')->where('branch_id', $branchIkeja->id)->where('product_id', $productA->id)->value('stock_quantity');
$initialMasterStock = Product::find($productA->id)->current_stock;

$checkoutReq = Request::create('/pos/checkout', 'POST', [
    'warehouse_id' => $branchLekki->id,
    'customerName' => 'Chief Emeka',
    'customerPhone' => '08099887766',
    'totalAmount' => 240000,
    'paidAmount' => 240000,
    'cashAmount' => 240000,
    'posAmount' => 0,
    'transferAmount' => 0,
    'is_supplied' => '1',
    'items' => [
        [
            'productId' => $productA->id,
            'quantity' => 2,
            'unitPrice' => 120000
        ]
    ]
]);

$checkoutRes = $posCtrl->checkout($checkoutReq);

$afterLekkiStock = DB::table('pos_branch_stocks')->where('branch_id', $branchLekki->id)->where('product_id', $productA->id)->value('stock_quantity');
$afterIkejaStock = DB::table('pos_branch_stocks')->where('branch_id', $branchIkeja->id)->where('product_id', $productA->id)->value('stock_quantity');
$afterMasterStock = Product::find($productA->id)->current_stock;

assertCondition("Lekki branch stock decreased by 2 (15 -> 13)", ($afterLekkiStock === $initialLekkiStock - 2), $passed, $total);
assertCondition("Ikeja branch stock remained unchanged at 25", ($afterIkejaStock === $initialIkejaStock), $passed, $total);
assertCondition("Master product current_stock decreased by 2 (50 -> 48)", ($afterMasterStock === $initialMasterStock - 2), $passed, $total);

// ─────────────────────────────────────────────────────────────────────────────
// TEST 4: Inter-Branch Stock Transfer (Waybill Sync)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n--- TEST 4: Inter-Branch Stock Transfer (Waybill Sync) ---\n";
$stockCtrl = app(StockController::class);

$transferReq = Request::create('/pos/stock/transfer-out', 'POST', [
    'from_branch_id' => $branchIkeja->id,
    'to_branch_id' => $branchAbuja->id,
    'product_id' => $productA->id,
    'quantity' => 5
]);

$stockCtrl->createTransfer($transferReq);

$afterTransferIkeja = DB::table('pos_branch_stocks')->where('branch_id', $branchIkeja->id)->where('product_id', $productA->id)->value('stock_quantity');
$afterTransferAbuja = DB::table('pos_branch_stocks')->where('branch_id', $branchAbuja->id)->where('product_id', $productA->id)->value('stock_quantity');

assertCondition("Ikeja source branch stock decreased by 5 (25 -> 20)", ($afterTransferIkeja === 20), $passed, $total);
assertCondition("Abuja destination branch stock increased by 5 (10 -> 15)", ($afterTransferAbuja === 15), $passed, $total);

// Check mathematical zero-drift balance
$totalBranchStock = DB::table('pos_branch_stocks')->where('product_id', $productA->id)->sum('stock_quantity');
$masterStock = Product::find($productA->id)->current_stock;
$delta = abs($totalBranchStock - $masterStock);

assertCondition("Mathematical Invariant Proof: Total Branch Stock ({$totalBranchStock}) == Master Product Stock ({$masterStock}) with Delta = 0.00", ($delta == 0), $passed, $total);

// ─────────────────────────────────────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────────────────────────────────────
echo "\n========================================================================================\n";
echo "🏁 VERIFICATION SUMMARY: {$passed}/{$total} CHECKS PASSED\n";
echo "========================================================================================\n";

if ($passed === $total) {
    echo "🎉 ALL MULTI-BRANCH SYNC & IN-STORE LOOKUP TESTS PASSED WITH 100% SUCCESS!\n";
} else {
    echo "⚠️ SOME TESTS FAILED.\n";
    exit(1);
}
