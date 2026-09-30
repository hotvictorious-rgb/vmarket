<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Validation\ValidationException;

class RecaptchaService
{
    public static function verify(string $token, ?string $action = null): bool
    {
        $recaptchaRaw = getWebConfig(name: 'recaptcha');
        if (is_string($recaptchaRaw)) {
            $decoded = json_decode($recaptchaRaw, true);
            $recaptchaRaw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($recaptchaRaw)) {
            return false;
        }

        $secretKey = $recaptchaRaw['secret_key'] ?? '';
        if (empty($secretKey)) {
            return false;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => request()->ip(),
        ]);

        $data = $response->json();
        if (!($data['success'] ?? false)) {
            ToastMagic::error(translate('ReCAPTCHA_Failed'));
            return false;
        }

        if (($data['score'] ?? 0) < 0.5) {
            ToastMagic::error(translate('ReCAPTCHA_Score_Too_Low_Please_Try_Again'));
            return false;
        }
        if ($action !== null && ($data['action'] ?? '') !== $action) {
            ToastMagic::error(translate('ReCAPTCHA_Action_Invalid'));
            return false;
        }

        return true;
    }

    public static function verificationStatus(object|array $request, string $session, ?string $action = 'default', ?bool $firebase = false): array
    {
        $firebaseOTPVerificationRaw = getWebConfig(name: 'firebase_otp_verification');
        $firebaseOTPVerification = $firebaseOTPVerificationRaw;

        // getWebConfig sometimes returns a JSON string instead of an array.
        if (is_string($firebaseOTPVerificationRaw)) {
            $decoded = json_decode($firebaseOTPVerificationRaw, true);
            $firebaseOTPVerification = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($firebaseOTPVerification)) {
            $firebaseOTPVerification = [];
        }

        $defaultCaptchaValue = $request['default_captcha_value'] ?? '';
        $sessionCaptchaValue = session($session);

        $firebaseBranchEnabled = (bool) ($firebase && !empty($firebaseOTPVerification['status']));
        if ($firebaseBranchEnabled) {
            // Firebase reCAPTCHA uses a different input name than Google reCAPTCHA.
            $firebaseToken = $request['firebase-auth-recaptcha-response'] ?? null;
            $googleToken = $request['g-recaptcha-response'] ?? null;
            $token = $firebaseToken ?: $googleToken;

            if (empty($token)) {
                return [
                    'status' => false,
                    'message' => translate('please_check_the_recaptcha'),
                ];
            }

            return [
                'status' => true,
                'message' => translate('ReCAPTCHA_verification_success.'),
            ];
        }

        $recaptchaRaw = getWebConfig(name: 'recaptcha');
        if (is_string($recaptchaRaw)) {
            $decoded = json_decode($recaptchaRaw, true);
            $recaptchaRaw = is_array($decoded) ? $decoded : [];
        }
        $recaptcha = is_array($recaptchaRaw) ? $recaptchaRaw : [];

        if (($recaptcha['status'] ?? 0) == 1 && empty($defaultCaptchaValue)) {
            try {
                $request->validate([
                    'g-recaptcha-response' => [
                        function ($attribute, $value, $fail) use ($action) {
                            if (empty($value)) {
                                $fail(translate('ReCAPTCHA_verification_failed.'));
                                return;
                            }
                            if (!RecaptchaService::verify(token: $value, action: $action)) {
                                $fail(translate('ReCAPTCHA_verification_failed.'));
                                return;
                            }
                        },
                    ],
                ]);
            } catch (ValidationException $e) {
                return [
                    'status' => false,
                    'message' => $e->validator->errors()->first('g-recaptcha-response'),
                ];
            }
        } else {
            $defaultCaptchaValue = (string) $defaultCaptchaValue;
            $sessionCaptchaValue = (string) ($sessionCaptchaValue ?? '');

            if ($sessionCaptchaValue === '' || strtolower($sessionCaptchaValue) !== strtolower($defaultCaptchaValue)) {
                return [
                    'status' => false,
                    'message' => translate('ReCAPTCHA_failed.'),
                ];
            }
        }

        session()->forget($session);
        return [
            'status' => true,
            'message' => translate('ReCAPTCHA_verification_success.'),
        ];
    }
}


?>
