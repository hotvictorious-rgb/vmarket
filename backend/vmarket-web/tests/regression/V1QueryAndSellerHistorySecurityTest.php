<?php
namespace Tests\Regression;

require_once __DIR__.'/../Feature/GatewayMoneyTestCase.php';
require_once __DIR__.'/../../app/Repositories/ProductRepository.php';
require_once __DIR__.'/../../app/Http/Controllers/Web/WebController.php';
require_once __DIR__.'/../../app/Http/Controllers/RestAPI/v1/BrandController.php';
require_once __DIR__.'/../../app/Http/Controllers/RestAPI/v3/seller/DeliveryManController.php';
require_once __DIR__.'/../../app/Http/Middleware/SellerApiAuthMiddleware.php';
require_once __DIR__.'/../../app/Utils/ProductManager.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Order;

/** [AI] Real query execution and production seller HTTP authorization on isolated SQLite. */
class V1QueryAndSellerHistorySecurityTest extends \Tests\Feature\GatewayMoneyTestCase
{
    protected function setUp():void
    {
        parent::setUp();
        (require base_path('database/migrations/2026_09_12_000003_add_marketplace_listing_freshness_columns_to_products_table.php'))->up();
        foreach (['availability_expires_at','availability_confirmed_at'] as $c) {
            if (!Schema::hasColumn('products',$c)) { Schema::table('products',fn($t)=>$t->timestamp($c)->nullable()); }
        }
        if (!Schema::hasColumn('products','shop_id')) { Schema::table('products',fn($t)=>$t->integer('shop_id')->nullable()); }
        foreach (['shop_id','auth_token'] as $c) {
            if (!Schema::hasColumn('vendor_employees',$c)) { Schema::table('vendor_employees',fn($t)=>$t->string($c)->nullable()); }
        }
        DB::connection()->getPdo()->sqliteCreateFunction('LOCATE', fn($needle,$haystack)=>($pos=strpos((string)$haystack,(string)$needle))===false?0:$pos+1,2);
        $this->row('sellers',['id'=>91,'status'=>'approved','auth_token'=>hash('sha256',str_repeat('a',40))]);
        $this->row('sellers',['id'=>92,'status'=>'approved','auth_token'=>hash('sha256',str_repeat('b',40))]);
    }

    private function row(string $table,array $values):int
    {
        $defaults=[];
        foreach(DB::select('PRAGMA table_info("'.$table.'")')as$c) {
            if($c->name!=='id'&&$c->notnull&&$c->dflt_value===null){$defaults[$c->name]=preg_match('/INT|DECIMAL|REAL|FLOAT|DOUBLE|NUMERIC/i',$c->type)?0:'';}
        }
        return DB::table($table)->insertGetId(array_merge($defaults,$values));
    }

    public function testActualProductRepositoryTreatsQuoteBackslashAndSqlTextAsBoundData():void
    {
        $payload="needle\\') OR 1=1 --";
        $this->row('products',['id'=>8001,'name'=>$payload,'code'=>'needle','added_by'=>'admin','user_id'=>1]);
        $this->row('products',['id'=>8002,'name'=>'Unrelated product','code'=>'unrelated','added_by'=>'admin','user_id'=>1]);
        $queries=[];DB::listen(function($q)use(&$queries){$queries[]=$q;});
        $results=app(\App\Repositories\ProductRepository::class)->getListWithScope(filters:['search_from'=>'pos','keywords'=>$payload],dataLimit:'all');
        $this->assertSame([8001],$results->pluck('id')->all());
        $rank=array_values(array_filter($queries,fn($q)=>str_contains($q->sql,'LOCATE(')));
        $this->assertCount(1,$rank);
        $this->assertStringContainsString('LOCATE(?, name)',$rank[0]->sql);
        $this->assertStringNotContainsString($payload,$rank[0]->sql);
        $this->assertStringContainsString('OR 1=1 --',end($rank[0]->bindings));
    }

    public function testActualWebAndBrandSearchExecuteBoundRankingQueries():void
    {
        $payload="x\\') OR 1=1 --";
        $queries=[];DB::listen(function($q)use(&$queries){$queries[]=$q;});
        $view=\Mockery::mock(\Illuminate\Contracts\View\View::class);$view->shouldReceive('render')->andReturn('');
        View::shouldReceive('make')->andReturn($view);
        $request=\Illuminate\Http\Request::create('/search','GET',['name'=>$payload]);
        $this->assertSame(200,app(\App\Http\Controllers\Web\WebController::class)->getSearchedProducts($request)->getStatusCode());
        $request=\Illuminate\Http\Request::create('/brands/1/products','GET',['search'=>$payload,'limit'=>'all']);
        $this->assertSame(200,(new \App\Http\Controllers\RestAPI\v1\BrandController())->get_products($request,1)->getStatusCode());
        $rank=array_values(array_filter($queries,fn($q)=>str_contains($q->sql,'LOCATE(')));
        $this->assertGreaterThanOrEqual(2,count($rank));
        foreach($rank as$q){$this->assertStringContainsString('LOCATE(?, name)',$q->sql);$this->assertStringNotContainsString($payload,$q->sql);$this->assertNotEmpty($q->bindings);}
    }

