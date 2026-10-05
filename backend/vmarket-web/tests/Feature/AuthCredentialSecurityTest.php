<?php
namespace Tests\Feature;
require_once __DIR__.'/GatewayMoneyTestCase.php';
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use App\Services\PasswordResetCredentialService;

/** [AI] Actual routed auth regressions; SQLite is selected before any provider boot. */
class AuthCredentialSecurityTest extends GatewayMoneyTestCase
{
    protected function setUp():void {
        parent::setUp();Http::preventStrayRequests();
        (require base_path('database/migrations/2026_10_04_000002_bind_password_reset_credentials.php'))->up();
        foreach(['remember_token','temporary_token'] as $c)if(!Schema::hasColumn('users',$c))Schema::table('users',fn($t)=>$t->string($c)->nullable());
        $this->assertSame('sqlite',DB::connection()->getDriverName());$this->assertSame(':memory:',config('database.connections.sqlite.database'));
    }
    private function fixture(string $table,array $values):int {
        $d=[];foreach(DB::select('PRAGMA table_info("'.$table.'")') as $c)if($c->name!=='id' && $c->notnull && $c->dflt_value===null)$d[$c->name]=preg_match('/INT|DECIMAL|DOUBLE|FLOAT|REAL|NUMERIC/i',$c->type)?0:'';
        return DB::table($table)->insertGetId($values+$d);
    }
    private function user():\App\Models\User {
        $id=$this->fixture('users',['email'=>'customer@example.test','phone'=>'08012345678','password'=>Hash::make('Old Password123'),'remember_token'=>'remember','temporary_token'=>'temporary','is_active'=>1,'is_phone_verified'=>1,'is_email_verified'=>1]);return \App\Models\User::find($id);
    }
    public function test_rider_reset_requires_account_purpose_expiry_and_consumes_once():void {
        $id=$this->fixture('delivery_men',['phone'=>'08012345678','email'=>'rider@example.test','password'=>Hash::make('Old Password123'),'auth_token'=>'old','is_active'=>1]);$r=\App\Models\DeliveryMan::find($id);
        $data=['identity'=>$r->phone,'phone'=>$r->phone,'password'=>' New Password123 ','confirm_password'=>' New Password123 '];
        $this->postJson('/api/v2/delivery-man/auth/reset-password',$data)->assertStatus(403);
        $this->postJson('/api/v2/delivery-man/auth/reset-password',$data+['otp'=>'123456'])->assertStatus(403);
        app(PasswordResetCredentialService::class)->issue('delivery_man',$r,$r->phone,'123456');
        $this->postJson('/api/v2/delivery-man/auth/reset-password',$data+['otp'=>'123456'])->assertOk();
        $this->assertTrue(Hash::check(' New Password123 ',$r->fresh()->password));$this->assertSame('',$r->fresh()->auth_token);
        $this->postJson('/api/v2/delivery-man/auth/reset-password',$data+['otp'=>'123456'])->assertStatus(403);
        app(PasswordResetCredentialService::class)->issue('delivery_man',$r,$r->phone,'654321');DB::table('password_resets')->where('account_id',$id)->update(['created_at'=>now()->subMinutes(16)]);
        $this->postJson('/api/v2/delivery-man/auth/reset-password',$data+['otp'=>'654321'])->assertStatus(403);
    }
    public function test_customer_and_seller_cannot_reset_from_public_store_or_other_purpose():void {
        $u=$this->user();$sid=$this->fixture('sellers',['email'=>'seller@example.test','phone'=>'08098765432','password'=>Hash::make('Original123'),'status'=>'approved']);$seller=\App\Models\Seller::find($sid);
        foreach([['/api/v1/auth',$u->phone],['/api/v3/seller/auth',$seller->phone]] as [$url,$identity]){
            $this->postJson($url.'/firebase-auth-token-store',['identity'=>$identity,'token'=>'123456'])->assertStatus(403);
            $this->fixture('phone_or_email_verifications',['phone_or_email'=>$identity,'token'=>'123456','created_at'=>now()]);
            $this->putJson($url.'/reset-password',['identity'=>$identity,'otp'=>'123456','password'=>'Changed123','confirm_password'=>'Changed123'])->assertStatus(403);
        }
        app(PasswordResetCredentialService::class)->issue('seller',$seller,$seller->phone,'123456');
        $this->putJson('/api/v1/auth/reset-password',['identity'=>$u->phone,'otp'=>'123456','password'=>'Changed123','confirm_password'=>'Changed123'])->assertStatus(403);
        $this->assertTrue(Hash::check('Original123',$seller->fresh()->password));Http::assertNothingSent();
    }
    public function test_valid_customer_reset_revokes_access_refresh_remember_and_temporary_credentials():void {
        $u=$this->user();$this->fixture('oauth_access_tokens',['id'=>'access-one','user_id'=>$u->id,'client_id'=>1,'name'=>'Test','scopes'=>'[]','revoked'=>0]);
        $this->fixture('oauth_refresh_tokens',['id'=>'refresh-one','access_token_id'=>'access-one','revoked'=>0,'expires_at'=>now()->addDay()]);
        app(PasswordResetCredentialService::class)->issue('customer',$u,$u->phone,'123456');
        $this->postJson('/api/v1/auth/verify-token',['email_or_phone'=>$u->phone,'reset_token'=>'123456'])->assertOk();
        $this->putJson('/api/v1/auth/reset-password',['identity'=>$u->phone,'otp'=>'123456','password'=>'Changed123','confirm_password'=>'Changed123'])->assertOk();
        $this->assertTrue(Hash::check('Changed123',$u->fresh()->password));$this->assertNull($u->fresh()->remember_token);$this->assertNull($u->fresh()->temporary_token);
        $this->assertEquals(1,DB::table('oauth_access_tokens')->where('id','access-one')->value('revoked'));$this->assertEquals(1,DB::table('oauth_refresh_tokens')->where('id','refresh-one')->value('revoked'));
    }
    public function test_account_failed_otp_budget_blocks_even_correct_credential():void {
        $u=$this->user();app(PasswordResetCredentialService::class)->issue('customer',$u,$u->phone,'123456');
        for($i=0;$i<5;$i++)$this->assertFalse(app(PasswordResetCredentialService::class)->verify('customer',$u->phone,'654321'));
        $this->assertFalse(app(PasswordResetCredentialService::class)->consume('customer',$u->phone,'123456','Changed123'));
        $this->assertTrue(Hash::check('Old Password123',$u->fresh()->password));
    }

