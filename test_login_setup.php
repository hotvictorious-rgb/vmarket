<?php

require __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$loginSetups = DB::table('login_setups')->get();
echo "login_setups count: " . $loginSetups->count() . "\n";
foreach ($loginSetups as $s) {
    echo "  - key: {$s->key}, value: {$s->value}\n";
}
