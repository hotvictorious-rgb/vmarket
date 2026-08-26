<?php

namespace App\Http\Controllers\Admin\Vendor;

use App\Http\Controllers\BaseController;
use App\Models\Seller;
use App\Utils\Helpers;
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
        $seller = Seller::with('shop')->findOrFail($id);
        $seller->marketplace_status = 'approved';
        $seller->marketplace_approved_at = now();
        $seller->save();

        // [AI] Dispatch real-time push notification to merchant on marketplace approval
        if (!empty($seller->cm_firebase_token)) {
            $shopName = $seller->shop ? $seller->shop->name : 'Your Store';
            $notifData = [
                'title' => translate('🎉 Marketplace Store Approved!'),
                'description' => translate('Congratulations! ') . $shopName . translate(' is now APPROVED for the Victorious MARKET online marketplace. Your catalog is live!'),
                'image' => '',
                'order_id' => '',
                'type' => 'marketplace_approved',
            ];
            Helpers::send_push_notif_to_device($seller->cm_firebase_token, $notifData);
        }

        ToastMagic::success(translate('Vendor_approved_for_online_marketplace_selling'));
        return back();
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $seller = Seller::with('shop')->findOrFail($id);
        $seller->marketplace_status = 'pos_only';
        $seller->save();

        // [AI] Dispatch real-time notification on application status update
        if (!empty($seller->cm_firebase_token)) {
            $notifData = [
                'title' => translate('Marketplace Application Update'),
                'description' => translate('Your store has been set to POS Only. Contact Super Admin to complete marketplace KYC verification.'),
                'image' => '',
                'order_id' => '',
                'type' => 'marketplace_pos_only',
            ];
            Helpers::send_push_notif_to_device($seller->cm_firebase_token, $notifData);
        }

        ToastMagic::info(translate('Marketplace_application_set_to_POS_Only'));
        return back();
    }
}
