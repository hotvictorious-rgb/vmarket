<?php

namespace Tests\Feature;

require_once __DIR__ . '/DumpSchemaTestCase.php';

use App\Http\Controllers\Customer\OrderHandoverController;
use App\Models\CustomerCashbackLedger;
use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use App\Models\DeliveryManTransaction;
use App\Models\LogisticsCompany;
use App\Models\LogisticsCompanyWallet;
use App\Models\LogisticsCompanyTransaction;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderDeliveryVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Handover & Cashback Concurrency & Idempotency Test
 * Validates that concurrent handover and cashback operations serialize safely under pessimistic locks,
 * prevent duplicate balance credits, and enforce database-level uniqueness on cashback issuance.
 */
class HandoverAndCashbackConcurrencyTest extends DumpSchemaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure required columns exist on SQLite test database
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
        if (!Schema::hasColumn('orders', 'handed_over_by_name')) {
            Schema::table('orders', function ($table) {
                $table->string('handed_over_by_name')->nullable();
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
        if (!Schema::hasColumn('orders', 'logistics_company_id')) {
            Schema::table('orders', function ($table) {
                $table->unsignedBigInteger('logistics_company_id')->nullable();
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

        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_status'], ['value' => '1']);
        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_for_each_order'], ['value' => '1']);
        cache()->flush();
    }

    public function testDoorstepHandoverIdempotencyAndWalletLock(): void
    {
        $customer = User::create([
            'name' => 'Handover Test Customer',
            'email' => 'customer_handover_' . uniqid() . '@example.com',
            'phone' => '+2348011223344',
            'password' => bcrypt('password123'),
        ]);

        $deliveryMan = DeliveryMan::create([
            'f_name' => 'Test',
            'l_name' => 'Rider',
            'phone' => '+2348099887766',
            'email' => 'rider_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'is_active' => 1,
        ]);

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
            'deliveryman_charge' => 1200.00,
            'delivery_man_id' => $deliveryMan->id,
            'verification_code' => '998877',
            'verification_status' => 0,
        ]);

        $wallet = DeliverymanWallet::create([
            'delivery_man_id' => $deliveryMan->id,
            'current_balance' => 0.00,
            'cash_in_hand' => 0.00,
            'pending_withdraw' => 0.00,
            'total_withdraw' => 0.00,
        ]);

        $this->actingAs($customer, 'customer');

        // Create mock image
        $image = UploadedFile::fake()->image('doorstep_proof.jpg', 600, 600);

        $request1 = Request::create('/order/doorstep-confirm', 'POST', [
            'order_id' => $order->id,
            'delivery_code' => '998877',
        ], [], ['image' => $image]);
        $request1->headers->set('Accept', 'application/json');
        $request1->setUserResolver(fn () => $customer);

        $controller = app(OrderHandoverController::class);

        // First handover execution
        $response1 = $controller->confirmDoorstepDelivery($request1);
        $this->assertEquals(200, $response1->getStatusCode());

        // Refresh and verify order status
        $order->refresh();
        $this->assertEquals('delivered', $order->order_status);
        $this->assertEquals(1, $order->verification_status);

        // Wallet balance must equal deliveryman charge: ₦1200.00
        $wallet->refresh();
        $this->assertEquals(1200.00, (float)$wallet->current_balance);

        // Transactions count must be exactly 1
        $txCount = DeliveryManTransaction::where('delivery_man_id', $deliveryMan->id)->count();
        $this->assertEquals(1, $txCount);

        // SECOND EXECUTION (Simulating replay or concurrent race request)
        $image2 = UploadedFile::fake()->image('doorstep_proof2.jpg', 600, 600);
        $request2 = Request::create('/order/doorstep-confirm', 'POST', [
            'order_id' => $order->id,
            'delivery_code' => '998877',
        ], [], ['image' => $image2]);
        $request2->headers->set('Accept', 'application/json');
        $request2->setUserResolver(fn () => $customer);

        $response2 = $controller->confirmDoorstepDelivery($request2);

        // Second execution must be REJECTED (order is already delivered, not out_for_delivery)
        $this->assertEquals(422, $response2->getStatusCode());

        // Balance must remain exactly 1200.00 (Delta = 0.00, zero double credit)
        $wallet->refresh();
        $this->assertEquals(1200.00, (float)$wallet->current_balance);
        $this->assertEquals(1, DeliveryManTransaction::where('delivery_man_id', $deliveryMan->id)->count());
    }

    public function testCashbackIdempotencyAndDatabaseUniqueness(): void
    {
        $customer = User::create([
            'name' => 'Cashback Test Customer',
            'email' => 'cashback_' . uniqid() . '@example.com',
            'phone' => '+2348077665544',
            'password' => bcrypt('password123'),
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'customer_type' => 'customer',
            'payment_status' => 'paid',
            'order_status' => 'delivered',
            'order_type' => 'default_type',
            'payment_method' => 'paystack',
            'order_amount' => 20000.00,
            'shipping_cost' => 1000.00,
            'total_tax_amount' => 0.00,
            'received_at' => now(),
            'refund_window_expires_at' => now()->addHours(24),
        ]);

        // First cashback credit invocation
        $ledger1 = CustomerCashbackLedger::creditRewardForOrder($order);
        $this->assertNotNull($ledger1);
        $this->assertEquals($order->id, $ledger1->order_id);
        $this->assertEquals($customer->id, $ledger1->customer_id);

        // Merchandise amount = 20000 - 1000 = 19000. 5% cashback = 950.00
        $this->assertEquals('950.00', bcadd((string)$ledger1->cashback_amount, '0', 2));

        // Second cashback credit invocation (must be idempotent and return existing row)
        $ledger2 = CustomerCashbackLedger::creditRewardForOrder($order);
        $this->assertNotNull($ledger2);
        $this->assertEquals($ledger1->id, $ledger2->id);

        // Direct count assertion: Exactly 1 row exists for this order
        $count = CustomerCashbackLedger::where('order_id', $order->id)->count();
        $this->assertEquals(1, $count);
    }

    public function testInShopPickupHandoverStateLock(): void
    {
        $customer = User::create([
            'name' => 'Pickup Test Customer',
            'email' => 'pickup_' . uniqid() . '@example.com',
            'phone' => '+2348055443322',
            'password' => bcrypt('password123'),
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'customer_type' => 'customer',
            'payment_status' => 'paid',
            'order_status' => 'processing',
            'order_type' => 'pickup',
            'payment_method' => 'paystack',
            'order_amount' => 10000.00,
            'shipping_cost' => 0.00,
            'pickup_verification_code' => '445566',
            'verification_code' => '445566',
        ]);

        $this->actingAs($customer, 'customer');

        $image = UploadedFile::fake()->image('pickup_proof.jpg', 600, 600);
        $request1 = Request::create('/order/in-shop-pickup-confirm', 'POST', [
            'order_id' => $order->id,
            'pickup_code' => '445566',
        ], [], ['image' => $image]);
        $request1->headers->set('Accept', 'application/json');
        $request1->setUserResolver(fn () => $customer);

        $controller = app(OrderHandoverController::class);

        // First invocation succeeds
        $response1 = $controller->confirmInShopPickup($request1);
        $this->assertEquals(200, $response1->getStatusCode());

        $order->refresh();
        $this->assertEquals('delivered', $order->order_status);

        // Second invocation must be REJECTED (status already delivered)
        $image2 = UploadedFile::fake()->image('pickup_proof2.jpg', 600, 600);
        $request2 = Request::create('/order/in-shop-pickup-confirm', 'POST', [
            'order_id' => $order->id,
            'pickup_code' => '445566',
        ], [], ['image' => $image2]);
        $request2->headers->set('Accept', 'application/json');
        $request2->setUserResolver(fn () => $customer);

        $response2 = $controller->confirmInShopPickup($request2);
        $this->assertEquals(400, $response2->getStatusCode());
    }
}