    public function test_seller_helpers_accept_only_raw_bearer_against_stored_hash():void {
        $token=str_repeat('h',50);$id=$this->fixture('sellers',['email'=>'helper@example.test','phone'=>'08099999999','status'=>'approved','auth_token'=>hash('sha256',$token)]);
        foreach([$token,hash('sha256',$token)] as $bearer){
            $request=\Illuminate\Http\Request::create('/');$request->headers->set('Authorization','Bearer '.$bearer);
            $model=\App\Utils\Helpers::getSellerByToken($request);$legacy=\App\Utils\Helpers::get_seller_by_token($request);
            if($bearer===$token){$this->assertSame($id,$model->id);$this->assertSame(1,$legacy['success']);}
            else{$this->assertNull($model);$this->assertSame(0,$legacy['success']);}
        }
        DB::table('sellers')->where('id',$id)->update(['auth_token'=>$token]);
        $request=\Illuminate\Http\Request::create('/');$request->headers->set('Authorization','Bearer '.$token);
        $this->assertNull(\App\Utils\Helpers::getSellerByToken($request));$this->assertSame(0,\App\Utils\Helpers::get_seller_by_token($request)['success']);
    }
    public function test_native_web_resend_delivers_the_exact_random_reset_credential():void {
        $u=$this->user();$sent=null;
        $this->mock(\App\Services\Web\CustomerAuthService::class,function($mock)use(&$sent,$u){
            $mock->shouldReceive('sendCustomerPhoneVerificationToken')->once()->withArgs(function($phone,$token)use(&$sent,$u){$sent=(string)$token;return $phone===$u->phone && preg_match('/^[0-9]{6}$/D',$sent); })->andReturn(['status'=>'success']);
        });
        $this->withSession(['default_recaptcha_id_customer_auth'=>'captcha'])->post('/customer/auth/resend-otp-reset-password',['identity'=>base64_encode($u->phone),'default_captcha_value'=>'captcha'])->assertRedirect();
        $this->assertNotNull($sent);$this->assertSame(hash('sha256',$sent),DB::table('password_resets')->where('account_id',$u->id)->value('token'));
        $this->withSession(['forgot_password_identity'=>$u->phone])->post('/customer/auth/otp-verification',['identity'=>$u->phone,'otp'=>$sent])->assertRedirect(route('customer.auth.reset-password',['identity'=>base64_encode($u->phone),'token'=>$sent]));
        $this->get('/customer/auth/reset-password?'.http_build_query(['identity'=>base64_encode($u->phone),'token'=>$sent]))->assertOk();
        $this->post('/customer/auth/reset-password',['identity'=>base64_encode($u->phone),'reset_token'=>$sent,'password'=>'ResentPassword123!','confirm_password'=>'ResentPassword123!'])->assertRedirect('/');
        $this->assertTrue(Hash::check('ResentPassword123!',$u->fresh()->password));Http::assertNothingSent();
    }
    public function test_precreation_invitation_cannot_authorize_reset_and_bound_tokens_are_hashed():void {
        $service=app(\App\Services\PasswordResetService::class);$identity='08012345678';$proof=str_repeat('i',120);
        $data=$service->getAddData($identity,$proof,'customer');$this->assertSame('registration_invite',$data['purpose']);$this->assertNull($data['account_id']);DB::table('password_resets')->insert($data);
        $u=$this->user();$this->assertFalse(app(PasswordResetCredentialService::class)->consume('customer',$identity,$proof,'NewPassword123'));
        $bound=$service->getAddData($identity,$proof,'customer');$this->assertSame('password_reset',$bound['purpose']);$this->assertSame($u->id,$bound['account_id']);$this->assertSame(hash('sha256',$proof),$bound['token']);
    }
    public function test_employee_info_never_exposes_owner_digest_and_logout_revokes_only_actual_principal():void {
        $ownerToken=str_repeat('o',50);$employeeToken=str_repeat('e',50);
        $sid=$this->fixture('sellers',['email'=>'owner@example.test','phone'=>'08011111111','status'=>'approved','password'=>Hash::make('Owner123'),'auth_token'=>hash('sha256',$ownerToken),'bank_name'=>'Owner Bank','account_no'=>'0123456789','holder_name'=>'Owner']);
        $rid=$this->fixture('vendor_roles',['seller_id'=>$sid,'name'=>'Staff','module_access'=>'[]','status'=>1]);
        $eid=$this->fixture('vendor_employees',['seller_id'=>$sid,'vendor_role_id'=>$rid,'name'=>'Employee','email'=>'employee@example.test','password'=>Hash::make('Staff123'),'status'=>1,'auth_token'=>hash('sha256',$employeeToken)]);
        $out=$this->withHeader('Authorization','Bearer '.$employeeToken)->getJson('/api/v3/seller/seller-info');$out->assertOk();
        $this->assertStringNotContainsString(hash('sha256',$ownerToken),$out->getContent());$this->assertArrayNotHasKey('auth_token',$out->json());$this->assertArrayNotHasKey('remember_token',$out->json());
        foreach(['bank_name','branch','account_no','holder_name','nin','cac_number','kyc_status','sales_commission_percentage','gst'] as $key)$this->assertArrayNotHasKey($key,$out->json());
        $owner=$this->withHeader('Authorization','Bearer '.$ownerToken)->getJson('/api/v3/seller/seller-info');$owner->assertOk();$this->assertSame('Owner Bank',$owner->json('bank_name'));$this->assertSame('0123456789',$owner->json('account_no'));$this->assertArrayHasKey('image_full_url',$owner->json());$this->assertArrayNotHasKey('auth_token',$owner->json());
        $this->withHeader('Authorization','Bearer '.hash('sha256',$ownerToken))->getJson('/api/v3/seller/seller-info')->assertStatus(401);
        $this->withHeader('Authorization','Bearer '.$employeeToken)->postJson('/api/v3/seller/logout')->assertOk();
        $this->assertNull(DB::table('vendor_employees')->where('id',$eid)->value('auth_token'));$this->assertSame(hash('sha256',$ownerToken),DB::table('sellers')->where('id',$sid)->value('auth_token'));
        $this->withHeader('Authorization','Bearer '.$employeeToken)->getJson('/api/v3/seller/seller-info')->assertStatus(401);
        DB::table('vendor_employees')->where('id',$eid)->update(['auth_token'=>hash('sha256',$employeeToken)]);
        $this->withHeader('Authorization','Bearer '.$ownerToken)->postJson('/api/v3/seller/logout',['vendor_employee'=>['id'=>$eid],'is_vendor_employee'=>true])->assertOk();
        $this->assertNull(DB::table('sellers')->where('id',$sid)->value('auth_token'));$this->assertSame(hash('sha256',$employeeToken),DB::table('vendor_employees')->where('id',$eid)->value('auth_token'));
    }
    public function test_web_customer_and_vendor_finalizers_deny_generic_and_consume_dedicated_credentials():void {
        $u=$this->user();$generic='generic-proof';$this->fixture('phone_or_email_verifications',['phone_or_email'=>$u->phone,'token'=>$generic,'created_at'=>now()]);
        $payload=['identity'=>base64_encode($u->phone),'reset_token'=>$generic,'password'=>'NewPassword123!','confirm_password'=>'NewPassword123!'];
        $this->post('/customer/auth/reset-password',$payload)->assertRedirect();$this->assertTrue(Hash::check('Old Password123',$u->fresh()->password));
        $proof=str_repeat('w',64);app(PasswordResetCredentialService::class)->issue('customer',$u,$u->phone,$proof);
        $this->get('/customer/auth/reset-password?'.http_build_query(['identity'=>base64_encode($u->phone),'token'=>$proof]))->assertOk();
        $this->post('/customer/auth/reset-password',array_replace($payload,['reset_token'=>$proof]))->assertRedirect('/');$this->assertTrue(Hash::check('NewPassword123!',$u->fresh()->password));
        $sid=$this->fixture('sellers',['email'=>'vendor@example.test','phone'=>'08087654321','password'=>Hash::make('OldPassword123!'),'status'=>'approved','auth_token'=>'old']);$seller=\App\Models\Seller::find($sid);
        app(PasswordResetCredentialService::class)->issue('seller',$seller,$seller->phone,$proof);
        $this->get('/vendor/auth/forgot-password/reset-password?'.http_build_query(['token'=>$proof]))->assertOk();
        $this->post('/vendor/auth/forgot-password/reset-password',['reset_token'=>$proof,'password'=>'VendorPassword123!','confirm_password'=>'VendorPassword123!'])->assertRedirect();
        $this->assertTrue(Hash::check('VendorPassword123!',$seller->fresh()->password));$this->assertNull($seller->fresh()->auth_token);
    }
    public function test_firebase_verified_claim_mints_purpose_bound_reset_proof_and_mismatched_claim_fails():void {
        $u=$this->user();Http::fake(['identitytoolkit.googleapis.com/*'=>Http::sequence()->push(['phoneNumber'=>$u->phone],200)->push(['phoneNumber'=>'08000000000'],200)]);
        $out=$this->postJson('/api/v1/auth/firebase-auth-verify',['phoneNumber'=>$u->phone,'sessionInfo'=>'provider-session','code'=>'123456','is_reset_token'=>1]);$out->assertOk();
        $proof=$out->json('reset_token');$this->assertIsString($proof);$this->assertSame(64,strlen($proof));
        $this->assertSame(hash('sha256',$proof),DB::table('password_resets')->where('user_type','customer')->value('token'));
        $this->putJson('/api/v1/auth/reset-password',['identity'=>$u->phone,'otp'=>$proof,'password'=>'Changed123','confirm_password'=>'Changed123'])->assertOk();

        $this->postJson('/api/v1/auth/firebase-auth-verify',['phoneNumber'=>$u->phone,'sessionInfo'=>'provider-session','code'=>'123456','is_reset_token'=>1])->assertStatus(403);
    }

