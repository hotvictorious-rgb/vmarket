<?php

namespace App\Utils;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Modules\Gateways\Traits\SmsGateway;

class SMSModule
{
    public static function sendCentralizedSMS($phone, $token)
    {
        $paymentPublishedStatus = config('get_payment_publish_status') ?? 0;
        return $paymentPublishedStatus == 1 ? SmsGateway::send($phone, $token) : SMSModule::send($phone, $token);
    }

    public static function send($receiver, $otp): string
    {
        // 1. Termii (Nigeria - Primary)
        $config = self::get_settings('termii');
        if (isset($config) && $config['status'] == 1) {
            return self::termii($receiver, $otp);
        }

        // 2. Ebulksms (Nigeria)
        $config = self::get_settings('ebulksms');
        if (isset($config) && $config['status'] == 1) {
            return self::ebulksms($receiver, $otp);
        }

        // 3. SmartSMSSolutions (Nigeria)
        $config = self::get_settings('smart_sms');
        if (isset($config) && $config['status'] == 1) {
            return self::smart_sms($receiver, $otp);
        }

        // 4. KudiSMS (Nigeria)
        $config = self::get_settings('kudisms');
        if (isset($config) && $config['status'] == 1) {
            return self::kudisms($receiver, $otp);
        }

        // 5. Sendchamp (Nigeria)
        $config = self::get_settings('sendchamp');
        if (isset($config) && $config['status'] == 1) {
            return self::sendchamp($receiver, $otp);
        }

        // 6. Twilio (Global Fallback)
        $config = self::get_settings('twilio');
        if (isset($config) && $config['status'] == 1) {
            return self::twilio($receiver, $otp);
        }

        // Legacy Fallbacks
        $config = self::get_settings('releans');
        if (isset($config) && $config['status'] == 1) {
            return self::releans($receiver, $otp);
        }

        $config = self::get_settings('nexmo');
        if (isset($config) && $config['status'] == 1) {
            return self::nexmo($receiver, $otp);
        }

        $config = self::get_settings('2factor');
        if (isset($config) && $config['status'] == 1) {
            return self::two_factor($receiver, $otp);
        }

        $config = self::get_settings('msg91');
        if (isset($config) && $config['status'] == 1) {
            return self::msg_91($receiver, $otp);
        }

        $config = self::get_settings('alphanet_sms');
        if (isset($config) && $config['status'] == 1) {
            return self::alphanet_sms($receiver, $otp);
        }

        return 'not_found';
    }

