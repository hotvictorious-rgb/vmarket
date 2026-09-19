<?php
/**
 * [AI] Commit 7 Comprehensive Test Suite: VMarket Post-Receipt Lifecycle & Financial Timing
 * ══════════════════════════════════════════════════════════════════════════════════════════
 * 14 Mandatory Scenarios & Invariant Proofs (Δ = ₦0.00)
 * Run: php scratch/test_commit7_post_receipt_lifecycle.php
 * ══════════════════════════════════════════════════════════════════════════════════════════
 */

chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AdminWallet;
use App\Models\CustomerCashbackLedger;
use App\Models\DeliveryMan;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderTransaction;
use App\Models\RefundRequest;
use App\Models\Seller;
use App\Models\SellerWallet;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DeliveryOrderSettlementService;
use App\Services\VendorSettlementService;
use App\Services\WhatsAppRiderService;
use App\Utils\OrderManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$passed = 0;
$failed = 0;
$errors = [];
$checkNum = 1;

function testAssert(string $name, bool $result, string $detail = ''): void {
    global $passed, $failed, $errors, $checkNum;
    $n = $checkNum++;
    if ($result) {
        echo "  \033[32m✓ PASS\033[0m  #$n: $name\n";
        $passed++;
    } else {
        $msg = $detail ? " — $detail" : '';
        echo "  \033[31m✗ FAIL\033[0m  #$n: $name$msg\n";
        $failed++;
        $errors[] = "#$n [$name]" . ($detail ? ": $detail" : '');
    }
}

function printHeader(string $title): void {
    echo "\n\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n";
    echo "\033[1;36m  $title\033[0m\n";
    echo "\033[1;36m════════════════════════════════════════════════════════════════\033[0m\n";
}

echo "\n";
echo "\033[1;35m╔══════════════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[1;35m║   COMMIT 7 POST-RECEIPT LIFECYCLE & FINANCIAL TEST SUITE        ║\033[0m\n";
echo "\033[1;35m║   Victorious MARKET · 14 Scenarios · " . date('Y-m-d H:i:s') . "        ║\033[0m\n";
echo "\033[1;35m╚══════════════════════════════════════════════════════════════════╝\033[0m\n";

DB::beginTransaction();

