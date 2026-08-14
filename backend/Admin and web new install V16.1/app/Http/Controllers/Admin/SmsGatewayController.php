<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use Illuminate\Http\Request;

class SmsGatewayController extends Controller
{
    public function index()
    {
        return view('admin-views.business-settings.sms-gateway.index');
    }

    public function update(Request $request, $name)
    {
        if ($name == 'sms_nexmo') {
            // [AI] Use Eloquent updateOrCreate to trigger boot events and invalidate business settings cache automatically
            BusinessSetting::updateOrCreate(
                ['type' => 'sms_nexmo'],
                ['value' => json_encode([
                    'status' => $request['status'] ?? 0,
                    'nexmo_key' => $request['nexmo_key'] ?? '',
                    'nexmo_secret' => $request['nexmo_secret'] ?? '',
                ])]
            );
            clearWebConfigCacheKeys();
        }

        return back();
    }
}
