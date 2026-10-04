<?php
namespace App\Services;
class PasswordResetService
{
    public function getAddData(string|int $identity,string $token,string $userType):array
    {
        $class=match($userType){'seller'=>\App\Models\Seller::class,'customer'=>\App\Models\User::class,default=>throw new \InvalidArgumentException('Unsupported reset actor.')};
        $accounts=$class::where(fn($q)=>$q->where('phone',$identity)->orWhere('email',$identity))->get();
        if($accounts->count()!==1)throw new \RuntimeException('Ambiguous reset account.');
        // [AI] Firebase session identifiers await provider verification and are never reset credentials.
        $pending=!str_contains((string)$identity,'@') && !preg_match('/^[0-9]{6}$/D',$token);
        return ['identity'=>$identity,'token'=>$pending ? $token : hash('sha256',$token),'user_type'=>$userType,
            'account_id'=>$accounts->first()->id,'purpose'=>$pending ? 'firebase_pending' : 'password_reset','created_at'=>now(),'updated_at'=>now()];
    }
}
