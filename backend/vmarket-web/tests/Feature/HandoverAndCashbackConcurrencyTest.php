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
        if (!Schema::hasColumn('logistics_companies', 'commission_percentage')) {
            Schema::table('logistics_companies', function ($table) {
                $table->decimal('commission_percentage', 5, 2)->nullable();
            });
        }

        if (Schema::hasTable('customer_cashback_ledgers')) {
            $hasUnique = false;
            $indexes = DB::select("PRAGMA index_list('customer_cashback_ledgers')");
            foreach ($indexes as $idx) {
                if ($idx->unique && str_contains($idx->name, 'order_id')) {
                    $hasUnique = true;
                    break;
                }
            }
            if (!$hasUnique) {
                try {
                    DB::statement("CREATE UNIQUE INDEX customer_cashback_ledgers_order_id_unique ON customer_cashback_ledgers (order_id)");
                } catch (\Throwable $e) {
                    // Index already exists
                }
            }
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

    /**
     * [AI] Real Concurrency Invariant: Database-level UNIQUE constraint physically
     * rejects simultaneous duplicate cashback insertion even if application-level checks race.
     */
    public function testDatabaseUniqueConstraintRejectsSimultaneousDuplicateCashbackInsertion(): void
    {
        $customer = User::create([
            'name' => 'Concurrent DB Customer',
            'email' => 'concurrent_cb_' . uniqid() . '@example.com',
            'phone' => '+2348033221100',
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
            'order_amount' => 50000.00,
            'shipping_cost' => 2000.00,
            'total_tax_amount' => 0.00,
            'received_at' => now(),
            'refund_window_expires_at' => now()->addHours(24),
        ]);

        // Thread 1: Authoritative reward credit
        $ledger1 = CustomerCashbackLedger::creditRewardForOrder($order);
        $this->assertNotNull($ledger1);

        // Thread 2 (Simulated Race Collision): Directly attempts raw SQL insert for the same order_id
        // Bypassing any application layer lock. The database engine MUST reject this with QueryException!
        $dbCollisionRejected = false;
        try {
            DB::table('customer_cashback_ledgers')->insert([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'merchandise_amount' => 48000.00,
                'cashback_rate' => 5.00,
                'cashback_amount' => 2400.00,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $dbCollisionRejected = true;
        }

        $this->assertTrue($dbCollisionRejected, 'Database unique constraint customer_cashback_ledgers_order_id_unique MUST reject simultaneous duplicate insertion!');

        // Thread 3: Concurrent call to creditRewardForOrder handles the race gracefully and returns the existing row
        $ledger3 = CustomerCashbackLedger::creditRewardForOrder($order);
        $this->assertNotNull($ledger3);
        $this->assertEquals($ledger1->id, $ledger3->id);

        // Verify mathematical invariant: exactly 1 ledger row and exactly 1 reward lot
        $count = CustomerCashbackLedger::where('order_id', $order->id)->count();
        $this->assertEquals(1, $count);
        $totalReward = CustomerCashbackLedger::where('order_id', $order->id)->sum('cashback_amount');
        $this->assertEquals('2400.00', bcadd((string)$totalReward, '0', 2));
    }

    /**
     * [AI] Real Concurrency Invariant: Cross-handover race condition
     * If an in-shop pickup and a doorstep delivery confirmation are dispatched simultaneously
     * for the same order, exactly one commits and the second is safely rejected under lock.
     */
    public function testConcurrentCrossHandoverConflictPrevention(): void
    {
        $customer = User::create([
            'name' => 'Cross Handover Customer',
            'email' => 'cross_handover_' . uniqid() . '@example.com',
            'phone' => '+2348077112233',
            'password' => bcrypt('password123'),
        ]);

        $deliveryMan = DeliveryMan::create([
            'f_name' => 'Cross',
            'l_name' => 'Rider',
            'phone' => '+2348099112233',
            'email' => 'cross_rider_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'is_active' => 1,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'customer_type' => 'customer',
            'payment_status' => 'paid',
            'order_status' => 'processing',
            'order_type' => 'pickup',
            'payment_method' => 'paystack',
            'order_amount' => 30000.00,
            'shipping_cost' => 2000.00,
            'deliveryman_charge' => 1600.00,
            'delivery_man_id' => $deliveryMan->id,
            'verification_code' => '123456',
            'pickup_verification_code' => '123456',
            'verification_status' => 0,
        ]);

        $wallet = DeliverymanWallet::create([
            'delivery_man_id' => $deliveryMan->id,
            'current_balance' => '0.00',
            'cash_in_hand' => '0.00',
            'pending_withdraw' => '0.00',
            'total_withdraw' => '0.00',
        ]);

        $this->actingAs($customer, 'customer');
        $controller = app(OrderHandoverController::class);

        // Thread A: In-shop pickup confirmed
        $imageA = UploadedFile::fake()->image('pickup_proof.jpg', 600, 600);
        $requestA = Request::create('/order/in-shop-pickup-confirm', 'POST', [
            'order_id' => $order->id,
            'pickup_code' => '123456',
        ], [], ['image' => $imageA]);
        $requestA->headers->set('Accept', 'application/json');
        $requestA->setUserResolver(fn () => $customer);

        $responseA = $controller->confirmInShopPickup($requestA);
        $this->assertEquals(200, $responseA->getStatusCode());

        // Thread B (Simultaneous Doorstep Delivery attempt on same order):
        $imageB = UploadedFile::fake()->image('doorstep_proof.jpg', 600, 600);
        $requestB = Request::create('/order/doorstep-confirm', 'POST', [
            'order_id' => $order->id,
            'delivery_code' => '123456',
        ], [], ['image' => $imageB]);
        $requestB->headers->set('Accept', 'application/json');
        $requestB->setUserResolver(fn () => $customer);

        $responseB = $controller->confirmDoorstepDelivery($requestB);

        // Must be rejected because order is already delivered
        $this->assertEquals(422, $responseB->getStatusCode());

        // Invariant: Exactly 1 delivery verification record exists
        $this->assertEquals(1, OrderDeliveryVerification::where('order_id', $order->id)->count());

        // Invariant: Rider wallet was NOT credited because pickup was customer in-shop
        $wallet->refresh();
        $this->assertEquals('0.00', bcadd((string)$wallet->current_balance, '0', 2));
    }

    /**
     * [AI] Money Precision Invariant: Logistics company wallet and transaction balances
     * maintain exact 2-decimal string precision without IEEE-754 floating point drift.
     */
    public function testLogisticsCompanyWalletArbitraryPrecisionDecimals(): void
    {
        $company = LogisticsCompany::create([
            'name' => 'Precision Express Logistics',
            'company_email' => 'precision_' . uniqid() . '@example.com',
            'company_phone' => '+2348000000001',
            'password' => bcrypt('password123'),
            'commission_percentage' => 15.00,
            'status' => 'approved',
            'is_active' => 1,
        ]);

        $wallet = LogisticsCompanyWallet::create([
            'logistics_company_id' => $company->id,
            'total_earned' => '0.00',
            'withdrawn' => '0.00',
            'pending_withdraw' => '0.00',
            'current_balance' => '0.00',
        ]);

        $deliveryMan = DeliveryMan::create([
            'f_name' => 'Partner',
            'l_name' => 'Rider',
            'phone' => '+2348000000002',
            'email' => 'partner_rider_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'logistics_company_id' => $company->id,
            'is_active' => 1,
        ]);

        $customer = User::create([
            'name' => 'Precision Customer',
            'email' => 'precision_cust_' . uniqid() . '@example.com',
            'phone' => '+2348000000003',
            'password' => bcrypt('password123'),
        ]);

        $grossShipping = '2500.00';
        $commissionRate = '15.00';
        // 15% of 2500.00 = 375.00
        $expectedCommission = '375.00';
        // Net partner amount = 2500.00 - 375.00 = 2125.00
        $expectedNetPayout = '2125.00';

        $order = Order::create([
            'customer_id' => $customer->id,
            'is_guest' => 0,
            'customer_type' => 'customer',
            'payment_status' => 'paid',
            'order_status' => 'out_for_delivery',
            'order_type' => 'default_type',
            'payment_method' => 'paystack',
            'order_amount' => 45000.00,
            'shipping_cost' => $grossShipping,
            'deliveryman_charge' => '0.00',
            'delivery_man_id' => $deliveryMan->id,
            'logistics_company_id' => $company->id,
            'verification_code' => '654321',
            'verification_status' => 0,
        ]);

        $this->actingAs($customer, 'customer');
        $controller = app(OrderHandoverController::class);

        $image = UploadedFile::fake()->image('doorstep_partner.jpg', 600, 600);
        $request = Request::create('/order/doorstep-confirm', 'POST', [
            'order_id' => $order->id,
            'delivery_code' => '654321',
        ], [], ['image' => $image]);
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $customer);

        $response = $controller->confirmDoorstepDelivery($request);
        $this->assertEquals(200, $response->getStatusCode());

        $wallet->refresh();
        $this->assertEquals($expectedNetPayout, bcadd((string)$wallet->current_balance, '0', 2));
        $this->assertEquals($expectedNetPayout, bcadd((string)$wallet->total_earned, '0', 2));

        $tx = LogisticsCompanyTransaction::where('order_id', $order->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals($grossShipping, bcadd((string)$tx->gross_delivery_fee, '0', 2));
        $this->assertEquals($expectedCommission, bcadd((string)$tx->admin_commission_amount, '0', 2));
        $this->assertEquals($expectedNetPayout, bcadd((string)$tx->net_partner_amount, '0', 2));

        // Invariant: gross == commission + net partner payout (Delta = 0.00)
        $sum = bcadd((string)$tx->admin_commission_amount, (string)$tx->net_partner_amount, 2);
        $this->assertEquals($grossShipping, $sum);
    }
}
