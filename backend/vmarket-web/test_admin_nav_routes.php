<?php
/**
 * [AI] Extract all route() calls from admin sidebar and verify they exist.
 * Run from: backend/vmarket-web/
 */

chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Route;

$sidebarFile   = __DIR__ . '/resources/views/layouts/admin/partials/_side-bar.blade.php';
$headerFile    = __DIR__ . '/resources/views/layouts/admin/partials/_header.blade.php';

echo "\n";
echo "============================================================\n";
echo "  ADMIN NAV ROUTE EXTRACTION & VERIFICATION\n";
echo "  Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "============================================================\n\n";

// Extract all route('...') calls from a file
function extractRoutes(string $file): array {
    $content = file_get_contents($file);
    // match route('name') and route('name', [...]) and route("name")
    preg_match_all("/route\(['\"]([a-zA-Z0-9_.\-]+)['\"]/", $content, $m);
    return array_unique($m[1]);
}

$sidebarRoutes = extractRoutes($sidebarFile);
$headerRoutes  = extractRoutes($headerFile);
$allRoutes     = array_unique(array_merge($headerRoutes, $sidebarRoutes));
sort($allRoutes);

echo "Found " . count($sidebarRoutes) . " routes in sidebar\n";
echo "Found " . count($headerRoutes)  . " routes in header\n";
echo "Total unique routes to verify: " . count($allRoutes) . "\n\n";

echo sprintf("%-6s %-48s %s\n", 'STATUS', 'ROUTE NAME', 'RESOLVED URL');
echo str_repeat('-', 110) . "\n";

$pass = 0;
$fail = 0;
$failList = [];

foreach ($allRoutes as $routeName) {
    $exists = Route::has($routeName);
    if ($exists) {
        try {
            // Try to generate URL (may fail if route has required params)
            $url = route($routeName);
            printf("%-6s %-48s %s\n", '✅', $routeName, parse_url($url, PHP_URL_PATH));
            $pass++;
        } catch (\Throwable $e) {
            // Route exists but needs params - that's OK
            printf("%-6s %-48s %s\n", '✅*', $routeName, '(requires params - route exists)');
            $pass++;
        }
    } else {
        printf("%-6s %-48s %s\n", '❌', $routeName, 'NOT REGISTERED');
        $fail++;
        $failList[] = $routeName;
    }
}

echo str_repeat('=', 110) . "\n\n";
echo "SUMMARY:\n";
echo "  ✅ PASS: $pass routes registered\n";
echo "  ❌ FAIL: $fail routes NOT registered\n";
echo "  (* = route exists but requires URL parameters)\n\n";

if ($fail > 0) {
    echo "❌ BROKEN ROUTES (used in admin nav but not registered):\n";
    foreach ($failList as $r) {
        echo "   - $r\n";
    }
    echo "\n";
} else {
    echo "✅ ALL ADMIN NAV ROUTES ARE REGISTERED — PASS\n\n";
}
