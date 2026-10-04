<?php

namespace Tests\Feature;

require_once __DIR__ . '/DumpSchemaTestCase.php';
foreach (['PaymentExceptionRecorder', 'PaystackRefundService', 'DeliveryPaymentInitializationService', 'PickupCashbackAwardService'] as $service) {
    require_once __DIR__ . '/../../app/Services/' . $service . '.php';
}
require_once __DIR__ . '/../../app/Console/Kernel.php';
require_once __DIR__ . '/../../app/Models/Order.php';
require_once __DIR__ . '/../../app/Models/CustomerCashbackLedger.php';
require_once __DIR__ . '/../../app/Http/Requests/Admin/CustomerUpdateSettingsRequest.php';
require_once __DIR__ . '/../../app/Console/Commands/MatureCustomerCashbackCommand.php';
require_once __DIR__ . '/../../app/Traits/Processor.php';
require_once __DIR__ . '/../../app/Http/Controllers/Payment_Methods/PaystackController.php';
require_once __DIR__ . '/../../app/Http/Controllers/RestAPI/v1/customer/DeliveryCheckoutIntentController.php';

use App\Models\CheckoutIntent;
use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\PickupReservation;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\PaystackRefundService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** [AI] Real production controller/service regressions on isolated SQLite, not a contention certificate. */
abstract class GatewayMoneyTestCase extends DumpSchemaTestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->beforeBootstrapping(\Illuminate\Foundation\Bootstrap\BootProviders::class, function ($app) {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite.database', ':memory:');
            $app['config']->set('cache.default', 'array');
            $app['config']->set('session.driver', 'array');
            SqliteDumpLoader::load($app['db']->connection()->getPdo(), $app->basePath('installation/backup/database.sql'));
            foreach (SqliteDumpLoader::migrationsCreatingMissingTables($app->basePath('installation/backup/database.sql')) as $path) {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true, '--path' => $path]);
            }
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->assertSame('sqlite', DB::getDefaultConnection());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['payment_domain','order_group_id','pickup_reservation_id','gateway_reference','attempt_status','active_order_group_id','active_pickup_reservation_id','attempt_expires_at'] as $column) {
            if (!Schema::hasColumn('payment_requests', $column)) {
                Schema::table('payment_requests', fn ($table) => $table->string($column)->nullable());
            }
        }
        foreach (['received_at','refund_window_expires_at'] as $column) {
            if (!Schema::hasColumn('orders', $column)) {
                Schema::table('orders', fn ($table) => $table->timestamp($column)->nullable());
            }
        }
        foreach (['execution_status','paystack_refund_id','execution_ref','payment_info'] as $column) {
            if (!Schema::hasColumn('refund_requests', $column)) {
                Schema::table('refund_requests', fn ($table) => $table->string($column)->nullable());
            }
        }
        DB::table('addon_settings')->where('key_name', 'paystack')->delete();
        DB::table('addon_settings')->insert(['id' => (string) Str::uuid(), 'key_name' => 'paystack', 'mode' => 'test', 'settings_type' => 'payment_config',
            'test_values' => json_encode(['secret_key' => 'isolated-unit-secret', 'public_key' => 'isolated-public', 'merchant_email' => 'test@example.test']),
            'live_values' => '{}', 'created_at' => now(), 'updated_at' => now()]);
        config(['paystack.secretKey' => 'isolated-unit-secret']);
        putenv('PAYSTACK_SECRET_KEY=isolated-unit-secret');
    }

}
