<?php

/**
 * [AI] End-to-End Dual Server Health Check
 * Probes both Victorious MARKET (Port 8000) and Vmarket POS (Port 8001) over real HTTP sockets.
 */

function checkUrl($name, $url) {
    echo "Testing {$name} ({$url})...\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 400) {
        echo "  ✅ SUCCESS: [HTTP {$httpCode}]\n";
        return true;
    } else {
        echo "  ❌ FAILED: [HTTP {$httpCode}] - {$error}\n";
        return false;
    }
}

echo "=================================================================\n";
echo "🌐 DUAL-SERVER HTTP LIVE PROBE TEST\n";
echo "=================================================================\n\n";

$tests = [
    'Victorious MARKET Storefront' => 'http://127.0.0.1:8000/',
    'Victorious MARKET Admin Login' => 'http://127.0.0.1:8000/login/admin',
    'Victorious MARKET Vendor Login' => 'http://127.0.0.1:8000/vendor/auth/login',
    'Vmarket POS Login Page' => 'http://127.0.0.1:8001/login',
];

$allPassed = true;
foreach ($tests as $name => $url) {
    if (!checkUrl($name, $url)) {
        $allPassed = false;
    }
}

echo "\n=================================================================\n";
if ($allPassed) {
    echo "🎉 ALL LOCAL SERVERS ARE 100% LIVE, FUNCTIONAL, AND RESPONSIVE!\n";
} else {
    echo "⚠️ SOME SERVERS ARE STILL INITIALIZING OR NOT RUNNING.\n";
}
echo "=================================================================\n";
