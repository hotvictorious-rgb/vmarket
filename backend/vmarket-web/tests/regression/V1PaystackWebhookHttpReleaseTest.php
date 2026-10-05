<?php
namespace Tests\Regression;

require_once __DIR__.'/../Feature/GatewayMoneyTestCase.php';
require_once __DIR__.'/../../app/Services/PickupOrderSettlementService.php';
require_once __DIR__.'/../../app/Services/PickupPaymentInitializationService.php';

use App\Models\Order;
use App\Models\PaymentRequest;
use App\Models\PickupReservation;
use App\Models\RefundRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** [AI] Real production webhook HTTP route, signature authentication and replay; isolated SQLite only. */
class V1PaystackWebhookHttpReleaseTest extends \Tests\Feature\GatewayMoneyTestCase
{
    private array $previousSecret=[];
    protected function setUp():void
    {
        $this->previousSecret=['env'=>$_ENV['PAYSTACK_SECRET_KEY']??null,'server'=>$_SERVER['PAYSTACK_SECRET_KEY']??null,'process'=>getenv('PAYSTACK_SECRET_KEY')];
        parent::setUp();
        $_ENV['PAYSTACK_SECRET_KEY']='isolated-unit-secret';$_SERVER['PAYSTACK_SECRET_KEY']='isolated-unit-secret';putenv('PAYSTACK_SECRET_KEY=isolated-unit-secret');
    }
    protected function tearDown():void
    {
        parent::tearDown();
        foreach(['env'=>'_ENV','server'=>'_SERVER']as$key=>$global){if($this->previousSecret[$key]===null)unset($GLOBALS[$global]['PAYSTACK_SECRET_KEY']);else$GLOBALS[$global]['PAYSTACK_SECRET_KEY']=$this->previousSecret[$key];}
        putenv($this->previousSecret['process']===false?'PAYSTACK_SECRET_KEY':'PAYSTACK_SECRET_KEY='.$this->previousSecret['process']);
    }
    private function webhook(array $event,?string $signature=null):\Illuminate\Testing\TestResponse
    {
        $body=json_encode($event);
        return $this->call('POST','/payment/paystack/webhook',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_ACCEPT'=>'application/json','HTTP_X_PAYSTACK_SIGNATURE'=>$signature??hash_hmac('sha512',$body,'isolated-unit-secret')],$body);
    }
    public function testProductionHttpRejectsForgedAndBodyTamperedSignaturesWithoutMutation():void
    {
        $event=['event'=>'charge.success','data'=>['reference'=>'forged-http','amount'=>10000,'currency'=>'NGN']];
        $this->webhook($event,str_repeat('0',128))->assertStatus(401);
        $signature=hash_hmac('sha512',json_encode($event),'isolated-unit-secret');$event['data']['amount']=20000;
        $this->webhook($event,$signature)->assertStatus(401);
        $this->assertSame(0,DB::table('payment_reconciliations')->count());$this->assertSame(0,Order::count());$this->assertSame(0,PaymentRequest::count());
    }
    public function testProductionHttpSignedUnknownCaptureCreatesOneDurableCaseOnReplay():void
    {
        $event=['event'=>'charge.success','data'=>['id'=>777,'reference'=>'unknown-http','amount'=>12345,'currency'=>'NGN','metadata'=>['type'=>'delivery_payment','order_id'=>1]]];
        $this->webhook($event)->assertOk();$this->webhook($event)->assertOk();
        $this->assertSame(0,Order::count());$this->assertSame(1,DB::table('payment_reconciliations')->where('gateway_reference','unknown-http')->count());
        $case=DB::table('payment_reconciliations')->where('gateway_reference','unknown-http')->first();
        $this->assertEquals('123.45',$case->captured_amount);$this->assertCount(1,json_decode($case->audit_events,true));
    }
    public function testProductionHttpExpiredPickupCaptureStaysQuarantinedOnReplay():void
    {
        $user=User::create(['name'=>'Webhook','email'=>'webhook@example.test','phone'=>'08012345678','password'=>'test','loyalty_point'=>0]);
        $reservation=PickupReservation::create(['customer_id'=>$user->id,'seller_id'=>1,'shop_id'=>1,'reservation_code'=>'HTTP-LATE','idempotency_key'=>'http-late-key','reservation_fingerprint'=>str_repeat('a',64),'status'=>'inspected_accepted','total_amount'=>'100.00','reservation_items'=>[],'expires_at'=>now()->subMinute()]);
        $payment=PaymentRequest::create(['payer_id'=>(string)$user->id,'payment_amount'=>'100.00','currency_code'=>'NGN','payment_method'=>'paystack','payment_domain'=>'marketplace_pickup','pickup_reservation_id'=>$reservation->id,'active_pickup_reservation_id'=>$reservation->id,'attempt_status'=>'pending','gateway_reference'=>'late-http','is_paid'=>0,'additional_data'=>'{}']);
        $event=['event'=>'charge.success','data'=>['reference'=>'late-http','amount'=>10000,'currency'=>'NGN','metadata'=>['payment_id'=>$payment->id,'payment_domain'=>'marketplace_pickup']]];
        $this->webhook($event)->assertOk();$this->webhook($event)->assertOk();
        $this->assertSame('reconciliation_required',$payment->fresh()->attempt_status);$this->assertEquals(1,$payment->fresh()->is_paid);
        $this->assertSame(0,Order::count());$this->assertSame(1,DB::table('payment_reconciliations')->where('gateway_reference','late-http')->count());
    }
    public function testProductionHttpRefundNotificationReplayCannotRegressTerminalFinancialState():void
    {
        $user=User::create(['name'=>'RefundWebhook','email'=>'refund-webhook@example.test','phone'=>'08012345679','password'=>'test']);
        $order=Order::create(['customer_id'=>$user->id,'customer_type'=>'customer','seller_id'=>1,'seller_is'=>'seller','order_status'=>'delivered','payment_status'=>'paid','payment_method'=>'paystack','order_amount'=>'100.00','transaction_ref'=>'internal-http']);
        $refund=RefundRequest::create(['order_id'=>$order->id,'customer_id'=>$user->id,'order_details_id'=>1,'product_id'=>1,'amount'=>'100.00','refund_reason'=>'test','status'=>'refunded','execution_status'=>'succeeded','paystack_refund_id'=>'789']);
        foreach(['refund.pending','refund.failed','refund.pending']as$name){$this->webhook(['event'=>$name,'data'=>['id'=>'789','transaction_reference'=>'internal-http','merchant_note'=>'vmarket_refund_'.$refund->id,'amount'=>10000,'currency'=>'NGN']])->assertOk();}
        $this->assertSame('succeeded',$refund->fresh()->execution_status);$this->assertSame('refunded',$refund->fresh()->status);$this->assertSame(0,DB::table('refund_transactions')->count());
    }
}
