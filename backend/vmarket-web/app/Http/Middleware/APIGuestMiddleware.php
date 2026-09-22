<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class APIGuestMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->header('Authorization') && app('auth')->guard('api')) {
            $user = auth('api')->user();
            if ($user && isset($user->is_active) && $user->is_active != 1) {
                return response()->json([
                    'message' => translate('Your account has been suspended or deactivated.'),
                    'status' => 'inactive'
                ], 403);
            }
            $request->merge(['user' => $user]);
            return $next($request);
        } elseif ($request->guest_id) {
            return $next($request);
        }

        return response()->json(['Unauthorized', 401]);
    }
}
