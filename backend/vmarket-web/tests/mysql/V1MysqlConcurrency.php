<?php
/** [AI] Opt-in disposable MariaDB verification: php tests/mysql/V1MysqlConcurrency.php --prepare|--run */
require __DIR__.'/V1MysqlHarness.php';
$mode=$argv[1]??'';
if(!in_array($mode,['--prepare','--run'],true))throw new RuntimeException('Explicit prepare or run required.');
if($mode!=='--run') {
    require_once V1_VERIFY_ROOT.'/vendor/autoload.php';require_once V1_VERIFY_ROOT.'/tests/Feature/DumpSchemaTestCase.php';
    $reflection=new ReflectionMethod(\Tests\Feature\SqliteDumpLoader::class,'splitStatements');$reflection->setAccessible(true);
    $ddl=[];$statements=$reflection->invoke(null,file_get_contents(V1_VERIFY_ROOT.'/installation/backup/database.sql'));
    foreach($statements as $sql){
        if(!preg_match('/^\s*(CREATE TABLE|ALTER TABLE)\s+`?([a-z0-9_]+)`?\s/i',$sql,$match))continue;
        $tokens=preg_replace("/'(?:[^'\\\\]|\\\\.)*'|`[^`]*`/s",' ',$sql);
        if(preg_match('/`\s*\.\s*`/', $sql)||preg_match('/\b(USE|OUTFILE|INFILE|DATABASE|SERVER|CONNECTION)\b/i',$tokens))throw new RuntimeException('Cross-database or external SQL rejected.');
        $ddl[]=[$match[1],$match[2],$sql];
    }
    $pdo=v1Pdo(false);$exists=$pdo->query("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".V1_VERIFY_DB."'")->fetchColumn();
    if($mode==='--prepare'&&$exists)throw new RuntimeException('Target already exists; refuse schema overwrite.');
    if(!$exists)$pdo->exec('CREATE DATABASE `'.V1_VERIFY_DB.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$pdo=v1Pdo();
    $existing=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach($ddl as [$kind,$table,$sql])$pdo->exec($sql);
    $pdo->exec('CREATE TABLE v1_verification_guard (name VARCHAR(100) PRIMARY KEY) ENGINE=InnoDB');$pdo->exec("INSERT INTO v1_verification_guard VALUES ('".V1_VERIFY_DB."')");
    $app=v1Boot();
    foreach(\Tests\Feature\SqliteDumpLoader::migrationsCreatingMissingTables(V1_VERIFY_ROOT.'/installation/backup/database.sql') as $path)(require V1_VERIFY_ROOT.'/'.$path)->up();
    foreach(['2026_09_19_000003_add_marketplace_fields_to_payment_requests_table.php','2026_09_19_000007_add_post_receipt_lifecycle_to_orders_table.php','2026_09_19_000008_add_execution_fields_to_refund_requests_table.php','2026_09_22_000017_add_canonical_geography_to_orders.php','2026_10_03_000001_add_expires_at_to_customer_cashback_ledgers_table.php','2026_10_03_000010_add_order_escrow_remaining.php','2026_10_04_000001_add_frozen_refund_allocations.php','2026_10_04_000002_bind_password_reset_credentials.php','2026_10_05_000001_rotate_public_oauth_passwords.php'] as $file)(require V1_VERIFY_ROOT.'/database/migrations/'.$file)->up();
    foreach(['guest_access_token'=>'2026_09_18_000002_add_guest_access_token_to_orders_table.php','total_tax_amount'=>'2025_08_27_213749_add_total_tax_amount_to_orders_table.php'] as $column=>$file)if(!\Illuminate\Support\Facades\Schema::hasColumn('orders',$column))(require V1_VERIFY_ROOT.'/database/migrations/'.$file)->up();
    echo 'PREPARED '.V1_VERIFY_DB.' '.$pdo->query('SELECT VERSION()')->fetchColumn()."\n";exit;
}
$app=v1Boot();use Illuminate\Support\Facades\DB;
if(DB::table('v1_verification_guard')->where('name','run_started')->exists())throw new RuntimeException('One-shot fixture run already started; refuse accumulated verification.');
DB::table('v1_verification_guard')->insert(['name'=>'run_started']);
foreach(DB::select('SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=?',[V1_VERIFY_DB]) as $t)v1Assert($t->ENGINE==='InnoDB','InnoDB '.$t->TABLE_NAME);
function race(string $table,int|string $id,array $job):array {
    $dir=sys_get_temp_dir().'/vmarket-v1-race-'.bin2hex(random_bytes(6));mkdir($dir);file_put_contents($dir.'/job.json',json_encode($job));$processes=[];
    DB::beginTransaction();DB::table($table)->where('id',$id)->lockForUpdate()->first();
    try {
        for($i=0;$i<2;$i++){$out=$dir.'/result'.$i.'.json';$spec=[0=>['pipe','r'],1=>['file',$dir.'/stdout'.$i,'a'],2=>['file',$dir.'/stderr'.$i,'a']];$p=proc_open([PHP_BINARY,'-d','allow_url_fopen=0','-d','disable_functions='.V1_VERIFY_DISABLED,__DIR__.'/V1MysqlWorker.php',$dir.'/job.json',$out],$spec,$pipes,V1_VERIFY_ROOT);if(!is_resource($p))throw new RuntimeException('Worker launch failed.');fclose($pipes[0]);$processes[]=[$p,$out];}
        $deadline=microtime(true)+45;while(!is_file($processes[0][1].'.ready')||!is_file($processes[1][1].'.ready')){if(microtime(true)>$deadline)throw new RuntimeException('Worker ready timeout; inspect '.$dir);usleep(50000);}
        usleep(300000);v1Assert(!is_file($processes[0][1])&&!is_file($processes[1][1]),$job['kind'].' actual production callers block behind row lock');DB::commit();
        $results=[];foreach($processes as [$process,$out]){$deadline=microtime(true)+45;while(!is_file($out)){if(microtime(true)>$deadline)throw new RuntimeException('Worker result timeout: '.$dir);usleep(50000);}proc_close($process);$r=json_decode(file_get_contents($out),true);v1Assert($r['ok'],$job['kind'].' worker '.($r['error']??'completed'));$results[]=$r['result'];}return $results;
    } catch(Throwable $e){if(DB::transactionLevel())DB::rollBack();foreach($processes as [$p])if(is_resource($p))@proc_terminate($p);throw $e;}
}

$public=v1Row('users',['email'=>'public-oauth@example.test','phone'=>'08000000001','social_id'=>'public-provider-id','login_medium'=>'google','password'=>\Illuminate\Support\Facades\Hash::make('public-provider-id'),'is_active'=>1,'wallet_balance'=>'123.45']);
$chosen=v1Row('users',['email'=>'chosen-oauth@example.test','phone'=>'08000000002','social_id'=>'public-chosen-id','login_medium'=>'google','password'=>\Illuminate\Support\Facades\Hash::make('ChosenPassword123!'),'is_active'=>1]);
$rotation=require V1_VERIFY_ROOT.'/database/migrations/2026_10_05_000001_rotate_public_oauth_passwords.php';$rotation->up();$rotation->down();
v1Assert(!\Illuminate\Support\Facades\Hash::check('public-provider-id',DB::table('users')->where('id',$public)->value('password')),'additive migration rotates proven public password on already-bound MariaDB schema');
v1Assert(\Illuminate\Support\Facades\Hash::check('ChosenPassword123!',DB::table('users')->where('id',$chosen)->value('password')),'additive migration preserves customer-chosen password');
v1Assert(bccomp((string)DB::table('users')->where('id',$public)->value('wallet_balance'),'123.45',2)===0,'credential migration preserves money');
$user=v1Row('users',['email'=>'race@example.test','phone'=>'08012345678','password'=>\Illuminate\Support\Facades\Hash::make('Original123!'),'is_active'=>1,'loyalty_point'=>0]);$u=\App\Models\User::find($user);
app(\App\Services\PasswordResetCredentialService::class)->issue('customer',$u,$u->phone,'567891');
$r=race('users',$user,['kind'=>'reset','identity'=>$u->phone,'proof'=>'567891','password'=>'Winner Password123!']);v1Assert(count(array_filter($r))===1,'reset proof has exactly one successful consumer');v1Assert(DB::table('password_resets')->where('account_id',$user)->count()===0,'reset proof consumed');v1Assert((int)$u->fresh()->credential_version===1,'reset credential version increments once');
$wallet=v1Row('seller_wallets',['seller_id'=>99,'total_earning'=>'0.00','pending_withdraw'=>'100.00','withdrawn'=>'0.00','collected_cash'=>'0.00']);$withdraw=v1Row('withdraw_requests',['seller_id'=>99,'amount'=>'100.00','approved'=>0]);
race('withdraw_requests',$withdraw,['kind'=>'payout','id'=>$withdraw]);v1Assert(bccomp((string)DB::table('seller_wallets')->where('id',$wallet)->value('total_earning'),'100.00',2)===0,'payout denial restores reserved money exactly once');v1Assert(bccomp((string)DB::table('seller_wallets')->where('id',$wallet)->value('pending_withdraw'),'0.00',2)===0,'payout reservation cleared once');v1Assert(DB::table('admin_audit_logs')->where('action','vendor.withdrawal_decision')->count()===1,'payout has one durable decision');
$order=v1Row('orders',['customer_id'=>$user,'seller_id'=>99,'seller_is'=>'seller','order_status'=>'delivered','payment_status'=>'paid','payment_method'=>'paystack','order_amount'=>'100.00','vendor_settlement_status'=>'held','shipping_cost'=>'0.00','discount_amount'=>'0.00']);$detail=v1Row('order_details',['order_id'=>$order,'product_id'=>1,'seller_id'=>99,'qty'=>1,'price'=>'100.00','tax'=>'0.00','discount'=>'0.00','refund_request'=>0]);$o=\App\Models\Order::with('details')->find($order);\App\Utils\OrderManager::getAddOrderTransactionsOnGenerateOrder($o,['payment_method'=>'paystack']);
$refund=v1Row('refund_requests',['order_id'=>$order,'order_details_id'=>$detail,'customer_id'=>$user,'product_id'=>1,'refund_reason'=>'Isolated returned item','amount'=>'100.00','status'=>'approved','execution_status'=>'awaiting_manual_payment','payment_info'=>json_encode(['refundable_money_amount'=>'100.00','cashback_amount'=>'0.00','refundable_merchandise_value'=>'100.00'])]);
$r=race('orders',$order,['kind'=>'refund','id'=>$refund,'order'=>$order]);v1Assert(count(array_filter($r,fn($x)=>$x['status']))===1,'manual refund has exactly one accounting finalization');v1Assert(DB::table('refund_transactions')->where('refund_id',$refund)->count()===1,'manual refund has one transaction');v1Assert(bccomp((string)DB::table('admin_wallets')->value('pending_amount'),'0.00',2)===0,'refund releases only its100NGN hold once');
$product=v1Row('products',['name'=>'Isolated Product','product_type'=>'physical','current_stock'=>5,'added_by'=>'seller','user_id'=>99]);$group='isolated-group';$reference='ISOLATED-CALLBACK-1';$snapshot=['vendors'=>[['seller_id'=>99,'seller_is'=>'seller','subtotal'=>'100.00','merchandise'=>'100.00','tax'=>'0.00','shipping_cost'=>'0.00','total'=>'100.00','items'=>[['product_id'=>$product,'quantity'=>1,'unit_price'=>'100.00','tax'=>'0.00','discount'=>'0.00']]]]];
$intent=v1Row('checkout_intents',['customer_id'=>$user,'order_group_id'=>$group,'idempotency_key'=>'isolated-key','cart_fingerprint'=>str_repeat('a',64),'active_cart_token'=>str_repeat('a',64),'status'=>'pending','total_amount'=>'100.00','currency'=>'NGN','checkout_snapshot'=>json_encode($snapshot),'expires_at'=>now()->addHour()]);
DB::table('payment_requests')->insert(['id'=>'isolated-payment','payer_id'=>(string)$user,'payment_amount'=>'100.00','currency_code'=>'NGN','payment_method'=>'paystack','is_paid'=>0,'payer_information'=>'{}','additional_data'=>'{}','payment_domain'=>'marketplace_delivery','order_group_id'=>$group,'gateway_reference'=>$reference,'attempt_status'=>'pending']);
$r=race('checkout_intents',$intent,['kind'=>'callback','reference'=>$reference]);v1Assert(count(array_filter($r,fn($x)=>$x['status']==='CLAIMED'))===1,'delivery callback has exactly one successful financial claim');v1Assert(DB::table('orders')->where('order_group_id',$group)->count()===1,'delivery callback produces one order');v1Assert((int)DB::table('products')->where('id',$product)->value('current_stock')===4,'callback deducts stock once');v1Assert(bccomp((string)DB::table('admin_wallets')->value('pending_amount'),'100.00',2)===0,'callback creates one100NGN escrow hold');
echo "COMPLETE application-level MariaDB races; configured database untouched; no live-provider certification.\n";
