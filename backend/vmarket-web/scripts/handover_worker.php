<?php
/**
 * [AI] Worker Process for Concurrent Handover Simulation
 * Bootstraps Laravel, authenticates as customer, and executes confirmDoorstepDelivery.
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Customer\OrderHandoverController;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

$options = getopt('', ['order-id:', 'code:', 'db-path:']);
$orderId = (int) ($options['order-id'] ?? 0);
$code = (string) ($options['code'] ?? '998877');

if (!empty($options['db-path'])) {
    config(['database.connections.sqlite.database' => $options['db-path']]);
    config(['database.default' => 'sqlite']);
    \Illuminate\Support\Facades\DB::purge('sqlite');
    \Illuminate\Support\Facades\DB::reconnect('sqlite');
}

$order = Order::find($orderId);
if (!$order) {
    echo json_encode(['pid' => getmypid(), 'status' => 404, 'message' => 'Order not found']);
    exit(1);
}

$customer = User::find($order->customer_id);

$image = UploadedFile::fake()->image('doorstep_worker_' . getmypid() . '.jpg', 400, 400);
$request = Request::create('/order/doorstep-confirm', 'POST', [
    'order_id' => $order->id,
    'delivery_code' => $code,
], [], ['image' => $image]);
$request->headers->set('Accept', 'application/json');
$request->setUserResolver(fn () => $customer);

$controller = app(OrderHandoverController::class);

$startTime = microtime(true);
$response = $controller->confirmDoorstepDelivery($request);
$endTime = microtime(true);

$statusCode = $response->getStatusCode();
$content = json_decode($response->getContent(), true) ?: [];

echo json_encode([
    'pid' => getmypid(),
    'start_time' => $startTime,
    'end_time' => $endTime,
    'duration_ms' => round(($endTime - $startTime) * 1000, 2),
    'status_code' => $statusCode,
    'response' => $content,
]);

exit($statusCode === 200 ? 0 : 2);
