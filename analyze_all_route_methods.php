<?php
require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = \Illuminate\Support\Facades\Route::getRoutes();

$methodBreakdown = [
    'GET'    => ['count' => 0, 'examples' => []],
    'POST'   => ['count' => 0, 'examples' => []],
    'PUT'    => ['count' => 0, 'examples' => []],
    'PATCH'  => ['count' => 0, 'examples' => []],
    'DELETE' => ['count' => 0, 'examples' => []],
];

$moduleBreakdown = [
    'Super Admin Panel'       => ['count' => 0, 'methods' => []],
    'Merchant / Vendor Panel' => ['count' => 0, 'methods' => []],
    'In-Store POS Terminal'   => ['count' => 0, 'methods' => []],
    'Delivery & Fleet Hub'    => ['count' => 0, 'methods' => []],
    'Customer Web Storefront' => ['count' => 0, 'methods' => []],
    'Mobile REST APIs (v1-v3)'=> ['count' => 0, 'methods' => []],
];

foreach ($routes as $route) {
    $methods = array_diff($route->methods(), ['HEAD']);
    $primaryMethod = $methods[0] ?? 'GET';
    $uri = $route->uri();
    
    if (isset($methodBreakdown[$primaryMethod])) {
        $methodBreakdown[$primaryMethod]['count']++;
        if (count($methodBreakdown[$primaryMethod]['examples']) < 4) {
            $methodBreakdown[$primaryMethod]['examples'][] = $uri;
        }
    }

    $module = 'Customer Web Storefront';
    if (str_starts_with($uri, 'admin')) {
        $module = 'Super Admin Panel';
    } elseif (str_starts_with($uri, 'vendor')) {
        $module = 'Merchant / Vendor Panel';
    } elseif (str_starts_with($uri, 'pos')) {
        $module = 'In-Store POS Terminal';
    } elseif (str_starts_with($uri, 'delivery')) {
        $module = 'Delivery & Fleet Hub';
    } elseif (str_starts_with($uri, 'api/')) {
        $module = 'Mobile REST APIs (v1-v3)';
    }

    $moduleBreakdown[$module]['count']++;
    $moduleBreakdown[$module]['methods'][$primaryMethod] = ($moduleBreakdown[$module]['methods'][$primaryMethod] ?? 0) + 1;
}

echo "========================================================================================\n";
echo "📊 COMPLETE ECOSYSTEM ROUTE & HTTP METHOD AUDIT ANALYSIS\n";
echo "========================================================================================\n\n";

echo "=== 1. BREAKDOWN BY HTTP VERB / METHOD ===\n";
foreach ($methodBreakdown as $m => $info) {
    printf("  %-8s: %4d endpoints | Examples: %s\n", $m, $info['count'], implode(', ', $info['examples']));
}
echo "  TOTAL   : " . count($routes) . " endpoints\n\n";

echo "=== 2. BREAKDOWN BY PLATFORM MODULE & METHODS ===\n";
foreach ($moduleBreakdown as $mod => $info) {
    $methodStr = [];
    foreach ($info['methods'] as $m => $c) {
        $methodStr[] = "{$m}: {$c}";
    }
    printf("  • %-26s: %4d endpoints (%s)\n", $mod, $info['count'], implode(', ', $methodStr));
}
echo "\n========================================================================================\n";
