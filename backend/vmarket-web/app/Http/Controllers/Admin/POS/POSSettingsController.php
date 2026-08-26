<?php

namespace App\Http\Controllers\Admin\POS;

use App\Http\Controllers\BaseController;
use App\Models\BusinessSetting;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * [AI] Class POSSettingsController
 * Dynamic configuration deck for POS Free Limits, Pricing, and Receipt Footer Branding.
 */
class POSSettingsController extends BaseController
{
    public function index(): View
    {
        $freeBranchLimit = (int)(json_decode(getWebConfig(name: 'pos_free_branch_limit'), true) ?? 1);
        $monthlyPrice = (float)(json_decode(getWebConfig(name: 'pos_multi_branch_monthly_price'), true) ?? 15000);
        $annualPrice = (float)(json_decode(getWebConfig(name: 'pos_multi_branch_annual_price'), true) ?? 150000);
        $trialDays = (int)(json_decode(getWebConfig(name: 'pos_trial_days'), true) ?? 14);
        $receiptFooter = json_decode(getWebConfig(name: 'pos_receipt_footer_text'), true) ?? 'Powered by Victorious MARKET - Your Trusted Online Market';
        $reorderQrStatus = (int)(json_decode(getWebConfig(name: 'pos_reorder_qr_status'), true) ?? 1);

        return view('admin-views.pos.settings', compact(
            'freeBranchLimit',
            'monthlyPrice',
            'annualPrice',
            'trialDays',
            'receiptFooter',
            'reorderQrStatus'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'pos_free_branch_limit' => 'required|integer|min:1',
            'pos_multi_branch_monthly_price' => 'required|numeric|min:0',
            'pos_multi_branch_annual_price' => 'required|numeric|min:0',
            'pos_trial_days' => 'required|integer|min:0',
            'pos_receipt_footer_text' => 'required|string|max:255',
        ]);

        $settings = [
            'pos_free_branch_limit' => (int)$request->pos_free_branch_limit,
            'pos_multi_branch_monthly_price' => (float)$request->pos_multi_branch_monthly_price,
            'pos_multi_branch_annual_price' => (float)$request->pos_multi_branch_annual_price,
            'pos_trial_days' => (int)$request->pos_trial_days,
            'pos_receipt_footer_text' => $request->pos_receipt_footer_text,
            'pos_reorder_qr_status' => $request->has('pos_reorder_qr_status') ? 1 : 0,
        ];

        foreach ($settings as $key => $val) {
            BusinessSetting::updateOrInsert(
                ['type' => $key],
                ['value' => json_encode($val), 'updated_at' => now()]
            );
        }

        clearWebConfigCacheKeys();
        ToastMagic::success(translate('POS_settings_updated_successfully'));
        return back();
    }
}
