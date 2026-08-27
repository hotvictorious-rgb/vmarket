<?php

/**
 * [AI] Automated Verification Suite for POS-First Catalog Sync & Unified Roles
 */

require_once __DIR__ . '/hysam/vendor/autoload.php';
$app = require_once __DIR__ . '/hysam/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$passed = 0;
$failed = 0;

function assertCondition($cond, $msg) {
    global $passed, $failed;
    if ($cond) {
        echo "  ✅ PASS: {$msg}\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: {$msg}\n";
        $failed++;
    }
}

echo "=================================================================\n";
echo "🛡️ POS-FIRST PRODUCT DRAFT SYNC & UNIFIED ROLES VERIFICATION\n";
echo "=================================================================\n\n";

DB::beginTransaction();
// Also begin transaction on the Vmarket connection to keep database clean
DB::connection('vmarket')->beginTransaction();

try {
    // -------------------------------------------------------------
    // Setup Shared Context (Programmatically migrate missing tables)
    // -------------------------------------------------------------
    $schema = Schema::connection('vmarket');
    if (!$schema->hasTable('vendor_roles')) {
        $schema->create('vendor_roles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->index();
            $table->string('name');
            $table->text('module_access')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }
    if (!$schema->hasTable('vendor_employees')) {
        $schema->create('vendor_employees', function ($table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('vendor_role_id')->index();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('image')->nullable();
            $table->boolean('status')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    $sellerId = 999;
    
    // Seed Vmarket tables
    DB::connection('vmarket')->table('sellers')->insert([
        'id' => $sellerId,
        'f_name' => 'John',
        'l_name' => 'Merchant',
        'email' => 'john.merchant@vmarket.com',
        'password' => Hash::make('password123'),
        'status' => 'approved',
    ]);

    $company = Company::create([
        'name' => 'Merchant Shop Ltd',
        'marketplace_vendor_id' => $sellerId,
        'api_key' => Str::random(32),
    ]);

    // Create a local POS Admin User for the vendor
    $posAdmin = User::create([
        'id' => 'vendor-' . $sellerId,
        'company_id' => $company->id,
        'name' => 'John Merchant',
        'email' => 'john.merchant@vmarket.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    // -------------------------------------------------------------
    // Test Case 1: POS-First Product Creation Syncs Draft to Vmarket
    // -------------------------------------------------------------
    echo "Test 1: Creating a product on POS as Admin...\n";
    Auth::login($posAdmin);

    $barcode = '999888777';
    $product = Product::create([
        'id' => (string) Str::uuid(),
        'company_id' => $company->id,
        'name' => 'Test Wristwatch',
        'code' => $barcode,
        'category' => 'Electronics',
        'unitPrice' => 7500,
        'currentStock' => 50,
        'minStockLevel' => 2,
        'updatedAt' => now()->toIso8601String(),
    ]);

    // Query Vmarket to verify draft sync
    $vmarketProduct = DB::connection('vmarket')
        ->table('products')
        ->where('code', $barcode)
        ->first();

    assertCondition($vmarketProduct !== null, "Product successfully synced to Vmarket database.");
    assertCondition($vmarketProduct->status == 0, "Storefront status is correctly set to 0 (Draft).");
    assertCondition($vmarketProduct->request_status == 0, "Request status is correctly set to 0 (Pending Verification / Approval).");
    assertCondition($vmarketProduct->user_id == $sellerId, "Product draft is correctly associated with seller_id {$sellerId}.");

    // -------------------------------------------------------------
    // Test Case 2: Reject product creation by non-admin role (Manager)
    // -------------------------------------------------------------
    echo "\nTest 2: Rejects product creation by Branch Manager...\n";
    
    $posManager = User::create([
        'id' => 'manager-1',
        'company_id' => $company->id,
        'name' => 'Branch Manager',
        'email' => 'manager.branch@vmarket.com',
        'password' => Hash::make('password123'),
        'role' => 'manager',
    ]);
    Auth::login($posManager);

    $productController = new \App\Http\Controllers\Web\ProductController();
    $reqStore = \Illuminate\Http\Request::create('/products', 'POST', [
        'name' => 'Forbidden Product',
        'code' => '111222333',
        'category' => 'Groceries',
        'unitPrice' => 300,
    ]);
    
    $resStore = $productController->store($reqStore);
    
    // Check if it redirected back with permission error
    $redirectUrl = $resStore->headers->get('Location');
    $errorMsg = session('error');
    assertCondition(str_contains($errorMsg, 'Permission Denied'), "Product creation correctly blocked for Branch Manager (gated message: '{$errorMsg}').");

    // -------------------------------------------------------------
    // Test Case 3: Sync and Map Vendor Employee from Vmarket
    // -------------------------------------------------------------
    echo "\nTest 3: Syncing and mapping Vendor Employee on login...\n";
    
    $empEmail = 'attendant.uyo@vmarket.com';
    $empPass = 'pass1234';
    
    // 1. Seed a Vendor Role on Vmarket
    $roleId = 88;
    DB::connection('vmarket')->table('vendor_roles')->insert([
        'id' => $roleId,
        'seller_id' => $sellerId,
        'name' => 'Uyo Branch Manager', // Role name contains "manager" -> maps to POS 'manager'
        'module_access' => json_encode(['pos', 'products']),
        'status' => 1,
    ]);

    // 2. Seed a Vendor Employee on Vmarket
    DB::connection('vmarket')->table('vendor_employees')->insert([
        'id' => 77,
        'seller_id' => $sellerId,
        'vendor_role_id' => $roleId,
        'name' => 'Victor Bassey',
        'phone' => '08098765432',
        'email' => $empEmail,
        'password' => Hash::make($empPass),
        'status' => 1,
    ]);

    // 3. Dispatch webLogin request through AuthController
    $authController = new \App\Http\Controllers\AuthController();
    $reqLogin = \Illuminate\Http\Request::create('/login', 'POST', [
        'email' => $empEmail,
        'password' => $empPass,
    ]);
    $session = $app->make('session')->driver('array');
    $session->start();
    $reqLogin->setLaravelSession($session);
    
    $resLogin = $authController->webLogin($reqLogin);
    
    // Assert user was provisioned locally in POS
    $posEmployeeUser = User::where('email', $empEmail)->first();
    assertCondition($posEmployeeUser !== null, "Vendor Employee successfully synced and created in local POS database.");
    assertCondition($posEmployeeUser->role === 'manager', "Vendor role 'Uyo Branch Manager' correctly mapped to POS role 'manager'.");
    assertCondition($posEmployeeUser->company_id === $company->id, "Employee company tenant matches the merchant's company ID.");
    assertCondition(Auth::check() && Auth::id() === $posEmployeeUser->id, "Employee successfully authenticated and logged in.");

} catch (\Throwable $e) {
    echo "❌ FATAL TEST ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    DB::connection('vmarket')->rollBack();
    DB::rollBack();
}

echo "\n=================================================================\n";
echo "📊 VERIFICATION RESULTS: Passed {$passed}, Failed {$failed}\n";
echo "=================================================================\n";

if ($failed === 0) {
    echo "🎉 SUCCESS: POS-first catalog sync and unified role mapping verified successfully!\n";
    exit(0);
} else {
    echo "❌ FAILURE: Some test cases failed.\n";
    exit(1);
}
