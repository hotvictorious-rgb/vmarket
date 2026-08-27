<?php

if (!defined('DOMAIN_POINTED_DIRECTORY')) {
    define('DOMAIN_POINTED_DIRECTORY', 'public');
}

$start = microtime(true);

require __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$t_autoload = (microtime(true) - $start) * 1000;

$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/', 'GET');

$t_before = (microtime(true) - $start) * 1000;

$response = $kernel->handle($request);

$t_total = (microtime(true) - $start) * 1000;

echo "=================================================================\n";
echo "🔬 DEEP PROFILE OF VICTORIOUS MARKET STOREFRONT (/)\n";
echo "=================================================================\n";
printf("Total Execution Time:  %8.2f ms\n", $t_total);
printf("  - Composer Autoload: %8.2f ms\n", $t_autoload);
printf("  - Bootstrap / App:   %8.2f ms\n", $t_before - $t_autoload);
printf("  - Router & Views:    %8.2f ms\n", $t_total - $t_before);
printf("  - Response Status:   HTTP %d\n", $response->getStatusCode());
printf("  - Response Size:     %8.2f KB\n", strlen($response->getContent()) / 1024);
echo "=================================================================\n";
