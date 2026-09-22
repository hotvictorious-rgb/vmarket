<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string[] ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        foreach ($guards as $guard) {
            $user = auth($guard)->user();
            if ($user && isset($user->is_active) && $user->is_active != 1) {
                if ($request->expectsJson() || $guard === 'api') {
                    return response()->json([
                        'message' => translate('Your account has been suspended or deactivated.'),
                        'status' => 'inactive'
                    ], 403);
                }
                auth($guard)->logout();
                return redirect()->route('customer.auth.login');
            }
        }

        return $next($request);
    }
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param Request $request
     * @return string|null
     */
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            return route('authentication-failed');
        }
        return null;
    }
}
