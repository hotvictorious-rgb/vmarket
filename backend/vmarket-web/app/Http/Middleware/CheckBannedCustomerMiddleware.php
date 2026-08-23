<?php

namespace App\Http\Middleware;

use App\Models\BlacklistedCustomer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBannedCustomerMiddleware
{
    /**
     * [AI] Intercepts requests to check if user, phone, email, or IP is blacklisted.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('customer');
        $phone = $user?->phone ?? $request->input('phone') ?? $request->input('contact_phone');
        $email = $user?->email ?? $request->input('email');
        $ip = $request->ip();

        // 1. Check if user is inactive in database
        if ($user && $user->is_active == 0) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account has been suspended for violating marketplace security policies.',
                ], 403);
            }

            auth('customer')->logout();
            return redirect()->route('customer.auth.login')->withErrors([
                'account' => 'Your account has been suspended for violating marketplace security policies.',
            ]);
        }

        // 2. Check BlacklistedCustomer Table
        if (BlacklistedCustomer::isBlacklisted($phone, $email, $ip)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Access denied. This device or account has been restricted by security.',
                ], 403);
            }

            return redirect()->route('home')->withErrors([
                'account' => 'Access denied. This device or account has been restricted by security.',
            ]);
        }

        return $next($request);
    }
}