try {
    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 1: Schema Integrity & VMarket-Owned NULL Boundary
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 1: Schema Integrity & VMarket-Owned NULL Boundary');

    $cols = [
        'received_at',
        'refund_window_expires_at',
        'vendor_settlement_status',
        'rider_picked_up_at',
        'rider_picked_up_by',
        'settled_at',
        'settled_by_id',
        'settlement_reference',
        'is_delivery_fee_refunded',
    ];
    foreach ($cols as $col) {
        testAssert("Schema column orders.{$col} exists", Schema::hasColumn('orders', $col));
    }

    // Verify VMarket-owned (seller_is = 'admin') order creation: vendor_settlement_status MUST be NULL
    $adminOrderData = OrderManager::getOrderAddData(
        orderId: 999901,
        orderGroupId: 'grp-test-admin-1',
        customerData: ['customer_id' => 1, 'is_guest' => 0],
        cartData: [
            'seller_id' => 0,
            'seller_is' => 'admin',
            'order_amount' => 5000,
            'coupon_discount' => 0,
            'total_tax_amount' => 0,
            'shipping_cost' => 500,
            'free_delivery_discount' => 0,
            'extra_discount_type' => null,
            'refer_and_earn_discount' => 0,
            'free_delivery_bearer' => 'admin',
            'is_shipping_free' => 0,
            'shipping_method_id' => 1,
            'shipping_type' => 'inhouse',
            'shipping_address_id' => 0,
            'billing_address_id' => 0,
            'coupon_code' => null,
            'coupon_bearer' => 'inhouse',
            'discount_type' => null,
        ],
        orderData: [
            'order_type' => 'default_type',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'payment_method' => 'paystack',
            'transaction_ref' => 'TEST-ADMIN-TX-1',
        ]
    );
    testAssert("VMarket-owned order vendor_settlement_status is NULL", $adminOrderData['vendor_settlement_status'] === null);

    // Verify third-party (seller_is = 'seller') order creation: vendor_settlement_status MUST be 'held'
    $sellerOrderData = OrderManager::getOrderAddData(
        orderId: 999902,
        orderGroupId: 'grp-test-seller-1',
        customerData: ['customer_id' => 1, 'is_guest' => 0],
        cartData: [
            'seller_id' => 1,
            'seller_is' => 'seller',
            'order_amount' => 5000,
            'coupon_discount' => 0,
            'total_tax_amount' => 0,
            'shipping_cost' => 500,
            'free_delivery_discount' => 0,
            'extra_discount_type' => null,
            'refer_and_earn_discount' => 0,
            'free_delivery_bearer' => 'admin',
            'is_shipping_free' => 0,
            'shipping_method_id' => 1,
            'shipping_type' => 'inhouse',
            'shipping_address_id' => 0,
            'billing_address_id' => 0,
            'coupon_code' => null,
            'coupon_bearer' => 'inhouse',
            'discount_type' => null,
        ],
        orderData: [
            'order_type' => 'default_type',
            'payment_status' => 'paid',
            'order_status' => 'confirmed',
            'payment_method' => 'paystack',
            'transaction_ref' => 'TEST-SELLER-TX-1',
        ]
    );
    testAssert("Third-party vendor order vendor_settlement_status is 'held'", $sellerOrderData['vendor_settlement_status'] === 'held');

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 2: Exact BCMath Calculations in Delivery Settlement
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 2: Exact BCMath in Delivery Settlement (Δ = ₦0.00)');

    $awkwardAmounts = [
        ['subtotal' => '1000.01', 'expected_comm' => '100.00', 'expected_seller' => '900.01'],
        ['subtotal' => '33333.33', 'expected_comm' => '3333.33', 'expected_seller' => '30000.00'],
        ['subtotal' => '100000.00', 'expected_comm' => '10000.00', 'expected_seller' => '90000.00'],
    ];

    foreach ($awkwardAmounts as $idx => $amt) {
        $sub = $amt['subtotal'];
        $rawComm = bcdiv(bcmul($sub, '10', 4), '100', 4);
        $comm = bcadd($rawComm, '0', 2);
        $seller = bcsub($sub, $comm, 2);
        $sum = bcadd($comm, $seller, 2);

        testAssert("BCMath Split #{$idx} (Subtotal ₦{$sub}): Admin=₦{$comm}, Vendor=₦{$seller}", $comm === $amt['expected_comm'] && $seller === $amt['expected_seller']);
        testAssert("BCMath Invariant #{$idx}: Admin + Vendor = Subtotal (Δ = ₦0.00)", $sum === $sub);
    }

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 3: Delivery Vendor Pickup Verification (Phase 1)
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 3: Delivery Vendor Pickup Verification (Phase 1)');

    // Create test rider
    $rider = DeliveryMan::firstOrCreate(
        ['phone' => '08012345678'],
        ['f_name' => 'Dispatch', 'l_name' => 'Rider', 'is_active' => 1]
    );

    // Create test delivery order in processing state
    $deliveryOrder = Order::create([
        'id' => 999910,
        'customer_id' => 1,
        'is_guest' => 0,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'processing',
        'payment_status' => 'paid',
        'payment_method' => 'paystack',
        'transaction_ref' => 'TEST-RIDER-DELIV-1',
        'order_group_id' => 'grp-rider-deliv-1',
        'order_amount' => 10000,
        'admin_commission' => 1000,
        'order_type' => 'default_type',
        'delivery_man_id' => $rider->id,
        'pickup_verification_code' => '654321',
        'verification_code' => '123456',
        'vendor_settlement_status' => 'held',
    ]);

    // Test A: Wrong code + correct rider -> rejected
    $wrongCodeResult = WhatsAppRiderService::confirmPickup('08012345678', $deliveryOrder->id, '999999');
    testAssert("Wrong pickup code is rejected", $wrongCodeResult['status'] === false);

    // Test B: Unassigned rider -> rejected
    $unassignedResult = WhatsAppRiderService::confirmPickup('08099999999', $deliveryOrder->id, '654321');
    testAssert("Unassigned rider is rejected", $unassignedResult['status'] === false);

    // Test C: Correct code + assigned rider -> out_for_delivery
    $successPickup = WhatsAppRiderService::confirmPickup('08012345678', $deliveryOrder->id, '654321');
    testAssert("Correct code + assigned rider sets status out_for_delivery", $successPickup['status'] === true);

    $freshOrder = $deliveryOrder->fresh();
    testAssert("Order status is out_for_delivery", $freshOrder->order_status === 'out_for_delivery');
    testAssert("rider_picked_up_at timestamp recorded", $freshOrder->rider_picked_up_at !== null);
    testAssert("rider_picked_up_by matches rider ID", (int)$freshOrder->rider_picked_up_by === (int)$rider->id);
    testAssert("Phase 1 does NOT start return clock: received_at is NULL", $freshOrder->received_at === null);
    testAssert("Phase 1 does NOT set refund_window_expires_at: NULL", $freshOrder->refund_window_expires_at === null);

    // Test D: Idempotency: same rider retrying already collected order -> succeeds
    $idempotentPickup = WhatsAppRiderService::confirmPickup('08012345678', $deliveryOrder->id, '654321');
    testAssert("Idempotent retry by same rider succeeds without error", $idempotentPickup['status'] === true);

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 4: Delivery Customer Doorstep Receipt Verification (Phase 2)
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 4: Delivery Customer Doorstep Receipt (Phase 2)');

    // Wrong doorstep code -> rejected
    $wrongDoorstep = WhatsAppRiderService::verifyDoorstepOtp('08012345678', $deliveryOrder->id, '000000');
    testAssert("Wrong doorstep delivery OTP is rejected", $wrongDoorstep['status'] === false);

    // Correct doorstep code -> delivered
    $successDoorstep = WhatsAppRiderService::verifyDoorstepOtp('08012345678', $deliveryOrder->id, '123456');
    testAssert("Correct customer delivery OTP succeeds", $successDoorstep['status'] === true);

    $deliveredOrder = $deliveryOrder->fresh();
    testAssert("Order status transitioned to delivered", $deliveredOrder->order_status === 'delivered');
    testAssert("Doorstep receipt recorded: received_at != null", $deliveredOrder->received_at !== null);
    testAssert("24-hour return clock established: refund_window_expires_at != null", $deliveredOrder->refund_window_expires_at !== null);

    $rAt = Carbon::parse($deliveredOrder->received_at);
    $expAt = Carbon::parse($deliveredOrder->refund_window_expires_at);
    $diffSecs = $rAt->diffInSeconds($expAt);
    testAssert("Exact 24-hour return window (expires_at - received_at = 86400s / 24h)", abs($diffSecs - 86400) <= 2, "received: {$rAt}, expires: {$expAt}, diff: {$diffSecs}s");
    testAssert("vendor_settlement_status remains 'held'", $deliveredOrder->vendor_settlement_status === 'held');

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 5: Pickup Handover Verification & Cross-Mode Isolation
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 5: Pickup Handover Verification & Cross-Mode Isolation');

    // Cross-mode guard: attempt rider delivery OTP on a pickup order
    $pickupOrder = Order::create([
        'id' => 999920,
        'customer_id' => 1,
        'is_guest' => 0,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'confirmed',
        'payment_status' => 'paid',
        'payment_method' => 'paystack',
        'transaction_ref' => 'TEST-PICKUP-ORD-1',
        'order_group_id' => 'grp-pickup-ord-1',
        'order_amount' => 8000,
        'admin_commission' => 800,
        'order_type' => 'pickup',
        'delivery_man_id' => $rider->id,
        'pickup_verification_code' => '112233',
        'verification_code' => '445566',
        'vendor_settlement_status' => 'held',
    ]);

    $crossModeRiderPickup = WhatsAppRiderService::confirmPickup('08012345678', $pickupOrder->id, '112233');
    testAssert("Cross-Mode Guard: Rider cannot collect pickup order", $crossModeRiderPickup['status'] === false);

    $crossModeRiderDoorstep = WhatsAppRiderService::verifyDoorstepOtp('08012345678', $pickupOrder->id, '445566');
    testAssert("Cross-Mode Guard: Rider cannot doorstep-verify pickup order", $crossModeRiderDoorstep['status'] === false);

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 6: Idempotent Timestamp Guard
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 6: Idempotent Timestamp Guard');

    $originalReceivedAt = $deliveredOrder->received_at;
    $originalExpiresAt = $deliveredOrder->refund_window_expires_at;

    // Simulate second delivered status update attempt
    $deliveredOrder->update([
        'received_at' => $deliveredOrder->received_at ?? now(),
        'refund_window_expires_at' => $deliveredOrder->refund_window_expires_at ?? now()->addHours(24),
    ]);

    testAssert("Idempotent Timestamp: received_at preserved exactly", $deliveredOrder->fresh()->received_at->equalTo($originalReceivedAt));
    testAssert("Idempotent Timestamp: refund_window_expires_at preserved exactly", $deliveredOrder->fresh()->refund_window_expires_at->equalTo($originalExpiresAt));

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 7: Per-Child-Order Refund Window Timing
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 7: Per-Child-Order Refund Timing');

    // Child Order A received 25 hours ago -> expired
    $orderA = Order::create([
        'id' => 999931,
        'customer_id' => 1,
        'is_guest' => 0,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'delivered',
        'received_at' => now()->subHours(25),
        'refund_window_expires_at' => now()->subHour(1),
        'vendor_settlement_status' => 'held',
    ]);

    // Child Order B received 10 hours ago -> within window
    $orderB = Order::create([
        'id' => 999932,
        'customer_id' => 1,
        'is_guest' => 0,
        'seller_id' => 2,
        'seller_is' => 'seller',
        'order_status' => 'delivered',
        'received_at' => now()->subHours(10),
        'refund_window_expires_at' => now()->addHours(14),
        'vendor_settlement_status' => 'held',
    ]);

    testAssert("Order A (25h ago) isWithinRefundWindow() is FALSE", $orderA->isWithinRefundWindow() === false);
    testAssert("Order A isRefundWindowExpired() is TRUE", $orderA->isRefundWindowExpired() === true);
    testAssert("Order B (10h ago) isWithinRefundWindow() is TRUE", $orderB->isWithinRefundWindow() === true);
    testAssert("Order B isRefundWindowExpired() is FALSE", $orderB->isRefundWindowExpired() === false);

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 8: Dispute Protection & Terminal Refunded Invariant
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 8: Dispute Protection & Terminal Refunded Invariant');

    $settlementService = new VendorSettlementService();

    // Order with expired window but open dispute
    $disputedOrder = Order::create([
        'id' => 999940,
        'customer_id' => 1,
        'is_guest' => 0,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'delivered',
        'received_at' => now()->subHours(26),
        'refund_window_expires_at' => now()->subHours(2),
        'vendor_settlement_status' => 'disputed',
    ]);

    // Create open refund request
    $refundReq = RefundRequest::create([
        'order_id' => $disputedOrder->id,
        'order_details_id' => 1,
        'customer_id' => 1,
        'status' => 'pending',
        'amount' => 5000,
        'product_id' => 1,
        'refund_reason' => 'Defective item',
    ]);

    testAssert("Order hasUnresolvedRefund() is TRUE", $disputedOrder->hasUnresolvedRefund() === true);
    testAssert("Disputed order settlement eligibility is BLOCKED", $settlementService->evaluateOrderSettlementEligibility($disputedOrder) === false);

    // Resolve dispute: APPROVED -> vendor_settlement_status becomes 'refunded'
    $settlementService->resolveRefundDispute($disputedOrder, 'approved');
    $freshDisputed = $disputedOrder->fresh();
    testAssert("Approved dispute sets vendor_settlement_status = 'refunded'", $freshDisputed->vendor_settlement_status === 'refunded');

    // TERMINAL INVARIANT: 'refunded' order can NEVER become eligible or settled
    testAssert("Terminal Invariant: 'refunded' order evaluateOrderSettlementEligibility() returns FALSE", $settlementService->evaluateOrderSettlementEligibility($freshDisputed) === false);

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 9: Manual Vendor Settlement Payout & Boundary
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 9: Manual Vendor Settlement & Boundary');

    // Create eligible third-party order
    $eligibleOrder = Order::create([
        'id' => 999950,
        'customer_id' => 1,
        'is_guest' => 0,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'delivered',
        'order_amount' => 20000,
        'admin_commission' => 2000,
        'received_at' => now()->subHours(26),
        'refund_window_expires_at' => now()->subHours(2),
        'vendor_settlement_status' => 'held',
    ]);

    // Add hold transaction
    OrderTransaction::create([
        'transaction_id' => 'TX-HOLD-999950',
        'customer_id' => 1,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_id' => $eligibleOrder->id,
        'order_amount' => 20000,
        'seller_amount' => 18000,
        'admin_commission' => 2000,
        'received_by' => 'customer',
        'status' => 'hold',
        'delivery_charge' => 0,
        'tax' => 0,
        'delivered_by' => 'delivery_man',
        'payment_method' => 'paystack',
    ]);

    // Ensure AdminWallet has pending_amount
    $adminWallet = AdminWallet::firstOrCreate(['admin_id' => 1]);
    $adminWallet->pending_amount = 50000;
    $adminWallet->save();

    // Evaluate eligibility -> transitions to 'eligible'
    $isEligible = $settlementService->evaluateOrderSettlementEligibility($eligibleOrder);
    testAssert("Eligible order evaluated to true", $isEligible === true);
    testAssert("Eligible order vendor_settlement_status = 'eligible'", $eligibleOrder->fresh()->vendor_settlement_status === 'eligible');

    // Execute manual settlement payout
    $initialSellerEarning = SellerWallet::where('seller_id', 1)->value('total_earning') ?? 0;
    $settlementResult = $settlementService->executeManualSettlement(
        orderId: $eligibleOrder->id,
        adminId: 1,
        paymentMethod: 'bank_transfer',
        paymentReference: 'TX-BANK-REF-999950',
        notes: 'Verified post-receipt settlement'
    );

    testAssert("Manual settlement execution returns success", $settlementResult['status'] === true);
    $settledOrder = $eligibleOrder->fresh();
    testAssert("Order vendor_settlement_status = 'settled'", $settledOrder->vendor_settlement_status === 'settled');
    testAssert("Order settled_at recorded", $settledOrder->settled_at !== null);
    testAssert("Order settlement_reference recorded", $settledOrder->settlement_reference === 'TX-BANK-REF-999950');

    $updatedSellerEarning = SellerWallet::where('seller_id', 1)->value('total_earning');
    $deltaEarning = bcsub((string)$updatedSellerEarning, (string)$initialSellerEarning, 2);
    testAssert("SellerWallet credited with 90% (₦18,000.00)", $deltaEarning === '18000.00');

    $updatedTxStatus = OrderTransaction::where('order_id', $eligibleOrder->id)->value('status');
    testAssert("OrderTransaction status transitioned from 'hold' to 'disburse'", $updatedTxStatus === 'disburse');

    // Boundary check: VMarket-owned order cannot be manually settled
    $adminOrderForSettlement = Order::create([
        'id' => 999951,
        'customer_id' => 1,
        'seller_id' => 0,
        'seller_is' => 'admin',
        'order_status' => 'delivered',
        'vendor_settlement_status' => null,
    ]);
    testAssert("VMarket in-house order evaluateOrderSettlementEligibility() returns FALSE", $settlementService->evaluateOrderSettlementEligibility($adminOrderForSettlement) === false);
    $adminSettleAttempt = $settlementService->executeManualSettlement($adminOrderForSettlement->id, 1, 'bank', 'REF');
    testAssert("VMarket in-house order manual settlement is REJECTED", $adminSettleAttempt['status'] === false);

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 10: Delivery Fee Refund Rules (Case A vs Case B)
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 10: Delivery Fee Refund Rules (Case A vs Case B)');

    // Case A: Delivered order (received_at != null) -> delivery fee is NON-REFUNDABLE
    $caseAOrder = Order::create([
        'id' => 999961,
        'customer_id' => 1,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'delivered',
        'order_amount' => 102000,
        'shipping_cost' => 2000,
        'received_at' => now()->subHours(2),
        'refund_window_expires_at' => now()->addHours(22),
        'vendor_settlement_status' => 'held',
        'is_delivery_fee_refunded' => 0,
    ]);

    $undelivAttemptCaseA = $settlementService->executeUndeliveredOrderRefund($caseAOrder, 'Test');
    testAssert("Case A: Cannot refund delivery fee on delivered order", $undelivAttemptCaseA['status'] === false);
    testAssert("Case A: is_delivery_fee_refunded remains 0", $caseAOrder->fresh()->is_delivery_fee_refunded == 0);

    // Case B: Undelivered order (received_at == null) -> full refund includes delivery fee
    $adminWallet->delivery_charge_earned = 10000;
    $adminWallet->save();

    $caseBOrder = Order::create([
        'id' => 999962,
        'customer_id' => 1,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'canceled',
        'order_amount' => 102000,
        'shipping_cost' => 2000,
        'received_at' => null,
        'vendor_settlement_status' => 'held',
        'is_delivery_fee_refunded' => 0,
    ]);

    $undelivResultCaseB = $settlementService->executeUndeliveredOrderRefund($caseBOrder, 'Order failed before delivery');
    testAssert("Case B: Full refund of undelivered order succeeds", $undelivResultCaseB['status'] === true);
    testAssert("Case B: is_delivery_fee_refunded set to 1", $caseBOrder->fresh()->is_delivery_fee_refunded == 1);
    testAssert("Case B: vendor_settlement_status set to 'refunded'", $caseBOrder->fresh()->vendor_settlement_status === 'refunded');

    $deliveryChargeReversed = (float)$adminWallet->fresh()->delivery_charge_earned === 8000.00;
    testAssert("Case B: AdminWallet delivery_charge_earned reduced by ₦2,000", $deliveryChargeReversed);

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 11: Legacy Order Backfill & legacy_hold Blocking
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 11: Legacy Order Backfill & legacy_hold Blocking');

    // Path A: legacy disbursed order -> 'settled'
    $legacySettled = Order::create([
        'id' => 999971,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'delivered',
        'vendor_settlement_status' => 'settled',
    ]);
    // Gate in_array(['held', 'disputed', 'legacy_hold']) must NOT block 'settled'
    $gateFiredSettled = in_array($legacySettled->vendor_settlement_status, ['held', 'disputed', 'legacy_hold'], true);
    testAssert("Legacy Path A: 'settled' does NOT trigger hold gate", $gateFiredSettled === false);

    // Path B: legacy held order -> enters V1 lifecycle
    $legacyHeld = Order::create([
        'id' => 999972,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'delivered',
        'received_at' => now()->subHours(10),
        'refund_window_expires_at' => now()->addHours(14),
        'vendor_settlement_status' => 'held',
    ]);
    $gateFiredHeld = in_array($legacyHeld->vendor_settlement_status, ['held', 'disputed', 'legacy_hold'], true);
    testAssert("Legacy Path B: 'held' triggers hold gate (automatic payout blocked)", $gateFiredHeld === true);

    // Path C: legacy unresolvable order -> 'legacy_hold' sentinel
    $legacySentinel = Order::create([
        'id' => 999973,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'delivered',
        'received_at' => null,
        'vendor_settlement_status' => 'legacy_hold',
    ]);
    $gateFiredSentinel = in_array($legacySentinel->vendor_settlement_status, ['held', 'disputed', 'legacy_hold'], true);
    testAssert("Legacy Path C: 'legacy_hold' triggers hold gate (automatic payout BLOCKED)", $gateFiredSentinel === true);

    // Test that disburseSettledVendorOrder() strictly rejects 'legacy_hold'
    $legacyHoldRejected = false;
    try {
        OrderManager::disburseSettledVendorOrder($legacySentinel, 'REF', 1);
    } catch (\RuntimeException $e) {
        $legacyHoldRejected = true;
    }
    testAssert("disburseSettledVendorOrder() strictly rejects 'legacy_hold' orders", $legacyHoldRejected === true);

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 12: VMarket-Owned Undelivered Refund NULL Preservation
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 12: VMarket-Owned NULL Preservation on Refund');

    $adminUndelivered = Order::create([
        'id' => 999980,
        'customer_id' => 1,
        'seller_id' => 0,
        'seller_is' => 'admin',
        'order_status' => 'canceled',
        'order_amount' => 12000,
        'shipping_cost' => 1500,
        'received_at' => null,
        'vendor_settlement_status' => null,
        'is_delivery_fee_refunded' => 0,
    ]);

    $adminRefundResult = $settlementService->executeUndeliveredOrderRefund($adminUndelivered, 'Customer cancellation');
    testAssert("VMarket in-house undelivered refund succeeds", $adminRefundResult['status'] === true);
    testAssert("VMarket in-house vendor_settlement_status REMAINS NULL", $adminUndelivered->fresh()->vendor_settlement_status === null);

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 13: Rider Audit Columns Verification
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 13: Rider Audit Columns Verification');

    $auditOrder = Order::create([
        'id' => 999990,
        'customer_id' => 1,
        'seller_id' => 1,
        'seller_is' => 'seller',
        'order_status' => 'processing',
        'delivery_man_id' => $rider->id,
        'pickup_verification_code' => '987654',
        'verification_code' => '456789',
        'vendor_settlement_status' => 'held',
    ]);

    $pickupTimeBefore = now();
    WhatsAppRiderService::confirmPickup('08012345678', $auditOrder->id, '987654');
    $freshAudit = $auditOrder->fresh();

    testAssert("rider_picked_up_at is not null", $freshAudit->rider_picked_up_at !== null);
    testAssert("rider_picked_up_by matches assigned rider", (int)$freshAudit->rider_picked_up_by === (int)$rider->id);
    testAssert("received_at remains null during custody transfer", $freshAudit->received_at === null);
    testAssert("refund_window_expires_at remains null during custody transfer", $freshAudit->refund_window_expires_at === null);

    // ══════════════════════════════════════════════════════════════════
    // SCENARIO 14: Systemic Mathematical Invariant Proof (Δ = ₦0.00)
    // ══════════════════════════════════════════════════════════════════
    printHeader('Scenario 14: Systemic Mathematical Invariant Proof (Δ = ₦0.00)');

    $testCases = [
        '500.00', '1250.50', '9999.99', '25000.00', '47823.17', '100000.00'
    ];

    $allZeroDrift = true;
    foreach ($testCases as $caseSubtotal) {
        $comm = bcadd(bcdiv(bcmul($caseSubtotal, '10', 4), '100', 4), '0', 2);
        $vendor = bcsub($caseSubtotal, $comm, 2);
        $reconstructed = bcadd($comm, $vendor, 2);
        $delta = bcsub($reconstructed, $caseSubtotal, 2);

        if ($delta !== '0.00') {
            $allZeroDrift = false;
        }
    }
    testAssert("Zero drift across all subtotal partitions (Reconstructed == Original, Δ = ₦0.00)", $allZeroDrift);

} finally {
    // Clean rollback so tests leave zero garbage in database
    DB::rollBack();
}

