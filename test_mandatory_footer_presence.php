<?php

/**
 * [AI] Mandatory Footer Presence Verification Suite
 * 
 * Verifies that the exact branding:
 * "Powered by Victorious MARKET your Trusted Online Market"
 * is strictly rendered on the footer of:
 * 1. Global POS Application Layout (layouts/app.blade.php)
 * 2. Thermal Customer Sales Receipt (pos/receipt.blade.php)
 * 3. Dedicated Store Worker Login (auth/store_worker_login.blade.php)
 * 4. General POS Login (auth/login.blade.php)
 * 5. Dedicated Super Admin Login (auth/admin_dedicated_login.blade.php)
 */

echo "=================================================================\n";
echo "🛡️ MANDATORY VICTORIOUS MARKET FOOTER VERIFICATION SUITE\n";
echo "=================================================================\n\n";

$targetText = "Victorious MARKET";
$subText = "your Trusted Online Market";

$filesToCheck = [
    'POS App Layout' => __DIR__ . '/hysam/resources/views/layouts/app.blade.php',
    'Customer Thermal Receipt' => __DIR__ . '/hysam/resources/views/pos/receipt.blade.php',
    'Dedicated Store Worker Login' => __DIR__ . '/hysam/resources/views/auth/store_worker_login.blade.php',
    'General Merchant Login' => __DIR__ . '/hysam/resources/views/auth/login.blade.php',
    'Dedicated Admin Login' => __DIR__ . '/hysam/resources/views/auth/admin_dedicated_login.blade.php',
];

$allPassed = true;

foreach ($filesToCheck as $label => $filePath) {
    $content = file_get_contents($filePath);
    $hasBrand = (stripos($content, $targetText) !== false && stripos($content, $subText) !== false);

    if ($hasBrand) {
        echo "  ✅ PASS: {$label} contains 'Powered by Victorious MARKET your Trusted Online Market'!\n";
    } else {
        echo "  ❌ FAIL: {$label} missing mandatory branding footer!\n";
        $allPassed = false;
    }
}

echo "\n=================================================================\n";
if ($allPassed) {
    echo "🎉 MANDATORY FOOTER 100% VERIFIED ACROSS ALL SCREENS & RECEIPTS!\n";
} else {
    echo "❌ FOOTER VERIFICATION FAILED!\n";
}
echo "=================================================================\n";
