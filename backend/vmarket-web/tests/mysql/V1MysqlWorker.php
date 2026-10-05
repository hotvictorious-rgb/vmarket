<?php
/** [AI] Separate application process executing production services/controllers, not copied accounting logic. */
require __DIR__.'/V1MysqlHarness.php';
$app=v1Boot();$job=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
file_put_contents($argv[2].'.ready','ready');
try {
    $result=match($job['kind']) {
        'reset'=>app(\App\Services\PasswordResetCredentialService::class)->consume('customer',$job['identity'],$job['proof'],$job['password']),
        'refund'=>app(\App\Services\PaystackRefundService::class)->finalizeManualPaymentConfirmation(\App\Models\RefundRequest::findOrFail($job['id']),\App\Models\Order::findOrFail($job['order']),['payment_method'=>'bank_transfer','amount'=>'100.00','payment_reference'=>'ISOLATED-BANK-1','payment_date'=>now()->toDateString(),'confirmed_by'=>1]),
        'callback'=>app(\App\Services\DeliveryOrderSettlementService::class)->settleVerifiedPayment($job['reference'],['reference'=>$job['reference'],'amount'=>10000,'currency'=>'NGN','status'=>'success']),
        'payout'=>v1Payout($job),
        default=>throw new RuntimeException('Unknown worker job.'),
    };
    file_put_contents($argv[2],json_encode(['ok'=>true,'result'=>$result],JSON_THROW_ON_ERROR));
} catch(Throwable $e) {file_put_contents($argv[2],json_encode(['ok'=>false,'error'=>get_class($e).': '.$e->getMessage()]));exit(1);}
function v1Payout(array $job):array {
    $admin=new \App\Models\Admin(['admin_role_id'=>1,'name'=>'Isolated Finance']);$admin->id=1;auth('admin')->setUser($admin);
    $response=app(\App\Http\Controllers\Admin\Vendor\VendorController::class)->withdrawStatus(new \Illuminate\Http\Request(['approved'=>2,'note'=>'Isolated concurrent denial']),$job['id']);
    return ['http_status'=>$response->getStatusCode()];
}
