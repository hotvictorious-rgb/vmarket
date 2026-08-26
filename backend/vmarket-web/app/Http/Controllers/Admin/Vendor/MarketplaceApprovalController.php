<?php

namespace App\Http\Controllers\Admin\Vendor;

use App\Http\Controllers\BaseController;
use App\Models\Seller;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * [AI] Class MarketplaceApprovalController
 * Manages vendor applications to activate online marketplace selling on Victorious Market.
 */
class MarketplaceApprovalController extends BaseController
{
    public function index(Request $request): View
    {
        $status = $request->get('status', 'all');

        $vendors = Seller::with(['shop.deliveryCity', 'shop.deliveryHub'])
            ->when($status !== 'all', function ($query) use ($status) {
                return $query->where('marketplace_status', $status);
            })
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin-views.vendor.marketplace-applications', compact('vendors', 'status'));
    }

    public function approve(int $id): RedirectResponse
    {
        $seller = Seller::findOrFail($id);
        $seller->marketplace_status = 'approved';
        $seller->marketplace_approved_at = now();
        $seller->save();

        ToastMagic::success(translate('Vendor_approved_for_online_marketplace_selling'));
        return back();
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $seller = Seller::findOrFail($id);
        $seller->marketplace_status = 'pos_only';
        $seller->save();

        ToastMagic::info(translate('Marketplace_application_set_to_POS_Only'));
        return back();
    }
}
