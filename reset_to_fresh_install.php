<?php

/**
 * [AI] Clean System Reset to Fresh Installation State
 * Prepares both Victorious MARKET and Vmarket POS for interactive user onboarding.
 */

echo "=================================================================\n";
echo "🧹 RESETTING BOTH SYSTEMS TO FRESH INSTALLATION STATE\n";
echo "=================================================================\n\n";

// 1. Reset Victorious MARKET
echo "1. Preparing Victorious MARKET Installer...\n";
$vmInstalledFile = __DIR__ . '/backend/vmarket-web/storage/installed';
if (file_exists($vmInstalledFile)) {
    unlink($vmInstalledFile);
    echo "  ✅ Removed storage/installed\n";
}

$routesInstall = __DIR__ . '/backend/vmarket-web/installation/activate_install_routes.txt';
$routesActive  = __DIR__ . '/backend/vmarket-web/app/Providers/RouteServiceProvider.php';
if (file_exists($routesInstall)) {
    copy($routesInstall, $routesActive);
    echo "  ✅ Activated installer routing (routes/install.php)\n";
}

// Clear Victorious MARKET Framework Caches
shell_exec('cd backend/vmarket-web && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear');
echo "  ✅ Cleared Victorious MARKET caches\n";

// 2. Reset Vmarket POS
echo "\n2. Preparing Vmarket POS Installer...\n";
$posInstalledFile = __DIR__ . '/hysam/storage/installed';
if (file_exists($posInstalledFile)) {
    unlink($posInstalledFile);
    echo "  ✅ Removed hysam/storage/installed\n";
}

// Clear Vmarket POS Framework Caches
shell_exec('cd hysam && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear');
echo "  ✅ Cleared Vmarket POS caches\n";

echo "\n=================================================================\n";
echo "🎉 BOTH SYSTEMS ARE NOW IN FRESH INSTALLATION MODE!\n";
echo "=================================================================\n";
