<?php

namespace App\Http\Controllers\Vendor\Subscription;

use App\Http\Controllers\BaseController;
use App\Models\OfflinePayment;
use App\Models\PosSubscription;
use App\Models\Seller;
use App\Models\SellerWallet;
use App\Utils\BackEndHelper;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * [AI] Class POSSubscriptionController
 * Handles Multi-Branch Pro upgrades via Paystack, Offline Payment, or Vendor Wallet.
 */
class POSSubscriptionController extends BaseController
{
    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse|JsonResponse
    {
        $sellerId = auth('seller')->id();
        $seller = Seller::with(['posSubscriptions', 'shop'])->findOrFail($sellerId);
        $activeSub = PosSubscription::where('seller_id', $sellerId)
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->first();

        $freeLimit = (int)(json_decode(getWebConfig(name: 'pos_free_branch_limit'), true) ?? 1);
        $monthlyPrice = (float)(json_decode(getWebConfig(name: 'pos_multi_branch_monthly_price'), true) ?? 15000);
        $annualPrice = (float)(json_decode(getWebConfig(name: 'pos_multi_branch_annual_price'), true) ?? 150000);
        $trialDays = (int)(json_decode(getWebConfig(name: 'pos_trial_days'), true) ?? 14);

        $wallet = SellerWallet::where('seller_id', $sellerId)->first();
        $walletBalance = $wallet ? $wallet->total_earning : 0.00;

        return view('vendor-views.subscription.index', compact(
            'seller',
            'activeSub',
            'freeLimit',
            'monthlyPrice',
            'annualPrice',
            'trialDays',
            'walletBalance'
        ));
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_type' => 'required|string|in:multi_branch_pro',
            'billing_cycle' => 'required|string|in:monthly,yearly',
            'payment_method' => 'required|string|in:wallet,paystack,offline_payment',
        ]);

        $sellerId = auth('seller')->id();
        $monthlyPrice = (float)(json_decode(getWebConfig(name: 'pos_multi_branch_monthly_price'), true) ?? 15000);
        $annualPrice = (float)(json_decode(getWebConfig(name: 'pos_multi_branch_annual_price'), true) ?? 150000);

        $price = $request->billing_cycle === 'yearly' ? $annualPrice : $monthlyPrice;
        $durationMonths = $request->billing_cycle === 'yearly' ? 12 : 1;

        if ($request->payment_method === 'wallet') {
            $wallet = SellerWallet::where('seller_id', $sellerId)->lockForUpdate()->first();
            $priceInUsd = BackEndHelper::currency_to_usd($price);

            if (!$wallet || $wallet->total_earning < $priceInUsd) {
                ToastMagic::error(translate('Insufficient_wallet_balance_for_subscription'));
                return back();
            }

            DB::transaction(function () use ($wallet, $priceInUsd, $sellerId, $request, $price, $durationMonths) {
                $wallet->total_earning -= $priceInUsd;
                $wallet->save();

                PosSubscription::create([
                    'seller_id' => $sellerId,
                    'plan_type' => 'multi_branch_pro',
                    'billing_cycle' => $request->billing_cycle,
                    'price_paid' => $price,
                    'payment_method' => 'wallet',
                    'status' => 'active',
                    'started_at' => now(),
                    'expires_at' => now()->addMonths($durationMonths),
                ]);
            });

            ToastMagic::success(translate('Multi_Branch_Pro_subscription_activated_successfully!'));
            return back();
        }

        if ($request->payment_method === 'offline_payment') {
            $request->validate([
                'payment_note' => 'nullable|string',
            ]);

            PosSubscription::create([
                'seller_id' => $sellerId,
                'plan_type' => 'multi_branch_pro',
                'billing_cycle' => $request->billing_cycle,
                'price_paid' => $price,
                'payment_method' => 'offline_payment',
                'status' => 'pending_verification',
                'started_at' => null,
                'expires_at' => null,
            ]);

            ToastMagic::info(translate('Offline_subscription_submitted_awaiting_Admin_verification'));
            return back();
        }

        // Paystack Gateway Redirect or Webhook Trigger
        ToastMagic::info(translate('Redirecting_to_secure_Paystack_checkout...'));
        return redirect()->route('vendor.subscription.index');
    }

    public function applyMarketplace(Request $request): RedirectResponse
    {
        $request->validate([
            'bank_name' => 'required|string',
            'account_no' => 'required|string|size:10',
            'holder_name' => 'required|string',
        ]);

        $sellerId = auth('seller')->id();
        $seller = Seller::findOrFail($sellerId);

        $seller->bank_name = $request->bank_name;
        $seller->account_no = $request->account_no;
        $seller->holder_name = $request->holder_name;
        $seller->marketplace_status = 'pending_approval';
        $seller->marketplace_applied_at = now();
        $seller->save();

        ToastMagic::success(translate('Marketplace_application_submitted_successfully._Awaiting_Admin_approval.'));
        return back();
    }
}
