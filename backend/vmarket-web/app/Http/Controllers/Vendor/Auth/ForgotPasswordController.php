<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Contracts\Repositories\PasswordResetRepositoryInterface;
use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Enums\SessionKey;
use App\Enums\ViewPaths\Vendor\ForgotPassword;
use App\Events\PasswordResetEvent;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\PasswordResetRequest;
use App\Http\Requests\Vendor\VendorPasswordRequest;
use App\Services\FirebaseService;
use App\Services\PasswordResetService;
use App\Services\RecaptchaService;
use App\Traits\EmailTemplateTrait;
use App\Traits\SmsGateway;
use App\Utils\SMSModule;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class ForgotPasswordController extends BaseController
{
    use SmsGateway, EmailTemplateTrait;

    /**
     * @param VendorRepositoryInterface $vendorRepo
     * @param PasswordResetRepositoryInterface $passwordResetRepo
     * @param PasswordResetService $passwordResetService
     * @param FirebaseService $firebaseService
     */
    public function __construct(
        private readonly VendorRepositoryInterface        $vendorRepo,
        private readonly PasswordResetRepositoryInterface $passwordResetRepo,
        private readonly PasswordResetService             $passwordResetService,
        private readonly FirebaseService                  $firebaseService,
    )
    {
        $this->middleware('guest:seller', ['except' => ['logout']]);
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View|Collection|LengthAwarePaginator|callable|RedirectResponse|null
     */
    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return $this->getForgotPasswordView();
    }

    /**
     * @return View
     */
    public function getForgotPasswordView(): View
    {
        return view(ForgotPassword::INDEX[VIEW]);
    }

    /**
     * @param PasswordResetRequest $request
     * @return JsonResponse|RedirectResponse
     * @throws Exception
     */
    public function getPasswordResetRequest(PasswordResetRequest $request): JsonResponse|RedirectResponse
    {
        session()->put(SessionKey::FORGOT_PASSWORD_IDENTIFY, $request['identity']);
        $verificationBy = getWebConfig('vendor_forgot_password_method') ?? 'phone';

        $result = RecaptchaService::verificationStatus(request: $request, session: 'default_recaptcha_id_vendor_forgot_password', action: 'vendor_forgot_password', firebase: true);
        if ($result && !$result['status']) {
            if ($request->ajax()) {
                return response()->json([
                    'error' => $result['message'],
                ]);
            }
            ToastMagic::error($result['message']);
            return back();
        }

        if ($verificationBy == 'email') {
            $vendor = $this->vendorRepo->getFirstWhere(['identity' => $request['identity']]);
            if (isset($vendor)) {
                $emailServicesSmtp = getWebConfig(name: 'mail_config');
                if ($emailServicesSmtp['status'] == 0) {
                    $emailServicesSmtp = getWebConfig(name: 'mail_config_sendgrid');
                }
                if ($emailServicesSmtp['status'] == 1) {
                    $token = Str::random(120);
                    app(\App\Services\PasswordResetCredentialService::class)->issue('seller',$vendor,$request['identity'],(string)$token);
                    $resetUrl = route('vendor.auth.forgot-password.reset-password', ['token' => $token]);
                    try {
                        $data = [
                            'userType' => 'vendor',
                            'templateName' => 'forgot-password',
                            'vendorName' => $vendor['f_name'],
                            'subject' => translate('password_reset'),
                            'title' => translate('password_reset'),
                            'passwordResetURL' => $resetUrl,
                        ];
                        event(new PasswordResetEvent(email: $vendor['email'], data: $data));
                    } catch (Exception $exception) {
                        if ($request->ajax()) {
                            return response()->json(['error' => translate('email_send_fail') . '!!']);
                        }
                        ToastMagic::error(translate('email_send_fail'));
                        return back();
                    }
                    if ($request->ajax()) {
                        return response()->json([
                            'verificationBy' => 'mail',
                            'success' => translate('otp_has_been_sent_to_your_email_address'),
                        ]);
                    }
                    ToastMagic::success(translate('otp_has_been_sent_to_your_email_address'));
                    return back();
                }
                $smsErrorMsg = translate('something_went_wrong.') . ' ' . translate('please_try_again_after_sometime');
                if ($request->ajax()) {
                    return response()->json(['error' => $smsErrorMsg]);
                }
                ToastMagic::error($smsErrorMsg);
                return back();
            }
        } elseif ($verificationBy == 'phone') {
            $vendor = $this->vendorRepo->getFirstWhere(['identity' => $request['identity']]);
            if (isset($vendor)) {
                $response = "not_found";
                $smsErrorMsg = translate('something_went_wrong.') . ' ' . translate('please_try_again_after_sometime');
                $token = random_int(100000, 999999);

                $firebaseOTPVerification = getWebConfig(name: 'firebase_otp_verification') ?? [];
                if ($firebaseOTPVerification && $firebaseOTPVerification['status']) {
                    try {
                        $firebaseResponse = $this->firebaseService->sendOtp($request['identity']);
                        if ($firebaseResponse['status'] == 'success') {
                            $token = $firebaseResponse['sessionInfo'];
                            $response = $firebaseResponse['status'];
                        } else {
                            $smsErrorMsg = translate(strtolower($firebaseResponse['errors']));
                        }
                    } catch (Exception $e) {
                        $response = "not_found";
                        $smsErrorMsg = translate('something_went_wrong.') . ' ' . translate('please_try_again_after_sometime');
                    }
                } else {
                    $response = SMSModule::sendCentralizedSMS($request['identity'], $token);
                    if (env('APP_MODE') == 'dev') {
                        $response = 'success';
                    }
                }

                if (($firebaseOTPVerification['status']??false)) {
                    $this->passwordResetRepo->add($this->passwordResetService->getAddData(identity: $request['identity'], token: $token, userType: 'seller', purpose: 'firebase_pending'));
                } else {
                    app(\App\Services\PasswordResetCredentialService::class)->issue('seller',$vendor,$request['identity'],(string)$token);
                }

                if (env('APP_MODE') == 'dev') {
                    if ($request->ajax()) {
                        return response()->json([
                            'verificationBy' => 'phone',
                            'redirectRoute' => route('vendor.auth.forgot-password.otp-verification'),
                            'success' => translate('Check_your_phone') . ', ' . translate('password_reset_otp_sent'),
                        ]);
                    }
                    ToastMagic::success(translate('Check_your_phone') . ', ' . translate('password_reset_otp_sent'));
                    return redirect()->route('vendor.auth.forgot-password.otp-verification');
                }

                if ($response === "not_found") {
                    if ($request->ajax()) {
                        return response()->json([
                            'error' => $smsErrorMsg,
                        ]);
                    }
                    ToastMagic::error($smsErrorMsg);
                    return back();
                }

                if ($request->ajax()) {
                    return response()->json([
                        'verificationBy' => 'phone',
                        'redirectRoute' => route('vendor.auth.forgot-password.otp-verification'),
                        'success' => translate('Check_your_phone') . ', ' . translate('password_reset_otp_sent'),
                    ]);
                }
                ToastMagic::success(translate('Check_your_phone') . ', ' . translate('password_reset_otp_sent'));
                return redirect()->route('vendor.auth.forgot-password.otp-verification');
            }
        }
        if ($request->ajax()) {
            return response()->json([
                'error' => translate('no_such_user_found') . '!!',
            ]);
        }
        ToastMagic::error(translate('no_such_user_found'));
        return back();
    }


    public function getOTPVerificationView(): View
    {
        return view('vendor-views.auth.forgot-password.verify-otp-view');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function submitOTPVerificationCode(Request $request): RedirectResponse
    {
        $identity = session(SessionKey::FORGOT_PASSWORD_IDENTIFY);
        $record = $this->passwordResetRepo->getFirstWhere(params: ['user_type'=>'seller','identity'=>$identity]);
        $proof=(string)$request->token;
        $service=app(\App\Services\PasswordResetCredentialService::class);
        if ($record && $record->purpose==='firebase_pending') {
            if (!($record->created_at && Carbon::parse($record->created_at)->addMinutes(15)->gt(now()))) return back();
            $verified=$this->firebaseService->verifyOtp($record->token,$identity,$proof);
            if (($verified['status']??'')!=='success' || ($verified['result']['phoneNumber']??null)!==$identity) return back();
            $vendor=\App\Models\Seller::find($record->account_id);
            if (!$vendor || $vendor->phone!==$identity) return back();
            $proof=Str::random(64); $service->issue('seller',$vendor,$identity,$proof);
        }
        if ($service->verify('seller',(string)$identity,$proof)) {
            return redirect()->route('vendor.auth.forgot-password.reset-password',['token'=>$proof]);
        }
        ToastMagic::error(translate('invalid_otp')); return back();
    }

    /**
     * @param Request $request
     * @return View|RedirectResponse
     * @throws Exception
     */
    public function getPasswordResetView(Request $request): View|RedirectResponse
    {
        $passwordResetData = $this->passwordResetRepo->getFirstWhere(params: ['user_type' => 'seller', 'purpose'=>'password_reset', 'token' => hash('sha256',(string)$request['token'])]);
        if ($passwordResetData && app(\App\Services\PasswordResetCredentialService::class)->verify('seller',$passwordResetData->identity,(string)$request->token)) {
            // [AI] Expiration Guard: Ensure reset token is not older than 15 minutes
            if (Carbon::parse($passwordResetData['created_at'] ?? $passwordResetData['updated_at'])->addMinutes(15)->isPast()) {
                ToastMagic::error(translate('OTP_expired_please_request_a_new_one'));
                return redirect()->route('vendor.auth.login');
            }
            $token = $request['token'];
            return view(ForgotPassword::RESET_PASSWORD[VIEW], compact('token'));
        }
        ToastMagic::error(translate('Invalid_URL'));
        return redirect()->route('vendor.auth.login');
    }

    /**
     * @param VendorPasswordRequest $request
     * @return JsonResponse|RedirectResponse
     * @throws Exception
     */
    public function resetPassword(VendorPasswordRequest $request): JsonResponse|RedirectResponse
    {
        $passwordResetData = $this->passwordResetRepo->getFirstWhere(params: ['user_type' => 'seller', 'purpose'=>'password_reset', 'token' => hash('sha256',(string)$request['reset_token'])]);
        if ($passwordResetData) {
            // [AI] Expiration Guard: Ensure reset token is not older than 15 minutes
            if (Carbon::parse($passwordResetData['created_at'] ?? $passwordResetData['updated_at'])->addMinutes(15)->isPast()) {
                if ($request->ajax()) {
                    return response()->json(['error' => translate('OTP_expired_please_request_a_new_one')]);
                }
                ToastMagic::error(translate('OTP_expired_please_request_a_new_one'));
                return redirect()->route('vendor.auth.login');
            }

            if (!app(\App\Services\PasswordResetCredentialService::class)->consume('seller',$passwordResetData->identity,(string)$request->reset_token,(string)$request->password)) {
                if ($request->ajax()) return response()->json(['error'=>'Invalid or expired reset credential.'],403);
                return back();
            }
            if ($request->ajax()) {
                return response()->json([
                    'passwordUpdate' => 1,
                    'success' => translate('Password_reset_successfully'),
                    'redirectRoute' => route('vendor.auth.login')
                ]);
            }
            ToastMagic::success(translate('Password_reset_successfully'));
            return redirect()->route('vendor.auth.login');
        }

        if ($request->ajax()) {
            return response()->json(['error' => translate('invalid_URL')]);
        }
        ToastMagic::error(translate('invalid_URL'));
        return back();
    }
}
