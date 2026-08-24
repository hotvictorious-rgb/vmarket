<?php

namespace App\Http\Controllers\Admin\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppAiCorrection;
use App\Models\WhatsAppCustomerAiProfile;
use App\Models\WhatsAppFaq;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsAppAiSettingsController extends Controller
{
    /**
     * [AI] AI Knowledge Base, Memory & Settings View
     */
    public function index(): View
    {
        $faqs = WhatsAppFaq::orderBy('times_used', 'desc')->paginate(20);
        $corrections = WhatsAppAiCorrection::with('agent')->latest()->take(10)->get();
        $memoryProfilesCount = WhatsAppCustomerAiProfile::count();
        
        $config = DB::table('addon_settings')
            ->where('key_name', 'whatsapp_meta')
            ->where('settings_type', 'sms_config')
            ->first();

        $settings = $config ? json_decode($config->live_values, true) : [];

        $geminiApiKey = DB::table('business_settings')->where('type', 'gemini_api_key')->first()?->value ?? env('GEMINI_API_KEY', '');
        $geminiModel = DB::table('business_settings')->where('type', 'gemini_model')->first()?->value ?? env('GEMINI_MODEL', 'gemini-1.5-flash');

        return view('admin-views.whatsapp-crm.ai-settings', compact(
            'faqs',
            'corrections',
            'memoryProfilesCount',
            'settings',
            'geminiApiKey',
            'geminiModel'
        ));
    }

    /**
     * [AI] Save / Update FAQ in Knowledge Base
     */
    public function storeFaq(Request $request): RedirectResponse
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'nullable|string',
        ]);

        WhatsAppFaq::create([
            'question' => $request->question,
            'answer' => $request->answer,
            'category' => $request->category ?? 'general',
            'is_active' => true,
        ]);

        Toastr::success('New FAQ added to AI Knowledge Base successfully!');
        return back();
    }

    /**
     * [AI] Delete FAQ
     */
    public function deleteFaq(int $id): RedirectResponse
    {
        WhatsAppFaq::findOrFail($id)->delete();
        Toastr::success('FAQ deleted successfully.');
        return back();
    }

    /**
     * [AI] Save WhatsApp API & Gemini Settings
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'phone_number_id' => 'required|string',
            'token' => 'required|string',
            'waba_id' => 'nullable|string',
            'status' => 'required|in:0,1',
            'gemini_api_key' => 'nullable|string',
            'gemini_model' => 'nullable|string',
        ]);

        $liveValues = json_encode([
            'gateway' => 'whatsapp_meta',
            'mode' => 'live',
            'status' => (int)$request->status,
            'phone_number_id' => $request->phone_number_id,
            'token' => $request->token,
            'waba_id' => $request->waba_id ?? '',
            'template_name' => $request->template_name ?? 'victorious_otp_auth',
            'language_code' => $request->language_code ?? 'en',
        ]);

        DB::table('addon_settings')->updateOrInsert(
            ['key_name' => 'whatsapp_meta', 'settings_type' => 'sms_config'],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'live_values' => $liveValues,
                'test_values' => $liveValues,
                'is_active' => (int)$request->status,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if ($request->filled('gemini_api_key')) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => 'gemini_api_key'],
                ['value' => trim($request->gemini_api_key), 'updated_at' => now()]
            );
        }

        if ($request->filled('gemini_model')) {
            DB::table('business_settings')->updateOrInsert(
                ['type' => 'gemini_model'],
                ['value' => trim($request->gemini_model), 'updated_at' => now()]
            );
        }

        Toastr::success('WhatsApp Gateway & Gemini AI Model settings updated successfully!');
        return back();
    }
}
