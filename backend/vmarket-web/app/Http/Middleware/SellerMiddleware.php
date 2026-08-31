<?php

namespace App\Http\Middleware;

use Closure;
use App\Utils\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class SellerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (auth('seller')->check()) {
            $seller = auth('seller')->user();
            if ($seller && $seller->status === 'approved') {
                return $next($request);
            }
            if ($seller && $seller->status !== 'suspended') {
                ToastMagic::info(translate('Your Online Marketplace store is undergoing KYC review. Your In-Store Free POS is active!'));
                return redirect()->route('pos.dashboard');
            }
            auth()->guard('seller')->logout();
        }

        return redirect()->route('vendor.auth.login');
    }
}
