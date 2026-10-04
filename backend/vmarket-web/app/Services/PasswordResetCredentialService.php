<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;

/** [AI] Password-reset credentials are server-issued, account/purpose-bound, expiring and consumed atomically. */
class PasswordResetCredentialService
{
    private function model(string $type): string
    {
        return match ($type) { 'customer'=>\App\Models\User::class, 'seller'=>\App\Models\Seller::class,
            'delivery_man'=>\App\Models\DeliveryMan::class, default=>throw new \InvalidArgumentException('Invalid reset principal.') };
    }
    private function account(string $type, string $identity): ?object
    {
        $class=$this->model($type);
        $accounts=$class::where(fn($q)=>$q->where('phone',$identity)->orWhere('email',$identity))->lockForUpdate()->get();
        return $accounts->count()===1 ? $accounts->first() : null;
    }
    public function issue(string $type, object $account, string $identity, string $token): void
    {
        DB::transaction(function()use($type,$account,$identity,$token){
            $class=$this->model($type);$locked=$class::whereKey($account->id)->lockForUpdate()->firstOrFail();
            if (!in_array($identity,[$locked->phone,$locked->email],true))throw new \RuntimeException('Reset identity does not belong to account.');
            $prior=DB::table('password_resets')->where('user_type',$type)->where('account_id',$locked->id)->lockForUpdate()->first();
            if ($prior && $prior->reset_blocked_until && Carbon::parse($prior->reset_blocked_until)->gt(now())) {
                throw \Illuminate\Validation\ValidationException::withMessages(['identity'=>'Too many reset attempts. Request a new credential after the cooldown.']);
            }
            DB::table('password_resets')->where('user_type',$type)->where('account_id',$locked->id)->delete();
            DB::table('password_resets')->insert(['identity'=>$identity,'user_type'=>$type,'account_id'=>$locked->id,
                'purpose'=>'password_reset','token'=>hash('sha256',$token),'created_at'=>now(),'updated_at'=>now()]);
        });
    }
    public function verify(string $type,string $identity,string $token): bool
    {
        return DB::transaction(function()use($type,$identity,$token){
            $account=$this->account($type,$identity);if(!$account)return false;
            $row=DB::table('password_resets')->where('user_type',$type)->where('identity',$identity)
                ->where('account_id',$account->id)->where('purpose','password_reset')->lockForUpdate()->first();
            if (!$row || !$row->created_at) return false;
            if ($row->reset_blocked_until && Carbon::parse($row->reset_blocked_until)->gt(now())) return false;
            $ok = preg_match('/^(?:[0-9]{6}|[A-Za-z0-9]{40,120})$/D', $token)
                && hash_equals((string)$row->token,hash('sha256',$token))
                && Carbon::parse($row->created_at)->lte(now()) && Carbon::parse($row->created_at)->addMinutes(15)->gt(now());
            if (!$ok) {
                $attempts=(int)$row->reset_attempts+1;
                DB::table('password_resets')->where('id',$row->id)->update(['reset_attempts'=>$attempts,
                    'reset_blocked_until'=>$attempts>=5 ? now()->addMinutes(10) : null]);
            }
            return (bool)$ok;
        });
    }
    public function consume(string $type,string $identity,string $token,string $password): bool
    {
        return DB::transaction(function()use($type,$identity,$token,$password){
            $account=$this->account($type,$identity);if(!$account || !$this->verify($type,$identity,$token))return false;
            $account->password=Hash::make($password); // Spaces are part of the chosen password.
            if(Schema::hasColumn($account->getTable(),'auth_token'))$account->auth_token=$type==='delivery_man' ? '' : null;
            if(Schema::hasColumn($account->getTable(),'temporary_token'))$account->temporary_token=null;
            if(Schema::hasColumn($account->getTable(),'remember_token'))$account->remember_token=null;
            if(Schema::hasColumn($account->getTable(),'credential_version'))$account->credential_version=(int)$account->getRawOriginal('credential_version')+1;
            $account->save();
            if($type==='customer' && Schema::hasTable('oauth_access_tokens')){
                $ids=DB::table('oauth_access_tokens')->where('user_id',$account->id)->pluck('id');
                if(Schema::hasTable('oauth_refresh_tokens'))DB::table('oauth_refresh_tokens')->whereIn('access_token_id',$ids)->update(['revoked'=>1]);
                DB::table('oauth_access_tokens')->whereIn('id',$ids)->update(['revoked'=>1]);
            }
            if($type==='customer' && Schema::hasTable('sessions') && Schema::hasColumn('sessions','user_id'))DB::table('sessions')->where('user_id',$account->id)->delete();
            DB::table('password_resets')->where('user_type',$type)->where('account_id',$account->id)->delete();
            return true;
        });
    }
}
