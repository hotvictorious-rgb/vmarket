<?php

namespace App\Http\Controllers\Admin\POS;

use App\Http\Controllers\BaseController;
use App\Models\PosCustomerLedger;
use App\Models\PosSubscription;
use App\Models\PosTransfer;
use App\Models\Seller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * [AI] Class AdminPOSDashboardController
 * Central Super Admin Command Center for POS metrics, SaaS subscriptions, and Waybill theft tracking.
 */
class AdminPOSDashboardController extends BaseController
{
    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse|JsonResponse
    {
        $totalMerchants = Seller::count();
        $posOnlyCount = Seller::where('marketplace_status', 'pos_only')->count();
        $pendingMarketplaceCount = Seller::where('marketplace_status', 'pending_approval')->count();
        $approvedMarketplaceCount = Seller::where('marketplace_status', 'approved')->count();

        $activeSubscriptions = PosSubscription::where('status', 'active')->count();
        $monthlySubPrice = (float)(json_decode(getWebConfig(name: 'pos_multi_branch_monthly_price'), true) ?? 15000);
        $estimatedMRR = $activeSubscriptions * $monthlySubPrice;

        $inTransitWaybills = PosTransfer::whereIn('status', ['dispatched', 'in_transit'])->count();
        $varianceTheftFlagged = PosTransfer::where('status', 'variance_flagged')->count();

        $recentTransfers = PosTransfer::with(['seller.shop', 'originBranch', 'destinationBranch'])
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        $pendingSubscriptions = PosSubscription::with('seller.shop')
            ->where('status', 'pending_verification')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin-views.pos.dashboard', compact(
            'totalMerchants',
            'posOnlyCount',
            'pendingMarketplaceCount',
            'approvedMarketplaceCount',
            'activeSubscriptions',
            'estimatedMRR',
            'inTransitWaybills',
            'varianceTheftFlagged',
            'recentTransfers',
            'pendingSubscriptions'
        ));
    }
}
