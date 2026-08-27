<?php

if (!defined('DOMAIN_POINTED_DIRECTORY')) {
    define('DOMAIN_POINTED_DIRECTORY', 'public');
}

require __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$urls = [
    '/' => 'Customer Storefront',
    '/login/admin' => 'Admin Login',
    '/vendor/auth/login' => 'Vendor Login',
    '/customer/auth/login' => 'Customer Login',
];

echo "=================================================================\n";
echo "🔍 INTERNAL KERNEL DISPATCH TEST (SQLITE BACKEND)\n";
echo "=================================================================\n\n";

foreach ($urls as $url => $label) {
    try {
        $request = Illuminate\Http\Request::create($url, 'GET');
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        echo ($status >= 200 && $status < 400 ? "✅" : "⚠️") . " [HTTP {$status}] {$label} ({$url})\n";
    } catch (\Throwable $e) {
        echo "❌ [ERROR] {$label} ({$url}): " . $e->getMessage() . "\n";
    }
}
echo "\n=================================================================\n";
