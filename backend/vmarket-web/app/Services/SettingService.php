<?php

namespace App\Services;

class SettingService
{
    public function getVacationData(string $type): string
    {
        $url = '';
        foreach (config('addon_admin_routes') as $routeArray) {
            foreach ($routeArray as $route) {
                if ($route['name'] === $type) {
                    $url = $route['url'];
                    break 2;
                }
            }
        }
        return $url;
    }

    public function getSMSModuleValidationData(object $request): array
    {
        collect(['status'])->each(fn($item, $key) => $request[$item] = $request->has($item) ? (int)$request[$item] : 0);
        $validation = [
            'gateway' => 'required|in:' . implode(',', \App\Enums\GlobalConstant::DEFAULT_SMS_GATEWAYS),
            'mode' => 'required|in:live,test'
        ];
        $additional_data = [];
        if ($request['gateway'] == 'whatsapp_meta') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'token' => 'required',
                'phone_number_id' => 'required',
                'template_name' => 'required',
                'language_code' => 'nullable',
            ];
        } elseif ($request['gateway'] == 'termii') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
                'from' => 'required',
                'channel' => 'nullable',
                'otp_template' => 'nullable'
            ];
        } elseif ($request['gateway'] == 'ebulksms') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'username' => 'required',
                'api_key' => 'required',
                'sender' => 'required'
            ];
        } elseif ($request['gateway'] == 'smart_sms') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'api_key' => 'required',
                'sender_id' => 'required',
                'otp_template' => 'nullable'
            ];
        } elseif ($request['gateway'] == 'kudisms') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'token' => 'required',
                'sender' => 'required',
                'otp_template' => 'nullable'
            ];
        } elseif ($request['gateway'] == 'sendchamp') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'public_key' => 'required',
                'sender_name' => 'required',
                'otp_template' => 'nullable'
            ];
        } elseif ($request['gateway'] == 'twilio') {
            $additional_data = [
                'status' => 'required|in:1,0',
                'sid' => 'required',
                'messaging_service_sid' => 'required',
                'token' => 'required',
                'from' => 'required',
                'otp_template' => 'required'
            ];
        }

        return $request->validate(array_merge($validation, $additional_data));
    }

}
