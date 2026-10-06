<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Contracts\Repositories\BusinessSettingRepositoryInterface;
use App\Http\Controllers\BaseController;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeliverymanSettingsController extends BaseController
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
        $data = $this->businessSettingRepo->getFirstWhere(params: ['type' => 'upload_picture_on_delivery']);
        return view('admin-views.business-settings.delivery-man-settings.index', compact('data'));
    }

    public function update(Request $request): RedirectResponse
    {
        if ($request->has('deliveryman_forgot_password_method')) {
            $this->businessSettingRepo->updateOrInsert(type: 'deliveryman_forgot_password_method', value: $request->get('deliveryman_forgot_password_method', 'phone'));
        }
        if ($request->has('delivery_commission_percentage')) {
            $this->businessSettingRepo->updateOrInsert(type: 'delivery_commission_percentage', value: max(0, min(100, (float)$request->get('delivery_commission_percentage', 15))));
        }
        if ($request->has('bulky_cargo_surcharge')) {
            $this->businessSettingRepo->updateOrInsert(type: 'bulky_cargo_surcharge', value: max(0, (float)$request->get('bulky_cargo_surcharge', 2500)));
        }
        if ($request->has('enable_logistics_company_module')) {
            $this->businessSettingRepo->updateOrInsert(type: 'enable_logistics_company_module', value: $request->get('enable_logistics_company_module', 1));
        }

        clearWebConfigCacheKeys();
        ToastMagic::success(translate('Updated_successfully'));
        return redirect()->back();
    }
    public function uploadPicture(Request $request): RedirectResponse
    {
        $this->businessSettingRepo->updateOrInsert(type: 'upload_picture_on_delivery', value: $request->get('upload_picture_on_delivery', 0));
        clearWebConfigCacheKeys();
        ToastMagic::success(translate('Updated_successfully'));
        return redirect()->back();
    }

}
