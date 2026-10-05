<?php

namespace Tests\Feature;

require_once __DIR__ . '/DumpSchemaTestCase.php';
foreach ([
    'PaymentExceptionRecorder',
    'PaystackRefundService',
    'DeliveryPaymentInitializationService',
    'DeliveryOrderSettlementService',
    'PickupPaymentInitializationService',
    'PickupOrderSettlementService',
    'PickupCashbackAwardService',
    'PaystackBankService',
] as $service) {
    require_once __DIR__ . '/../../app/Services/' . $service . '.php';
}
require_once __DIR__ . '/../../app/Traits/Processor.php';
require_once __DIR__ . '/../../app/Http/Controllers/Payment_Methods/PaystackController.php';
require_once __DIR__ . '/GatewayMoneyTestCase.php';

use App\Http\Controllers\Payment_Methods\PaystackController;
use App\Models\AdminWallet;
use App\Models\CheckoutIntent;
use App\Models\CustomerCashbackLedger;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentRequest;
use App\Models\PickupReservation;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * [AI] Comprehensive End-to-End Paystack Gateway Scenario Test Suite.
 * Executes on isolated in-memory SQLite schema with zero live network I/O.
 */
class PaystackEndToEndScenariosTest extends GatewayMoneyTestCase
{
    private const SECRET = 'sk_test_d871eccb6c96d8cd20aa7240a31f2dccf4a657ad';
    private const PUBLIC_KEY = 'pk_test_d3811b3c26d0d0603e07601e8bdda44ac8857047';
    private const MERCHANT_EMAIL = 'payments@victoriousmarket.com.ng';

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure addon_settings and config use real test keys
        DB::table('addon_settings')->where('key_name', 'paystack')->delete();
        DB::table('addon_settings')->insert([
            'id' => (string) Str::uuid(),
            'key_name' => 'paystack',
            'mode' => 'test',
            'settings_type' => 'payment_config',
            'test_values' => json_encode([
                'secret_key' => self::SECRET,
                'public_key' => self::PUBLIC_KEY,
                'merchant_email' => self::MERCHANT_EMAIL,
            ]),
            'live_values' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        config(['paystack.secretKey' => self::SECRET]);
        config(['paystack.publicKey' => self::PUBLIC_KEY]);
        config(['paystack.merchantEmail' => self::MERCHANT_EMAIL]);
        putenv('PAYSTACK_SECRET_KEY=' . self::SECRET);
        putenv('PAYSTACK_PUBLIC_KEY=' . self::PUBLIC_KEY);
        putenv('MERCHANT_EMAIL=' . self::MERCHANT_EMAIL);

        // Ensure required columns exist on SQLite test schema
        foreach ([
            'guest_access_token', 'total_tax_amount', 'origin_lga_id', 'origin_lga_name',
            'origin_state_name', 'destination_lga_id', 'destination_lga_name', 'destination_state_name',
            'authoritative_delivery_fee', 'pickup_verification_code', 'received_at', 'refund_window_expires_at',
            'vendor_settlement_status', 'init_order_amount', 'estimated_delivery_time', 'delivered_by',
        ] as $col) {
            if (!Schema::hasColumn('orders', $col)) {
                Schema::table('orders', fn ($t) => $t->string($col)->nullable());
            }
        }
        foreach (['cashback_amount', 'status', 'expires_at'] as $col) {
            if (!Schema::hasColumn('customer_cashback_ledgers', $col)) {
                Schema::table('customer_cashback_ledgers', fn ($t) => $t->string($col)->nullable());
            }
        }
        if (!Schema::hasColumn('order_transactions', 'escrow_remaining')) {
            Schema::table('order_transactions', fn ($t) => $t->string('escrow_remaining')->nullable());
        }
        if (!Schema::hasTable('admin_wallets')) {
            Schema::create('admin_wallets', function ($table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->default(1);
                $table->decimal('inhouse_earning', 24, 4)->default(0);
                $table->decimal('withdrawn', 24, 4)->default(0);
                $table->decimal('commission_earned', 24, 4)->default(0);
                $table->decimal('delivery_charge_earned', 24, 4)->default(0);
                $table->decimal('pending_amount', 24, 4)->default(0);
                $table->decimal('total_tax_collected', 24, 4)->default(0);
                $table->timestamps();
            });
        }
        if (AdminWallet::count() === 0) {
            AdminWallet::create(['admin_id' => 1, 'pending_amount' => '0.00']);
        }
        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_status'], ['value' => '1', 'updated_at' => now()]);
        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_for_each_order'], ['value' => '1', 'updated_at' => now()]);
        DB::table('business_settings')->updateOrInsert(['type' => 'loyalty_point_exchange_rate'], ['value' => '1', 'updated_at' => now()]);
        \Illuminate\Support\Facades\Cache::flush();
    }

    private function customer(string $name = 'Paystack Customer'): User
    {
        return User::create([
            'name' => $name,
            'f_name' => $name,
            'l_name' => 'Test',
            'email' => Str::uuid() . '@example.test',
            'phone' => '080' . random_int(10000000, 99999999),
            'password' => bcrypt('password123'),
            'is_active' => 1,
            'loyalty_point' => 0,
        ]);
    }

    private function sellerWithShop(int $sellerId = 1): array
    {
        $seller = Seller::create([
            'id' => $sellerId,
            'f_name' => 'Vendor',
            'l_name' => 'Owner',
            'phone' => '080' . random_int(10000000, 99999999),
            'email' => "vendor{$sellerId}@example.test",
            'password' => bcrypt('password123'),
            'status' => 'approved',
        ]);
        $shop = Shop::create([
            'seller_id' => $seller->id,
            'name' => "Vendor {$sellerId} Shop",
            'address' => '123 Market Road, Uyo',
            'contact' => '08012345678',
            'image' => 'def.png',
            'banner' => 'def.png',
        ]);
        return [$seller, $shop];
    }

    private function product(int $sellerId, int $stock = 10, string $price = '10000.00'): Product
    {
        return Product::create([
            'added_by' => 'seller',
            'user_id' => $sellerId,
            'name' => 'Test Phone',
            'slug' => 'test-phone-' . Str::uuid(),
            'product_type' => 'physical',
            'category_id' => 1,
            'unit_price' => $price,
            'purchase_price' => '8000.00',
            'tax' => 7.5,
            'tax_type' => 'percent',
            'tax_model' => 'exclude',
            'discount' => 0,
            'discount_type' => 'flat',
            'current_stock' => $stock,
            'status' => 1,
            'thumbnail' => 'def.png',
            'images' => '[]',
            'color_image' => '[]',
            'colors' => '[]',
            'attributes' => '[]',
            'choice_options' => '[]',
            'variation' => '[]',
            'unit' => 'pc',
        ]);
    }

    private function sendSignedWebhook(array $event, ?string $signature = null): \Illuminate\Http\JsonResponse
    {
        $body = json_encode($event);
        $sig = $signature ?? hash_hmac('sha512', $body, self::SECRET);
        $request = Request::create('/payment/paystack/webhook', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $sig,
        ], $body);
        $controller = $this->app->make(PaystackController::class);
        return $controller->webhook($request);
    }

