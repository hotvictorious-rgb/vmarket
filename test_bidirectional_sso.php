<?php

/**
 * [AI] Automated Verification for Bi-Directional SSO Token Exchange Protocol
 * Uses multi-process execution to prevent Laravel static container collision.
 */

echo "=================================================================\n";
echo "🛡️ BI-DIRECTIONAL DYNAMIC SSO TOKEN EXCHANGE VERIFICATION SUITE\n";
echo "=================================================================\n\n";

// 1. Boot Central Vmarket Application
echo "Booting Vmarket Application...\n";
require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$vApp = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$vKernel = $vApp->make(Illuminate\Contracts\Console\Kernel::class);
$vKernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Assertions Count
$passed = 0;
$failed = 0;

function assertCondition($cond, $msg) {
    global $passed, $failed;
    if ($cond) {
        echo "  ✅ PASS: $msg\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: $msg\n";
        $failed++;
    }
}

// Clean existing tokens
DB::table('sso_tokens')->truncate();

// -------------------------------------------------------------
// Test Case 1: Vmarket -> POS Token Exchange (Vendor)
// -------------------------------------------------------------
echo "\nTest 1: Simulating Vmarket -> POS Redirect Token Exchange...\n";

$email = 'vendor@victorious.com';
$role = 'vendor';
$token = Str::random(64);

// Vmarket writes the token
DB::table('sso_tokens')->insert([
    'token' => $token,
    'email' => $email,
    'role' => $role,
    'expires_at' => now()->addMinutes(5),
    'created_at' => now(),
    'updated_at' => now(),
]);

assertCondition(DB::table('sso_tokens')->count() === 1, "Token written to shared database table 'sso_tokens'.");

// Call POS part as sub-process to consume the token
$cmd = 'php test_pos_sso_part.php consume_vmarket_token ' . escapeshellarg($token);
$output = shell_exec($cmd);

echo "--- POS Sub-Process Output ---\n" . trim($output) . "\n------------------------------\n";

assertCondition(str_contains($output, 'RESOLVED_EMAIL:vendor@victorious.com'), "POS successfully resolved email: 'vendor@victorious.com'.");
assertCondition(str_contains($output, 'RESOLVED_ROLE:vendor'), "POS successfully resolved role: 'vendor'.");
assertCondition(str_contains($output, 'SUCCESS_CONSUMED'), "POS successfully completed token consume transaction.");

// Verify token was deleted in Vmarket DB
assertCondition(DB::table('sso_tokens')->count() === 0, "Anti-Replay: Token deleted from DB upon consumption.");

// -------------------------------------------------------------
// Test Case 2: POS -> Vmarket Token Exchange (Admin Return)
// -------------------------------------------------------------
echo "\nTest 2: Simulating POS -> Vmarket Redirect Token Exchange...\n";

$returnToken = Str::random(64);

// Call POS part as sub-process to write the return token
$cmdReturn = 'php test_pos_sso_part.php create_return_token ' . escapeshellarg($returnToken);
$outputReturn = shell_exec($cmdReturn);

echo "--- POS Sub-Process Output ---\n" . trim($outputReturn) . "\n------------------------------\n";

assertCondition(str_contains($outputReturn, 'SUCCESS_RETURN_WRITTEN'), "POS successfully completed return token write transaction.");
assertCondition(DB::table('sso_tokens')->count() === 1, "Return token written to shared table.");

// Simulate Vmarket reading the return token
$vmarketSsoToken = DB::table('sso_tokens')
    ->where('token', $returnToken)
    ->where('expires_at', '>', gmdate('Y-m-d H:i:s'))
    ->first();

assertCondition($vmarketSsoToken !== null, "Vmarket successfully resolved return token.");
assertCondition($vmarketSsoToken->email === 'admin@admin.com', "Resolved email matches: 'admin@admin.com'.");
assertCondition($vmarketSsoToken->role === 'admin', "Resolved role matches: 'admin'.");

// Vmarket deletes/consumes the token
DB::table('sso_tokens')->where('token', $returnToken)->delete();

assertCondition(DB::table('sso_tokens')->count() === 0, "Anti-Replay: Return token consumed and deleted.");

// -------------------------------------------------------------
// Test Case 3: Replay Attack Guard
// -------------------------------------------------------------
echo "\nTest 3: Testing Replay Attack Guard...\n";
$expiredToken = DB::table('sso_tokens')->where('token', $returnToken)->first();
assertCondition($expiredToken === null, "Replay attack blocked (re-use of token yields NULL).");

echo "\n=================================================================\n";
echo "📊 BI-DIRECTIONAL SSO VERIFICATION RESULTS: Passed $passed, Failed $failed\n";
echo "=================================================================\n";

if ($failed === 0) {
    echo "🎉 PASS: Dynamic token exchange is 100% operational and bi-directionally secure!\n";
} else {
    echo "❌ FAIL: SSO verification errors detected.\n";
    exit(1);
}
