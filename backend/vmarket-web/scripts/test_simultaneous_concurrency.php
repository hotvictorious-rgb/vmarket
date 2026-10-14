<?php
/**
 * [AI] Real Simultaneous Multi-Process Concurrency Test
 *
 * SAFETY INVARIANTS:
 * 1. Strictly refuses to run in production (APP_ENV=production guard).
 * 2. Operates EXCLUSIVELY on an isolated, disposable temporary test database file.
 *    The active/shared database is NEVER modified, polluted, or touched.
 * 3. Spawns 2 concurrent OS worker processes via proc_open simultaneously.
 * 4. STRICT PASS CRITERIA:
 *    - Exactly one process commits with HTTP 200.
 *    - The competing process receives a controlled non-500 rejection (HTTP 422 or HTTP 409).
 *    - ZERO HTTP 500 infrastructure crashes permitted.
 *    - Wallet balance credited by exactly 1 delivery fee (Delta = 0.00).
 *    - Exactly 1 delivery verification record and 1 customer cashback row.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CustomerCashbackLedger;
use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use App\Models\DeliveryManTransaction;
use App\Models\Order;
use App\Models\OrderDeliveryVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "===============================================================\n";
echo " Victorious MARKET - Isolated Multi-Process Concurrency Proof  \n";
echo "===============================================================\n";

// Guard 1: Environment Safety Guard
if (app()->environment('production') || env('APP_ENV') === 'production') {
    fwrite(STDERR, "CRITICAL ERROR: Concurrency simulation CANNOT run in production environment!\n");
    exit(1);
}

// Guard 2: Dedicated Disposable Test Database Isolation
$testDir = storage_path('framework/testing');
if (!is_dir($testDir)) {
    mkdir($testDir, 0777, true);
}
$tempDbPath = $testDir . DIRECTORY_SEPARATOR . 'concurrency_test_' . uniqid() . '.sqlite';

$sourceDb = database_path('database.sqlite');
if (file_exists($sourceDb)) {
    copy($sourceDb, $tempDbPath);
} else {
    touch($tempDbPath);
}

echo "[*] Initialized isolated disposable test database:\n    {$tempDbPath}\n";

try {
    // Configure dynamic test connection pointing strictly to the isolated temp database
    config(['database.connections.concurrency_test' => [
        'driver' => 'sqlite',
        'database' => $tempDbPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    config(['database.default' => 'concurrency_test']);
    DB::purge('concurrency_test');
    DB::reconnect('concurrency_test');

    // Path to PHP binary
    $phpBin = PHP_BINARY;
    if (!file_exists($phpBin)) {
        $phpBin = 'C:\\Users\\USER\\AppData\\Local\\Programs\\PHP\\php.exe';
    }

    // Ensure schema columns exist on the isolated test database
    if (!Schema::hasColumn('orders', 'refund_window_expires_at')) {
        Schema::table('orders', function ($table) {
            $table->timestamp('refund_window_expires_at')->nullable();
        });
    }
    if (!Schema::hasColumn('orders', 'received_at')) {
        Schema::table('orders', function ($table) {
            $table->timestamp('received_at')->nullable();
        });
    }
    if (!Schema::hasColumn('orders', 'handed_over_at')) {
        Schema::table('orders', function ($table) {
            $table->timestamp('handed_over_at')->nullable();
        });
    }
    if (!Schema::hasColumn('orders', 'deliveryman_charge')) {
        Schema::table('orders', function ($table) {
            $table->decimal('deliveryman_charge', 12, 2)->default(0.00);
        });
    }
    if (!Schema::hasColumn('orders', 'delivery_commission_amount')) {
        Schema::table('orders', function ($table) {
            $table->decimal('delivery_commission_amount', 12, 2)->default(0.00);
        });
    }
    if (!Schema::hasColumn('orders', 'pickup_verification_code')) {
        Schema::table('orders', function ($table) {
            $table->string('pickup_verification_code')->nullable();
        });
    }
    if (!Schema::hasColumn('orders', 'delivered_by')) {
        Schema::table('orders', function ($table) {
            $table->string('delivered_by')->nullable();
        });
    }
    if (!Schema::hasColumn('order_delivery_verifications', 'handover_type')) {
        Schema::table('order_delivery_verifications', function ($table) {
            $table->string('handover_type')->nullable();
            $table->string('verified_by_type')->nullable();
            $table->unsignedBigInteger('verified_by_id')->nullable();
            $table->string('pickup_otp_used')->nullable();
        });
    }
    if (!Schema::hasColumn('customer_cashback_ledgers', 'expires_at')) {
        Schema::table('customer_cashback_ledgers', function ($table) {
            $table->timestamp('expires_at')->nullable();
        });
    }

    // Ensure unique index exists on customer_cashback_ledgers
    $indexes = DB::select("PRAGMA index_list('customer_cashback_ledgers')");
    $hasUnique = false;
    foreach ($indexes as $idx) {
        if ($idx->unique && str_contains($idx->name, 'order_id')) {
            $hasUnique = true;
            break;
        }
    }
    if (!$hasUnique) {
        try {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS customer_cashback_ledgers_order_id_unique ON customer_cashback_ledgers (order_id)");
        } catch (\Throwable $e) {}
    }

    DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_status'], ['value' => '1']);
    DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_for_each_order'], ['value' => '1']);
    cache()->flush();

    // Setup Test Fixtures strictly inside isolated disposable database
    $customer = User::create([
        'name' => 'Concurrent Isolation Customer ' . uniqid(),
        'email' => 'concurr_iso_' . uniqid() . '@example.com',
        'phone' => '+23480' . rand(10000000, 99999999),
        'password' => bcrypt('password123'),
    ]);

    $deliveryMan = DeliveryMan::create([
        'f_name' => 'Parallel',
        'l_name' => 'Rider',
        'phone' => '+23481' . rand(10000000, 99999999),
        'email' => 'rider_iso_' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
        'is_active' => 1,
    ]);

    $wallet = DeliverymanWallet::create([
        'delivery_man_id' => $deliveryMan->id,
        'current_balance' => '0.00',
        'cash_in_hand' => '0.00',
        'pending_withdraw' => '0.00',
        'total_withdraw' => '0.00',
    ]);

    $deliveryCharge = '1200.00';
    $secretCode = '998877';

    $order = Order::create([
        'customer_id' => $customer->id,
        'is_guest' => 0,
        'customer_type' => 'customer',
        'payment_status' => 'paid',
        'order_status' => 'out_for_delivery',
        'order_type' => 'default_type',
        'payment_method' => 'paystack',
        'order_amount' => 15000.00,
        'shipping_cost' => 1500.00,
        'deliveryman_charge' => $deliveryCharge,
        'delivery_man_id' => $deliveryMan->id,
        'verification_code' => $secretCode,
        'verification_status' => 0,
    ]);

    echo "[*] Created Test Order #{$order->id} (Status: out_for_delivery)\n";
    echo "[*] Assigned Rider #{$deliveryMan->id} (Initial Balance: ₦{$wallet->current_balance})\n";
    echo "[*] Spawning 2 simultaneous worker processes on isolated DB...\n";

    // Launch Process A and Process B concurrently targeting the isolated test DB
    $workerScript = escapeshellarg(__DIR__ . '/handover_worker.php');
    $orderArg = escapeshellarg($order->id);
    $codeArg = escapeshellarg($secretCode);
    $dbArg = escapeshellarg($tempDbPath);

    $cmdA = "\"$phpBin\" $workerScript --order-id=$orderArg --code=$codeArg --db-path=$dbArg";
    $cmdB = "\"$phpBin\" $workerScript --order-id=$orderArg --code=$codeArg --db-path=$dbArg";

    $descriptor = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $startTimeMicro = microtime(true);
    $procA = proc_open($cmdA, $descriptor, $pipesA);
    $procB = proc_open($cmdB, $descriptor, $pipesB);

    fclose($pipesA[0]);
    fclose($pipesB[0]);

    $outA = stream_get_contents($pipesA[1]);
    $errA = stream_get_contents($pipesA[2]);
    fclose($pipesA[1]); fclose($pipesA[2]);
    $statusA = proc_close($procA);

    $outB = stream_get_contents($pipesB[1]);
    $errB = stream_get_contents($pipesB[2]);
    fclose($pipesB[1]); fclose($pipesB[2]);
    $statusB = proc_close($procB);

    $endTimeMicro = microtime(true);
    $totalElapsedMs = round(($endTimeMicro - $startTimeMicro) * 1000, 2);

    echo "[+] Both processes completed in {$totalElapsedMs}ms\n\n";

    $dataA = json_decode(trim($outA), true) ?: ['status_code' => 500, 'raw' => $outA];
    $dataB = json_decode(trim($outB), true) ?: ['status_code' => 500, 'raw' => $outB];

    echo "Worker A (PID " . ($dataA['pid'] ?? '?') . "): HTTP {$dataA['status_code']} in " . ($dataA['duration_ms'] ?? '?') . "ms\n";
    echo "  Response: " . json_encode($dataA['response'] ?? $dataA) . "\n";
    if (!empty($errA)) echo "  Stderr: $errA\n";

    echo "Worker B (PID " . ($dataB['pid'] ?? '?') . "): HTTP {$dataB['status_code']} in " . ($dataB['duration_ms'] ?? '?') . "ms\n";
    echo "  Response: " . json_encode($dataB['response'] ?? $dataB) . "\n";
    if (!empty($errB)) echo "  Stderr: $errB\n";

    // Strict Pass & Financial Invariant Assertions
    $order->refresh();
    $wallet->refresh();
    $txCount = DeliveryManTransaction::where('delivery_man_id', $deliveryMan->id)->count();
    $verifCount = OrderDeliveryVerification::where('order_id', $order->id)->count();
    $cashbackCount = CustomerCashbackLedger::where('order_id', $order->id)->count();

    $codes = [$dataA['status_code'] ?? 0, $dataB['status_code'] ?? 0];
    sort($codes);

    $hasOneSuccess = in_array(200, $codes, true);
    // Controlled non-500 rejection (422: order already delivered, or 409: busy lock contention)
    $hasOneControlledRejection = (in_array(422, $codes, true) || in_array(409, $codes, true));
    // Zero 500 infrastructure crashes
    $hasZero500Errors = !in_array(500, $codes, true);

    echo "\n--- Mathematical & Financial Verification ---\n";
    echo "Order Status: {$order->order_status} (Expected: delivered)\n";
    echo "Order Verifications: {$verifCount} (Expected: 1)\n";
    echo "Rider Wallet Balance: ₦{$wallet->current_balance} (Expected: ₦{$deliveryCharge})\n";
    echo "Rider Transactions Count: {$txCount} (Expected: 1)\n";
    echo "Customer Cashback Ledger Rows: {$cashbackCount} (Expected: 1)\n";

    $delta = bcsub((string)$wallet->current_balance, $deliveryCharge, 2);
    echo "Financial Balance Drift: Δ = ₦{$delta}\n";

    echo "\n--- Concurrency Response Code Validation ---\n";
    echo "Success Worker (HTTP 200): " . ($hasOneSuccess ? "PASS" : "FAIL") . "\n";
    echo "Controlled Rejection (HTTP 422/409): " . ($hasOneControlledRejection ? "PASS" : "FAIL") . "\n";
    echo "Zero 500 Infrastructure Errors: " . ($hasZero500Errors ? "PASS" : "FAIL") . "\n";

    $pass = ($order->order_status === 'delivered')
        && ($verifCount === 1)
        && ($txCount === 1)
        && ($cashbackCount === 1)
        && (bccomp($delta, '0.00', 2) === 0)
        && $hasOneSuccess
        && $hasOneControlledRejection
        && $hasZero500Errors;

    $exitCode = 1;
    if ($pass) {
        echo "\n[PASS] Real simultaneous multi-process concurrency proof SUCCEEDED!\n";
        echo "       - Exactly one worker committed (HTTP 200).\n";
        echo "       - Competing worker received controlled rejection (HTTP 422/409).\n";
        echo "       - Zero HTTP 500 errors.\n";
        echo "       - Zero balance drift (Δ = ₦0.00). No double credit.\n";
        $exitCode = 0;
    } else {
        echo "\n[FAIL] Concurrency proof invariants violated!\n";
        $exitCode = 1;
    }
} finally {
    // Clean up temporary disposable database
    DB::disconnect('concurrency_test');
    unset($order, $wallet, $customer, $deliveryMan);
    gc_collect_cycles();
    if (file_exists($tempDbPath)) {
        @unlink($tempDbPath);
        echo "[*] Cleaned up disposable test database.\n";
    }
}

exit($exitCode);
