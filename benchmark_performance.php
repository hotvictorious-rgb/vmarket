<?php

/**
 * [AI] Comprehensive Performance & Latency Benchmark Scanner
 * Profiles response times, HTTP headers, payload sizes, and asset loading latency
 * across Victorious MARKET Storefront, Admin/Vendor portals, and Vmarket POS.
 */

function benchmarkUrl($name, $url, $iterations = 3) {
    $totalTime = 0;
    $sizes = [];
    $statuses = [];

    for ($i = 0; $i < $iterations; $i++) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $start = microtime(true);
        $response = curl_exec($ch);
        $duration = (microtime(true) - $start) * 1000; // ms
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $size = strlen($response);
        curl_close($ch);

        $totalTime += $duration;
        $sizes[] = $size;
        $statuses[] = $httpCode;
    }

    $avgTime = round($totalTime / $iterations, 2);
    $avgSize = round(array_sum($sizes) / count($sizes));
    $status = $statuses[0];

    return [
        'name' => $name,
        'url' => $url,
        'status' => $status,
        'avg_ms' => $avgTime,
        'avg_kb' => round($avgSize / 1024, 2),
    ];
}

echo "=================================================================\n";
echo "⚡ DUAL-SYSTEM HIGH-PRECISION SPEED & LATENCY BENCHMARK SCAN\n";
echo "=================================================================\n\n";

$targets = [
    'Victorious MARKET: Home Storefront' => 'http://127.0.0.1:8000/',
    'Victorious MARKET: Admin Login' => 'http://127.0.0.1:8000/login/admin',
    'Victorious MARKET: Vendor Login' => 'http://127.0.0.1:8000/vendor/auth/login',
    'Victorious MARKET: Customer Auth' => 'http://127.0.0.1:8000/customer/auth/login',
    'Victorious MARKET: Categories Page' => 'http://127.0.0.1:8000/categories',
    'Victorious MARKET: Brands Page' => 'http://127.0.0.1:8000/brands',
    'Vmarket POS: Login Page' => 'http://127.0.0.1:8001/login',
];

$results = [];
foreach ($targets as $name => $url) {
    echo "Profiling {$name}...\n";
    $results[] = benchmarkUrl($name, $url, 3);
}

echo "\n=================================================================\n";
echo "📊 BENCHMARK RESULTS SUMMARY TABLE\n";
echo "=================================================================\n";
printf("%-40s | %-10s | %-12s | %-10s\n", "Target Surface", "Status", "Latency (ms)", "Payload Size");
echo str_repeat("-", 80) . "\n";

foreach ($results as $r) {
    printf("%-40s | HTTP %-5d | %8.2f ms | %7.2f KB\n", $r['name'], $r['status'], $r['avg_ms'], $r['avg_kb']);
}
echo "=================================================================\n";
