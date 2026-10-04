<?php

namespace Tests\Regression;

require_once __DIR__ . '/../Feature/DumpSchemaTestCase.php';

use App\Models\AdminWallet;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\RefundRequest;
use App\Models\SellerWallet;
use App\Models\Transaction;
use App\Services\DeliveryManWithdrawService;
use App\Services\PaystackRefundService;
use App\Services\VendorRefundDecisionService;
use App\Services\VendorSettlementService;
use App\Utils\OrderManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class V1FinancePathsTest extends \Tests\Feature\DumpSchemaTestCase
{
    public function createApplication()
    {
        // [AI] Isolation is explicit before application/provider bootstrap, independent of local .env.
        foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array', 'CACHE_DRIVER' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync'] as $k => $v) {
            putenv("$k=$v"); $_ENV[$k] = $v; $_SERVER[$k] = $v;
        }
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->beforeBootstrapping(\Illuminate\Foundation\Bootstrap\BootProviders::class, function ($app) {
            // [AI] Force the actual loaded config too: a cached production config cannot override test isolation.
            $app->make('config')->set('database.default', 'sqlite');
            $app->make('config')->set('database.connections.sqlite.database', ':memory:');
            $app->make('config')->set('cache.default', 'array');
            $app->make('config')->set('session.driver', 'array');
            $dump = $app->basePath('installation/backup/database.sql');
            \Tests\Feature\SqliteDumpLoader::load($app->make('db')->connection()->getPdo(), $dump);
            foreach (\Tests\Feature\SqliteDumpLoader::migrationsCreatingMissingTables($dump) as $path) {
                $migration = require $app->basePath($path);
                $migration->up();
            }
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Http::preventStrayRequests(); Event::fake();
        (require base_path('database/migrations/2026_10_03_000001_add_expires_at_to_customer_cashback_ledgers_table.php'))->up();
        foreach (['vendor_settlement_status' => 'string', 'received_at' => 'timestamp', 'refund_window_expires_at' => 'timestamp',
            'settled_at' => 'timestamp', 'settlement_reference' => 'string', 'settled_by_id' => 'integer',
            'settlement_notes' => 'text', 'settlement_method' => 'string', 'total_tax_amount' => 'decimal', 'is_delivery_fee_refunded' => 'integer'] as $column => $type) {
            if (!Schema::hasColumn('orders', $column)) Schema::table('orders', fn ($t) => $t->$type($column)->nullable());
        }
        if (!Schema::hasColumn('order_transactions', 'escrow_remaining')) Schema::table('order_transactions', fn ($t) => $t->decimal('escrow_remaining', 20, 2)->nullable());
        foreach (['recognized_merchandise_remaining', 'recognized_commission_remaining', 'recognized_tax_remaining'] as $column) {
            if (!Schema::hasColumn('order_transactions', $column)) Schema::table('order_transactions', fn ($t) => $t->decimal($column, 20, 2)->nullable());
        }
        foreach (['execution_status', 'execution_id', 'payment_info'] as $column) {
            if (!Schema::hasColumn('refund_requests', $column)) Schema::table('refund_requests', fn ($t) => $t->text($column)->nullable());
        }
        foreach (['loyalty_point', 'cashback_pending', 'cashback_reserved'] as $column) {
            if (!Schema::hasColumn('users', $column)) Schema::table('users', fn ($t) => $t->decimal($column, 20, 4)->default(0));
        }
        if (!DB::table('users')->where('id', 1)->exists()) $this->row('users', ['id' => 1]);
        DB::table('admin_wallets')->delete(); DB::table('seller_wallets')->delete();
        $this->row('admin_wallets', ['admin_id' => 1, 'pending_amount' => '0.00', 'commission_earned' => '0.00',
            'delivery_charge_earned' => '0.00', 'total_tax_collected' => '0.00']);
    }

    // [AI] Fill required legacy fixture columns from the install schema; all business amounts are explicit overrides.
    private function row(string $table, array $values): int
    {
        $data = [];
        foreach (DB::select('PRAGMA table_info("' . $table . '")') as $column) {
            if ($column->name !== 'id' && $column->notnull && $column->dflt_value === null) {
                $data[$column->name] = preg_match('/INT|REAL|DECIMAL|NUMERIC/i', $column->type) ? 0 : '';
            }
        }
        return DB::table($table)->insertGetId(array_merge($data, $values));
    }

    private function order(int $id, string $merchandise = '100.00', string $tax = '0.00', string $delivery = '0.00', string $rewards = '0.00'): Order
    {
        $this->row('orders', ['id' => $id, 'customer_id' => 1, 'seller_id' => 99, 'seller_is' => 'seller', 'order_type' => 'default_type',
            'order_status' => 'delivered', 'payment_status' => 'paid', 'payment_method' => 'paystack',
            'vendor_settlement_status' => 'eligible', 'received_at' => now()->subDays(2), 'refund_window_expires_at' => now()->subDay(),
            'order_amount' => bcsub(bcadd(bcadd($merchandise, $tax, 2), $delivery, 2), $rewards, 2),
            'discount_amount' => $rewards, 'discount_type' => 'cashback', 'shipping_cost' => $delivery, 'total_tax_amount' => $tax]);
        $this->row('order_details', ['order_id' => $id, 'product_id' => 1, 'seller_id' => 99, 'qty' => 1, 'price' => $merchandise,
            'discount' => '0.00', 'tax' => $tax, 'refund_request' => 0]);
        return Order::with('details')->findOrFail($id);
    }

    public function test_actual_vendor_endpoint_cannot_complete_refund_or_reopen_admin_terminal_state(): void
    {
        $order = $this->order(901);
        $id = $this->row('refund_requests', ['order_id' => 901, 'order_details_id' => $order->details->first()->id,
            'customer_id' => 1, 'product_id' => 1, 'refund_reason' => 'Returned', 'amount' => 100, 'status' => 'pending', 'change_by' => 'customer']);
        $request = Request::create('/', 'POST', ['seller' => ['id' => 99], 'refund_request_id' => $id, 'refund_status' => 'refunded']);
        $response = app(\App\Http\Controllers\RestAPI\v3\seller\RefundController::class)->refund_status_update($request);
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('pending', RefundRequest::find($id)->status);
        RefundRequest::whereKey($id)->update(['status' => 'refunded', 'change_by' => 'admin', 'execution_status' => 'succeeded']);
        $request->merge(['refund_status' => 'approved']);
        $this->assertSame(409, app(\App\Http\Controllers\RestAPI\v3\seller\RefundController::class)->refund_status_update($request)->getStatusCode());
        $this->assertSame('refunded', RefundRequest::find($id)->status);
    }

    public function test_actual_refund_finalizer_rejects_cashback_spoof(): void
    {
        $order = $this->order(902);
        $id = $this->row('refund_requests', ['order_id' => 902, 'order_details_id' => $order->details->first()->id,
            'customer_id' => 1, 'product_id' => 1, 'refund_reason' => 'Returned', 'amount' => 100,
            'status' => 'approved', 'execution_status' => 'awaiting_manual_payment', 'payment_info' => json_encode(['refundable_money_amount' => '100.00', 'cashback_amount' => '0.00'])]);
        $result = app(PaystackRefundService::class)->finalizeManualPaymentConfirmation(RefundRequest::find($id), $order,
            ['payment_method' => 'cashback', 'amount' => '100.00', 'payment_reference' => 'fake', 'payment_date' => now()->toDateString()]);
        $this->assertFalse($result['status']);
        $this->assertSame('approved', RefundRequest::find($id)->status);
    }

    public function test_actual_partial_refund_hold_preserves_other_order_and_settlement_replay(): void
    {
        $a = $this->order(903, '50.00');
        $b = $this->order(904, '100.00');
        foreach ([$a, $b] as $o) OrderManager::getAddOrderTransactionsOnGenerateOrder($o, ['payment_method' => 'paystack']);
        // [AI] This exercises the production refund escrow helper, not a copied balance calculation.
        DB::transaction(fn () => OrderManager::releaseRefundEscrow($a, '25.00'));
        $a->details->first()->update(['price' => '25.00']);
        $result = app(VendorSettlementService::class)->executeManualSettlement(903, 1, 'wallet_release', 'REL-903', 'Regression');
        $this->assertTrue($result['status']);
        $this->assertSame('100.00', bcadd((string)AdminWallet::first()->getRawOriginal('pending_amount'), '0', 2));
        $this->assertSame('22.50', bcadd((string)SellerWallet::where('seller_id', 99)->first()->getRawOriginal('total_earning'), '0', 2));
        $this->assertSame('2.50', bcadd((string)AdminWallet::first()->getRawOriginal('commission_earned'), '0', 2));
        $this->assertTrue(app(VendorSettlementService::class)->executeManualSettlement(903, 1, 'wallet_release', 'REL-REPLAY')['replayed']);
        $this->assertSame(1, Transaction::where('order_id', 903)->where('payment_for', 'vendor_settlement')->count());
        $a->vendor_settlement_status = 'held';
        app(VendorSettlementService::class)->evaluateOrderSettlementEligibility($a);
        $this->assertSame('settled', Order::find(903)->vendor_settlement_status);
    }

    public function test_actual_mixed_reward_tax_hold_and_vendor_split(): void
    {
        $order = $this->order(905, '100.00', '7.50', '2.00', '10.00');
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'paystack']);
        $this->assertSame('109.50', bcadd((string)AdminWallet::first()->getRawOriginal('pending_amount'), '0', 2));
        $this->assertSame('90.00', bcadd((string)$order->orderTransaction->getRawOriginal('seller_amount'), '0', 2));
        app(VendorSettlementService::class)->executeManualSettlement(905, 1, 'wallet_release', 'REL-905');
        $wallet = AdminWallet::first();
        $this->assertSame('0.00', bcadd((string)$wallet->getRawOriginal('pending_amount'), '0', 2));
        $this->assertSame('10.00', bcadd((string)$wallet->getRawOriginal('commission_earned'), '0', 2));
        $this->assertSame('7.50', bcadd((string)$wallet->getRawOriginal('total_tax_collected'), '0', 2));
        $this->assertSame('2.00', bcadd((string)$wallet->getRawOriginal('delivery_charge_earned'), '0', 2));
    }

    public function test_actual_rider_payout_service_rejects_pending_decision_and_replay(): void
    {
        $wallet = new \App\Models\DeliveryManWallet(['pending_withdraw' => '100.00', 'current_balance' => '100.00', 'total_withdraw' => '0.00']);
        $wallet->syncOriginal();
        $withdraw = new \App\Models\WithdrawRequest(['amount' => '100.00', 'approved' => 0]); $withdraw->syncOriginal();
        try {
            (new DeliveryManWithdrawService())->getUpdateData(new Request(['approved' => 0]), $wallet, $withdraw);
            $this->fail('Pending decision must fail.');
        } catch (\InvalidArgumentException $e) { $this->assertSame('Invalid withdrawal decision.', $e->getMessage()); }
        $data = (new DeliveryManWithdrawService())->getUpdateData(new Request(['approved' => 2]), $wallet, $withdraw);
        $this->assertSame('0.00', $data['wallet']['pending_withdraw']);
        $withdraw->approved = 2; $withdraw->syncOriginal();
        $this->expectException(\RuntimeException::class);
        (new DeliveryManWithdrawService())->getUpdateData(new Request(['approved' => 2]), $wallet, $withdraw);
    }

    public function test_actual_admin_vendor_withdrawal_rejects_invalid_state_and_repeated_denial(): void
    {
        auth('admin')->setUser(new \App\Models\Admin(['id' => 1, 'admin_role_id' => 1, 'name' => 'Finance Regression']));
        $this->row('seller_wallets', ['seller_id' => 99, 'total_earning' => '0.00', 'pending_withdraw' => '100.00', 'withdrawn' => '0.00', 'collected_cash' => '0.00']);
        $id = $this->row('withdraw_requests', ['seller_id' => 99, 'amount' => '100.00', 'approved' => 0]);
        $controller = app(\App\Http\Controllers\Admin\Vendor\VendorController::class);
        try {
            $controller->withdrawStatus(new Request(['approved' => 0]), $id);
            $this->fail('Invalid decision should fail validation.');
        } catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('approved', $e->errors()); }
        $this->assertSame(0, \App\Models\WithdrawRequest::find($id)->approved);
        $this->assertSame('100.00', bcadd((string)SellerWallet::first()->getRawOriginal('pending_withdraw'), '0', 2));
        $controller->withdrawStatus(new Request(['approved' => 2, 'note' => 'Denied once']), $id);
        $controller->withdrawStatus(new Request(['approved' => 2, 'note' => 'Replay']), $id);
        $this->assertSame(2, \App\Models\WithdrawRequest::find($id)->approved);
        $this->assertSame('100.00', bcadd((string)SellerWallet::first()->getRawOriginal('total_earning'), '0', 2));
        $this->assertSame('0.00', bcadd((string)SellerWallet::first()->getRawOriginal('pending_withdraw'), '0', 2));
        $this->assertSame(1, \App\Models\AdminAuditLog::where('action', 'vendor.withdrawal_decision')->count());
    }

    public function test_actual_manual_partial_refund_and_settlement_preserve_other_order(): void
    {
        $a = $this->order(910, '50.00', '3.75', '2.00');
        $first = $a->details->first();
        $first->update(['price' => '25.00', 'tax' => '1.87']);
        $secondId = $this->row('order_details', ['order_id' => 910, 'product_id' => 2, 'seller_id' => 99,
            'qty' => 1, 'price' => '25.00', 'discount' => '0.00', 'tax' => '1.88', 'refund_request' => 0]);
        $a->load('details');
        $b = $this->order(911, '100.00');
        foreach ([$a, $b] as $o) OrderManager::getAddOrderTransactionsOnGenerateOrder($o, ['payment_method' => 'paystack']);
        $id = $this->row('refund_requests', ['order_id' => 910, 'order_details_id' => $first->id, 'customer_id' => 1, 'product_id' => 1,
            'refund_reason' => 'Returned first item', 'amount' => '26.87', 'status' => 'approved', 'execution_status' => 'awaiting_manual_payment',
            'payment_info' => json_encode(['refundable_money_amount' => '26.87', 'cashback_amount' => '0.00', 'refundable_merchandise_value' => '25.00'])]);
        $result = app(PaystackRefundService::class)->finalizeManualPaymentConfirmation(RefundRequest::find($id), $a,
            ['payment_method' => 'bank_transfer', 'amount' => '26.87', 'payment_reference' => 'BANK-A-1', 'payment_date' => now()->toDateString(), 'confirmed_by' => 1]);
        $this->assertTrue($result['status'], $result['message']);
        $this->assertSame(4, (int)OrderDetail::find($first->id)->refund_request);
        $a->refresh();
        // [AI] The partial refund leaves only the second item entitlement, tax and incurred delivery charge held.
        $this->assertSame('28.88', bcadd((string)$a->orderTransaction->getRawOriginal('escrow_remaining'), '0', 2));
        $a->vendor_settlement_status = 'eligible'; $a->save();
        app(VendorSettlementService::class)->executeManualSettlement(910, 1, 'wallet_release', 'PARTIAL-A');
        $this->assertSame('100.00', bcadd((string)AdminWallet::first()->getRawOriginal('pending_amount'), '0', 2));
        $this->assertSame('22.50', bcadd((string)SellerWallet::where('seller_id', 99)->first()->getRawOriginal('total_earning'), '0', 2));
        $this->assertSame('1.88', bcadd((string)AdminWallet::first()->getRawOriginal('total_tax_collected'), '0', 2));
    }

    public function test_actual_owned_inventory_recognition_does_not_create_vendor_entitlement(): void
    {
        $order = $this->order(912, '100.00', '7.50', '2.00', '10.00');
        $order->seller_is = 'admin'; $order->vendor_settlement_status = null; $order->save();
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'paystack']);
        OrderManager::getWalletManageOnOrderStatusChange($order, 'admin');
        OrderManager::getWalletManageOnOrderStatusChange($order, 'admin');
        $this->assertSame(0, SellerWallet::count());
        $wallet = AdminWallet::first();
        $this->assertSame('0.00', bcadd((string)$wallet->getRawOriginal('pending_amount'), '0', 2));
        $this->assertSame('100.00', bcadd((string)$wallet->getRawOriginal('inhouse_earning'), '0', 2));
        $this->assertSame('0.00', bcadd((string)$wallet->getRawOriginal('commission_earned'), '0', 2));
        DB::transaction(fn () => OrderManager::reverseRecognizedRefund($order, '100.00', '107.50'));
        $this->assertSame('0.00', bcadd((string)AdminWallet::first()->getRawOriginal('inhouse_earning'), '0', 2));
        $this->assertSame('0.00', bcadd((string)AdminWallet::first()->getRawOriginal('total_tax_collected'), '0', 2));
        $this->assertSame('2.00', bcadd((string)AdminWallet::first()->getRawOriginal('delivery_charge_earned'), '0', 2));
    }

    public function test_actual_recognized_refund_records_postpayout_vendor_debt_and_reverses_tax(): void
    {
        $order = $this->order(913, '100.00', '7.50');
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'paystack']);
        app(VendorSettlementService::class)->executeManualSettlement(913, 1, 'wallet_release', 'POST-PAYOUT');
        // [AI] Simulate only the completed bank payout fixture; invoke the actual refund reversal for all tested money changes.
        SellerWallet::where('seller_id', 99)->update(['total_earning' => '0.00', 'withdrawn' => '90.00']);
        $id = $this->row('refund_requests', ['order_id' => 913, 'order_details_id' => $order->details->first()->id,
            'customer_id' => 1, 'product_id' => 1, 'refund_reason' => 'Return after completed payout', 'amount' => '107.50',
            'status' => 'approved', 'execution_status' => 'awaiting_manual_payment', 'payment_info' => json_encode([
                'refundable_money_amount' => '107.50', 'cashback_amount' => '0.00', 'refundable_merchandise_value' => '100.00'])]);
        $result = app(PaystackRefundService::class)->finalizeManualPaymentConfirmation(RefundRequest::find($id), $order,
            ['payment_method' => 'bank_transfer', 'amount' => '107.50', 'payment_reference' => 'POST-PAYOUT-REFUND', 'payment_date' => now()->toDateString()]);
        $this->assertTrue($result['status'], $result['message']);
        $wallet = SellerWallet::where('seller_id', 99)->first();
        $this->assertSame('90.00', bcadd((string)$wallet->getRawOriginal('collected_cash'), '0', 2));
        $this->assertSame('0.00', bcadd((string)AdminWallet::first()->getRawOriginal('commission_earned'), '0', 2));
        $this->assertSame('0.00', bcadd((string)AdminWallet::first()->getRawOriginal('total_tax_collected'), '0', 2));
    }

    public function test_fractional_refunds_allocate_final_commission_residual_without_inventing_debt(): void
    {
        $order = $this->order(920, '0.15');
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'paystack']);
        app(VendorSettlementService::class)->executeManualSettlement(920, 1, 'wallet_release', 'PENNY-920');
        DB::transaction(fn () => OrderManager::reverseRecognizedRefund($order, '0.08', '0.08'));
        DB::transaction(fn () => OrderManager::reverseRecognizedRefund($order, '0.07', '0.07'));
        $wallet = SellerWallet::where('seller_id', 99)->first();
        foreach (['total_earning', 'commission_given', 'collected_cash'] as $field) {
            $this->assertSame('0.00', bcadd((string)$wallet->getRawOriginal($field), '0', 2));
        }
        $this->assertSame('0.00', bcadd((string)AdminWallet::first()->getRawOriginal('commission_earned'), '0', 2));
        $this->expectException(\RuntimeException::class);
        DB::transaction(fn () => OrderManager::reverseRecognizedRefund($order, '0.01', '0.01'));
    }

    public function test_actual_admin_cashback_approval_completes_only_after_trusted_approval(): void
    {
        auth('admin')->setUser(new \App\Models\Admin(['id' => 1, 'admin_role_id' => 1, 'name' => 'Finance Regression']));
        $order = $this->order(921, '100.00', '0.00', '0.00', '100.00');
        $order->payment_method = 'cashback'; $order->save();
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'cashback']);
        $id = $this->row('refund_requests', ['order_id' => 921, 'order_details_id' => $order->details->first()->id, 'customer_id' => 1,
            'product_id' => 1, 'refund_reason' => 'Reward-funded item returned', 'amount' => '100.00', 'status' => 'pending',
            'execution_status' => 'pending', 'payment_info' => json_encode(['refundable_money_amount' => '0.00',
                'cashback_amount' => '100.00', 'refundable_merchandise_value' => '100.00'])]);
        $request = \App\Http\Requests\Admin\RefundStatusRequest::create('/', 'POST', ['id' => $id, 'refund_status' => 'approved', 'approved_note' => 'Inspected return']);
        $response = app(\App\Http\Controllers\Admin\Order\RefundController::class)->updateRefundStatus($request,
            app(\App\Services\RefundStatusService::class), app(\App\Services\RefundTransactionService::class));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('refunded', RefundRequest::find($id)->status);
        $this->assertSame('succeeded', RefundRequest::find($id)->execution_status);
        $this->assertSame(1, \App\Models\RefundTransaction::where('refund_id', $id)->where('payment_method', 'cashback')->count());
        $this->assertSame('100.0000', bcadd((string)DB::table('users')->where('id', 1)->value('loyalty_point'), '0', 4));
    }

    public function test_unauthorized_admin_cannot_confirm_financial_refund_or_vendor_payout(): void
    {
        $admin = new \App\Models\Admin(['id' => 7, 'admin_role_id' => 7, 'name' => 'Read Only']);
        $admin->setRelation('role', new \App\Models\AdminRole(['id' => 7, 'status' => 1, 'module_access' => json_encode(['order_management', 'payments.view'])]));
        auth('admin')->setUser($admin);
        try {
            app(\App\Http\Controllers\Admin\Vendor\VendorController::class)->withdrawStatus(new Request(['approved' => 2]), 777);
            $this->fail('Read-only administrator must not mutate payout.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
        $request = \App\Http\Requests\Admin\RefundStatusRequest::create('/', 'POST', ['id' => 777, 'refund_status' => 'refunded']);
        try {
            app(\App\Http\Controllers\Admin\Order\RefundController::class)->updateRefundStatus($request,
                app(\App\Services\RefundStatusService::class), app(\App\Services\RefundTransactionService::class));
            $this->fail('Read-only administrator must not confirm refund.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(403, $e->getStatusCode()); }
    }

    public function test_reconciliation_diagnostic_and_retired_refund_helper_never_change_balances(): void
    {
        $order = $this->order(922);
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'paystack']);
        $before = AdminWallet::first()->getRawOriginal('pending_amount');
        $result = app(VendorSettlementService::class)->executeUndeliveredOrderRefund($order, 'Cannot fabricate payment proof');
        $this->assertFalse($result['status']);
        $this->assertSame(0, Transaction::where('order_id', 922)->count());
        DB::table('order_transactions')->where('order_id', 922)->update(['escrow_remaining' => null]);
        $command = app(\App\Console\Commands\ReconcileV1MoneyCommand::class);
        $exit = \Illuminate\Support\Facades\Artisan::call($command->getName(), ['--json' => true]);
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('unknown_historical_hold', \Illuminate\Support\Facades\Artisan::output());
        $this->assertSame($before, AdminWallet::first()->getRawOriginal('pending_amount'));
    }

    public function test_actual_manual_refund_after_owned_inventory_recognition_reverses_only_owned_revenue_and_tax(): void
    {
        $order = $this->order(923, '100.00', '7.50', '2.00');
        $order->seller_is = 'admin'; $order->vendor_settlement_status = null; $order->save();
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'paystack']);
        OrderManager::getWalletManageOnOrderStatusChange($order, 'admin');
        $id = $this->row('refund_requests', ['order_id' => 923, 'order_details_id' => $order->details->first()->id,
            'customer_id' => 1, 'product_id' => 1, 'refund_reason' => 'Owned item returned', 'amount' => '107.50',
            'status' => 'approved', 'execution_status' => 'awaiting_manual_payment', 'payment_info' => json_encode([
                'refundable_money_amount' => '107.50', 'cashback_amount' => '0.00', 'refundable_merchandise_value' => '100.00'])]);
        $result = app(PaystackRefundService::class)->finalizeManualPaymentConfirmation(RefundRequest::find($id), $order,
            ['payment_method' => 'bank_transfer', 'amount' => '107.50', 'payment_reference' => 'OWNED-REFUND', 'payment_date' => now()->toDateString()]);
        $this->assertTrue($result['status'], $result['message']);
        $this->assertSame(0, SellerWallet::count());
        $this->assertNull(Order::find(923)->vendor_settlement_status);
        foreach (['inhouse_earning', 'commission_earned', 'total_tax_collected', 'pending_amount'] as $field) {
            $this->assertSame('0.00', bcadd((string)AdminWallet::first()->getRawOriginal($field), '0', 2));
        }
        $this->assertSame('2.00', bcadd((string)AdminWallet::first()->getRawOriginal('delivery_charge_earned'), '0', 2));
    }

    public function test_actual_manual_partial_owned_inventory_refund_before_receipt_recognizes_remaining_tax(): void
    {
        $order = $this->order(924, '100.00', '7.50', '2.00');
        $order->seller_is = 'admin'; $order->vendor_settlement_status = null; $order->save();
        $first = $order->details->first(); $first->update(['price' => '50.00', 'tax' => '3.75']);
        $this->row('order_details', ['order_id' => 924, 'product_id' => 2, 'seller_id' => 99, 'qty' => 1,
            'price' => '50.00', 'discount' => '0.00', 'tax' => '3.75', 'refund_request' => 0]);
        $order->load('details');
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'paystack']);
        $id = $this->row('refund_requests', ['order_id' => 924, 'order_details_id' => $first->id,
            'customer_id' => 1, 'product_id' => 1, 'refund_reason' => 'One owned item returned', 'amount' => '53.75',
            'status' => 'approved', 'execution_status' => 'awaiting_manual_payment', 'payment_info' => json_encode([
                'refundable_money_amount' => '53.75', 'cashback_amount' => '0.00', 'refundable_merchandise_value' => '50.00'])]);
        $result = app(PaystackRefundService::class)->finalizeManualPaymentConfirmation(RefundRequest::find($id), $order,
            ['payment_method' => 'bank_transfer', 'amount' => '53.75', 'payment_reference' => 'OWNED-PARTIAL', 'payment_date' => now()->toDateString()]);
        $this->assertTrue($result['status'], $result['message']);
        OrderManager::getWalletManageOnOrderStatusChange($order, 'admin');
        $this->assertSame('50.00', bcadd((string)AdminWallet::first()->getRawOriginal('inhouse_earning'), '0', 2));
        $this->assertSame('3.75', bcadd((string)AdminWallet::first()->getRawOriginal('total_tax_collected'), '0', 2));
        $this->assertSame('0.00', bcadd((string)AdminWallet::first()->getRawOriginal('pending_amount'), '0', 2));
    }

    public function test_coupon_and_quantity_product_discount_hold_matches_capture_and_release(): void
    {
        $order = $this->order(925, '80.00', '7.50', '2.00');
        $order->discount_type = 'coupon_discount'; $order->discount_amount = '10.00'; $order->order_amount = '79.50'; $order->save();
        $order->details->first()->update(['qty' => 2, 'price' => '50.00', 'discount' => '20.00']);
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'paystack']);
        OrderManager::getAddOrderTransactionsOnGenerateOrder($order, ['payment_method' => 'paystack']);
        $this->assertSame(1, \App\Models\OrderTransaction::where('order_id', 925)->count());
        $this->assertSame('79.50', bcadd((string)AdminWallet::first()->getRawOriginal('pending_amount'), '0', 2));
        app(VendorSettlementService::class)->executeManualSettlement(925, 1, 'wallet_release', 'COUPON-925');
        $this->assertSame('63.00', bcadd((string)SellerWallet::first()->getRawOriginal('total_earning'), '0', 2));
        $this->assertSame('7.00', bcadd((string)AdminWallet::first()->getRawOriginal('commission_earned'), '0', 2));
    }
}
