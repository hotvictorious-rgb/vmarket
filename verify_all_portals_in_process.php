<?php

/**
 * [AI] Comprehensive Multi-Portal End-to-End Verification Suite
 */

require __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Seller;
use App\Models\Shop;

$initialReq = Request::create('/', 'GET');
$app->instance('request', $initialReq);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "========================================================================================\n";
echo "🔬 COMPREHENSIVE IN-PROCESS PORTAL & ROUTE VERIFICATION\n";
echo "========================================================================================\n\n";

$passed = 0;
$total = 7;

function testRoute($app, $kernel, $method, $uri, $expectedStatus, $desc, $guardToLogin = null, $user = null) {
    global $passed;
    
    Auth::guard('admin')->logout();
    Auth::guard('seller')->logout();
    Auth::guard('vendor_employee')->logout();
    Auth::guard('customer')->logout();

    if ($guardToLogin && $user) {
        Auth::guard($guardToLogin)->login($user);
    }

    $request = Request::create($uri, $method);
    $app->instance('request', $request);
    
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    
    $success = ($status === $expectedStatus) || ($expectedStatus === 302 && in_array($status, [301, 302]));
    
    if ($success) {
        $targetLoc = $response->headers->get('Location');
        $locText = $targetLoc ? " -> Redirected to: $targetLoc" : "";
        echo "✅ PASS: $desc [$uri] => HTTP $status (Expected $expectedStatus)$locText\n";
        $passed++;
    } else {
        echo "❌ FAIL: $desc [$uri] => HTTP $status (Expected $expectedStatus)\n";
        if ($status >= 400) {
            echo "   Error Content: " . substr(strip_tags($response->getContent()), 0, 150) . "...\n";
        }
    }
    
    $kernel->terminate($request, $response);
    return $response;
}

// 1. Unauthenticated Admin Dashboard Access -> Should redirect to login (302)
testRoute($app, $kernel, 'GET', '/admin/dashboard', 302, 'Unauthenticated /admin/dashboard');

// 2. Direct Admin Login Access -> Should return 200
testRoute($app, $kernel, 'GET', '/login/admin', 200, 'Direct Admin Login Page');

// 3. Direct /admin/login Alias -> Should return 200
testRoute($app, $kernel, 'GET', '/admin/login', 200, 'Direct /admin/login Alias');

// 4. Authenticated Super Admin Dashboard -> Should return 200
$superAdmin = Admin::firstOrCreate(
    ['id' => 1],
    [
        'name' => 'Victory Edet',
        'email' => 'victoriousmarket1@gmail.com',
        'password' => bcrypt('Esther@100&200'),
        'admin_role_id' => 1,
        'status' => 1,
    ]
);
testRoute($app, $kernel, 'GET', '/admin/dashboard', 200, 'Authenticated Super Admin Dashboard', 'admin', $superAdmin);

// 5. Unauthenticated Vendor Dashboard Access -> Should redirect to vendor login (302)
testRoute($app, $kernel, 'GET', '/vendor/dashboard', 302, 'Unauthenticated /vendor/dashboard');

// 6. Vendor Login Page -> Should return 200
testRoute($app, $kernel, 'GET', '/vendor/auth/login', 200, 'Vendor Login Page');

// 7. Vendor Registration Page -> Should return 200
testRoute($app, $kernel, 'GET', '/vendor/auth/registration/index', 200, 'Vendor Registration Page');

echo "\n========================================================================================\n";
echo "🏁 VERIFICATION SUMMARY: $passed / $total CHECKS PASSED\n";
echo "========================================================================================\n";
