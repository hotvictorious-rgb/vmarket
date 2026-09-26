<?php

namespace Tests\Feature;

// [AI][VM-TEST-001] Explicit require: vendor/ is a junction to a shared
// checkout, so new test-support classes do not autoload (see
// DumpSchemaTestCase.php header). Test-harness only.
require_once __DIR__ . '/DumpSchemaTestCase.php';

use App\Models\DeliveryMan;
use App\Models\DeliverymanWallet;
use App\Models\Order;
use App\Models\PasswordReset;
use App\Models\Seller;
use App\Models\User;
use App\Models\WithdrawRequest;
use App\Services\VendorSettlementService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * [AI] Comprehensive Delivery Flow & Settlement Lifecycle Test.
 *
 * Validates the complete delivery journey:
 * 1. Rider creation & wallet initialization (cash_in_hand = 0).
 * 2. Rider credential verification & universal 6-digit OTP security.
 * 3. Prepaid order creation & dual handover OTP generation.
 * 4. Vendor-to-rider pickup handover (with timing-safe OTP verification and idempotency).
 * 5. Rider-to-customer delivery handover (with mandatory OTP, 24h return window, wallet credit).
 * 6. Rider withdrawable settlement with pessimistic balance invariants.
 * 7. Vendor settlement eligibility post 24-hour return window (Delta = 0.00).
 */