    // =========================================================================
    // SCENARIO 1: Standard Marketplace Delivery Order (Webhook Happy Path)
    // =========================================================================
    public function testScenario1DeliveryOrderSettlementViaWebhook(): void
    {
        $user = $this->customer();
        [$seller, $shop] = $this->sellerWithShop(10);
        $product = $this->product(10, 10, '10000.00');

        $orderGroup = 'group-scen-1-' . Str::uuid();
        $ref = 'PAYSTACK-SCEN-1-' . Str::uuid();

        $snapshot = [
            'vendors' => [
                [
                    'seller_id' => 10,
                    'seller_is' => 'seller',
                    'subtotal' => '10000.00',
                    'merchandise' => '10000.00',
                    'tax' => '750.00',
                    'shipping_cost' => '1500.00',
                    'total' => '12250.00',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 1,
                            'unit_price' => '10000.00',
                            'tax' => '750.00',
                            'discount' => '0.00',
                        ]
                    ]
                ]
            ]
        ];

        $intent = CheckoutIntent::create([
            'customer_id' => $user->id,
            'order_group_id' => $orderGroup,
            'idempotency_key' => 'idem-1-' . Str::uuid(),
            'cart_fingerprint' => str_repeat('a', 64),
            'active_cart_token' => str_repeat('a', 64),
            'status' => 'pending',
            'total_amount' => '12250.00',
            'currency' => 'NGN',
            'checkout_snapshot' => $snapshot,
            'expires_at' => now()->addHour(),
        ]);

        $payment = PaymentRequest::create([
            'id' => (string) Str::uuid(),
            'payer_id' => (string) $user->id,
            'payment_amount' => '12250.00',
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'marketplace_delivery',
            'order_group_id' => $orderGroup,
            'gateway_reference' => $ref,
            'attempt_status' => 'pending',
            'is_paid' => 0,
            'payer_information' => '{}',
            'additional_data' => '{}',
        ]);

        $event = [
            'event' => 'charge.success',
            'data' => [
                'id' => 101,
                'reference' => $ref,
                'amount' => 1225000, // ₦12,250.00 in kobo
                'currency' => 'NGN',
                'status' => 'success',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_domain' => 'marketplace_delivery',
                ]
            ]
        ];

        $response = $this->sendSignedWebhook($event);
        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('CLAIMED', $payload['settlement']);

        // Assert Order Created
        $this->assertSame(1, Order::where('order_group_id', $orderGroup)->count());
        $order = Order::where('order_group_id', $orderGroup)->first();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->order_status);
        $this->assertSame('held', $order->vendor_settlement_status);

        // Assert Stock Decremented
        $this->assertSame(9, $product->fresh()->current_stock);

        // Assert PaymentRequest marked paid
        $this->assertSame(1, (int) $payment->fresh()->is_paid);
        $this->assertSame('successful', $payment->fresh()->attempt_status);

        // Assert Mathematical Conservation
        $adminPending = (float) AdminWallet::first()->pending_amount;
        $this->assertEquals(12250.00, $adminPending, "Platform escrow hold must exactly equal customer inflow (Delta = 0.00)");
    }

    // =========================================================================
    // SCENARIO 2: Dual Delivery Concurrent Race (Webhook vs Callback Idempotency)
    // =========================================================================
    public function testScenario2DualDeliveryWebhookAndCallbackRace(): void
    {
        $user = $this->customer();
        [$seller, $shop] = $this->sellerWithShop(11);
        $product = $this->product(11, 5, '5000.00');

        $orderGroup = 'group-scen-2-' . Str::uuid();
        $ref = 'PAYSTACK-SCEN-2-' . Str::uuid();

        $snapshot = [
            'vendors' => [
                [
                    'seller_id' => 11,
                    'seller_is' => 'seller',
                    'subtotal' => '5000.00',
                    'merchandise' => '5000.00',
                    'tax' => '0.00',
                    'shipping_cost' => '1000.00',
                    'total' => '6000.00',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 1,
                            'unit_price' => '5000.00',
                            'tax' => '0.00',
                            'discount' => '0.00',
                        ]
                    ]
                ]
            ]
        ];

        $intent = CheckoutIntent::create([
            'customer_id' => $user->id,
            'order_group_id' => $orderGroup,
            'idempotency_key' => 'idem-2-' . Str::uuid(),
            'cart_fingerprint' => str_repeat('b', 64),
            'active_cart_token' => str_repeat('b', 64),
            'status' => 'pending',
            'total_amount' => '6000.00',
            'currency' => 'NGN',
            'checkout_snapshot' => $snapshot,
            'expires_at' => now()->addHour(),
        ]);

        $payment = PaymentRequest::create([
            'id' => (string) Str::uuid(),
            'payer_id' => (string) $user->id,
            'payment_amount' => '6000.00',
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'marketplace_delivery',
            'order_group_id' => $orderGroup,
            'gateway_reference' => $ref,
            'attempt_status' => 'pending',
            'is_paid' => 0,
            'payer_information' => '{}',
            'additional_data' => '{}',
        ]);

        $event = [
            'event' => 'charge.success',
            'data' => [
                'id' => 102,
                'reference' => $ref,
                'amount' => 600000,
                'currency' => 'NGN',
                'status' => 'success',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_domain' => 'marketplace_delivery',
                ]
            ]
        ];

        // 1. Webhook Arrives First -> Claims payment
        $response1 = $this->sendSignedWebhook($event);
        $this->assertSame(200, $response1->getStatusCode());
        $this->assertSame('CLAIMED', json_decode($response1->getContent(), true)['settlement']);

        // 2. Webhook Replay / Concurrent Callback Arrives Second -> Reports ALREADY_PAID
        $response2 = $this->sendSignedWebhook($event);
        $this->assertSame(200, $response2->getStatusCode());
        $this->assertSame('ALREADY_PAID', json_decode($response2->getContent(), true)['settlement']);

        // Exactly one order group exists
        $this->assertSame(1, Order::where('order_group_id', $orderGroup)->count());
        $this->assertSame(4, $product->fresh()->current_stock, "Stock deducted exactly once");
    }

    // =========================================================================
    // SCENARIO 3: Self-Pickup Reservation Checkout (5% Instant Cashback + Zero Delivery)
    // =========================================================================
    public function testScenario3SelfPickupCheckoutWithFivePercentCashback(): void
    {
        $user = $this->customer();
        [$seller, $shop] = $this->sellerWithShop(12);
        $product = $this->product(12, 10, '5000.00');
        $ref = 'PAYSTACK-SCEN-3-' . Str::uuid();

        $reservation = PickupReservation::create([
            'customer_id' => $user->id,
            'seller_id' => 12,
            'shop_id' => $shop->id,
            'reservation_code' => 'PICKUP-3-' . random_int(1000, 9999),
            'idempotency_key' => 'pickup-idem-3-' . Str::uuid(),
            'reservation_fingerprint' => str_repeat('c', 64),
            'status' => 'inspected_accepted',
            'total_amount' => '5000.00',
            'snapshot' => [
                'subtotal' => '5000.00',
                'tax' => '0.00',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'product_name' => 'Pickup Item',
                        'quantity' => 1,
                        'unit_price' => '5000.00',
                        'tax' => '0.00',
                        'discount' => '0.00',
                    ]
                ]
            ],
            'reservation_items' => [
                [
                    'product_id' => $product->id,
                    'product_name' => 'Pickup Item',
                    'quantity' => 1,
                    'unit_price' => '5000.00',
                    'tax' => '0.00',
                    'discount' => '0.00',
                ]
            ],
            'expires_at' => now()->addHour(),
        ]);

        $payment = PaymentRequest::create([
            'id' => (string) Str::uuid(),
            'payer_id' => (string) $user->id,
            'payment_amount' => '5000.00',
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'marketplace_pickup',
            'pickup_reservation_id' => $reservation->id,
            'active_pickup_reservation_id' => $reservation->id,
            'gateway_reference' => $ref,
            'attempt_status' => 'pending',
            'is_paid' => 0,
            'payer_information' => '{}',
            'additional_data' => '{}',
        ]);

        $event = [
            'event' => 'charge.success',
            'data' => [
                'id' => 103,
                'reference' => $ref,
                'amount' => 500000, // ₦5,000.00
                'currency' => 'NGN',
                'status' => 'success',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_domain' => 'marketplace_pickup',
                ]
            ]
        ];

        $response = $this->sendSignedWebhook($event);
        $this->assertSame(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('CLAIMED', $payload['settlement']);

        // Assert Order Created with zero shipping cost & 6-digit pickup verification code
        $order = Order::where('customer_id', $user->id)->where('order_type', 'pickup')->first();
        $this->assertNotNull($order);
        $this->assertEquals(0.00, (float) $order->shipping_cost);
        $this->assertNotEmpty($order->pickup_verification_code);
        $this->assertSame(6, strlen((string) $order->pickup_verification_code));

        // Authoritative Handover & Receipt triggers 5% Cashback Award (5% of ₦5,000.00 = ₦250.00)
        $order->update([
            'order_status' => 'delivered',
            'received_at' => now(),
            'refund_window_expires_at' => now()->addDays(14),
        ]);
        $cashback = CustomerCashbackLedger::creditRewardForOrder($order->fresh());
        $this->assertNotNull($cashback);
        $this->assertEquals(250.00, (float) $cashback->cashback_amount);
        $this->assertSame('pending', $cashback->status);
    }

    // =========================================================================
    // SCENARIO 4: Expired Intent / Late Capture Anomaly Quarantine
    // =========================================================================
    public function testScenario4LateCaptureOnExpiredCheckoutIntentIsQuarantined(): void
    {
        $user = $this->customer();
        [$seller, $shop] = $this->sellerWithShop(13);
        $product = $this->product(13, 5, '3000.00');

        $orderGroup = 'group-scen-4-' . Str::uuid();
        $ref = 'PAYSTACK-SCEN-4-' . Str::uuid();

        // Expired intent
        $intent = CheckoutIntent::create([
            'customer_id' => $user->id,
            'order_group_id' => $orderGroup,
            'idempotency_key' => 'idem-4-' . Str::uuid(),
            'cart_fingerprint' => str_repeat('d', 64),
            'active_cart_token' => str_repeat('d', 64),
            'status' => 'pending',
            'total_amount' => '3000.00',
            'currency' => 'NGN',
            'checkout_snapshot' => ['vendors' => []],
            'expires_at' => now()->subMinutes(15), // Expired 15 mins ago
        ]);

        $payment = PaymentRequest::create([
            'id' => (string) Str::uuid(),
            'payer_id' => (string) $user->id,
            'payment_amount' => '3000.00',
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'marketplace_delivery',
            'order_group_id' => $orderGroup,
            'gateway_reference' => $ref,
            'attempt_status' => 'pending',
            'is_paid' => 0,
            'payer_information' => '{}',
            'additional_data' => '{}',
        ]);

        $event = [
            'event' => 'charge.success',
            'data' => [
                'id' => 104,
                'reference' => $ref,
                'amount' => 300000,
                'currency' => 'NGN',
                'status' => 'success',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'payment_domain' => 'marketplace_delivery',
                ]
            ]
        ];

        $response = $this->sendSignedWebhook($event);
        $this->assertSame(200, $response->getStatusCode());

        // Zero orders created
        $this->assertSame(0, Order::where('order_group_id', $orderGroup)->count());

        // Payment marked reconciliation_required to safeguard money
        $this->assertSame(1, (int) $payment->fresh()->is_paid);
        $this->assertSame('reconciliation_required', $payment->fresh()->attempt_status);

        // Durable reconciliation record created
        $recon = DB::table('payment_reconciliations')->where('gateway_reference', $ref)->first();
        $this->assertNotNull($recon);
        $this->assertSame('late_capture_expired', $recon->initial_anomaly_type);
        $this->assertSame('open', $recon->current_status);
    }

    // =========================================================================
    // SCENARIO 5: Underpayment / Amount Tampering Attack Rejected
    // =========================================================================
    public function testScenario5UnderpaymentAmountTamperingIsQuarantined(): void
    {
        $user = $this->customer();
        $orderGroup = 'group-scen-5-' . Str::uuid();
        $ref = 'PAYSTACK-SCEN-5-' . Str::uuid();

        $intent = CheckoutIntent::create([
            'customer_id' => $user->id,
            'order_group_id' => $orderGroup,
            'idempotency_key' => 'idem-5-' . Str::uuid(),
            'cart_fingerprint' => str_repeat('e', 64),
            'active_cart_token' => str_repeat('e', 64),
            'status' => 'pending',
            'total_amount' => '10000.00', // ₦10,000 expected
            'currency' => 'NGN',
            'checkout_snapshot' => ['vendors' => []],
            'expires_at' => now()->addHour(),
        ]);

        $payment = PaymentRequest::create([
            'id' => (string) Str::uuid(),
            'payer_id' => (string) $user->id,
            'payment_amount' => '10000.00',
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'marketplace_delivery',
            'order_group_id' => $orderGroup,
            'gateway_reference' => $ref,
            'attempt_status' => 'pending',
            'is_paid' => 0,
            'payer_information' => '{}',
            'additional_data' => '{}',
        ]);

        // Attacker paid only ₦5,000 (500,000 kobo)
        $event = [
            'event' => 'charge.success',
            'data' => [
                'id' => 105,
                'reference' => $ref,
                'amount' => 500000,
                'currency' => 'NGN',
                'status' => 'success',
                'metadata' => ['payment_id' => $payment->id, 'payment_domain' => 'marketplace_delivery']
            ]
        ];

        $response = $this->sendSignedWebhook($event);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, Order::where('order_group_id', $orderGroup)->count());

        $recon = DB::table('payment_reconciliations')->where('gateway_reference', $ref)->first();
        $this->assertNotNull($recon);
        $this->assertSame('amount_mismatch', $recon->initial_anomaly_type);
        $this->assertSame('open', $recon->current_status);
    }

    // =========================================================================
    // SCENARIO 6: Currency Manipulation Attack Rejected
    // =========================================================================
    public function testScenario6ForeignCurrencyManipulationIsQuarantined(): void
    {
        $user = $this->customer();
        $orderGroup = 'group-scen-6-' . Str::uuid();
        $ref = 'PAYSTACK-SCEN-6-' . Str::uuid();

        CheckoutIntent::create([
            'customer_id' => $user->id,
            'order_group_id' => $orderGroup,
            'idempotency_key' => 'idem-6-' . Str::uuid(),
            'cart_fingerprint' => str_repeat('f', 64),
            'active_cart_token' => str_repeat('f', 64),
            'status' => 'pending',
            'total_amount' => '5000.00',
            'currency' => 'NGN',
            'checkout_snapshot' => ['vendors' => []],
            'expires_at' => now()->addHour(),
        ]);

        $payment = PaymentRequest::create([
            'id' => (string) Str::uuid(),
            'payer_id' => (string) $user->id,
            'payment_amount' => '5000.00',
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'marketplace_delivery',
            'order_group_id' => $orderGroup,
            'gateway_reference' => $ref,
            'attempt_status' => 'pending',
            'is_paid' => 0,
            'payer_information' => '{}',
            'additional_data' => '{}',
        ]);

        // Gateway sends USD instead of NGN
        $event = [
            'event' => 'charge.success',
            'data' => [
                'id' => 106,
                'reference' => $ref,
                'amount' => 500000,
                'currency' => 'USD',
                'status' => 'success',
                'metadata' => ['payment_id' => $payment->id, 'payment_domain' => 'marketplace_delivery']
            ]
        ];

        $response = $this->sendSignedWebhook($event);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, Order::where('order_group_id', $orderGroup)->count());

        $recon = DB::table('payment_reconciliations')->where('gateway_reference', $ref)->first();
        $this->assertNotNull($recon);
        $this->assertSame('currency_mismatch', $recon->initial_anomaly_type);
        $this->assertSame('open', $recon->current_status);
    }

    // =========================================================================
    // SCENARIO 7: IDOR Customer Ownership Binding Protection
    // =========================================================================
    public function testScenario7CustomerOwnershipMismatchAbortsSettlement(): void
    {
        $legitimateUser = $this->customer('Legit Customer');
        $maliciousUser = $this->customer('Attacker Customer');

        $orderGroup = 'group-scen-7-' . Str::uuid();
        $ref = 'PAYSTACK-SCEN-7-' . Str::uuid();

        // Intent belongs to legitimate user
        CheckoutIntent::create([
            'customer_id' => $legitimateUser->id,
            'order_group_id' => $orderGroup,
            'idempotency_key' => 'idem-7-' . Str::uuid(),
            'cart_fingerprint' => str_repeat('g', 64),
            'active_cart_token' => str_repeat('g', 64),
            'status' => 'pending',
            'total_amount' => '7000.00',
            'currency' => 'NGN',
            'checkout_snapshot' => ['vendors' => []],
            'expires_at' => now()->addHour(),
        ]);

        // PaymentRequest maliciously bound to attacker
        $payment = PaymentRequest::create([
            'id' => (string) Str::uuid(),
            'payer_id' => (string) $maliciousUser->id, // Attacker ID
            'payment_amount' => '7000.00',
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'marketplace_delivery',
            'order_group_id' => $orderGroup,
            'gateway_reference' => $ref,
            'attempt_status' => 'pending',
            'is_paid' => 0,
            'payer_information' => '{}',
            'additional_data' => '{}',
        ]);

        $event = [
            'event' => 'charge.success',
            'data' => [
                'id' => 107,
                'reference' => $ref,
                'amount' => 700000,
                'currency' => 'NGN',
                'status' => 'success',
                'metadata' => ['payment_id' => $payment->id, 'payment_domain' => 'marketplace_delivery']
            ]
        ];

        $response = $this->sendSignedWebhook($event);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, Order::where('order_group_id', $orderGroup)->count());

        $recon = DB::table('payment_reconciliations')->where('gateway_reference', $ref)->first();
        $this->assertNotNull($recon);
        $this->assertSame('other', $recon->initial_anomaly_type);
        $this->assertSame('open', $recon->current_status);
    }

    // =========================================================================
    // SCENARIO 8: Webhook Cryptographic Verification (HMAC-SHA512)
    // =========================================================================
    public function testScenario8CryptographicSignatureValidationStrictlyEnforced(): void
    {
        $event = ['event' => 'charge.success', 'data' => ['reference' => 'sig-test', 'amount' => 10000, 'currency' => 'NGN']];

        // 1. Forged Signature -> 401 Unauthorized
        $response1 = $this->sendSignedWebhook($event, str_repeat('0', 128));
        $this->assertSame(401, $response1->getStatusCode());

        // 2. Body Tampered after signing -> 401 Unauthorized
        $sig = hash_hmac('sha512', json_encode($event), self::SECRET);
        $tamperedEvent = $event;
        $tamperedEvent['data']['amount'] = 999999;
        $body = json_encode($tamperedEvent);
        $request = Request::create('/payment/paystack/webhook', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $sig,
        ], $body);
        $controller = $this->app->make(PaystackController::class);
        $this->assertSame(401, $controller->webhook($request)->getStatusCode());

        // 3. Valid Signature -> 200 OK
        $response3 = $this->sendSignedWebhook($event);
        $this->assertSame(200, $response3->getStatusCode());
    }

    // =========================================================================
    // SCENARIO 9: Paystack Refund Lifecycle & Terminal State Immutability
    // =========================================================================
    public function testScenario9RefundLifecycleCannotRegressTerminalStatus(): void
    {
        $user = $this->customer();
        $order = Order::create([
            'customer_id' => $user->id,
            'customer_type' => 'customer',
            'seller_id' => 1,
            'seller_is' => 'seller',
            'order_status' => 'delivered',
            'payment_status' => 'paid',
            'payment_method' => 'paystack',
            'order_amount' => '5000.00',
            'transaction_ref' => 'internal-ref-scen-9',
        ]);

        $refund = RefundRequest::create([
            'order_id' => $order->id,
            'customer_id' => $user->id,
            'order_details_id' => 1,
            'product_id' => 1,
            'amount' => '5000.00',
            'refund_reason' => 'Defective screen',
            'status' => 'refunded',
            'execution_status' => 'succeeded',
            'paystack_refund_id' => 'paystack-refund-999',
        ]);

        // Gateway sends redundant / delayed notifications
        foreach (['refund.pending', 'refund.processing', 'refund.failed'] as $event) {
            $response = $this->sendSignedWebhook([
                'event' => $event,
                'data' => [
                    'id' => 'paystack-refund-999',
                    'transaction_reference' => 'internal-ref-scen-9',
                    'merchant_note' => 'vmarket_refund_' . $refund->id,
                    'amount' => 500000,
                    'currency' => 'NGN',
                ]
            ]);
            $this->assertSame(200, $response->getStatusCode());
        }

        // Terminal status remains succeeded/refunded
        $this->assertSame('succeeded', $refund->fresh()->execution_status);
        $this->assertSame('refunded', $refund->fresh()->status);
    }

    // =========================================================================
    // SCENARIO 10: Unknown Capture Replay Idempotency
    // =========================================================================
    public function testScenario10UnknownCaptureRecordedIdempotentlyWithoutCrash(): void
    {
        $ref = 'UNKNOWN-CAPTURE-' . Str::uuid();
        $event = [
            'event' => 'charge.success',
            'data' => [
                'id' => 999,
                'reference' => $ref,
                'amount' => 250000, // ₦2,500.00
                'currency' => 'NGN',
                'metadata' => []
            ]
        ];

        // First delivery -> Creates reconciliation record
        $res1 = $this->sendSignedWebhook($event);
        $this->assertSame(200, $res1->getStatusCode());
        $this->assertSame(1, DB::table('payment_reconciliations')->where('gateway_reference', $ref)->count());

        // Replay -> Updates audit log without duplicate record or crash
        $res2 = $this->sendSignedWebhook($event);
        $this->assertSame(200, $res2->getStatusCode());
        $this->assertSame(1, DB::table('payment_reconciliations')->where('gateway_reference', $ref)->count());
        $case = DB::table('payment_reconciliations')->where('gateway_reference', $ref)->first();
        $this->assertEquals(2500.00, (float) $case->captured_amount);
    }

    // =========================================================================
    // SCENARIO 11: Cancellation Handling
    // =========================================================================
    public function testScenario11CancellationPreservesPaymentState(): void
    {
        $user = $this->customer();
        $payment = PaymentRequest::create([
            'id' => (string) Str::uuid(),
            'payer_id' => (string) $user->id,
            'payment_amount' => '1000.00',
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'marketplace_delivery',
            'order_group_id' => 'cancel-group',
            'gateway_reference' => 'cancel-ref',
            'attempt_status' => 'pending',
            'is_paid' => 0,
            'payer_information' => '{}',
            'additional_data' => '{}',
        ]);

        $controller = $this->app->make(PaystackController::class);
        $request = Request::create('/payment/paystack/cancel', 'GET', ['payments_id' => $payment->id]);
        $response = $controller->cancel($request);

        $this->assertSame(0, (int) $payment->fresh()->is_paid);
        $this->assertSame(0, Order::where('order_group_id', 'cancel-group')->count());
    }

    // =========================================================================
    // SCENARIO 12: Retired Stored-Value Wallet Add-Fund Gated
    // =========================================================================
    public function testScenario12LegacyStoredValueWalletAddFundPathIsRetired(): void
    {
        $user = $this->customer();
        // Payment request without recognized marketplace domain
        $payment = PaymentRequest::create([
            'id' => (string) Str::uuid(),
            'payer_id' => (string) $user->id,
            'payment_amount' => '5000.00',
            'currency_code' => 'NGN',
            'payment_method' => 'paystack',
            'payment_domain' => 'wallet_add_fund', // Retired legacy domain
            'order_group_id' => null,
            'gateway_reference' => 'legacy-wallet-ref',
            'attempt_status' => 'pending',
            'is_paid' => 0,
            'payer_information' => '{}',
            'additional_data' => '{}',
        ]);

        $controller = $this->app->make(PaystackController::class);
        $this->actingAs($user, 'customer');
        $request = Request::create('/payment/paystack/pay', 'GET', ['payment_id' => $payment->id]);
        $response = $controller->index($request);

        // HTTP 410 Gone — retired path
        $this->assertSame(410, $response->getStatusCode());
    }
}
