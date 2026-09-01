<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Contracts\Repositories\AdminRepositoryInterface;
use Exception;
use App\Enums\SessionKey;
use Illuminate\Http\Request;
use App\Services\ShopService;
use App\Services\VendorService;
use Illuminate\Http\JsonResponse;
use App\Services\RecaptchaService;
use App\Traits\EmailTemplateTrait;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Events\VendorRegistrationEvent;
use App\Http\Controllers\BaseController;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests\Vendor\VendorAddRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Contracts\Repositories\ShopRepositoryInterface;
use App\Repositories\VendorRegistrationReasonRepository;
use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Contracts\Repositories\HelpTopicRepositoryInterface;
use App\Contracts\Repositories\VendorWalletRepositoryInterface;
use App\Contracts\Repositories\EmailTemplatesRepositoryInterface;
use App\Contracts\Repositories\BusinessSettingRepositoryInterface;

class RegisterController extends BaseController
{
    use EmailTemplateTrait;

    public function __construct(
        private readonly VendorRepositoryInterface          $vendorRepo,
        private readonly AdminRepositoryInterface           $adminRepo,
        private readonly VendorWalletRepositoryInterface    $vendorWalletRepo,
        private readonly ShopRepositoryInterface            $shopRepo,
        private readonly VendorService                      $vendorService,
        private readonly ShopService                        $shopService,
        private readonly EmailTemplatesRepositoryInterface  $emailTemplatesRepo,
        private readonly BusinessSettingRepositoryInterface $businessSettingRepo,
        private readonly HelpTopicRepositoryInterface       $helpTopicRepo,
        private readonly VendorRegistrationReasonRepository $vendorRegistrationReasonRepo,
    )
    {
    }

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $businessMode = getWebConfig(name: 'business_mode');
        $vendorRegistration = getWebConfig(name: 'seller_registration');
        if ((isset($businessMode) && $businessMode == 'single') || (isset($vendorRegistration) && $vendorRegistration == 0)) {
            ToastMagic::warning(translate('access_denied') . '!!');
            return redirect('/');
        }
        $headerRecord = $this->businessSettingRepo->getFirstWhere(params: ['type' => 'vendor_registration_header']);
        $vendorRegistrationHeader = $headerRecord && !empty($headerRecord['value']) ? json_decode($headerRecord['value']) : null;

        $vendorRegistrationReasons = $this->vendorRegistrationReasonRepo->getListWhere(orderBy: ['priority' => 'desc'], filters: ['status' => 1], dataLimit: 'all') ?: [];

        $sellRecord = $this->businessSettingRepo->getFirstWhere(params: ['type' => 'vendor_registration_sell_with_us']);
        $sellWithUs = $sellRecord && !empty($sellRecord['value']) ? json_decode($sellRecord['value']) : null;

        $downloadRecord = $this->businessSettingRepo->getFirstWhere(params: ['type' => 'download_vendor_app']);
        $downloadVendorApp = $downloadRecord && !empty($downloadRecord['value']) ? json_decode($downloadRecord['value']) : null;

        $processRecord = $this->businessSettingRepo->getFirstWhere(params: ['type' => 'business_process_main_section']);
        $businessProcess = $processRecord && !empty($processRecord['value']) ? json_decode($processRecord['value']) : null;

        $stepRecord = $this->businessSettingRepo->getFirstWhere(params: ['type' => 'business_process_step']);
        $businessProcessStep = $stepRecord && !empty($stepRecord['value']) ? json_decode($stepRecord['value']) : null;

        $helpTopics = $this->helpTopicRepo->getListWhere(
            orderBy: ['id' => 'desc'],
            filters: ['type' => 'vendor_registration', 'status' => '1'],
            dataLimit: 'all') ?: [];

        return view(VIEW_FILE_NAMES['seller_registration'], compact('vendorRegistrationHeader', 'vendorRegistrationReasons', 'sellWithUs', 'downloadVendorApp', 'helpTopics', 'businessProcess', 'businessProcessStep'));
    }

    public function add(VendorAddRequest $request): JsonResponse
    {
        $result = RecaptchaService::verificationStatus(request: $request, session: SessionKey::VENDOR_RECAPTCHA_KEY, action: "register");
        if ($result && !$result['status']) {
            if ($request->ajax()) {
                return response()->json([
                    'error' => $result['message'],
                ]);
            }
        }
        $adminEmail = $this->adminRepo->getFirstWhere(['admin_role_id' => 1]);
        if ($adminEmail && isset($adminEmail['email']) && $request['email'] === $adminEmail['email']) {
            return response()->json([
                'error' => translate('Email_already_exist_please_try_another_email'),
            ]);
        }
        $vendor = $this->vendorRepo->add(data: $this->vendorService->getAddData($request));
        $this->shopRepo->add($this->shopService->getAddShopDataForRegistration(request: $request, vendorId: $vendor['id']));
        $this->vendorWalletRepo->add($this->vendorService->getInitialWalletData(vendorId: $vendor['id']));

        $data = [
            'vendorName' => $request['f_name'],
            'status' => 'pending',
            'subject' => translate('Vendor_Registration_Successfully_Completed'),
            'title' => translate('Vendor_Registration_Successfully_Completed'),
            'userType' => 'vendor',
            'templateName' => 'registration',
        ];
        // [AI] Redirect newly registered vendor to standard login page
        try {
            event(new VendorRegistrationEvent(email: $request['email'], data: $data));
        } catch (Exception $e) {
            return response()->json([
                'status' => 1,
                'message' => translate('Registration_successful_Please_login_to_your_merchant_account'),
                'redirectRoute' => route('vendor.auth.login')
            ]);
        }
        return response()->json([
            'status' => 1,
            'message' => translate('Registration_successful_Please_login_to_your_merchant_account'),
            'redirectRoute' => route('vendor.auth.login')
        ]);
    }
}
