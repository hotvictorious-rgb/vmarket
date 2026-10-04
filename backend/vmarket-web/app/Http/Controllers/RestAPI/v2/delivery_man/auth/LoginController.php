<?php

namespace App\Http\Controllers\RestAPI\v2\delivery_man\auth;

use App\Events\DeliverymanPasswordResetEvent;
use App\Http\Controllers\Controller;
use App\Models\DeliveryMan;
use App\Models\PasswordReset;
use App\Utils\Helpers;
use App\Utils\SMSModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Gateways\Traits\SmsGateway;

class LoginController extends Controller
{
    public function login(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        /**
         * checking if existing delivery man has a country code or not
         */

        $d_man = DeliveryMan::where(['phone' => $request->phone])->first();
        if ($d_man && isset($d_man->country_code) && ($d_man->country_code != $request->country_code)) {
            $errors = [];
            array_push($errors, ['code' => 'auth-001', 'message' => 'Invalid credential or account suspended']);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        if (isset($d_man) && $d_man['is_active'] == 1 && Hash::check($request->password, $d_man->password)) {
            $token = Str::random(50);
            $d_man->auth_token = hash('sha256', $token);
            $d_man->save();
            return response()->json(['token' => $token], 200);
        } else {
            $errors = [];
            array_push($errors, ['code' => 'auth-001', 'message' => 'Invalid credential or account suspended']);
            return response()->json([
                'errors' => $errors
            ], 401);
        }
    }

    public function reset_password_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        PasswordReset::where(['user_type' => 'delivery_man', 'identity' => $request['identity']])->delete();
        $deliveryMan = DeliveryMan::where(['phone' => $request['identity']])->orWhere(['email' => $request['identity']])->first();
        $verificationBy = getWebConfig(name: 'deliveryman_forgot_password_method') ?? 'phone';

        if (isset($deliveryMan)) {
            $otp = random_int(100000, 999999);

            app(\App\Services\PasswordResetCredentialService::class)->issue('delivery_man', $deliveryMan, $request['identity'], (string)$otp);

            if ($verificationBy == 'email') {
                $emailServices_smtp = getWebConfig(name: 'mail_config');

                if ($emailServices_smtp['status'] == 0) {
                    $emailServices_smtp = getWebConfig(name: 'mail_config_sendgrid');
                }
                if ($emailServices_smtp['status'] == 1) {
                    try {
                        $data = [
                            'userType' => 'delivery-man',
                            'templateName' => 'reset-password-verification',
                            'deliveryManName' => $deliveryMan['f_name'],
                            'subject' => translate('OTP_Verification_for_password_reset'),
                            'title' => translate('OTP_Verification'),
                            'verificationCode' => $otp,
                        ];
                        event(new DeliverymanPasswordResetEvent(email: $deliveryMan['email'], data: $data));
                    } catch (\Exception $ex) {
                        return response()->json(['message' => translate('email_send_failed')], 403);
                    }
                    return response()->json(['message' => translate('OTP_sent_successfully.') . ' ' . translate('Please_check_your_email')], 200);
                } else {
                    return response()->json(['message' => translate('email_failed')], 403);
                }
            } elseif ($verificationBy == 'phone') {
                $phoneNumber = $deliveryMan->country_code ? '+' . $deliveryMan->country_code . $deliveryMan->phone : $deliveryMan->phone;
                SMSModule::sendCentralizedSMS($phoneNumber, $otp);
                return response()->json(['message' => translate('OTP_sent_successfully.') . ' ' . translate('Please_check_your_phone')], 200);
            }
            return response()->json(['message' => translate('OTP_sent_successfully.')], 200);
        }

        return response()->json(['errors' => [
            ['code' => 'not-found', 'message' => translate('user_not_found')]
        ]], 403);
    }

    public function otp_verification_submit(Request $request): JsonResponse
    {
        $identity=$request->input('identity'); $token=$request->input('otp');
        $ok=is_string($identity) && is_string($token) && app(\App\Services\PasswordResetCredentialService::class)->verify('delivery_man',$identity,$token);
        return response()->json(['message'=>$ok ? 'OTP verified.' : 'Invalid or expired password-reset credential.'], $ok ? 200 : 403);
    }

    public function reset_password_submit(Request $request)
    {
        $identity = $request->input('identity', $request->input('phone'));
        $validator = Validator::make($request->all() + ['identity'=>$identity], [
            'identity'=>'required|string','otp'=>'required|string','password'=>'required|string|same:confirm_password|min:8',
        ]);
        if ($validator->fails()) return response()->json(['errors'=>Helpers::validationErrorProcessor($validator)], 403);
        $ok = app(\App\Services\PasswordResetCredentialService::class)->consume('delivery_man', $identity, $request->otp, $request->password);
        return response()->json(['message'=>$ok ? 'Password changed successfully.' : 'Invalid or expired password-reset credential.'], $ok ? 200 : 403);
    }

}