    /**
     * Normalize Nigerian phone numbers to standard 234XXXXXXXXXX
     */
    public static function formatNigerianPhone($phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', (string)$phone);
        if (str_starts_with($cleaned, '0')) {
            return '234' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '234')) {
            return $cleaned;
        } elseif (strlen($cleaned) == 10) {
            return '234' . $cleaned;
        }
        return $cleaned;
    }

    /**
     * Termii SMS Gateway (Nigeria #1)
     */
    public static function termii($receiver, $otp): string
    {
        $config = self::get_settings('termii');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $to = self::formatNigerianPhone($receiver);
            $appName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
            $template = !empty($config['otp_template']) ? $config['otp_template'] : "Your $appName verification code is #OTP#";
            $message = str_replace("#OTP#", $otp, $template);
            $apiKey = $config['api_key'] ?? '';
            $sender = !empty($config['from']) ? $config['from'] : 'Vmarket';
            $channel = !empty($config['channel']) ? $config['channel'] : 'dnd';

            try {
                $payload = [
                    'to' => $to,
                    'from' => $sender,
                    'sms' => $message,
                    'type' => 'plain',
                    'channel' => $channel,
                    'api_key' => $apiKey,
                ];

                $ch = curl_init('https://api.ng.termii.com/api/sms/send');
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);

                $result = curl_exec($ch);
                $err = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                Log::info('Termii SMS response', [
                    'to' => $to,
                    'http_code' => $httpCode,
                    'error' => $err,
                    'response' => $result,
                ]);

                if (!$err && ($httpCode == 200 || $httpCode == 201)) {
                    $resJson = json_decode($result, true);
                    if (isset($resJson['code']) && $resJson['code'] == 'ok' || isset($resJson['message']) && stripos($resJson['message'], 'Successfully') !== false) {
                        $response = 'success';
                    } elseif ($httpCode == 200) {
                        $response = 'success';
                    }
                }
            } catch (Exception $exception) {
                Log::error('Termii SMS Exception: ' . $exception->getMessage());
                $response = 'error';
            }
        }
        return $response;
    }

    /**
     * Ebulksms Gateway (Nigeria)
     */
    public static function ebulksms($receiver, $otp): string
    {
        $config = self::get_settings('ebulksms');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $apiKey   = $config['api_key'] ?? '';
            $sender   = !empty($config['sender']) ? $config['sender'] : (!empty($config['from']) ? $config['from'] : 'Vmarket');
            $username = $config['username'] ?? ($config['otp_template'] ?? '');

            $to = self::formatNigerianPhone($receiver);
            $appName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
            $message = "Your $appName verification code is $otp";

            try {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.ebulksms.com/sendsms?username=" . urlencode($username)
                        . "&apikey=" . urlencode($apiKey)
                        . "&sender=" . urlencode($sender)
                        . "&messagetext=" . urlencode($message)
                        . "&flash=0&dndsender=1&recipients=" . $to,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_CUSTOMREQUEST => "GET",
                ));
                $result = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                Log::info('Ebulksms response', [
                    'to' => $to,
                    'error' => $err,
                    'response' => $result,
                ]);

                $response = (!$err && $result && stripos($result, 'SUCCESS') !== false) ? 'success' : ((!$err) ? 'success' : 'error');
            } catch (Exception $exception) {
                Log::error('Ebulksms Exception: ' . $exception->getMessage());
                $response = 'error';
            }
        }
        return $response;
    }

    /**
     * SmartSMSSolutions Gateway (Nigeria)
     */
    public static function smart_sms($receiver, $otp): string
    {
        $config = self::get_settings('smart_sms');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $apiKey   = $config['api_key'] ?? '';
            $sender   = !empty($config['sender_id']) ? $config['sender_id'] : 'Vmarket';
            $to = self::formatNigerianPhone($receiver);
            $appName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
            $template = !empty($config['otp_template']) ? $config['otp_template'] : "Your $appName verification code is #OTP#";
            $message = str_replace("#OTP#", $otp, $template);

            try {
                $payload = [
                    'token' => $apiKey,
                    'sender' => $sender,
                    'to' => $to,
                    'message' => $message,
                    'type' => 0,
                    'routing' => 3, // Priority transactional/OTP routing
                ];

                $ch = curl_init('https://app.smartsmssolutions.com/io/api/client/v1/sms/send/');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);

                $result = curl_exec($ch);
                $err = curl_error($ch);
                curl_close($ch);

                Log::info('SmartSMS response', [
                    'to' => $to,
                    'error' => $err,
                    'response' => $result,
                ]);

                if (!$err && $result) {
                    $resJson = json_decode($result, true);
                    if (isset($resJson['code']) && $resJson['code'] == '1000' || isset($resJson['successful'])) {
                        $response = 'success';
                    }
                }
            } catch (Exception $exception) {
                Log::error('SmartSMS Exception: ' . $exception->getMessage());
                $response = 'error';
            }
        }
        return $response;
    }

    /**
     * KudiSMS Gateway (Nigeria)
     */
    public static function kudisms($receiver, $otp): string
    {
        $config = self::get_settings('kudisms');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $token    = $config['token'] ?? ($config['api_key'] ?? '');
            $sender   = !empty($config['sender']) ? $config['sender'] : 'Vmarket';
            $to = self::formatNigerianPhone($receiver);
            $appName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
            $template = !empty($config['otp_template']) ? $config['otp_template'] : "Your $appName verification code is #OTP#";
            $message = str_replace("#OTP#", $otp, $template);

            try {
                $payload = [
                    'token' => $token,
                    'sender' => $sender,
                    'recipient' => $to,
                    'message' => $message,
                ];

                $ch = curl_init('https://kudisms.net/api/sms');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);

                $result = curl_exec($ch);
                $err = curl_error($ch);
                curl_close($ch);

                Log::info('KudiSMS response', [
                    'to' => $to,
                    'error' => $err,
                    'response' => $result,
                ]);

                if (!$err && $result) {
                    $response = 'success';
                }
            } catch (Exception $exception) {
                Log::error('KudiSMS Exception: ' . $exception->getMessage());
                $response = 'error';
            }
        }
        return $response;
    }

    /**
     * Sendchamp Gateway (Nigeria)
     */
    public static function sendchamp($receiver, $otp): string
    {
        $config = self::get_settings('sendchamp');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $publicKey = $config['public_key'] ?? '';
            $senderName = !empty($config['sender_name']) ? $config['sender_name'] : 'Sendchamp';
            $to = self::formatNigerianPhone($receiver);
            $appName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
            $template = !empty($config['otp_template']) ? $config['otp_template'] : "Your $appName verification code is #OTP#";
            $message = str_replace("#OTP#", $otp, $template);

            try {
                $payload = [
                    'to' => '+' . $to,
                    'message' => $message,
                    'sender_name' => $senderName,
                    'route' => 'dnd',
                ];

                $ch = curl_init('https://api.sendchamp.com/api/v1/sms/send');
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $publicKey,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);

                $result = curl_exec($ch);
                $err = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                Log::info('Sendchamp response', [
                    'to' => $to,
                    'http_code' => $httpCode,
                    'error' => $err,
                    'response' => $result,
                ]);

                if (!$err && ($httpCode == 200 || $httpCode == 201)) {
                    $response = 'success';
                }
            } catch (Exception $exception) {
                Log::error('Sendchamp Exception: ' . $exception->getMessage());
                $response = 'error';
            }
        }
        return $response;
    }

    public static function twilio($receiver, $otp): string
    {
        $config = self::get_settings('twilio');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            $sid = $config['sid'];
            $token = $config['token'];
            try {
                $twilio = new Client($sid, $token);
                $twilio->messages
                    ->create($receiver,
                        array(
                            "messagingServiceSid" => $config['messaging_service_sid'],
                            "body" => $message
                        )
                    );
                $response = 'success';
            } catch (Exception $exception) {
                Log::error('Twilio Exception: ' . $exception->getMessage());
            }
        }
        return $response;
    }

    public static function nexmo($receiver, $otp): string
    {
        $config = self::get_settings('nexmo');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://rest.nexmo.com/sms/json');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "from=" . $config['from'] . "&text=" . $message . "&to=" . $receiver . "&api_key=" . $config['api_key'] . "&api_secret=" . $config['api_secret']);

                $headers = array();
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                curl_close($ch);
                $response = 'success';
            } catch (Exception $exception) {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function two_factor($receiver, $otp): string
    {
        $config = self::get_settings('2factor');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $api_key = $config['api_key'];
            $otp_template = $config['otp_template'];
            $apiUrl = "https://2factor.in/API/V1/$api_key/SMS/$receiver/$otp/$otp_template";
            try {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => "GET",
                ));
                $result = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if (!$err) {
                    $response = 'success';
                } else {
                    $response = 'error';
                }
            } catch (Exception $exception) {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function msg_91($receiver, $otp): string
    {
        $config = self::get_settings('msg91');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $receiver = str_replace("+", "", $receiver);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.msg91.com/api/v5/otp?template_id=" . $config['template_id'] . "&mobile=" . $receiver . "&authkey=" . $config['auth_key'] . "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/json"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if (!$err) {
                $response = 'success';
            } else {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function releans($receiver, $otp): string
    {
        $config = self::get_settings('releans');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $apiKey   = $config['api_key'];
            $sender   = $config['from'];
            $username = $config['otp_template'];

            $to = self::formatNigerianPhone($receiver);
            $appName = getWebConfig(name: 'company_name') ?? 'Victorious MARKET';
            $message = "Your $appName Verification code is $otp";

            try {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.ebulksms.com/sendsms?username=" . urlencode($username)
                        . "&apikey=" . urlencode($apiKey)
                        . "&sender=" . urlencode($sender)
                        . "&messagetext=" . urlencode($message)
                        . "&flash=0&dndsender=1&recipients=" . $to,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CUSTOMREQUEST => "GET",
                ));
                $result = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                Log::info('Ebulksms (releans) response', [
                    'to' => $to,
                    'curl_error' => $err,
                    'response' => $result,
                ]);

                $response = (!$err) ? 'success' : 'error';
            } catch (Exception $exception) {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function alphanet_sms($receiver, $otp): string
    {
        $config = self::get_settings('alphanet_sms');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $receiver = str_replace("+", "", $receiver);
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            $api_key = $config['api_key'];

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.sms.net.bd/sendsms',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('api_key' => $api_key, 'msg' => $message, 'to' => $receiver),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if (!$err) {
                $response = 'success';
            } else {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function get_settings($name)
    {
        try {
            $config = DB::table('addon_settings')->where('key_name', $name)
                ->where('settings_type', 'sms_config')->first();
        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? json_decode($config->live_values, true) : null;
    }
}
