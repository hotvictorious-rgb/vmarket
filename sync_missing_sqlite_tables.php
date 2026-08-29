<?php
require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$migration = require __DIR__ . '/backend/vmarket-web/database/migrations/2026_08_26_000001_create_omnichannel_pos_and_debt_tables.php';
$migration->up();

echo "Omnichannel POS and Subscription tables synchronized successfully!\n";
