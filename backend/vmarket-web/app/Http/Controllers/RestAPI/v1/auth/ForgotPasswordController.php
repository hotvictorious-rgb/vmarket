<?php

namespace App\Http\Controllers\RestAPI\v1\auth;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\PasswordResetRepositoryInterface;
use App\Events\PasswordResetEvent;
use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use App\Traits\CustomerTrait;
use App\Utils\Helpers;
use App\Utils\SMSModule;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    use CustomerTrait;

    public function __construct(
        private readonly CustomerRepositoryInterface                 $customerRepo,
        private readonly PasswordResetRepositoryInterface            $passwordResetRepo,
    )
    {
    }

    public function reset_password_request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identity' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::validationErrorProcessor($validator)], 403);
        }

        $verification_by = getWebConfig(name: 'forgot_password_verification');
        $otp_interval_time = getWebConfig(name: 'otp_resend_time') ?? 1; //second

        $password_verification_data = PasswordReset::where(['user_type'=>'customer'])->where('identity', $request['identity'])->latest()->first();
        if ($verification_by == 'email') {
            $customer = User::Where(['email' => $request['identity']])->first();
            if (isset($customer)) {
                if(isset($password_verification_data) &&  Carbon::parse($password_verification_data->created_at)->diffInSeconds() < $otp_interval_time){
                    $time= $otp_interval_time - Carbon::parse($password_verification_data->created_at)->diffInSeconds();

                    return response()->json(['message' => translate('please_try_again_after').' '.CarbonInterval::seconds($time)->cascade()->forHumans()], 200);
                }else {
                    $token = Str::random(120);
                    app(\App\Services\PasswordResetCredentialService::class)->issue('customer', $customer, $customer['email'], (string)$token);

                    $reset_url = url('/') . '/customer/auth/reset-password?token=' . $token;

                    $emailServices_smtp = getWebConfig(name: 'mail_config');
                    if ($emailServices_smtp['status'] == 0) {
                        $emailServices_smtp = getWebConfig(name: 'mail_config_sendgrid');
                    }
                    if ($emailServices_smtp['status'] == 1) {
                        try{
                            $data = [
                                'userType' => 'customer',
                                'templateName' => 'forgot-password',
                                'vendorName' => $customer['f_name'],
                                'subject' => translate('password_reset'),
                                'title' => translate('password_reset'),
                                'passwordResetURL' => $reset_url,
                            ];
                            event(new PasswordResetEvent(email: $customer['email'],data: $data));
                            $response = 'Check your email';
                        } catch (\Exception $exception) {
                            return response()->json([
                                'message' => translate('email_is_not_configured'). translate('contact_with_the_administrator')
                            ], 403);
                        }
                    } else {
                        $response = translate('email_failed');
                    }
                    return response()->json(['message' => $response], 200);
                }
            }
        } elseif ($verification_by == 'phone') {
            $customer = User::where('phone', $request['identity'])->first();
            $otp_resend_time = getWebConfig(name: 'otp_resend_time') > 0 ? getWebConfig(name: 'otp_resend_time') : 0;
            if (isset($customer)) {
                if(isset($password_verification_data) &&  Carbon::parse($password_verification_data->created_at)->diffInSeconds() < $otp_interval_time){
                    $time= $otp_interval_time - Carbon::parse($password_verification_data->created_at)->diffInSeconds();

                    return response()->json(['message' => translate('please_try_again_after').' '.CarbonInterval::seconds($time)->cascade()->forHumans()], 200);
                }else {
                    $token = random_int(100000, 999999);
                    app(\App\Services\PasswordResetCredentialService::class)->issue('customer', $customer, $customer['phone'], (string)$token);

                    SMSModule::sendCentralizedSMS($customer->phone, $token);
                    return response()->json([
                        'message' => translate('otp_sent_successfully'),
                        'resend_time'=> $otp_resend_time,
                    ], 200);
                }
            }
        }
        return response()->json(['errors' => [
            ['code' => 'not-found', 'message' => translate('user not found').'!']
        ]], 403);
    }

    public function tokenVerificationSubmit(Request $request): JsonResponse
    {
        $identity=$request->input('email_or_phone'); $token=$request->input('reset_token');
        $ok=is_string($identity) && is_string($token) && app(\App\Services\PasswordResetCredentialService::class)->verify('customer',$identity,$token);
        return response()->json(['message'=>$ok ? 'OTP verified.' : 'Invalid or expired password-reset credential.'], $ok ? 200 : 403);
    }

    public function reset_password_submit(Request $request)
    {
        $identity = $request->input('identity', $request->input('phone'));
        $validator = Validator::make($request->all() + ['identity'=>$identity], [
            'identity'=>'required|string','otp'=>'required|string','password'=>'required|string|same:confirm_password|min:8',
        ]);
        if ($validator->fails()) return response()->json(['errors'=>Helpers::validationErrorProcessor($validator)], 403);
        $ok = app(\App\Services\PasswordResetCredentialService::class)->consume('customer', $identity, $request->otp, $request->password);
        return response()->json(['message'=>$ok ? 'Password changed successfully.' : 'Invalid or expired password-reset credential.'], $ok ? 200 : 403);
    }

}