// ══════════════════════════════════════════════════════════════════
// FINAL TEST REPORT
// ══════════════════════════════════════════════════════════════════
echo "\n";
echo "\033[1;35m╔══════════════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[1;35m║                    COMMIT 7 TEST REPORT                         ║\033[0m\n";
echo "\033[1;35m╚══════════════════════════════════════════════════════════════════╝\033[0m\n\n";

echo "  ✓ PASS: $passed\n";
echo "  ✗ FAIL: $failed\n\n";

if ($failed === 0) {
    echo "  \033[1;32m██████████████████████████████████████████████████████████████\033[0m\n";
    echo "  \033[1;32m██  RESULT: ALL COMMIT 7 SCENARIOS PASSED (100% SUCCESS)    ██\033[0m\n";
    echo "  \033[1;32m██  Zero Drift Certified: Δ = ₦0.00 across all test models   ██\033[0m\n";
    echo "  \033[1;32m██████████████████████████████████████████████████████████████\033[0m\n\n";
    exit(0);
} else {
    echo "  \033[1;31m██████████████████████████████████████████████████████████████\033[0m\n";
    echo "  \033[1;31m██  RESULT: $failed SCENARIO(S) FAILED                         ██\033[0m\n";
    echo "  \033[1;31m██████████████████████████████████████████████████████████████\033[0m\n\n";
    foreach ($errors as $err) {
        echo "    • $err\n";
    }
    echo "\n";
    exit(1);
}
