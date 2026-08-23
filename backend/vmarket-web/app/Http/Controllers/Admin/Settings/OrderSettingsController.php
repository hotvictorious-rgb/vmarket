<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Contracts\Repositories\BusinessSettingRepositoryInterface;
use App\Http\Controllers\BaseController;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderSettingsController extends BaseController
{

    public function __construct(
        private readonly BusinessSettingRepositoryInterface $businessSettingRepo,
    )
    {
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View Index function is the starting point of a controller
     * Index function is the starting point of a controller
     */
    public function index(Request|null $request, ?string $type = null): View
    {
        return view('admin-views.business-settings.order-settings.index');
    }


    public function update(Request $request): RedirectResponse
    {
        $this->businessSettingRepo->updateOrInsert(type: 'billing_input_by_customer', value: $request->get('billing_input_by_customer', 0));
        $this->businessSettingRepo->updateOrInsert(type: 'minimum_order_amount_status', value: $request->get('minimum_order_amount_status', 0));
        $this->businessSettingRepo->updateOrInsert(type: 'order_verification', value: $request->get('order_verification', 0));
        $this->businessSettingRepo->updateOrInsert(type: 'free_delivery_status', value: $request->get('free_delivery_status', 0));
        $this->businessSettingRepo->updateOrInsert(type: 'free_delivery_responsibility', value: $request['free_delivery_responsibility']);
        $this->businessSettingRepo->updateOrInsert(type: 'free_delivery_over_amount_seller', value: currencyConverter(amount: $request['free_delivery_over_amount_seller']) ?? 0);
        $this->businessSettingRepo->updateOrInsert(type: 'pod_dispatch_fee_status', value: $request->get('pod_dispatch_fee_status', 0));
        $this->businessSettingRepo->updateOrInsert(type: 'pod_dispatch_fee_amount', value: currencyConverter(amount: (float)$request->get('pod_dispatch_fee_amount', 1000.00)) ?? 1000.00);
        $this->businessSettingRepo->updateOrInsert(type: 'pod_free_delivery_prepaid_only', value: $request->get('pod_free_delivery_prepaid_only', 0));
        clearWebConfigCacheKeys();
        ToastMagic::success(translate('successfully_updated'));
        return back();
    }


}
