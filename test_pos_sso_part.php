<?php

/**
 * [AI] POS Part for Multi-Process SSO Verification
 */

require_once __DIR__ . '/hysam/vendor/autoload.php';
$posApp = require_once __DIR__ . '/hysam/bootstrap/app.php';
$posKernel = $posApp->make(Illuminate\Contracts\Console\Kernel::class);
$posKernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$action = $argv[1] ?? '';
$token = $argv[2] ?? '';

if ($action === 'consume_vmarket_token') {
    // 1. Verify Vmarket token on POS
    $posSsoToken = DB::connection('vmarket')
        ->table('sso_tokens')
        ->where('token', $token)
        ->where('expires_at', '>', now())
        ->first();

    if (!$posSsoToken) {
        echo "FAIL: Token not found in Vmarket DB connection.\n";
        exit(1);
    }

    echo "RESOLVED_EMAIL:" . $posSsoToken->email . "\n";
    echo "RESOLVED_ROLE:" . $posSsoToken->role . "\n";

    // Consume/delete token
    DB::connection('vmarket')->table('sso_tokens')->where('token', $token)->delete();
    echo "SUCCESS_CONSUMED\n";
    exit(0);
}

if ($action === 'create_return_token') {
    // 2. Generate POS return token and write to Vmarket DB connection
    $returnToken = $token; // Re-use token argument for convenience
    $email = 'admin@admin.com';
    $role = 'admin';

    DB::connection('vmarket')->table('sso_tokens')->insert([
        'token' => $returnToken,
        'email' => $email,
        'role' => $role,
        'expires_at' => gmdate('Y-m-d H:i:s', time() + 300),
        'created_at' => gmdate('Y-m-d H:i:s'),
        'updated_at' => gmdate('Y-m-d H:i:s'),
    ]);

    echo "SUCCESS_RETURN_WRITTEN\n";
    exit(0);
}

if ($action === 'test_role_gating') {
    // Simulate Super Admin Employee session
    session(['user_role' => 'super_admin_employee', 'user_id' => 'admin-user-1']);
    $authController = new \App\Http\Controllers\AuthController();
    
    // Set a dummy user in Auth facade to bypass null user check
    $user = new \App\Models\User();
    $user->email = 'staff.support@victorious.com';
    \Illuminate\Support\Facades\Auth::login($user);

    try {
        $req = \Illuminate\Http\Request::create('/pos-sso-return', 'GET');
        $authController->posSsoReturn($req);
        echo "FAIL_EMPLOYEE_ALLOWED\n";
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            echo "SUCCESS_EMPLOYEE_BLOCKED\n";
        } else {
            echo "FAIL_UNEXPECTED_CODE:" . $e->getStatusCode() . "\n";
        }
    }
    
    // Simulate Cashier session
    session(['user_role' => 'verified_merchant_employee', 'user_id' => 'vendor-worker-1']);
    try {
        $req = \Illuminate\Http\Request::create('/pos-sso-return', 'GET');
        $authController->posSsoReturn($req);
        echo "FAIL_CASHIER_ALLOWED\n";
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        if ($e->getStatusCode() === 403) {
            echo "SUCCESS_CASHIER_BLOCKED\n";
        } else {
            echo "FAIL_UNEXPECTED_CODE:" . $e->getStatusCode() . "\n";
        }
    }
    exit(0);
}

echo "FAIL: Unknown action.\n";
exit(1);
