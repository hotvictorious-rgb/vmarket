<?php
namespace Tests\Feature;

require_once __DIR__.'/GatewayMoneyTestCase.php';
foreach (['PickupPaymentInitializationService','PickupOrderSettlementService','PickupReservationService'] as $service) {
    require_once __DIR__.'/../../app/Services/'.$service.'.php';
}
require_once __DIR__.'/../../app/Utils/OrderManager.php';
require_once __DIR__.'/../../app/Http/Controllers/Customer/PickupReservationController.php';

use App\Models\PickupReservation;
use App\Models\PaymentRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\PickupPaymentInitializationService;
use App\Services\PickupOrderSettlementService;
use App\Services\PaystackInitializationClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/** [AI] Production pickup services on isolated SQLite with a gateway double; not a MySQL contention certificate. */
class PickupMoneyRepairTest extends GatewayMoneyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // [AI] Match final production ALTER migrations omitted by the install-dump loader.
        (require base_path('database/migrations/2026_10_03_000001_add_expires_at_to_customer_cashback_ledgers_table.php'))->up();
        (require base_path('database/migrations/2026_10_04_000001_add_frozen_refund_allocations.php'))->up();
        Schema::table('payment_reconciliations', fn($t)=>$t->enum('initial_anomaly_type', ['amount_mismatch','currency_mismatch','late_capture_expired','stale_order_group','stale_reservation_state','charge_reversed','duplicate_capture','stale_superseded_attempt','invalid_snapshot','post_payment_stock_failure','other'])->change());
        DB::connection()->getPdo()->sqliteCreateFunction('NOW',fn()=>now()->format('Y-m-d H:i:s'));
        // [AI] Keep model creating events: HasUuid assigns payment request identifiers.
        (require base_path('database/migrations/2026_09_22_000002_add_pickup_cashback_to_cashback_redemptions.php'))->up();
        foreach (['vendor_settlement_status','pickup_verification_code','guest_access_token','total_tax_amount','tax_model','tax_type','shipping_responsibility'] as $column) {
            if (!Schema::hasColumn('orders',$column)) { Schema::table('orders',fn($t)=>$t->string($column)->nullable()); }
        }
        if (!Schema::hasColumn('products','shop_id')) { Schema::table('products',fn($t)=>$t->integer('shop_id')->nullable()); }
        foreach (['escrow_remaining','recognized_merchandise_remaining','recognized_commission_remaining','recognized_tax_remaining'] as $column) {
            if (!Schema::hasColumn('order_transactions',$column)) { Schema::table('order_transactions',fn($t)=>$t->decimal($column,24,4)->nullable()); }
        }
        foreach (['loyalty_point_status'=>1,'loyalty_point_exchange_rate'=>1,'loyalty_point_max_order_redemption_percentage'=>100,'loyalty_point_minimum_point'=>0,'loyalty_point_earn_on_each_order'=>1] as $key=>$value) {
            DB::table('business_settings')->updateOrInsert(['type'=>$key],['value'=>(string)$value]);
        }
        Cache::flush();
        $this->fixture('sellers',['id'=>99,'status'=>'approved']);
        $this->fixture('shops',['id'=>999,'seller_id'=>99,'author_type'=>'seller']);
        $this->fixture('products',['id'=>9999,'added_by'=>'seller','user_id'=>99,'shop_id'=>999,'product_type'=>'physical','current_stock'=>20,'unit_price'=>100,'name'=>'Pickup item']);
        DB::table('admin_wallets')->delete();
        $this->fixture('admin_wallets',['admin_id'=>1,'pending_amount'=>0]);
    }

    private function fixture(string $table,array $values): void
    {
        $defaults=[];
        foreach (DB::select('PRAGMA table_info("'.$table.'")') as $column) {
            if ($column->name!=='id' && $column->notnull && $column->dflt_value===null) {
                $defaults[$column->name]=preg_match('/INT|DECIMAL|DOUBLE|FLOAT|REAL|NUMERIC/i',$column->type)?0:'';
            }
        }
        DB::table($table)->insert(array_merge($defaults,$values));
    }

    private function reservation(string $points,string $tax='0.00'): array
    {
        $user=User::create(['name'=>'Pickup','email'=>Str::uuid().'@example.test','phone'=>'080'.random_int(10000000,99999999),'password'=>'test','loyalty_point'=>$points]);
        if (bccomp($points,'0',4)>0) {
            $this->fixture('customer_cashback_ledgers',['customer_id'=>$user->id,'order_id'=>0,'cashback_amount'=>$points,'status'=>'available','available_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        }
        $reservation=PickupReservation::create(['customer_id'=>$user->id,'seller_id'=>99,'shop_id'=>999,'reservation_code'=>'R-'.Str::random(8),
            'idempotency_key'=>Str::uuid(),'reservation_fingerprint'=>str_repeat('f',64),'status'=>'inspected_accepted','total_amount'=>bcadd('100',$tax,2),
            'currency'=>'NGN','reservation_items'=>['seller_is'=>'seller','subtotal'=>'100.00','tax'=>$tax,'items'=>[['product_id'=>9999,'quantity'=>1,'unit_price'=>'100.00','discount'=>'0.00','tax'=>$tax]]],'expires_at'=>now()->addHour()]);
        return [$user,$reservation];
    }

    private function client(): PaystackInitializationClient
    {
        return new class extends PaystackInitializationClient {
            public bool $timeout=false;
            public bool $rejected=false;
            public $during=null;
            public function initializeTransaction(string $email,int $amountKobo,string $reference,string $callbackUrl,array $metadata=[]):array {
                if ($this->during) { ($this->during)($reference); }
                if ($this->rejected) { return ['status'=>'GATEWAY_REJECTED','message'=>'fixture rejected']; }
                return $this->timeout?['status'=>'TRANSPORT_ERROR']:['status'=>'SUCCESS','authorization_url'=>'https://example.test/pay','access_code'=>'fake'];
            }
            public function verifyExistingTransaction(string $reference):array { return ['class'=>'REFERENCE_NOT_FOUND']; }
        };
    }

    public function testPickupNoMixedAndFullRewardFundingConservesEscrowAndReplay(): void
    {
        foreach (['0.0000','20.0000','100.0000'] as $points) {
            [$user,$reservation]=$this->reservation($points);
            $service=new PickupPaymentInitializationService($this->client());
            $quote=$service->quote($user->id,$reservation->reservation_code,true);
            $result=$service->initializePayment($user,$reservation,true,30,null,$quote['quote_token']);
            if ($result['status']!=='settled') {
                $payment=$result['payment_request'];
                $data=['status'=>'success','currency'=>'NGN','amount'=>(int)bcmul($payment->payment_amount,'100',0)];
                $settlement=new PickupOrderSettlementService();
                $result=$settlement->settleVerifiedPayment($payment->gateway_reference,$data);
                $this->assertSame('CLAIMED',$result['status'],json_encode($result));
                $this->assertSame('ALREADY_SETTLED',$settlement->settleVerifiedPayment($payment->gateway_reference,$data)['status']);
            }
            $order=Order::findOrFail($result['order_id']);
            $this->assertEquals(bcsub('100',$points,2),$order->getRawOriginal('order_amount'));
            $this->assertEquals($points,$order->getRawOriginal('discount_amount'));
            $this->assertEquals('0.0000',$user->fresh()->getRawOriginal('loyalty_point'));
            $this->assertSame(1,Order::where('customer_id',$user->id)->count());
        }
        $this->assertEquals('300',DB::table('admin_wallets')->value('pending_amount'));
        $this->assertSame(0,DB::table('payment_reconciliations')->count());
    }

    public function testPickupTaxCashOnlyAndReplacementRetainsRedemption(): void
    {
        [$user,$reservation]=$this->reservation('200.0000','7.50');
        $client=$this->client();$client->timeout=true;
        $service=new PickupPaymentInitializationService($client);
        $quote=$service->quote($user->id,$reservation->reservation_code,true);
        $this->assertSame('100.00',$quote['quote']['cashback_amount']);
        $this->assertSame('7.50',$quote['quote']['total_amount']);
        $first=$service->initializePayment($user,$reservation,true,30,null,$quote['quote_token']);
        $old=$first['payment_request'];$additional=json_decode($old->additional_data,true);
        $additional['init_claim_expires_at']=now()->subMinute()->toIso8601String();$old->update(['additional_data'=>json_encode($additional)]);
        $client->timeout=false;
        $next=$service->initializePayment($user,$reservation,true,30,null,$quote['quote_token']);
        $payment=$next['payment_request'];
        $this->assertNotSame($old->id,$payment->id);
        $result=(new PickupOrderSettlementService())->settleVerifiedPayment($payment->gateway_reference,['status'=>'success','currency'=>'NGN','amount'=>750]);
        $this->assertSame('CLAIMED',$result['status'],json_encode($result));
        $this->assertEquals('100.0000',$user->fresh()->getRawOriginal('loyalty_point'));
        $this->assertSame('captured',DB::table('cashback_redemptions')->where('pickup_reservation_id',$reservation->id)->value('status'));
        $this->assertEquals('107.50',DB::table('admin_wallets')->value('pending_amount'));
    }

    public function testPickupLateInitializationAndCaptureAreQuarantinedWithoutOrder():void
    {
        [$user,$reservation]=$this->reservation('20.0000');$client=$this->client();
        $client->during=function()use($reservation){$reservation->update(['expires_at'=>now()->subMinute()]);};
        $service=new PickupPaymentInitializationService($client);
        $result=$service->initializePayment($user,$reservation,true);
        $this->assertSame('expired',$result['status']);
        $payment=$result['payment_request'];
        $capture=(new PickupOrderSettlementService())->settleVerifiedPayment($payment->gateway_reference,['status'=>'success','currency'=>'NGN','amount'=>8000]);
        $this->assertSame('reconciliation_required',$capture['status']);
        $this->assertSame(0,Order::where('customer_id',$user->id)->count());
        $this->assertEquals('0',DB::table('admin_wallets')->value('pending_amount'));
        $this->assertSame(1,DB::table('payment_reconciliations')->where('gateway_reference',$payment->gateway_reference)->count());
        $this->assertSame('RECONCILIATION_ALREADY_RECORDED',(new PickupOrderSettlementService())->settleVerifiedPayment($payment->gateway_reference,['status'=>'success','currency'=>'NGN','amount'=>8000])['status']);
    }

    public function testPickupControllerQuoteConfirmationOwnershipAndPaidStatus():void
    {
        [$user,$reservation]=$this->reservation('20.0000');
        config(['auth.guards.api.driver'=>'session']);$this->actingAs($user,'api');
        $controller=new \App\Http\Controllers\Customer\PickupReservationController(new \App\Services\PickupReservationService(),new PickupPaymentInitializationService($this->client()));
        $request=\Illuminate\Http\Request::create('/api/v1/pickup-reservations/'.$reservation->reservation_code.'/quote','POST',['use_cashback'=>true]);
        $quote=$controller->quote($request,$reservation->reservation_code)->getData(true);
        $this->assertTrue($quote['status']);$this->assertSame('80.00',$quote['quote']['total_amount']);
        $this->assertSame(0,PaymentRequest::count());
        try {
            $controller->pay(\Illuminate\Http\Request::create('/pay','POST',['use_cashback'=>true]),$reservation->reservation_code);
            $this->fail('Quote token must be required before gateway initialization.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('quote_token',$e->errors());
        }
        $user->update(['loyalty_point'=>'10.0000']);
        $changed=$controller->pay(\Illuminate\Http\Request::create('/pay','POST',['quote_token'=>$quote['quote_token'],'use_cashback'=>true]),$reservation->reservation_code);
        $this->assertSame(409,$changed->getStatusCode());$this->assertSame(0,PaymentRequest::count());
        $user->update(['loyalty_point'=>'20.0000']);
        $result=$controller->pay(\Illuminate\Http\Request::create('/pay','POST',['quote_token'=>$quote['quote_token'],'use_cashback'=>true]),$reservation->reservation_code)->getData(true);
        $this->assertTrue($result['status']);
        $payment=PaymentRequest::findOrFail($result['payment_request']['id']);
        $settled=(new PickupOrderSettlementService())->settleVerifiedPayment($payment->gateway_reference,['status'=>'success','currency'=>'NGN','amount'=>8000]);
        $this->assertSame('CLAIMED',$settled['status'],json_encode($settled));
        $state=$controller->status(new \Illuminate\Http\Request(),$reservation->reservation_code)->getData(true);
        $this->assertSame('paid',$state['payment_status']);
        $this->assertSame((string)Order::find($state['order_id'])->pickup_verification_code,$state['pickup_verification_code']);
        $reservation->refresh()->update(['order_id'=>null]);
        $broken=$controller->status(new \Illuminate\Http\Request(),$reservation->reservation_code)->getData(true);
        $this->assertSame('reconciliation_required',$broken['payment_status']);$this->assertNull($broken['pickup_verification_code']);
        $order=Order::find($state['order_id']);$order->update(['order_group_id'=>'unrelated-order']);
        $reservation->update(['order_id'=>$order->id]);
        $broken=$controller->status(new \Illuminate\Http\Request(),$reservation->reservation_code)->getData(true);
        $this->assertSame('reconciliation_required',$broken['payment_status']);$this->assertNull($broken['pickup_verification_code']);
        [$other]=$this->reservation('0');$this->actingAs($other,'api');
        $this->assertSame(404,$controller->status(new \Illuminate\Http\Request(),$reservation->reservation_code)->getStatusCode());
        $this->assertSame(409,$controller->quote($request,$reservation->reservation_code)->getStatusCode());
    }

    public function testPickupCallbackDuringInitializationCannotBeOverwrittenByLateResponse():void
    {
        foreach ([false,true] as $rejected) {
            [$user,$reservation]=$this->reservation('20.0000');$client=$this->client();$client->rejected=$rejected;
            $client->during=function($reference) {
                $result=(new PickupOrderSettlementService())->settleVerifiedPayment($reference,['status'=>'success','currency'=>'NGN','amount'=>8000]);
                $this->assertSame('CLAIMED',$result['status'],json_encode($result));
            };
            $result=(new PickupPaymentInitializationService($client))->initializePayment($user,$reservation,true);
            $this->assertSame('settled',$result['status']);
            $payment=$result['payment_request']->fresh();
            $this->assertSame('successful',$payment->attempt_status);
            $this->assertNotEmpty(json_decode($payment->additional_data,true)['settled_order_id']);
            $this->assertEquals('0.0000',$user->fresh()->getRawOriginal('loyalty_point'));
        }
    }

    public function testPickupAccountingFailureAndMissingCurrencyPersistTruthfulCases():void
    {
        [$user,$reservation]=$this->reservation('0.0000');
        $reservation->update(['reservation_items'=>['subtotal'=>'100.00','tax'=>'0.00','items'=>[]]]);
        $result=(new PickupPaymentInitializationService($this->client()))->initializePayment($user,$reservation);
        $payment=$result['payment_request'];
        $failure=(new PickupOrderSettlementService())->settleVerifiedPayment($payment->gateway_reference,['status'=>'success','currency'=>'NGN','amount'=>10000]);
        $this->assertSame('post_payment_settlement_failure',$failure['anomaly_type']);
        $this->assertSame('other',DB::table('payment_reconciliations')->value('initial_anomaly_type'));
        $this->assertSame(0,Order::count());$this->assertEquals('0',DB::table('admin_wallets')->value('pending_amount'));
        [$user,$reservation]=$this->reservation('0.0000');
        $payment=(new PickupPaymentInitializationService($this->client()))->initializePayment($user,$reservation)['payment_request'];
        $failure=(new PickupOrderSettlementService())->settleVerifiedPayment($payment->gateway_reference,['status'=>'success','amount'=>10000]);
        $this->assertSame('currency_mismatch',$failure['anomaly_type']);
        $this->assertSame(0,Order::count());
    }

    public function testDeliveryChangedCartCannotSupersedeAnUnresolvedPaymentAttempt():void
    {
        require_once __DIR__.'/../../app/Services/DeliveryCheckoutIntentService.php';
        [$user]=$this->reservation('20.0000');
        foreach (['marketplace_listing_status','marketplace_availability'] as $column) {
            if (!Schema::hasColumn('products',$column)) { Schema::table('products',fn($t)=>$t->string($column)->nullable()); }
        }
        DB::table('products')->where('id',9999)->update(['added_by'=>'admin','user_id'=>1,'status'=>1,'request_status'=>1,'marketplace_listing_status'=>'listed','marketplace_availability'=>'in_stock']);
        $this->fixture('carts',['customer_id'=>$user->id,'is_guest'=>0,'is_checked'=>1,'product_id'=>9999,'seller_id'=>1,'seller_is'=>'admin','cart_group_id'=>'delivery-fixture','quantity'=>1,'price'=>'100.00','discount'=>0,'tax'=>0,'shipping_cost'=>0]);
        $service=new \App\Services\DeliveryCheckoutIntentService();
        $address=['customer_id'=>$user->id,'address'=>'Test address','city'=>'Test city','phone'=>'08012345678','contact_person_name'=>'Pickup','country'=>'Nigeria'];
        $intent=$service->createCheckoutIntent($user,'first-key',$address,null,true);
        PaymentRequest::create(['payer_id'=>(string)$user->id,'payment_domain'=>'marketplace_delivery','order_group_id'=>$intent->order_group_id,'active_order_group_id'=>$intent->order_group_id,'payment_amount'=>'80.00','currency_code'=>'NGN','attempt_status'=>'pending','gateway_reference'=>'delivery-active','is_paid'=>0]);
        DB::table('carts')->where('customer_id',$user->id)->update(['quantity'=>2]);
        try { $service->createCheckoutIntent($user,'changed-key',$address,null,true);$this->fail('A payable active agreement must not be silently superseded.'); }
        catch (\App\Exceptions\InvalidPaymentStateException $e) { $this->assertStringContainsString('must finish',$e->getMessage()); }
        $this->assertSame('pending',$intent->fresh()->status);
        $this->assertSame('reserved',DB::table('cashback_redemptions')->where('checkout_intent_id',$intent->id)->value('status'));
        $this->assertSame(1,\App\Models\CheckoutIntent::count());
    }

    public function testPickupOldReservationDoesNotProtectRewardsReservedAfterLotExpiry():void
    {
        [$user,$reservation]=$this->reservation('20.0000');
        $reservation->update(['created_at'=>now()->subMinutes(10)]);
        DB::table('customer_cashback_ledgers')->where('customer_id',$user->id)->update(['expires_at'=>now()->subMinute()]);
        $service=new PickupPaymentInitializationService($this->client());
        $quote=$service->quote($user->id,$reservation->reservation_code,true);
        $this->assertSame('0.00',$quote['quote']['cashback_amount']);
        $this->assertSame('100.00',$quote['quote']['total_amount']);
        $payment=$service->initializePayment($user,$reservation,true,30,null,$quote['quote_token'])['payment_request'];
        $this->assertEquals('100.00',$payment->payment_amount);
        $this->assertSame(0,DB::table('cashback_redemptions')->where('pickup_reservation_id',$reservation->id)->count());
        // [AI] A historical/corrupted attempt created after expiry must also fail closed at capture.
        $redemption=\App\Models\CashbackRedemption::create(['customer_id'=>$user->id,'pickup_reservation_id'=>$reservation->id,'order_group_id'=>'pickup-'.$reservation->reservation_code,'points'=>'20.0000','cashback_amount'=>'20.00','status'=>'reserved']);
        $additional=json_decode($payment->additional_data,true);$additional['cashback_amount']='20.00';$additional['cashback_reservation']=['redemption_id'=>$redemption->id];
        $payment->update(['payment_amount'=>'80.00','additional_data'=>json_encode($additional)]);
        $failure=(new PickupOrderSettlementService())->settleVerifiedPayment($payment->gateway_reference,['status'=>'success','currency'=>'NGN','amount'=>8000]);
        $this->assertSame('post_payment_settlement_failure',$failure['anomaly_type']);
        $this->assertSame(0,Order::where('customer_id',$user->id)->count());
        $this->assertEquals('20.0000',$user->fresh()->getRawOriginal('loyalty_point'));
        $this->assertEquals('0',DB::table('admin_wallets')->value('pending_amount'));
    }
}
