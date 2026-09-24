<?php

namespace App\Http\Controllers\Admin\ThirdParty;

use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Enums\GlobalConstant;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\SMSModuleUpdateRequest;
use App\Services\FirebaseService;
use App\Services\RecaptchaService;
use App\Services\SettingService;
use App\Utils\SMSModule;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SMSModuleController extends BaseController
{
    public function __construct(
        private readonly SettingRepositoryInterface $settingRepo,
        private readonly SettingService             $settingService,
        private readonly FirebaseService            $firebaseService,
    )
    {
    }

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $recaptcha = getWebConfig(name: 'recaptcha');
        $firebaseOtpVerification = getWebConfig(name: 'firebase_otp_verification');
        $companyPhone = getWebConfig(name: 'company_phone');
        $paymentPublishedStatus = config('get_payment_publish_status') ?? 0;
        $paymentGatewayPublishedStatus = isset($paymentPublishedStatus[0]['is_published']) ? $paymentPublishedStatus[0]['is_published'] : 0;

        // [AI] Ensure WhatsApp Meta Cloud configuration entry exists in addon_settings
        $whatsappMetaSetting = $this->settingRepo->getFirstWhere(params: ['key_name' => 'whatsapp_meta', 'settings_type' => 'sms_config']);
        if (!$whatsappMetaSetting) {
            $defaultWhatsappConfig = [
                'gateway' => 'whatsapp_meta',
                'mode' => 'live',
                'status' => 0,
                'token' => '',
                'phone_number_id' => '',
                'template_name' => 'victorious_otp_auth',
                'language_code' => 'en',
            ];
            $this->settingRepo->updateOrInsert(params: ['key_name' => 'whatsapp_meta', 'settings_type' => 'sms_config'], data: [
                'key_name' => 'whatsapp_meta',
                'live_values' => $defaultWhatsappConfig,
                'test_values' => $defaultWhatsappConfig,
                'settings_type' => 'sms_config',
                'mode' => 'live',
                'is_active' => 0,
            ]);
        }

        $smsGatewaysList = $this->settingRepo->getListWhereIn(
            whereInFilters: ['settings_type' => ['sms_config'], 'key_name' => GlobalConstant::DEFAULT_SMS_GATEWAYS],
            dataLimit: 'all',
        );

        $smsGateways = $smsGatewaysList->sortBy(function ($item) {
            // Put whatsapp_meta first as Tier 1 primary
            return $item['key_name'] === 'whatsapp_meta' ? 0 : 1;
        })->values()->all();

        $paymentUrl = $this->settingService->getVacationData(type: 'sms_setup');
        return view('admin-views.third-party.sms-index', compact('recaptcha', 'smsGateways', 'paymentGatewayPublishedStatus', 'paymentUrl', 'firebaseOtpVerification', 'companyPhone'));
    }

    public function update(SMSModuleUpdateRequest $request, SettingService $settingService): RedirectResponse
    {
        $service = $settingService->getSMSModuleValidationData(request: $request);
        $this->settingRepo->updateOrInsert(params: ['key_name' => $request['gateway'], 'settings_type' => 'sms_config'], data: [
            'key_name' => $request['gateway'],
            'live_values' => $service,
            'test_values' => $service,
            'settings_type' => 'sms_config',
            'mode' => $request['mode'],
            'is_active' => $request['status'],
        ]);

        // [AI] Decouple WhatsApp from SMS exclusivity: WhatsApp can run concurrently with an SMS Gateway for dual-tier failover
        if ($request['status'] == 1 && $request['gateway'] !== 'whatsapp_meta') {
            foreach (GlobalConstant::DEFAULT_SMS_GATEWAYS as $gateway) {
                $keep = $this->settingRepo->getFirstWhere(params: ['key_name' => $gateway, 'settings_type' => 'sms_config']);
                if (isset($keep)) {
                    $hold = $keep['live_values'];
                    if ($request['gateway'] != $gateway) {
                        $hold['status'] = 0;
                        $this->settingRepo->updateWhere(params: ['key_name' => $gateway, 'settings_type' => 'sms_config'], data: [
                            'live_values' => $hold,
                            'test_values' => $hold,
                            'is_active' => 0,
                        ]);
                    }
                }
            }
        }

        ToastMagic::success(GATEWAYS_DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function sendSMS(Request $request): RedirectResponse
    {
        $result = RecaptchaService::verificationStatus(request: $request, session: 'smsTestRecaptchaSessionKey', action: "login", firebase: true);
        if ($result && !$result['status']) {
            ToastMagic::error($result['message']);
            return back();
        }

        $status = 'error';
        $phoneNumber = $request['phone'];
        $firebaseOTPVerification = getWebConfig(name: 'firebase_otp_verification') ?? [];
        $errorMessage = translate('SMS_sent_failed');

        if ($firebaseOTPVerification && $firebaseOTPVerification['status']) {
            $firebaseResponse = $this->firebaseService->sendOtp($phoneNumber);
            if ($firebaseResponse['status'] == 'success') {
                $status = $firebaseResponse['status'];
            } else {
                $errorMessage = translate(ucfirst(strtolower($firebaseResponse['errors'])));
            }
        } else {
            $response = SMSModule::sendCentralizedSMS($phoneNumber, rand(100000, 999999));
            $status = $response == 'success' ? $response : 'error';
        }

        $status == 'success' ? ToastMagic::success(translate('SMS_sent_successfully')) : ToastMagic::error($errorMessage);
        return redirect()->back();
    }
}
