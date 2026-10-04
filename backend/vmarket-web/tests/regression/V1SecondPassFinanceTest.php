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

class V1SecondPassFinanceTest extends \Tests\Feature\DumpSchemaTestCase
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
        (require base_path('database/migrations/2026_09_19_000008_add_execution_fields_to_refund_requests_table.php'))->up();
        (require base_path('database/migrations/2026_08_11_111907_add_proof_of_payment_to_withdraw_requests_table.php'))->up();
        (require base_path('database/migrations/2026_10_04_000001_add_frozen_refund_allocations.php'))->up();
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


    public function test_api_payout_invalid_replayed_and_unproved_decisions_preserve_reservation(): void
    {
        $this->row('deliveryman_wallets', ['delivery_man_id'=>88,'current_balance'=>'100','pending_withdraw'=>'100','total_withdraw'=>'0']);
        $id=$this->row('withdraw_requests',['seller_id'=>99,'delivery_man_id'=>88,'amount'=>'100','approved'=>0]);
        $seller=(new \App\Models\Seller)->forceFill(['id'=>99]);
        $c=app(\App\Http\Controllers\RestAPI\v3\seller\DeliverymanWithdrawController::class);
        foreach ([0,0,8,1] as $v) {
            $this->assertSame(422,$c->status_update(new Request(['id'=>$id,'seller'=>$seller,'approved'=>$v]))->getStatusCode());
            $this->assertSame('100.00',bcadd((string)DB::table('deliveryman_wallets')->where('delivery_man_id',88)->value('pending_withdraw'),'0',2));
        }
        $this->assertSame(200,$c->status_update(new Request(['id'=>$id,'seller'=>$seller,'approved'=>2]))->getStatusCode());
        $this->assertSame(422,$c->status_update(new Request(['id'=>$id,'seller'=>$seller,'approved'=>2]))->getStatusCode());
        $this->assertSame('0.00',bcadd((string)DB::table('deliveryman_wallets')->where('delivery_man_id',88)->value('pending_withdraw'),'0',2));
        $this->assertSame(1,\App\Models\AdminAuditLog::where('action','vendor.rider_withdrawal_decision')->count());
    }
    public function test_rider_request_exact_ngn_and_immutable_profile_beneficiary(): void
    {
        $this->row('delivery_men',['id'=>88,'seller_id'=>99,'bank_name'=>'Bank','account_no'=>'1234567890','holder_name'=>'Rider']);
        $this->row('deliveryman_wallets',['delivery_man_id'=>88,'current_balance'=>'100.10','pending_withdraw'=>'0','total_withdraw'=>'0']);
        $r=\App\Models\DeliveryMan::find(88);$c=app(\App\Http\Controllers\RestAPI\v2\delivery_man\WithdrawController::class);
        $this->assertSame(403,$c->sendWithdrawRequest(new Request(['delivery_man'=>$r,'amount'=>'1.001']))->getStatusCode());
        $this->assertSame(200,$c->sendWithdrawRequest(new Request(['delivery_man'=>$r,'amount'=>'99.99','account_no'=>'attacker']))->getStatusCode());
        $w=\App\Models\WithdrawRequest::where('delivery_man_id',88)->first();
        $this->assertSame('1234567890',$w->withdrawal_method_fields['account_no']);
        $this->assertSame('NGN',$w->withdrawal_method_fields['currency']);
        $this->assertSame('99.99',bcadd((string)$w->getRawOriginal('amount'),'0',2));
    }
    public function test_raw_order_columns_owner_and_funding_completion_boundaries(): void
    {
        $o=$this->order(960);$repo=app(\App\Repositories\OrderRepository::class);
        auth('admin')->setUser((new \App\Models\Admin)->forceFill(['id'=>1,'admin_role_id'=>1]));
        foreach(['order_amount','payment_status','refund_window_expires_at','vendor_settlement_status'] as $f) $this->assertFalse($repo->updateAmountDate(new Request(['order_id'=>960,'field_name'=>$f,'field_val'=>'0']),1,'admin'));
        $c=app(\App\Http\Controllers\Admin\Order\OrderController::class);
        foreach(['paid','unpaid'] as $v)$this->assertSame(403,$c->updatePaymentStatus(new Request(['id'=>960,'payment_status'=>$v,'reason'=>'Superadmin']))->getStatusCode());
        $this->assertSame(409,$c->updateStatus(new Request(['id'=>960,'order_status'=>'processing']),app(\App\Services\DeliveryManTransactionService::class),app(\App\Services\DeliveryManWalletService::class),app(\App\Services\OrderStatusHistoryService::class))->getStatusCode());
        $o->received_at=null;$o->order_status='processing';$o->save();
        $this->assertFalse($repo->updateAmountDate(new Request(['order_id'=>960,'field_name'=>'deliveryman_charge','field_val'=>'100000']),1,'admin'));
        $this->assertFalse($repo->updateAmountDate(new Request(['order_id'=>960,'field_name'=>'deliveryman_charge','field_val'=>'-1']),1,'admin'));
        $this->assertFalse($repo->updateAmountDate(new Request(['order_id'=>960,'field_name'=>'expected_delivery_date','field_val'=>'2026-10-05']),99,'unknown'));
        $this->assertFalse($repo->updateAmountDate(new Request(['order_id'=>960,'field_name'=>'expected_delivery_date','field_val'=>'2026-10-05']),77,'seller'));
        $this->assertTrue($repo->updateAmountDate(new Request(['order_id'=>960,'field_name'=>'expected_delivery_date','field_val'=>'2026-10-05']),99,'seller'));
    }
    public function test_frozen_refund_allocations_conserve_cash_reward_tax_and_residual(): void
    {
        foreach([['0.03','0.01',['0.01','0.01','0.01']],['100.00','10.00',['33.33','33.33','33.34']]] as $i=>[$m,$r,$prices]) {
            $o=$this->order(970+$i,$m,'0.03','0',$r);$o->details->first()->update(['price'=>$prices[0],'tax'=>'0.01']);
            foreach(array_slice($prices,1) as $p)$this->row('order_details',['order_id'=>$o->id,'qty'=>1,'price'=>$p,'discount'=>'0','tax'=>'0.01','product_id'=>1,'refund_request'=>0]);
            $cash=$reward=$merch='0.00';
            foreach($o->fresh()->details as $d){$a=OrderManager::getRefundDetailsForSingleOrderDetails($d->id);
                $this->assertSame(bcadd($a['refundable_merchandise_value'],$a['tax'],2),bcadd($a['refundable_money_amount'],$a['refundable_cashback_amount'],2));
                $this->assertSame($a,OrderManager::getRefundDetailsForSingleOrderDetails($d->id));$this->assertNotNull($d->fresh()->getRawOriginal('refund_allocation'));
                $cash=bcadd($cash,$a['refundable_money_amount'],2);$reward=bcadd($reward,$a['refundable_cashback_amount'],2);$merch=bcadd($merch,$a['refundable_merchandise_value'],2);
            }$this->assertSame($r,$reward);$this->assertSame($m,$merch);$this->assertSame(bcadd(bcsub($m,$r,2),'0.03',2),$cash);
        }
    }

    public function test_real_payout_proof_uses_frozen_beneficiary_and_replay_cannot_debit_twice(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public'); config(['filesystems.disks.default'=>'public']);
        $this->row('deliveryman_wallets',['delivery_man_id'=>88,'current_balance'=>'100','pending_withdraw'=>'100','total_withdraw'=>'0']);
        $id=$this->row('withdraw_requests',['seller_id'=>99,'delivery_man_id'=>88,'amount'=>'100','approved'=>0,'withdrawal_method_fields'=>json_encode(['currency'=>'NGN','bank_name'=>'Old bank','account_no'=>'1111111111','holder_name'=>'Original beneficiary'])]);
        $this->row('delivery_men',['id'=>88,'seller_id'=>99,'bank_name'=>'New bank','account_no'=>'2222222222','holder_name'=>'Changed']);
        $seller=(new \App\Models\Seller)->forceFill(['id'=>99]);
        $request=new Request(['id'=>$id,'seller'=>$seller,'approved'=>1]);
        $request->files->set('proof_of_payment',\Illuminate\Http\UploadedFile::fake()->image('proof.png'));
        $c=app(\App\Http\Controllers\RestAPI\v3\seller\DeliverymanWithdrawController::class);
        $response=$c->status_update($request);$this->assertSame(200,$response->getStatusCode(),$response->getContent());
        $w=\App\Models\WithdrawRequest::find($id);$this->assertSame('1111111111',$w->withdrawal_method_fields['account_no']);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('withdraw_requests/'.$w->proof_of_payment);
        $this->assertSame(422,$c->status_update($request)->getStatusCode());
        $this->assertSame('0.00',bcadd((string)DB::table('deliveryman_wallets')->where('delivery_man_id',88)->value('current_balance'),'0',2));
        $this->assertSame(1,count(\Illuminate\Support\Facades\Storage::disk('public')->files('withdraw_requests')));
    }
    public function test_full_manual_refund_cycles_conserve_funding_before_and_after_release(): void
    {
        foreach([false,true] as $released) foreach([['0.03','0.01',['0.01','0.01','0.01']],['100.00','10.00',['33.33','33.33','33.34']]] as $i=>[$m,$r,$prices]) {
            $id=980+($released?10:0)+$i;$o=$this->order($id,$m,'0.03','0',$r);
            $o->details->first()->update(['price'=>$prices[0],'tax'=>'0.01']);
            foreach(array_slice($prices,1) as $p)$this->row('order_details',['order_id'=>$id,'qty'=>1,'price'=>$p,'discount'=>'0','tax'=>'0.01','product_id'=>1,'refund_request'=>0]);
            $o->load('details'); OrderManager::getAddOrderTransactionsOnGenerateOrder($o,['payment_method'=>'paystack']);
            foreach($o->details as $d) OrderManager::getRefundDetailsForSingleOrderDetails($d->id);
            if($released)app(VendorSettlementService::class)->executeManualSettlement($id,1,'wallet_release','REL-'.$id);
            foreach($o->details as $d) {
                $a=OrderManager::getRefundDetailsForSingleOrderDetails($d->id);
                $a += ['cashback_amount'=>$a['refundable_cashback_amount'],'merchandise_value'=>$a['refundable_merchandise_value'],'merchandise_money'=>$a['refundable_merchandise_money'],'money_amount'=>$a['refundable_money_amount'],'tax_amount'=>$a['tax']];
                $rid=$this->row('refund_requests',['order_id'=>$id,'order_details_id'=>$d->id,'customer_id'=>1,'product_id'=>1,'amount'=>$a['refundable_money_amount'],'status'=>'approved','execution_status'=>'awaiting_manual_payment','payment_info'=>json_encode($a)]);
                $out=app(PaystackRefundService::class)->finalizeManualPaymentConfirmation(RefundRequest::find($rid),$o->fresh(),['payment_method'=>'bank_transfer','amount'=>$a['refundable_money_amount'],'payment_reference'=>'BANK-'.$rid,'payment_date'=>now()->toDateString(),'confirmed_by'=>1]);
                $this->assertTrue($out['status'],$out['message']);
            }
            $this->assertSame($r,bcadd((string)\App\Models\RefundTransaction::where('order_id',$id)->where('payment_method','cashback')->sum('amount'),'0',2));
            $this->assertSame(bcadd(bcsub($m,$r,2),'0.03',2),bcadd((string)\App\Models\RefundTransaction::where('order_id',$id)->where('payment_method','!=','cashback')->sum('amount'),'0',2));
            $this->assertSame('0.00',bcadd((string)$o->fresh()->orderTransaction->getRawOriginal('escrow_remaining'),'0',2));
        }
        $this->assertSame('0.00',bcadd((string)AdminWallet::first()->getRawOriginal('total_tax_collected'),'0',2));
    }
    public function test_real_refund_allocation_migration_up_and_down_preserves_existing_rows(): void
    {
        $o=$this->order(999);$migration=require base_path('database/migrations/2026_10_04_000001_add_frozen_refund_allocations.php');
        $migration->down();$this->assertFalse(Schema::hasColumn('order_details','refund_allocation'));
        $migration->up();$this->assertTrue(Schema::hasColumn('order_details','refund_allocation'));
        $this->assertSame(1,OrderDetail::where('order_id',$o->id)->count());
        $this->assertNull($o->details->first()->fresh()->getRawOriginal('refund_allocation'));
    }

    public function test_provider_partial_refund_uses_merchandise_cumulative_excluding_tax_and_reward(): void
    {
        $o=$this->order(995,'100','60.02','0','10');$o->transaction_ref='PROVIDER-995';$o->order_group_id='' ;$o->save();
        $o->details->first()->update(['price'=>'33.33','tax'=>'60']);
        foreach([['33.33','0.01'],['33.34','0.01']] as [$p,$t])$this->row('order_details',['order_id'=>995,'qty'=>1,'price'=>$p,'discount'=>'0','tax'=>$t,'product_id'=>1,'refund_request'=>0]);
        $o->load('details');OrderManager::getAddOrderTransactionsOnGenerateOrder($o,['payment_method'=>'paystack']);
        foreach($o->details as $d)OrderManager::getRefundDetailsForSingleOrderDetails($d->id);
        foreach($o->details->sortByDesc('tax')->values() as $index=>$d) {
            $a=OrderManager::getRefundDetailsForSingleOrderDetails($d->id);
            $a+=['cashback_amount'=>$a['refundable_cashback_amount'],'merchandise_value'=>$a['refundable_merchandise_value'],'merchandise_money'=>$a['refundable_merchandise_money'],'tax_amount'=>$a['tax']];
            $rid=$this->row('refund_requests',['order_id'=>995,'order_details_id'=>$d->id,'customer_id'=>1,'product_id'=>1,'amount'=>$a['refundable_money_amount'],'status'=>'approved','payment_info'=>json_encode($a)]);
            app(PaystackRefundService::class)->finalizeRefundAccounting(RefundRequest::find($rid),['status'=>'processed','currency'=>'NGN','amount'=>(int)bcmul($a['refundable_money_amount'],'100',0),'transaction_reference'=>'PROVIDER-995','merchant_note'=>'vmarket_refund_'.$rid,'id'=>'REF-'.$rid]);
            $this->assertSame('succeeded',RefundRequest::find($rid)->execution_status);
            if($index<2)$this->assertNotSame('refunded',$o->fresh()->vendor_settlement_status);
        }
        $this->assertSame('refunded',$o->fresh()->vendor_settlement_status);
        $this->assertSame('0.00',bcadd((string)AdminWallet::first()->getRawOriginal('pending_amount'),'0',2));
        $this->assertSame('10.00',bcadd((string)\App\Models\RefundTransaction::where('order_id',995)->where('payment_method','cashback')->sum('amount'),'0',2));
    }

    public function test_actual_customer_preview_returns_exact_allocations_with_legacy_loyalty_enabled_or_disabled(): void
    {
        $o=$this->order(996,'0.03','0.01','0','0.01');$d=$o->details->first();$d->delivery_status='delivered';$d->save();
        $user=\App\Models\User::find(1);$request=new Request(['order_details_id'=>$d->id]);$request->setUserResolver(fn()=>$user);
        foreach(['1','0'] as $v){DB::table('business_settings')->updateOrInsert(['type'=>'loyalty_point_status'],['value'=>$v]);
            $out=app(\App\Http\Controllers\RestAPI\v1\OrderController::class)->refund_request($request);
            $this->assertSame(200,$out->getStatusCode());$data=$out->getData(true)['refund'];
            $this->assertSame('0.03',$data['refundable_money_amount']);$this->assertSame('0.01',$data['refundable_cashback_amount']);
            $this->assertSame('0.04',$data['refund_amount']);$this->assertTrue($data['allocations_frozen']);
        }
    }

    public function test_actual_delivery_quote_refuses_expired_backing_and_caps_other_reservations(): void
    {
        foreach(['marketplace_listing_status','marketplace_availability'] as $c) if(!Schema::hasColumn('products',$c))Schema::table('products',fn($t)=>$t->string($c)->nullable());
        foreach(['loyalty_point_status'=>1,'loyalty_point_exchange_rate'=>1,'loyalty_point_max_order_redemption_percentage'=>100,'loyalty_point_minimum_point'=>0] as $k=>$v)DB::table('business_settings')->updateOrInsert(['type'=>$k],['value'=>(string)$v]);
        \Illuminate\Support\Facades\Cache::flush();
        DB::table('users')->where('id',1)->update(['loyalty_point'=>'50.0000']);
        $this->row('products',['id'=>9999,'added_by'=>'admin','user_id'=>1,'status'=>1,'request_status'=>1,'marketplace_listing_status'=>'listed','marketplace_availability'=>'in_stock','product_type'=>'physical','current_stock'=>20,'unit_price'=>'100','name'=>'Delivery']);
        $this->row('carts',['customer_id'=>1,'is_guest'=>0,'is_checked'=>1,'product_id'=>9999,'seller_id'=>1,'seller_is'=>'admin','cart_group_id'=>'delivery-cap','quantity'=>1,'price'=>'100','discount'=>'0','tax'=>'0','shipping_cost'=>'0']);
        $lotId=$this->row('customer_cashback_ledgers',['customer_id'=>1,'order_id'=>0,'cashback_amount'=>'50.00','status'=>'available','available_at'=>now()->subDay(),'expires_at'=>now()->subMinute()]);
        $service=app(\App\Services\DeliveryCheckoutIntentService::class);
        $address=['customer_id'=>1,'address'=>'Test address','city'=>'Test city','phone'=>'08012345678','contact_person_name'=>'Customer','country'=>'Nigeria'];
        $intent=$service->createCheckoutIntent(1,'expired-key',$address,null,true);
        $this->assertSame('0.00',$intent->checkout_snapshot['cashback']['cashback_amount']);
        $this->assertSame(0,\App\Models\CashbackRedemption::where('checkout_intent_id',$intent->id)->count());
        $intent->update(['status'=>'expired','active_cart_token'=>null]);
        DB::table('customer_cashback_ledgers')->where('id',$lotId)->update(['expires_at'=>now()->addHour()]);
        $this->row('cashback_redemptions',['customer_id'=>1,'points'=>'10.0000','cashback_amount'=>'10.00','status'=>'reserved']);
        $next=$service->createCheckoutIntent(1,'backed-key',$address,null,true);
        $this->assertSame('40.00',$next->checkout_snapshot['cashback']['cashback_amount']);
        DB::table('customer_cashback_ledgers')->where('id',$lotId)->update(['expires_at'=>now()->subMinute()]);
        $this->assertSame($next->id,$service->createCheckoutIntent(1,'backed-key',$address,null,true)->id);
        $this->assertSame('40.00',$next->fresh()->checkout_snapshot['cashback']['cashback_amount']);
    }
}