    public function test_http_reset_rejects_wrong_account_and_wrong_purpose_bindings():void {
        $u=$this->user();$payload=['identity'=>$u->phone,'otp'=>'123456','password'=>'Changed123','confirm_password'=>'Changed123'];
        app(PasswordResetCredentialService::class)->issue('customer',$u,$u->phone,'123456');
        DB::table('password_resets')->where('user_type','customer')->update(['purpose'=>'registration']);
        $this->putJson('/api/v1/auth/reset-password',$payload)->assertStatus(403);
        DB::table('password_resets')->where('user_type','customer')->update(['purpose'=>'password_reset','account_id'=>$u->id+1]);
        $this->putJson('/api/v1/auth/reset-password',$payload)->assertStatus(403);
        $this->assertTrue(Hash::check('Old Password123',$u->fresh()->password));
    }
    public function test_authenticated_rider_profile_password_preserves_spaces():void {
        $token=str_repeat('p',50);$id=$this->fixture('delivery_men',['phone'=>'08012345678','email'=>'rider@example.test','password'=>Hash::make('OldPassword123!'),'is_active'=>1,'auth_token'=>hash('sha256',$token)]);
        $this->withHeader('Authorization','Bearer '.$token)->putJson('/api/v2/delivery-man/update-info',['f_name'=>'Rider','l_name'=>'Test','address'=>'Test','password'=>' New Password123! ','confirm_password'=>' New Password123! '])->assertOk();
        $hash=DB::table('delivery_men')->where('id',$id)->value('password');$this->assertTrue(Hash::check(' New Password123! ',$hash));$this->assertFalse(Hash::check('NewPassword123!',$hash));
    }
    public function test_oauth_web_and_api_creation_never_use_public_provider_identity_as_password():void {
        $id='public-provider-id';$email='social@example.test';
        Http::fake(['www.googleapis.com/*'=>Http::response(['id'=>$id,'email'=>$email,'name'=>'Social Customer'],200)]);
        $this->postJson('/api/v1/auth/social-login',['token'=>'verified-provider-token','email'=>$email,'unique_id'=>$id,'medium'=>'google'])->assertOk();
        $u=\App\Models\User::where('email',$email)->firstOrFail();$this->assertFalse(Hash::check($id,$u->password));$this->assertFalse(Hash::check($email,$u->password));
        $social=new \Laravel\Socialite\Two\User();$social->setRaw(['name'=>'Web Social'])->map(['id'=>$id,'email'=>'websocial@example.test','name'=>'Web Social']);
        $driver=\Mockery::mock();$driver->shouldReceive('stateless')->andReturnSelf();$driver->shouldReceive('user')->andReturn($social);
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
        $this->get('/customer/auth/login/google/callback')->assertRedirect();$new=session('social_login_new_customer');$this->assertIsArray($new);$this->assertFalse(Hash::check($id,$new['password']));
        $u->is_active=0;$u->save();$this->postJson('/api/v1/auth/social-login',['token'=>'verified-provider-token','email'=>$email,'unique_id'=>$id,'medium'=>'google'])->assertStatus(403);
        $this->assertSame(0,DB::table('oauth_access_tokens')->where('user_id',$u->id)->count());
        $social->map(['id'=>$id,'email'=>$email,'name'=>'Blocked Social']);$this->withSession(['social_login_new_customer'=>null])->get('/customer/auth/login/google/callback')->assertRedirect(route('home'));$this->assertNull(session('social_login_new_customer'));
    }
    public function test_migration_rotates_only_demonstrably_public_oauth_passwords_and_revokes_sessions():void {
        $migration=require base_path('database/migrations/2026_10_04_000002_bind_password_reset_credentials.php');$migration->down();
        $vulnerable=$this->fixture('users',['email'=>'old-social@example.test','phone'=>'08011111111','social_id'=>'public-old-id','login_medium'=>'google','password'=>Hash::make('public-old-id'),'is_active'=>1,'wallet_balance'=>'123.45']);
        $safe=$this->fixture('users',['email'=>'safe-social@example.test','phone'=>'08022222222','social_id'=>'public-safe-id','login_medium'=>'google','password'=>Hash::make('UserChosen123!'),'is_active'=>1]);
        $this->fixture('oauth_access_tokens',['id'=>'social-access','user_id'=>$vulnerable,'client_id'=>1,'name'=>'Test','scopes'=>'[]','revoked'=>0]);
        $migration->up();
        $rotation=require base_path('database/migrations/2026_10_05_000001_rotate_public_oauth_passwords.php');$rotation->up();$bad=\App\Models\User::find($vulnerable);$good=\App\Models\User::find($safe);
        $this->assertFalse(Hash::check('public-old-id',$bad->password));$this->assertTrue(Hash::check('UserChosen123!',$good->password));$this->assertSame(1,(int)$bad->credential_version);$this->assertSame(0,(int)$good->credential_version);$this->assertEquals(1,DB::table('oauth_access_tokens')->where('id','social-access')->value('revoked'));$this->assertSame('123.45',bcadd((string)$bad->wallet_balance,'0',2));
        // [AI] An installation that already applied the binding migration still receives this independent rotation.
        $installed=$this->fixture('users',['email'=>'installed-social@example.test','phone'=>'08033333333','social_id'=>'installed-public-id','login_medium'=>'google','password'=>Hash::make('installed-public-id'),'is_active'=>1]);
        $this->fixture('oauth_access_tokens',['id'=>'installed-access','user_id'=>$installed,'client_id'=>1,'name'=>'Test','scopes'=>'[]','revoked'=>0]);
        $rotation->up();$existing=\App\Models\User::find($installed);$this->assertFalse(Hash::check('installed-public-id',$existing->password));$this->assertSame(1,(int)$existing->credential_version);$this->assertEquals(1,DB::table('oauth_access_tokens')->where('id','installed-access')->value('revoked'));
        $this->assertSame(1,(int)$bad->fresh()->credential_version);$this->assertTrue(Hash::check('UserChosen123!',$good->fresh()->password));$rotation->down();$this->assertFalse(Hash::check('installed-public-id',$existing->fresh()->password));

    }
    public function test_rider_bearer_digest_is_not_a_credential_and_logout_revokes_raw_token():void {
        $token=str_repeat('r',50);$id=$this->fixture('delivery_men',['phone'=>'08012345678','email'=>'rider@example.test','password'=>Hash::make('Rider123!'),'is_active'=>1,'auth_token'=>hash('sha256',$token)]);
        $this->withHeader('Authorization','Bearer '.hash('sha256',$token))->postJson('/api/v2/delivery-man/logout')->assertStatus(401);
        $this->assertSame(hash('sha256',$token),DB::table('delivery_men')->where('id',$id)->value('auth_token'));
        $this->withHeader('Authorization','Bearer '.$token)->postJson('/api/v2/delivery-man/logout')->assertOk();
        $this->assertSame('',DB::table('delivery_men')->where('id',$id)->value('auth_token'));
        $this->withHeader('Authorization','Bearer '.$token)->postJson('/api/v2/delivery-man/logout')->assertStatus(401);
    }
    public function test_seller_firebase_authoritative_claim_creates_reset_proof_without_caller_store():void {
        $sid=$this->fixture('sellers',['email'=>'seller@example.test','phone'=>'08012345678','password'=>Hash::make('Original123!'),'status'=>'approved']);
        Http::fake(['identitytoolkit.googleapis.com/*'=>Http::sequence()->push(['phoneNumber'=>'08012345678'],200)->push(['phoneNumber'=>'08000000000'],200)]);
        $payload=['phoneNumber'=>'08012345678','sessionInfo'=>'provider-session','code'=>'123456'];
        $out=$this->postJson('/api/v3/seller/auth/firebase-auth-verify',$payload);$out->assertOk();$proof=$out->json('reset_token');$this->assertSame(64,strlen($proof));
        $this->putJson('/api/v3/seller/auth/reset-password',['identity'=>'08012345678','otp'=>$proof,'password'=>'Changed123!','confirm_password'=>'Changed123!'])->assertOk();
        $this->assertTrue(Hash::check('Changed123!',\App\Models\Seller::find($sid)->password));
        $this->postJson('/api/v3/seller/auth/firebase-auth-verify',$payload)->assertStatus(403);$this->assertSame(0,DB::table('password_resets')->where('user_type','seller')->count());
    }
    public function test_credential_migration_invalidates_existing_secrets_without_restoring_them_or_changing_money():void {
        $migration=require base_path('database/migrations/2026_10_04_000002_bind_password_reset_credentials.php');$migration->down();
        $sid=$this->fixture('sellers',['auth_token'=>'old-owner']);$this->fixture('vendor_employees',['seller_id'=>$sid,'auth_token'=>'old-employee']);$this->fixture('delivery_men',['auth_token'=>'old-rider']);
        $this->fixture('password_resets',['identity'=>'old@example.test','token'=>'old-secret','user_type'=>'seller','created_at'=>now()]);
        $this->fixture('seller_wallets',['seller_id'=>$sid,'total_earning'=>'100.25']);
        $migration->up();$this->assertSame(0,DB::table('password_resets')->count());
        $this->assertNull(DB::table('sellers')->where('id',$sid)->value('auth_token'));$this->assertNull(DB::table('vendor_employees')->where('seller_id',$sid)->value('auth_token'));$this->assertSame('',DB::table('delivery_men')->value('auth_token'));
        $this->assertSame('100.25',bcadd((string)DB::table('seller_wallets')->where('seller_id',$sid)->value('total_earning'),'0',2));
        $migration->down();$this->assertNull(DB::table('sellers')->where('id',$sid)->value('auth_token'));$this->assertFalse(Schema::hasColumn('password_resets','account_id'));
    }

