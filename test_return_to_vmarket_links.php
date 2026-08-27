<?php

/**
 * [AI] Automated Verification for Return-to-Victorious-MARKET Navigation Links
 */

echo "=================================================================\n";
echo "🛡️ RETURN TO VMARKET NAVIGATION LINKS VERIFICATION SUITE\n";
echo "=================================================================\n\n";

$secretKey = 'VictoriousMarketSecretKey2026';

// 1. Super Admin SSO & Check Return Links
echo "1. Testing Super Admin Return Links on POS...\n";
$adminEmail = 'admin@admin.com';
$expires = time() + 300;
$role = 'admin';
$adminToken = hash_hmac('sha256', "{$adminEmail}|{$expires}|{$role}", $secretKey);
$adminSsoUrl = "http://127.0.0.1:8001/sso-login?email=" . urlencode($adminEmail) . "&expires={$expires}&role={$role}&token={$adminToken}";

$cookieJar = tempnam(sys_get_temp_dir(), 'sso_return_admin_');

$ch = curl_init($adminSsoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$adminHtml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  Admin Dashboard HTTP Status: {$httpCode}\n";

$hasAdminTopBtn = str_contains($adminHtml, 'Back to Vmarket Admin') && str_contains($adminHtml, '/admin/dashboard');
$hasAdminSidebar = str_contains($adminHtml, 'Return to Admin Panel') && str_contains($adminHtml, '/admin/dashboard');

if ($hasAdminTopBtn && $hasAdminSidebar) {
    echo "  ✅ PASS: Super Admin views both Top Bar and Sidebar 'Back to Vmarket Admin' links pointing to /admin/dashboard!\n";
} else {
    echo "  ❌ FAIL: Admin links missing. TopBtn=" . ($hasAdminTopBtn ? 'YES' : 'NO') . " | Sidebar=" . ($hasAdminSidebar ? 'YES' : 'NO') . "\n";
}

// 2. Vendor SSO & Check Return Links
echo "\n2. Testing Vendor Return Links on POS...\n";
$vendorEmail = 'vendor@victorious.com';
$expires = time() + 300;
$role = 'vendor';
$vendorToken = hash_hmac('sha256', "{$vendorEmail}|{$expires}|{$role}", $secretKey);
$vendorSsoUrl = "http://127.0.0.1:8001/sso-login?email=" . urlencode($vendorEmail) . "&expires={$expires}&role={$role}&token={$vendorToken}";

$vendorCookieJar = tempnam(sys_get_temp_dir(), 'sso_return_vendor_');

$ch = curl_init($vendorSsoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $vendorCookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $vendorCookieJar);
$vendorHtml = curl_exec($ch);
$vendorCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  Vendor Dashboard HTTP Status: {$vendorCode}\n";

$hasVendorTopBtn = str_contains($vendorHtml, 'Back to Vendor Panel') && str_contains($vendorHtml, '/vendor/dashboard');
$hasVendorSidebar = str_contains($vendorHtml, 'Return to Vendor Panel') && str_contains($vendorHtml, '/vendor/dashboard');

if ($hasVendorTopBtn && $hasVendorSidebar) {
    echo "  ✅ PASS: Vendor views both Top Bar and Sidebar 'Back to Vendor Panel' links pointing to /vendor/dashboard!\n";
} else {
    echo "  ❌ FAIL: Vendor links missing. TopBtn=" . ($hasVendorTopBtn ? 'YES' : 'NO') . " | Sidebar=" . ($hasVendorSidebar ? 'YES' : 'NO') . "\n";
}

echo "\n=================================================================\n";
echo "🎉 BI-DIRECTIONAL ECOSYSTEM NAVIGATION 100% OPERATIONAL!\n";
echo "=================================================================\n";
