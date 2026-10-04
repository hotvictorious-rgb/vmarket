<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

/** [AI] Revocation works for file, Redis and database sessions without scanning private session stores. */
class PasswordSessionVersion
{
    public function handle(Request $request,Closure $next):mixed
    {
        foreach(['customer','seller'] as $name){
            $guard=auth($name);$user=$guard->user();
            if(!$user)continue;
            $current=(int)$user->newQuery()->whereKey($user->id)->value('credential_version');
            $saved=(int)$request->session()->get('credential_version_'.$name,0);
            if($saved!==$current){$guard->logout();$request->session()->forget('credential_version_'.$name);}
        }
        return $next($request);
    }
}
