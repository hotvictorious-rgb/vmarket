<?php

namespace App\Http\Controllers\RestAPI\v3\seller\auth;

use App\Contracts\Repositories\PhoneOrEmailVerificationRepositoryInterface;
use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Events\PasswordResetEvent;
use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Support\Carbon;
use App\Traits\CustomerTrait;
use App\Utils\Helpers;
use App\Utils\SMSModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Gateways\Traits\SmsGateway;

class ForgotPasswordController extends Controller
{
    use CustomerTrait;

    public function __construct(
        private readonly PhoneOrEmailVerificationRepositoryInterface $phoneOrEmailVerificationRepo,
        private readonly VendorRepositoryInterface                   $vendorRepo,
    )
    {
    }

    public function reset_password_request(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $verification_by = getWebConfig(name: 'vendor_forgot_password_method');


        if ($verification_by == 'email') {
            $seller = Seller::Where(['email' => $request['identity']])->first();
            if (isset($seller)) {
                $token = Str::random(120);
                app(\App\Services\PasswordResetCredentialService::class)->issue('seller', $seller, $seller['email'], (string)$token);
                $reset_url = route('vendor.auth.forgot-password.reset-password', ['token' => $token]);

                $emailServices_smtp = getWebConfig(name: 'mail_config');
                if ($emailServices_smtp['status'] == 0) {
                    $emailServices_smtp = getWebConfig(name: 'mail_config_sendgrid');
                }
                if ($emailServices_smtp['status'] == 1) {
                    $data = [
                        'userType' => 'vendor',
                        'templateName' => 'forgot-password',
                        'vendorName' => $seller['f_name'],
                        'subject' => translate('password_reset'),
                        'title' => translate('password_reset'),
                        'passwordResetURL' => $reset_url,
                    ];
                    event(new PasswordResetEvent(email: $seller['email'], data: $data));
                    $response = translate('check_your_email');
                } else {
                    $response = translate('email_failed');
                }
                return response()->json(['message' => $response], 200);
            }
        } elseif ($verification_by == 'phone') {
            $seller = Seller::where('phone', $request['identity'])->first();
            if (isset($seller)) {
                $token = random_int(100000, 999999);
                app(\App\Services\PasswordResetCredentialService::class)->issue('seller', $seller, $seller['phone'], (string)$token);

                $response = SMSModule::sendCentralizedSMS($seller->phone, $token);
                if (env('APP_MODE') == 'dev') {
                    $response = 'success';
                }

                if ($response == 'success') {
                    return response()->json(['message' => 'otp sent successfully.'], 200);
                }
                return response()->json(['message' => 'SMS configuration error.'], 403);
            }
        }
        return response()->json(['errors' => [
            ['code' => 'not-found', 'message' => 'Seller not found!']
        ]], 404);
    }

    public function otp_verification_submit(Request $request): JsonResponse
    {
        $identity=$request->input('identity'); $token=$request->input('otp');
        $ok=is_string($identity) && is_string($token) && app(\App\Services\PasswordResetCredentialService::class)->verify('seller',$identity,$token);
        return response()->json(['message'=>$ok ? 'OTP verified.' : 'Invalid or expired password-reset credential.'], $ok ? 200 : 403);
    }

    public function reset_password_submit(Request $request)
    {
        $identity = $request->input('identity', $request->input('phone'));
        $validator = Validator::make($request->all() + ['identity'=>$identity], [
            'identity'=>'required|string','otp'=>'required|string','password'=>'required|string|same:confirm_password|min:8',
        ]);
        if ($validator->fails()) return response()->json(['errors'=>Helpers::validationErrorProcessor($validator)], 403);
        $ok = app(\App\Services\PasswordResetCredentialService::class)->consume('seller', $identity, $request->otp, $request->password);
        return response()->json(['message'=>$ok ? 'Password changed successfully.' : 'Invalid or expired password-reset credential.'], $ok ? 200 : 403);
    }

    public function firebaseAuthTokenStore(Request $request): JsonResponse
    {
        // [AI] A caller-provided Firebase session is not an identity or password-reset credential.
        return response()->json(['message'=>'Caller-provided verification tokens are not accepted.'],403);
    }

    public function firebaseAuthVerify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sessionInfo' => 'required',
            'phoneNumber' => 'required',
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $firebaseOTPVerification = getWebConfig(name: 'firebase_otp_verification');
        $webApiKey = $firebaseOTPVerification ? $firebaseOTPVerification['web_api_key'] : '';

        $response = Http::post('https://identitytoolkit.googleapis.com/v1/accounts:signInWithPhoneNumber?key=' . $webApiKey, [
            'sessionInfo' => $request['sessionInfo'],
            'phoneNumber' => $request['phoneNumber'],
            'code' => $request['code'],
        ]);

        $responseData = $response->json();

        if (isset($responseData['error'])) {
            $errors = [];
            $errors[] = ['code' => "403", 'message' => translate(strtolower($responseData['error']['message']))];
            return response()->json(['errors' => $errors], 403);
        }

        if (!$response->successful() || empty($responseData['phoneNumber']) || $responseData['phoneNumber'] !== $request->phoneNumber) {
            return response()->json(['message'=>'Firebase phone proof does not match reset identity.'],403);
        }
        $seller = Seller::where('phone',$responseData['phoneNumber'])->first();
        if (!$seller) return response()->json(['message'=>'Account not found.'],403);
        $resetToken=Str::random(64);
        app(\App\Services\PasswordResetCredentialService::class)->issue('seller',$seller,$responseData['phoneNumber'],$resetToken);
        return response()->json(['message'=>'OTP verified.','reset_token'=>$resetToken,'identity'=>$responseData['phoneNumber']],200);
    }

    public function checkVendorExistInfo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $seller = $this->vendorRepo->getFirstWhere(params: ['identity' => $request['phone']]);
        if ($seller) {
            return response()->json(['message' => translate('Vendor found')], 200);
        }

        return response()->json(['errors' => [
            ['code' => 'not-found', 'message' => translate('Vendor not found')]
        ]], 404);
    }
}
