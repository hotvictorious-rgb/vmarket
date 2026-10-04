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
class GatewayMoneyRepairTest extends DumpSchemaTestCase
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

    private function customer(): User
    {
        return User::create(['name' => 'Regression', 'f_name' => 'Regression', 'l_name' => 'Test', 'email' => Str::uuid().'@example.test',
            'phone' => '080'.random_int(10000000,99999999), 'password' => 'test', 'is_active' => 1, 'loyalty_point' => 0]);
    }

    private function order(User $user, string $group = ''): Order
    {
        return Order::create(['customer_id' => $user->id, 'customer_type' => 'customer', 'seller_id' => 1, 'seller_is' => 'seller',
            'order_status' => 'delivered', 'payment_status' => 'paid', 'payment_method' => 'paystack', 'order_amount' => '100.00',
            'transaction_ref' => 'internal-reference', 'order_group_id' => $group]);
    }

    private function payment(User $user, array $values): PaymentRequest
    {
        return PaymentRequest::create($values + ['id' => (string) Str::uuid(), 'payer_id' => (string) $user->id,
            'payment_amount' => '100.00', 'currency_code' => 'NGN', 'payment_method' => 'paystack', 'is_paid' => 0,
            'payer_information' => '{}', 'additional_data' => '{}']);
    }

    public function testActualKernelRegistersFinancialJobsExactlyOnce(): void
    {
        $kernel = $this->app->make(\Illuminate\Contracts\Console\Kernel::class);
        $this->assertInstanceOf(\App\Console\Kernel::class, $kernel);
        $events = $kernel->resolveConsoleSchedule()->events();
        foreach (['cashback:mature', 'orders:process-settlement-eligibility', 'products:check-price-expiry', 'products:check-marketplace-freshness'] as $command) {
            $matches = array_filter($events, fn ($event) => str_contains($event->command ?? '', $command));
            $this->assertCount(1, $matches, $command);
        }
    }

    public function testRefundReferenceUsesSuccessfulAttemptAndPermanentPickupLink(): void
    {
        $user = $this->customer();
        $order = $this->order($user, 'reference-group');
        $this->payment($user, ['order_group_id' => 'reference-group', 'is_paid' => 1, 'attempt_status' => 'reconciliation_required', 'gateway_reference' => 'bad-reference']);
        $this->payment($user, ['order_group_id' => 'reference-group', 'is_paid' => 1, 'attempt_status' => 'successful', 'gateway_reference' => 'good-reference']);
        $this->assertSame('good-reference', PaystackRefundService::resolvePaystackReferenceForOrder($order));
        $pickupOrder = $this->order($user);
        $reservation = PickupReservation::create(['customer_id' => $user->id, 'seller_id' => 1, 'shop_id' => 1,
            'reservation_code' => 'R-TEST', 'idempotency_key' => 'pickup-test', 'reservation_fingerprint' => str_repeat('a',64),
            'status' => 'order_placed', 'total_amount' => '100.00', 'reservation_items' => [], 'order_id' => $pickupOrder->id, 'expires_at' => now()]);
        $this->payment($user, ['pickup_reservation_id' => $reservation->id, 'active_pickup_reservation_id' => null,
            'is_paid' => 1, 'attempt_status' => 'successful', 'gateway_reference' => 'pickup-reference']);
        $this->assertSame('pickup-reference', PaystackRefundService::resolvePaystackReferenceForOrder($pickupOrder));
    }

    public function testSignedUnknownCaptureIsDurableAndReplayDoesNotPostOrder(): void
    {
        $event = ['event' => 'charge.success', 'data' => ['id' => 987, 'reference' => 'unknown-capture', 'amount' => 12345, 'currency' => 'NGN', 'metadata' => ['type' => 'delivery_payment', 'order_id' => 1]]];
        $body = json_encode($event);
        $controller = $this->app->make(\App\Http\Controllers\Payment_Methods\PaystackController::class);
        config(['paystack.secretKey' => 'isolated-unit-secret']);
        $request = \Illuminate\Http\Request::create('/payment/paystack/webhook', 'POST', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512',$body,'isolated-unit-secret')], $body);
        $count = Order::count();
        $this->assertSame(200, $controller->webhook($request)->getStatusCode());
        $this->assertSame(200, $controller->webhook($request)->getStatusCode());
        $this->assertSame($count, Order::count());
        $this->assertSame(1, DB::table('payment_reconciliations')->where('gateway_reference','unknown-capture')->count());
        $case = DB::table('payment_reconciliations')->where('gateway_reference','unknown-capture')->first();
        $this->assertEquals('123.45', $case->captured_amount);
        $this->assertCount(1, json_decode($case->audit_events,true));
    }

    public function testCompletedRefundCannotRegressOnPendingOrFailedProviderNotification(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $refund = RefundRequest::create(['order_id' => $order->id, 'customer_id' => $user->id, 'order_details_id' => 1,
            'product_id' => 1, 'amount' => '100.00', 'refund_reason' => 'test', 'status' => 'refunded',
            'execution_status' => 'succeeded', 'paystack_refund_id' => '987']);
        foreach (['refund.pending','refund.failed'] as $name) {
            $payload = ['event' => $name, 'data' => ['id' => '987', 'transaction_reference' => 'internal-reference', 'merchant_note' => 'vmarket_refund_'.$refund->id, 'amount' => 10000, 'currency' => 'NGN']];
            $body = json_encode($payload);
            $result = (new PaystackRefundService())->handleRefundWebhook($payload, hash_hmac('sha512',$body,'isolated-unit-secret'),$body);
            $this->assertTrue($result['status']);
            $this->assertSame('succeeded',$refund->fresh()->execution_status);
        }
    }

    public function testActualStatusExpiryReleasesRewardsAndPreservesConvertedIntent(): void
    {
        $user = $this->customer();
        // [AI] Isolated controller identity without Passport keys or external token issuance.
        config(['auth.guards.api.driver' => 'session']);
        $this->actingAs($user,'api');
        $intent = CheckoutIntent::create(['customer_id' => $user->id, 'order_group_id' => 'expiry-group', 'idempotency_key' => 'expiry-key',
            'cart_fingerprint' => str_repeat('b',64), 'active_cart_token' => str_repeat('b',64), 'status' => 'pending',
            'total_amount' => '100.00', 'currency' => 'NGN', 'checkout_snapshot' => [], 'expires_at' => now()->subMinute()]);
        DB::table('cashback_redemptions')->insert(['customer_id' => $user->id, 'checkout_intent_id' => $intent->id, 'order_group_id' => $intent->order_group_id,
            'points' => 5, 'cashback_amount' => 5, 'status' => 'reserved', 'created_at' => now(), 'updated_at' => now()]);
        $payment = $this->payment($user,['order_group_id' => $intent->order_group_id,'attempt_status' => 'pending','active_order_group_id' => $intent->order_group_id]);
        $controller = $this->app->make(\App\Http\Controllers\RestAPI\v1\customer\DeliveryCheckoutIntentController::class);
        $response = $controller->status(new \Illuminate\Http\Request(), $intent->order_group_id);
        $this->assertSame('expired',$response->getData(true)['intent_status']);
        $this->assertSame('released',DB::table('cashback_redemptions')->where('checkout_intent_id',$intent->id)->value('status'));
        $this->assertNull($payment->fresh()->active_order_group_id);
        $intent->update(['status' => 'converted_to_orders']);
        $payment->update(['is_paid' => 1,'attempt_status' => 'reconciliation_required']);
        $response = $controller->status(new \Illuminate\Http\Request(),$intent->order_group_id);
        $this->assertSame('converted_to_orders',$intent->fresh()->status);
        $this->assertSame('reconciliation_required',$response->getData(true)['payment_status']);
    }

    public function testActualMaturationCreditsCurrentAmountExactlyOnceAndRetainsVendorDisputeHold(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $order->update(['received_at' => now()->subDays(2), 'refund_window_expires_at' => now()->subDay()]);
        $ledger = \App\Models\CustomerCashbackLedger::create(['customer_id' => $user->id,'order_id' => $order->id,
            'merchandise_amount' => 100, 'cashback_rate' => 5,'cashback_amount' => '5.00','status' => 'pending','available_at' => now()->subHour()]);
        $refund = RefundRequest::create(['order_id' => $order->id,'customer_id' => $user->id,'order_details_id' => 1,'product_id' => 1,
            'amount' => 50,'refund_reason' => 'test','status' => 'rejected','change_by' => 'seller']);
        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_exchange_rate'],['value' => '1']);
        \Illuminate\Support\Facades\Cache::flush();
        $this->artisan('cashback:mature')->assertExitCode(0);
        $this->assertSame('pending',$ledger->fresh()->status);
        $this->assertEquals(0,$user->fresh()->loyalty_point);
        $refund->update(['change_by' => 'admin']);
        // [AI] Deterministic stale-read interleaving against the real command, not a concurrency simulation certificate.
        $adjusted = false;
        DB::listen(function ($query) use ($ledger, &$adjusted) {
            if (!$adjusted && str_contains($query->sql, 'select * from "users"')) {
                $adjusted = true;
                DB::table('customer_cashback_ledgers')->where('id', $ledger->id)->update(['cashback_amount' => '2.50']);
            }
        });
        $this->artisan('cashback:mature')->assertExitCode(0);
        $this->artisan('cashback:mature')->assertExitCode(0);
        $this->assertSame('available',$ledger->fresh()->status);
        $this->assertEquals(2.5,$user->fresh()->loyalty_point);
        $this->assertTrue($adjusted);
        $this->assertSame(1,DB::table('loyalty_point_transactions')->where('user_id',$user->id)->count());
    }

    public function testDeliveryInitializationCommitsLeaseBeforeProviderIoAndReplaysOneReference(): void
    {
        $user = $this->customer();
        $intent = CheckoutIntent::create(['customer_id' => $user->id,'order_group_id' => 'network-group','idempotency_key' => 'network-key',
            'cart_fingerprint' => str_repeat('c',64),'active_cart_token' => str_repeat('c',64),'status' => 'pending','total_amount' => '123.45',
            'currency' => 'NGN','checkout_snapshot' => [],'expires_at' => now()->addHour()]);
        $test = $this;
        $service = null;
        $client = new class extends \App\Services\PaystackInitializationClient {
            public $onInitialize;
            public int $calls = 0;
            public function initializeTransaction(string $email,int $amountKobo,string $reference,string $callbackUrl,array $metadata = []): array
            {
                $this->calls++;
                ($this->onInitialize)($amountKobo,$reference);
                return ['status' => 'SUCCESS','authorization_url' => 'https://checkout.example.test/test','access_code' => 'test'];
            }
            public function verifyExistingTransaction(string $reference): array { throw new \RuntimeException('Unexpected gateway verification'); }
        };
        $client->onInitialize = function ($amount,$reference) use ($test,$user,$intent,&$service) {
            $test->assertSame(0,DB::transactionLevel());
            $test->assertSame(12345,$amount);
            $nested = $service->initializePayment($user,$intent);
            $test->assertSame('pending_on_gateway',$nested['status']);
            $test->assertSame($reference,$nested['gateway_reference']);
        };
        $service = new \App\Services\DeliveryPaymentInitializationService($client);
        $result = $service->initializePayment($user,$intent);
        $replay = $service->initializePayment($user,$intent);
        $this->assertSame('success',$result['status']);
        $this->assertSame($result['gateway_reference'],$replay['gateway_reference']);
        $this->assertSame(1,$client->calls);
        $this->assertSame(1,PaymentRequest::where('order_group_id',$intent->order_group_id)->count());
    }

    public function testActualSettingsValidationKeepsPointFaceValueAndCapsEarnRate(): void
    {
        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_exchange_rate'],['value' => '1']);
        $request = \App\Http\Requests\Admin\CustomerUpdateSettingsRequest::create('/', 'POST');
        $validator = \Illuminate\Support\Facades\Validator::make(['loyalty_point_exchange_rate' => '2','loyalty_point_earn_rate_percent' => '10'], $request->rules());
        $this->assertTrue($validator->fails());
        $validator = \Illuminate\Support\Facades\Validator::make(['loyalty_point_exchange_rate' => '1','loyalty_point_earn_rate_percent' => '0'], $request->rules());
        $this->assertFalse($validator->fails());
        foreach ([null, '', '1e0'] as $blank) {
            $validator = \Illuminate\Support\Facades\Validator::make(['loyalty_point_exchange_rate' => $blank], $request->rules());
            $this->assertTrue($validator->fails());
        }
        $validator = \Illuminate\Support\Facades\Validator::make([], $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function testLateInitializationResponseCannotReopenAnInterleavedSuccessfulPayment(): void
    {
        $this->assertInterleavedInitialization('successful');
    }

    public function testQuoteExpiringDuringProviderIoDoesNotReturnPayableUrl(): void
    {
        $this->assertInterleavedInitialization('expired');
    }

    private function assertInterleavedInitialization(string $terminal): void
    {
        $user = $this->customer();
        $group = 'interleave-' . $terminal;
        $intent = CheckoutIntent::create(['customer_id' => $user->id,'order_group_id' => $group,'idempotency_key' => $group,
            'cart_fingerprint' => hash('sha256',$group),'active_cart_token' => hash('sha256',$group),'status' => 'pending',
            'total_amount' => '100.00','currency' => 'NGN','checkout_snapshot' => [],'expires_at' => now()->addHour()]);
        DB::table('cashback_redemptions')->insert(['customer_id' => $user->id,'checkout_intent_id' => $intent->id,'order_group_id' => $group,
            'points' => 5,'cashback_amount' => 5,'status' => 'reserved','created_at' => now(),'updated_at' => now()]);
        $client = new class extends \App\Services\PaystackInitializationClient {
            public $onInitialize;
            public function initializeTransaction(string $email,int $amountKobo,string $reference,string $callbackUrl,array $metadata = []): array
            {
                ($this->onInitialize)($reference);
                return ['status' => 'SUCCESS','authorization_url' => 'https://checkout.example.test/stale','access_code' => 'test'];
            }
        };
        // [AI] Deterministic interleaving changes persisted state while the real service holds no DB locks.
        $client->onInitialize = function ($reference) use ($intent,$terminal) {
            $this->assertSame(0,DB::transactionLevel());
            if ($terminal === 'successful') {
                $intent->update(['status' => 'converted_to_orders']);
                PaymentRequest::where('gateway_reference',$reference)->update(['attempt_status' => 'successful','is_paid' => 1,'active_order_group_id' => null]);
            } else {
                $intent->update(['expires_at' => now()->subMinute()]);
            }
        };
        $result = (new \App\Services\DeliveryPaymentInitializationService($client))->initializePayment($user,$intent);
        $payment = PaymentRequest::where('gateway_reference',$result['gateway_reference'])->firstOrFail();
        $this->assertSame($terminal,$payment->attempt_status);
        $this->assertNull($payment->active_order_group_id);
        $this->assertArrayNotHasKey('authorization_url',$result);
        if ($terminal === 'expired') {
            $this->assertSame('expired',$intent->fresh()->status);
            $this->assertSame('released',DB::table('cashback_redemptions')->where('checkout_intent_id',$intent->id)->value('status'));
        } else {
            $this->assertSame(1,(int)$payment->is_paid);
            $this->assertSame('converted_to_orders',$intent->fresh()->status);
        }
    }

    public function testOldOversizedConfiguredRewardRateCannotIssueMoreThanV1Allocation(): void
    {
        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_earn_rate_percent'], ['value' => '10']);
        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_status'], ['value' => '1']);
        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_for_each_order'], ['value' => '1']);
        \Illuminate\Support\Facades\Cache::flush();
        $user = $this->customer();
        $order = $this->order($user);
        $order->update(['received_at' => now(), 'refund_window_expires_at' => now()->addDay()]);
        if (!Schema::hasColumn('customer_cashback_ledgers', 'expires_at')) {
            Schema::table('customer_cashback_ledgers', fn($table) => $table->timestamp('expires_at')->nullable());
        }
        $reward = \App\Models\CustomerCashbackLedger::creditRewardForOrder($order);
        $this->assertEquals('5.00', $reward->cashback_rate);
        $this->assertEquals('5.00', $reward->cashback_amount);
        DB::table('business_settings')->where('type', 'loyalty_point_earn_rate_percent')->update(['value' => '0']);
        \Illuminate\Support\Facades\Cache::flush();
        $this->assertSame('0.00', \App\Models\CustomerCashbackLedger::configuredEarnRate());
        $this->assertEquals('5.00', $reward->fresh()->cashback_amount);
        DB::table('business_settings')->where('type', 'loyalty_point_status')->update(['value' => '0']);
        \Illuminate\Support\Facades\Cache::flush();
        $this->assertSame($reward->id, \App\Models\CustomerCashbackLedger::creditRewardForOrder($order)->id);
        $second = $this->order($user);
        $second->update(['received_at' => now(), 'refund_window_expires_at' => now()->addDay()]);
        $this->assertNull(\App\Models\CustomerCashbackLedger::creditRewardForOrder($second));
    }

    public function testPickupRewardPreviewNeverCreatesSpendingOrIssuanceLedger(): void
    {
        foreach (['loyalty_point_status' => '1','loyalty_point_for_each_order' => '1','loyalty_point_earn_rate_percent' => '5'] as $key => $value) {
            DB::table('business_settings')->updateOrInsert(['type' => $key],['value' => $value]);
        }
        \Illuminate\Support\Facades\Cache::flush();
        $user = $this->customer();
        $order = $this->order($user);
        $reservation = new PickupReservation(['total_amount' => '100.00','reservation_code' => 'PREVIEW']);
        $spentCount = DB::table('cashback_redemptions')->count();
        $earnedCount = DB::table('customer_cashback_ledgers')->count();
        $result = (new \App\Services\PickupCashbackAwardService())->award($reservation,$order,$user->id);
        $this->assertFalse($result['awarded']);
        $this->assertTrue($result['is_estimate']);
        $this->assertEquals('5.00',$result['cashback_amount']);
        $this->assertSame($spentCount, DB::table('cashback_redemptions')->count());
        $this->assertSame($earnedCount, DB::table('customer_cashback_ledgers')->count());
        $this->assertEquals(0,$user->fresh()->loyalty_point);
    }

    public function testAdminPointValueInputRendersReadonlyWithMigrationExplanation(): void
    {
        $source = file_get_contents(base_path('resources/views/admin-views/customer/customer-settings.blade.php'));
        $this->assertSame(1,preg_match('/<input type="text" class="form-control" name="loyalty_point_exchange_rate".*?<\/small>/s',$source,$matches));
        $html = \Illuminate\Support\Facades\Blade::render($matches[0], ['loyaltyPointExchangeRate' => '1','loyaltyPointStatus' => 1]);
        $this->assertStringContainsString('readonly',$html);
        $this->assertStringContainsString('ledger migration',$html);
        $this->assertStringContainsString('name="loyalty_point_earn_rate_percent"',$source);
        $this->assertStringContainsString('min="0" max="5"',$source);
    }
}
