<?php
// [AI] Provider-free, local-only read diagnostic. Database enforces read-only transaction.
require __DIR__ . '/../../../backend/vmarket-web/vendor/autoload.php';
$base = dirname(__DIR__, 3) . '/backend/vmarket-web';
$env = Dotenv\Dotenv::parse(file_get_contents($base . '/.env'));
if (($env['APP_ENV'] ?? '') !== 'local' || ($env['DB_CONNECTION'] ?? '') !== 'mysql'
    || !empty($env['DATABASE_URL']) || !empty($env['DB_URL']) || is_file($base . '/bootstrap/cache/config.php')
    || !in_array($env['DB_HOST'] ?? '', ['127.0.0.1', 'localhost', '::1'], true)) {
    fwrite(STDERR, "Refused nonlocal configuration.\n"); exit(78);
}
$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection(['driver' => 'mysql', 'host' => $env['DB_HOST'], 'port' => $env['DB_PORT'] ?? 3306,
    'database' => $env['DB_DATABASE'], 'username' => $env['DB_USERNAME'], 'password' => $env['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '']);
$capsule->setAsGlobal(); $capsule->bootEloquent();
$container = $capsule->getContainer();
$container->instance('db', $capsule->getDatabaseManager());
$container->bind('db.schema', fn() => $capsule->getConnection()->getSchemaBuilder());
Illuminate\Support\Facades\Facade::setFacadeApplication($container);
require_once $base . '/app/Models/DeliverymanWallet.php';
require_once $base . '/app/Console/Commands/ReconcileV1MoneyCommand.php';
$db = $capsule->getConnection(); $db->statement('SET TRANSACTION READ ONLY'); $db->beginTransaction();
try {
    $tables = ['admin_wallets', 'seller_wallets', (new App\Models\DeliverymanWallet)->getTable(), 'orders',
        'order_details', 'order_transactions', 'transactions', 'withdraw_requests', 'refund_requests',
        'refund_transactions', 'customer_cashback_ledgers', 'cashback_redemptions', 'payment_requests', 'payment_reconciliations'];
    $fingerprint = function () use ($db, $tables): array {
        $result = [];
        foreach ($tables as $table) {
            if (!$db->getSchemaBuilder()->hasTable($table)) continue;
            $rows = $db->table($table)->get()->map(fn($row) => (array)$row)->all();
            usort($rows, fn($a, $b) => strcmp(json_encode($a), json_encode($b)));
            $result[$table] = ['rows' => count($rows), 'sha256' => hash('sha256', json_encode($rows))];
        }
        return $result;
    };
    $before = $fingerprint();
    $command = new class extends App\Console\Commands\ReconcileV1MoneyCommand {
        public array $report = [];
        public function line($string, $style = null, $verbosity = null) { $this->report = json_decode($string, true); }
        public function error($string, $verbosity = null) { throw new RuntimeException($string); }
    };
    $exit = $command->handle();
    $columns = array_values(array_intersect(['id', 'order_id', 'payment_for', 'amount', 'transaction_type', 'created_at', 'updated_at'], $db->getSchemaBuilder()->getColumnListing('transactions')));
    $fixtureLedger = $db->table('transactions')->whereIn('order_id', [4001,4002,4003,4004])->where('payment_for', 'vendor_settlement')->orderBy('id')->get($columns);
    $orderColumns = array_values(array_intersect(['id','transaction_ref','created_at','updated_at'], $db->getSchemaBuilder()->getColumnListing('orders')));
    $fixtureOrders = $db->table('orders')->whereIn('id',[4001,4002,4003,4004])->orderBy('id')->get($orderColumns);
    $applied = $db->table('migrations')->pluck('migration')->all();
    $pending = array_values(array_diff(array_map(fn($path) => basename($path, '.php'), glob($base.'/database/migrations/*.php')), $applied));
    $process = new Symfony\Component\Process\Process([PHP_BINARY, $base.'/tests/regression/e2e_marketplace_lifecycle_proof.php']);
    $process->setTimeout(15); $process->run();
    $after = $fingerprint();
    echo json_encode(['scope'=>'configured local DB; not production certification','read_only_transaction'=>true,
        'connection_identity'=>['app_env'=>'local','driver'=>'mysql','host'=>$env['DB_HOST'],'database'=>$env['DB_DATABASE'],
            'cached_config_present'=>false,'database_url_present'=>false],
        'captured_at_utc'=>gmdate('c'), 'diagnostic_exit'=>$exit, 'reconciliation'=>$command->report,
        'migrations'=>['applied'=>count($applied),'pending'=>$pending],
        'legacy_entrypoint_exit'=>$process->getExitCode(),'legacy_entrypoint_stderr'=>trim($process->getErrorOutput()),
        'fixture_order_rows'=>$fixtureOrders,'fixture_vendor_release_rows'=>$fixtureLedger,
        'financial_fingerprints_before'=>$before,'financial_fingerprints_after'=>$after,'same_snapshot_unchanged'=>$before===$after,
        'limitation'=>'Fingerprints compare one read-only transaction snapshot; they do not certify absence of independent concurrent writes.'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
} finally { $db->rollBack(); }
