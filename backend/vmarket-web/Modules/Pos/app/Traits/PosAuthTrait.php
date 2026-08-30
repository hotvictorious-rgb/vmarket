<?php

namespace Modules\Pos\app\Traits;

use App\Models\Seller;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;

/**
 * [AI] PosAuthTrait — Unified Multi-Guard Authentication Resolver for POS Module.
 * Seamlessly resolves seller tenant context for:
 * 1. Super Admin (auth:admin) — Managing In-House POS, demo stores, or inspecting merchant POS
 * 2. Store Owner (auth:seller) — Verified & Unverified merchants
 * 3. Cashier Staff (auth:vendor_employee) — Assigned store cashiers
 */
trait PosAuthTrait
{
    /**
     * Resolve the active seller ID across all authenticated ecosystem guards.
     */
    protected function resolveAuthSellerId(): int
    {
        if (Auth::guard('vendor_employee')->check()) {
            return (int) Auth::guard('vendor_employee')->user()->seller_id;
        }

        if (Auth::guard('admin')->check()) {
            $selectedId = session('pos_active_seller_id');
            if ($selectedId && Seller::find($selectedId)) {
                return (int) $selectedId;
            }
            $defaultSeller = Seller::where('status', 'approved')->first() ?? Seller::first();
            if ($defaultSeller) {
                return (int) $defaultSeller->id;
            }
            return 1;
        }

        return (int) (Auth::guard('seller')->id() ?? 1);
    }

    /**
     * Resolve the active branch / shop ID for the current session.
     */
    protected function resolveActiveBranchId($request): int
    {
        $sellerId = $this->resolveAuthSellerId();
        $fallbackBranch = Shop::where('seller_id', $sellerId)->where('temporary_close', 0)->first();
        return (int) $request->get(
            'warehouse_id',
            session('pos_active_branch_id', $fallbackBranch?->id ?? 0)
        );
    }
}