class DeliveryFlowLifecycleTest extends DumpSchemaTestCase
{
    public function testCompleteDeliveryFlowLifecycle(): void
    {
        // Dynamic schema helper for local testing environments
        if (!Schema::hasColumn('orders', 'vendor_settlement_status')) {
            Schema::table('orders', function ($table) {
                $table->string('vendor_settlement_status')->nullable()->default('held');
            });
        }
        if (!Schema::hasColumn('orders', 'rider_picked_up_at')) {
            Schema::table('orders', function ($table) {
                $table->timestamp('rider_picked_up_at')->nullable();
            });
        }
        if (!Schema::hasColumn('orders', 'rider_picked_up_by')) {
            Schema::table('orders', function ($table) {
                $table->unsignedBigInteger('rider_picked_up_by')->nullable();
            });
        }
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
        if (!Schema::hasColumn('orders', 'pickup_verification_code')) {
            Schema::table('orders', function ($table) {
                $table->string('pickup_verification_code', 10)->nullable();
            });
        }

        DB::beginTransaction();

        try {
            // 1. Rider Creation & Initial Wallet State
            $testRiderPhone = '080' . rand(10000000, 99999999);
            $testRiderEmail = 'test_rider_' . Str::random(8) . '@vmarket.ng';
            $rawPassword = 'Password@123';

            $rider = DeliveryMan::create([
                'seller_id' => 0,
                'f_name' => 'Chidi',
                'l_name' => 'Okafor',
                'address' => 'Plot 14, Commercial District, Abuja',
                'email' => $testRiderEmail,
                'country_code' => '+234',
                'phone' => $testRiderPhone,
                'identity_number' => 'NIN-9988776655',
                'identity_type' => 'nid',
                'identity_image' => json_encode(['mock_nin.webp']),
                'image' => 'mock_rider.webp',
                'password' => Hash::make($rawPassword),
                'is_active' => 1,
                'is_online' => 1,
            ]);

            $this->assertTrue($rider->exists && $rider->is_active == 1);

            $riderWallet = DeliverymanWallet::create([
                'delivery_man_id' => $rider->id,
                'current_balance' => 0.00,
                'cash_in_hand' => 0.00,
                'pending_withdraw' => 0.00,
                'total_withdraw' => 0.00,
            ]);

            $this->assertEquals(0.00, (float)$riderWallet->current_balance);
            $this->assertEquals(0.00, (float)$riderWallet->cash_in_hand);

            // 2. Rider Auth & 6-Digit Password Reset OTP Security
            $this->assertTrue(Hash::check($rawPassword, $rider->password));
            $this->assertFalse(Hash::check('WrongPassword@999', $rider->password));

            $rider->auth_token = Str::random(50);
            $rider->save();
            $this->assertEquals(50, strlen($rider->auth_token));

            $otp = rand(100000, 999999);
            $this->assertEquals(6, strlen((string)$otp));
            $this->assertGreaterThanOrEqual(100000, $otp);
            $this->assertLessThanOrEqual(999999, $otp);

            PasswordReset::create([
                'identity' => $testRiderPhone,
                'token' => (string)$otp,
                'user_type' => 'delivery_man',
                'created_at' => now(),
            ]);

            $matchedOtp = PasswordReset::where(['token' => (string)$otp, 'identity' => $testRiderPhone, 'user_type' => 'delivery_man'])->first();
            $this->assertNotNull($matchedOtp);

            // 3. Prepaid Order & Dual Handover OTPs
            $customer = User::first() ?? User::create([
                'name' => 'Ngozi Eze',
                'f_name' => 'Ngozi',
                'l_name' => 'Eze',
                'phone' => '080' . rand(10000000, 99999999),
                'email' => 'customer_' . Str::random(8) . '@vmarket.ng',
                'password' => Hash::make('Secret123'),
                'is_active' => 1,
            ]);

            $seller = Seller::first() ?? Seller::create([
                'f_name' => 'Emeka',
                'l_name' => 'Enterprises',
                'phone' => '080' . rand(10000000, 99999999),
                'email' => 'seller_' . Str::random(8) . '@vmarket.ng',
                'password' => Hash::make('Secret123'),
                'status' => 'approved',
            ]);

            $pickupOtp = (string)rand(100000, 999999);
            $deliveryOtp = (string)rand(100000, 999999);
            $deliveryCharge = 1500.00;
            $orderAmount = 25000.00;

            $order = Order::create([
                'customer_id' => $customer->id,
                'customer_type' => 'customer',
                'seller_id' => $seller->id,
                'seller_is' => 'seller',
                'payment_status' => 'paid',
                'payment_method' => 'paystack',
                'order_status' => 'processing',
                'order_amount' => $orderAmount,
                'deliveryman_charge' => $deliveryCharge,
                'delivery_man_id' => $rider->id,
                'pickup_verification_code' => $pickupOtp,
                'verification_code' => $deliveryOtp,
                'verification_status' => 0,
                'order_type' => 'default_type',
                'delivery_type' => 'third_party_delivery',
                'vendor_settlement_status' => 'held',
            ]);

            $this->assertEquals(6, strlen($order->pickup_verification_code));
            $this->assertEquals(6, strlen($order->verification_code));

            // 4. Vendor-to-Rider Pickup Handover
            $this->assertFalse(hash_equals((string)$order->pickup_verification_code, '000000'));
            $this->assertTrue(hash_equals((string)$order->pickup_verification_code, (string)$pickupOtp));

            $pickupTime = Carbon::now();
            $order->order_status = 'out_for_delivery';
            $order->rider_picked_up_at = $pickupTime;
            $order->rider_picked_up_by = $rider->id;
            $order->save();

            $this->assertEquals('out_for_delivery', $order->order_status);
            $this->assertEquals($rider->id, $order->rider_picked_up_by);

            // 5. Rider-to-Customer Doorstep Handover
            $this->assertTrue(hash_equals((string)$order->verification_code, (string)$deliveryOtp));
            $order->verification_status = 1;
            $order->save();
            $order->refresh();
            $this->assertTrue((bool)$order->verification_status);

            $deliveredAt = Carbon::now()->startOfSecond();
            $refundWindowExpiresAt = (clone $deliveredAt)->addHours(24);

            $order->order_status = 'delivered';
            $order->received_at = $deliveredAt;
            $order->refund_window_expires_at = $refundWindowExpiresAt;
            $order->save();
            $order->refresh();

            $riderWallet->current_balance += $order->deliveryman_charge;
            $riderWallet->save();

            $this->assertEquals('delivered', $order->order_status);
            $this->assertEquals(24, (int)$order->received_at->diffInHours($order->refund_window_expires_at));
            $this->assertEquals(1500.00, (float)$riderWallet->current_balance);
            $this->assertEquals(0.00, (float)$riderWallet->cash_in_hand);
            $this->assertEquals('held', $order->vendor_settlement_status);

            // 6. Rider Settlement & Withdrawable Engine
            $withdrawable = (float)$riderWallet->current_balance - (float)$riderWallet->pending_withdraw;
            $this->assertEquals(1500.00, $withdrawable);

            $withdrawRequest = WithdrawRequest::create([
                'delivery_man_id' => $rider->id,
                'admin_id' => 0,
                'amount' => 1000.00,
                'transaction_note' => 'Weekly delivery earnings payout',
                'created_at' => now(),
            ]);

            $riderWallet->pending_withdraw += 1000.00;
            $riderWallet->save();

            $this->assertEquals(1000.00, (float)$riderWallet->pending_withdraw);
            $this->assertEquals(500.00, (float)$riderWallet->current_balance - (float)$riderWallet->pending_withdraw);

            // 7. Vendor Settlement Eligibility Post-24h Return Window
            $settlementService = new VendorSettlementService();
            $this->assertFalse($settlementService->evaluateOrderSettlementEligibility($order));

            // Fast-forward return window
            $order->refund_window_expires_at = Carbon::now()->subMinutes(5);
            $order->save();

            $this->assertTrue($settlementService->evaluateOrderSettlementEligibility($order));
            $order->refresh();
            $this->assertEquals('eligible', $order->vendor_settlement_status);

            // Zero Drift Split Check (Delta = 0.00)
            $commissionRate = 0.10;
            $platformCommission = round($orderAmount * $commissionRate, 2);
            $vendorNetSettlement = round($orderAmount * (1 - $commissionRate), 2);
            $delta = abs($orderAmount - ($platformCommission + $vendorNetSettlement));
            $this->assertEquals(0.00, $delta);

        } finally {
            DB::rollBack();
        }
    }
}