    public function test_verified_firebase_cannot_issue_reset_or_login_credentials_for_suspended_customer():void {
        $u=$this->user();$u->is_active=0;$u->save();Http::fake(['identitytoolkit.googleapis.com/*'=>Http::response(['phoneNumber'=>$u->phone],200)]);
        foreach([0,1] as $reset)$this->postJson('/api/v1/auth/firebase-auth-verify',['phoneNumber'=>$u->phone,'sessionInfo'=>'provider-session','code'=>'123456','is_reset_token'=>$reset])->assertStatus(403);
        $this->assertSame(0,DB::table('password_resets')->count());$this->assertSame(0,DB::table('oauth_access_tokens')->where('user_id',$u->id)->count());
    }
    public function test_existing_customer_web_session_is_revoked_independently_of_session_storage():void {
        $u=$this->user();$this->actingAs($u,'customer')->withSession(['credential_version_customer'=>0]);
        app(PasswordResetCredentialService::class)->issue('customer',$u,$u->phone,'123456');
        $this->assertTrue(app(PasswordResetCredentialService::class)->consume('customer',$u->phone,'123456','NewPassword123!'));
        $this->get('/account-oder')->assertRedirect(route('customer.auth.login'));$this->assertGuest('customer');
        $this->withSession(['default_recaptcha_id_customer_auth'=>'testcaptcha'])->post('/customer/auth/login',['default_captcha_value'=>'testcaptcha','user_identity'=>$u->phone,'password'=>'NewPassword123!','login_type'=>'manual-login'])->assertRedirect();
        $this->assertAuthenticated('customer');$this->assertSame(1,(int)session('credential_version_customer'));
    }

    public function test_existing_seller_web_session_is_revoked_and_actual_new_login_stamps_current_version():void {
        $id=$this->fixture('sellers',['email'=>'vendor@example.test','phone'=>'08012345678','status'=>'approved','password'=>Hash::make('OldPassword123!')]);
        $this->fixture('seller_wallets',['seller_id'=>$id]);$seller=\App\Models\Seller::find($id);
        $this->actingAs($seller,'seller')->withSession(['credential_version_seller'=>0]);
        app(PasswordResetCredentialService::class)->issue('seller',$seller,$seller->email,str_repeat('s',64));
        $this->assertTrue(app(PasswordResetCredentialService::class)->consume('seller',$seller->email,str_repeat('s',64),'NewPassword123!'));
        $this->get('/vendor/dashboard')->assertRedirect();$this->assertGuest('seller');
        $this->withSession([\App\Enums\SessionKey::VENDOR_RECAPTCHA_KEY=>'testcaptcha'])->post('/vendor/auth/login',['default_captcha_value'=>'testcaptcha','email'=>$seller->email,'password'=>'NewPassword123!'])->assertRedirect(route('vendor.dashboard.index'));
        $this->assertAuthenticated('seller');$this->assertSame(1,(int)session('credential_version_seller'));
    }
}
