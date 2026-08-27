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
        'expires_at' => now()->addMinutes(5),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    echo "SUCCESS_RETURN_WRITTEN\n";
    exit(0);
}

echo "FAIL: Unknown action.\n";
exit(1);