    public function testJsonCategoryParentUpdateUsesBindingsAndPreservesOtherItems():void
    {
        $this->row('products',['id'=>8101,'name'=>'Category product','category_ids'=>'[{"id":"1","position":1},{"id":"2","position":2}]','sub_category_id'=>5]);
        $payload="7') OR 1=1 --";
        $queries=[];DB::listen(function($q)use(&$queries){$queries[]=$q;});
        // [AI] Execute the production MySQL JSON_SET grammar on SQLite JSON functions;
        // SQLite's own Laravel JSON update grammar uses JSON_PATCH and differs for root arrays.
        $connection=DB::connection();$grammar=$connection->getQueryGrammar();
        $connection->setQueryGrammar(new \Illuminate\Database\Query\Grammars\MySqlGrammar($connection));
        // [AI] SQLite rejects MySQL's qualified automatic updated_at target; timestamps are irrelevant to this JSON binding proof.
        $product=new \App\Models\Product();$product->timestamps=false;
        try { app()->makeWith(\App\Repositories\ProductRepository::class,['product'=>$product])->updateByParams(['sub_category_id'=>5],['category_id'=>7,'category_ids->[0]->id'=>$payload]); }
        finally { $connection->setQueryGrammar($grammar); }
        $snapshot=json_decode(DB::table('products')->where('id',8101)->value('category_ids'),true);
        $this->assertSame($payload,$snapshot[0]['id']);$this->assertSame('2',$snapshot[1]['id']);
        $writes=array_values(array_filter($queries,fn($q)=>str_starts_with($q->sql,'update `products`')));
        $this->assertCount(1,$writes);$this->assertStringNotContainsString($payload,$writes[0]->sql);$this->assertContains($payload,$writes[0]->bindings);
    }

    public function testActualSellerHistoryHttpRejectsOtherOwnerAndMissingOrder():void
    {
        $this->row('orders',['id'=>8501,'seller_id'=>91,'seller_is'=>'seller','customer_id'=>1]);
        $this->row('order_status_histories',['order_id'=>8501,'status'=>'confirmed','user_type'=>'seller','user_id'=>91]);
        $uri='/api/v3/seller/delivery-man/order-status-history/8501';
        $this->getJson($uri,['Authorization'=>'Bearer '.str_repeat('a',40)])->assertOk()->assertJsonCount(1);
        $this->getJson($uri.'?vendor_employee[shop_id]=911&vendor_employee[id]=999&is_vendor_employee=1',['Authorization'=>'Bearer '.str_repeat('a',40)])->assertOk()->assertJsonCount(1);
        $this->getJson($uri,['Authorization'=>'Bearer '.str_repeat('b',40)])->assertNotFound()->assertJsonMissing(['status'=>'confirmed']);
        $this->getJson('/api/v3/seller/delivery-man/order-status-history/999999',['Authorization'=>'Bearer '.str_repeat('a',40)])->assertNotFound();
    }

    public function testActualEmployeeHistoryHttpRequiresOrderModuleAndKnownAssignedBranch():void
    {
        $this->row('orders',['id'=>8601,'seller_id'=>91,'seller_is'=>'seller','customer_id'=>1,'order_type'=>'pickup']);
        $this->row('order_status_histories',['order_id'=>8601,'status'=>'confirmed','user_type'=>'seller','user_id'=>91]);
        $this->row('shops',['id'=>910,'seller_id'=>91,'author_type'=>'seller']);$this->row('shops',['id'=>911,'seller_id'=>91,'author_type'=>'seller']);
        $role=\App\Models\VendorRole::create(['seller_id'=>91,'name'=>'History staff','module_access'=>[],'status'=>1]);
        $employee=\App\Models\VendorEmployee::create(['seller_id'=>91,'shop_id'=>910,'vendor_role_id'=>$role->id,'name'=>'Staff','email'=>'staff@example.test','phone'=>'08012345678','password'=>'test','status'=>1,'auth_token'=>hash('sha256',str_repeat('c',40))]);
        \App\Models\PickupReservation::create(['reservation_code'=>'HISTORY','idempotency_key'=>'history-key','customer_id'=>1,'seller_id'=>91,'shop_id'=>910,'reservation_fingerprint'=>str_repeat('d',64),'status'=>'order_placed','total_amount'=>'100','reservation_items'=>[],'order_id'=>8601,'expires_at'=>now()->addHour()]);
        $uri='/api/v3/seller/delivery-man/order-status-history/8601';$headers=['Authorization'=>'Bearer '.str_repeat('c',40)];
        $this->getJson($uri,$headers)->assertForbidden();
        $role->update(['module_access'=>['order']]);$this->getJson($uri,$headers)->assertOk();
        $this->getJson($uri,$headers+['X-Branch-ID'=>'911'])->assertForbidden();
        $employee->update(['shop_id'=>911]);$this->getJson($uri,$headers)->assertForbidden();
        $employee->update(['shop_id'=>910]);$role->update(['status'=>0]);$this->getJson($uri,$headers)->assertForbidden();
        $role->update(['status'=>1]);$employee->update(['status'=>0]);$this->getJson($uri,$headers)->assertForbidden();
        $employee->update(['status'=>1]);DB::table('pickup_reservations')->where('order_id',8601)->delete();
        $this->getJson($uri,$headers)->assertForbidden();
    }

    public function testLocateHelperReturnsBoundTemplateAndRejectsInjectedIdentifiers():void
    {
        $payload="needle') OR 1=1 --";
        $this->row('products',['id'=>8701,'name'=>$payload]);
        $sql=\App\Utils\ProductManager::getLocateSql($payload);
        $this->assertSame('INSTR("name", ?)',$sql);
        $queries=[];DB::listen(function($q)use(&$queries){$queries[]=$q;});
        $result=\App\Models\Product::query()->where('id',8701)->orderByRaw($sql,[$payload])->get();
        $this->assertSame([8701],$result->pluck('id')->all());
        $this->assertStringNotContainsString($payload,$queries[0]->sql);$this->assertContains($payload,$queries[0]->bindings);
        $this->expectException(\InvalidArgumentException::class);
        \App\Utils\ProductManager::getLocateSql('needle','name) DESC, (SELECT password FROM users)--');
    }
}
