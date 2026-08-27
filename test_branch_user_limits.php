<?php

/**
 * [AI] Automated Verification Suite for Branch User Limits & Roles Constraints
 */

require_once __DIR__ . '/hysam/vendor/autoload.php';
$app = require_once __DIR__ . '/hysam/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
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
echo "🛡️ BRANCH USER LIMITS & 3-ROLE RESTRICTIONS VERIFICATION SUITE\n";
echo "=================================================================\n\n";

DB::beginTransaction();

try {
    // Setup test context
    $company = Company::first() ?? Company::create(['name' => 'Test Company']);
    $warehouse = Warehouse::create([
        'company_id' => $company->id,
        'name' => 'Branch Test Location',
        'code' => 'TEST-' . Str::upper(Str::random(4)),
        'is_active' => true,
    ]);

    // Create a mock admin user to perform the operations
    $adminUser = User::create([
        'id' => (string) Str::uuid(),
        'company_id' => $company->id,
        'name' => 'Main Vendor Admin',
        'email' => 'vendor.' . Str::random(5) . '@test.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);
    Auth::login($adminUser);

    $userController = new \App\Http\Controllers\Web\UserController();

    // -------------------------------------------------------------
    // Test Case 1: Reject legacy or custom roles
    // -------------------------------------------------------------
    echo "Test 1: Rejecting legacy roles...\n";
    try {
        $req = \Illuminate\Http\Request::create('/users', 'POST', [
            'name' => 'Legacy Cashier',
            'email' => 'cashier.test@test.com',
            'password' => 'password123',
            'role' => 'cashier', // Invalid role
            'warehouse_id' => $warehouse->id,
        ]);
        $userController->store($req);
        assertCondition(false, "Creating a worker with role 'cashier' should be rejected.");
    } catch (\Illuminate\Validation\ValidationException $e) {
        assertCondition(true, "Validation correctly rejected unauthorized 'cashier' role.");
    }

    // -------------------------------------------------------------
    // Test Case 2: Create valid users up to limit of 3
    // -------------------------------------------------------------
    echo "\nTest 2: Adding permitted roles up to the limit of 3...\n";
    
    // 1. Add Admin worker to branch
    $req1 = \Illuminate\Http\Request::create('/users', 'POST', [
        'name' => 'Branch Admin',
        'email' => 'br.admin@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'warehouse_id' => $warehouse->id,
    ]);
    $userController->store($req1);
    
    // 2. Add Manager worker to branch
    $req2 = \Illuminate\Http\Request::create('/users', 'POST', [
        'name' => 'Branch Manager',
        'email' => 'br.manager@test.com',
        'password' => 'password123',
        'role' => 'manager',
        'warehouse_id' => $warehouse->id,
    ]);
    $userController->store($req2);
    
    // 3. Add Executive worker to branch
    $req3 = \Illuminate\Http\Request::create('/users', 'POST', [
        'name' => 'Branch Executive',
        'email' => 'br.executive@test.com',
        'password' => 'password123',
        'role' => 'executive',
        'warehouse_id' => $warehouse->id,
    ]);
    $userController->store($req3);

    $branchUsersCount = User::where('warehouse_id', $warehouse->id)->count();
    assertCondition($branchUsersCount === 3, "Successfully added exactly 3 standardized roles to the branch (count = 3).");

    // -------------------------------------------------------------
    // Test Case 3: Block 4th user addition
    // -------------------------------------------------------------
    echo "\nTest 3: Blocking 4th user addition to the branch...\n";
    $req4 = \Illuminate\Http\Request::create('/users', 'POST', [
        'name' => 'Extra Executive',
        'email' => 'br.extra@test.com',
        'password' => 'password123',
        'role' => 'executive',
        'warehouse_id' => $warehouse->id,
    ]);
    $res4 = $userController->store($req4);
    
    // The response should be a redirect back with errors (due to validation failures)
    $errors = session('errors');
    $hasLimitError = $errors && $errors->has('warehouse_id');
    assertCondition($hasLimitError, "Validation blocked 4th user creation with branch user limit error.");

    // -------------------------------------------------------------
    // Test Case 4: Block duplicate roles in the same branch
    // -------------------------------------------------------------
    echo "\nTest 4: Blocking duplicate roles in the same branch...\n";
    
    // Delete 1 user to drop below total count limit, then try to add a duplicate role
    User::where('warehouse_id', $warehouse->id)->where('role', 'manager')->delete();
    
    $req5 = \Illuminate\Http\Request::create('/users', 'POST', [
        'name' => 'Duplicate Executive',
        'email' => 'br.dup.exec@test.com',
        'password' => 'password123',
        'role' => 'executive', // Duplicate role (Executive already exists in branch)
        'warehouse_id' => $warehouse->id,
    ]);
    $userController->store($req5);
    
    $errors2 = session('errors');
    $hasRoleError = $errors2 && $errors2->has('role');
    assertCondition($hasRoleError, "Validation blocked duplicate 'executive' role creation in the same branch.");

    // -------------------------------------------------------------
    // Test Case 5: Verify branch scoping filter for non-admin
    // -------------------------------------------------------------
    echo "\nTest 5: Verifying branch scoping lock for non-admin roles...\n";
    
    // Clear out branch users and create a clean manager for this test
    User::where('warehouse_id', $warehouse->id)->delete();
    $testManager = User::create([
        'id' => (string) Str::uuid(),
        'company_id' => $company->id,
        'name' => 'Branch Manager User',
        'email' => 'manager.' . Str::random(5) . '@test.com',
        'password' => Hash::make('password123'),
        'role' => 'manager',
        'warehouse_id' => $warehouse->id,
    ]);
    Auth::login($testManager);

    $productController = new \App\Http\Controllers\Web\ProductController();
    $reqIndex = \Illuminate\Http\Request::create('/products', 'GET');
    
    // Run controller index to check if it scopes warehouses list
    // We mock index method dependencies or invoke it to inspect data
    $refMethod = new ReflectionMethod($productController, 'index');
    $refMethod->setAccessible(true);
    $view = $refMethod->invoke($productController, $reqIndex);
    
    $viewData = $view->getData();
    $scopedWarehouses = $viewData['warehouses'];
    
    assertCondition($scopedWarehouses->count() === 1, "Branch manager strictly locked to 1 scoped branch.");
    assertCondition($scopedWarehouses->first()->id === $warehouse->id, "Scoped branch matches manager's assigned branch.");

    // -------------------------------------------------------------
    // Test Case 6: Verify read-only executive permissions
    // -------------------------------------------------------------
    echo "\nTest 6: Verifying read-only executive blocks mutations...\n";
    $testExecutive = User::create([
        'id' => (string) Str::uuid(),
        'company_id' => $company->id,
        'name' => 'Branch Executive User',
        'email' => 'executive.' . Str::random(5) . '@test.com',
        'password' => Hash::make('password123'),
        'role' => 'executive',
        'warehouse_id' => $warehouse->id,
    ]);
    Auth::login($testExecutive);

    $blockMiddleware = new \App\Http\Middleware\BlockReadOnlyMutations();
    $reqMutate = \Illuminate\Http\Request::create('/pos/checkout', 'POST', ['totalAmount' => 100]);
    
    $response = $blockMiddleware->handle($reqMutate, function() {
        return response('ALLOWED');
    });

    assertCondition($response->getStatusCode() === 302 || $response->getStatusCode() === 403, "Mutating POS checkouts rejected for executive role (HTTP {$response->getStatusCode()}).");

} catch (\Throwable $e) {
    echo "❌ FATAL TEST ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $failed++;
} finally {
    DB::rollBack();
}

echo "\n=================================================================\n";
echo "📊 VERIFICATION RESULTS: Passed {$passed}, Failed {$failed}\n";
echo "=================================================================\n";

if ($failed === 0) {
    echo "🎉 SUCCESS: All role limits and branch capping guards are 100% operational!\n";
    exit(0);
} else {
    echo "❌ FAILURE: Some test cases failed.\n";
    exit(1);
}
