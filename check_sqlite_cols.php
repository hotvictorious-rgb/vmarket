<?php
require_once __DIR__ . '/backend/vmarket-web/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/vmarket-web/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['admins', 'sellers', 'users', 'delivery_men', 'shops'] as $table) {
    echo "--- Table: {$table} ---\n";
    try {
        $cols = DB::select("PRAGMA table_info({$table})");
        foreach ($cols as $c) {
            echo "  " . $c->name . " (" . $c->type . ")\n";
        }
    } catch (\Throwable $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
