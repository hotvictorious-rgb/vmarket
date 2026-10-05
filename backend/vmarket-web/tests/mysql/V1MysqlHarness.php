<?php
/** [AI] Disposable MariaDB application verification only; no configured database is opened. */
const V1_VERIFY_DB = 'vmarket_v1_verify_finance_20261005_02';
const V1_VERIFY_ROOT = __DIR__.'/../..';
const V1_VERIFY_DISABLED = 'curl_init,curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_create,socket_connect';
function v1NetworkGuard(): void {
    if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) throw new RuntimeException('Launch with allow_url_fopen=0.');
    foreach(explode(',',V1_VERIFY_DISABLED) as $function) if(function_exists($function)) throw new RuntimeException('Raw network function remains enabled: '.$function);
}
v1NetworkGuard();
function v1Pdo(bool $database = true): PDO {
    return new PDO('mysql:host=127.0.0.1;port=3306'.($database ? ';dbname='.V1_VERIFY_DB : '').';charset=utf8mb4','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}
function v1Boot(): \Illuminate\Foundation\Application {
    foreach(['APP_ENV'=>'testing','DB_CONNECTION'=>'mysql','DB_DATABASE'=>V1_VERIFY_DB,'CACHE_STORE'=>'array','SESSION_DRIVER'=>'array','QUEUE_CONNECTION'=>'sync','MAIL_MAILER'=>'array'] as $key=>$value) {putenv("$key=$value");$_ENV[$key]=$value;$_SERVER[$key]=$value;}
    require_once V1_VERIFY_ROOT.'/vendor/autoload.php';
    spl_autoload_register(function($class){if(str_starts_with($class,'App\\')){$file=V1_VERIFY_ROOT.'/app/'.str_replace('\\','/',substr($class,4)).'.php';if(is_file($file))require_once $file;}},true,true);
    $app=require V1_VERIFY_ROOT.'/bootstrap/app.php';
    $app->beforeBootstrapping(\Illuminate\Foundation\Bootstrap\BootProviders::class,function($app){
        $app['config']->set('database.default','mysql');$app['config']->set('database.connections.mysql',[
            'driver'=>'mysql','host'=>'127.0.0.1','port'=>3306,'database'=>V1_VERIFY_DB,'username'=>'root','password'=>'','charset'=>'utf8mb4','collation'=>'utf8mb4_unicode_ci','prefix'=>'','strict'=>true,'engine'=>'InnoDB']);
        $app['config']->set('cache.default','array');$app['config']->set('session.driver','array');$app['config']->set('queue.default','sync');$app['config']->set('mail.default','array');
        \Illuminate\Support\Facades\Http::preventStrayRequests();\Illuminate\Support\Facades\Http::fake(fn()=>throw new RuntimeException('External I/O forbidden in isolated verification.'));
        if($app['db']->connection()->getDatabaseName()!==V1_VERIFY_DB)throw new RuntimeException('Database isolation failed.');
        if(!$app['db']->table('v1_verification_guard')->where('name',V1_VERIFY_DB)->exists())throw new RuntimeException('Disposable target marker missing.');
    });
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    \Illuminate\Support\Facades\Event::fake();return $app;
}
function v1Row(string $table,array $values): int|string {
    $defaults=[];foreach(\Illuminate\Support\Facades\DB::select('SHOW COLUMNS FROM `'.$table.'`') as $c){
        if($c->Field==='id'||$c->Null==='YES'||$c->Default!==null)continue;
        $defaults[$c->Field]=preg_match('/int|decimal|float|double/i',$c->Type)?0:(str_starts_with($c->Type,'enum(')?explode("'",$c->Type)[1]:'');
    }
    return \Illuminate\Support\Facades\DB::table($table)->insertGetId($values+$defaults);
}
function v1Assert(bool $condition,string $message):void {if(!$condition)throw new RuntimeException($message);echo "PASS $message\n";}
