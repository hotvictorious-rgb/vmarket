<?php

namespace App\Services;

use App\Utils\SMSModule;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCrmService
{
    protected string $apiUrl;
    protected string $accessToken;
    protected string $phoneNumberId;

    public function __construct()
    {
        $config = $this->getSettings();
        $this->phoneNumberId = $config['phone_number_id'] ?? env('WHATSAPP_PHONE_NUMBER_ID', '');
        $this->accessToken = $config['token'] ?? env('WHATSAPP_ACCESS_TOKEN', '');
        $this->apiUrl = "https://graph.facebook.com/v19.0/{$this->phoneNumberId}/messages";
    }

    /**
     * [AI] Send Free-Form Text Message (During 24-hr Service Window)
     */
    public function sendTextMessage(string $toPhone, string $text): array
    {
        return $this->dispatchPayload([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => SMSModule::formatNigerianPhone($toPhone),
            'type' => 'text',
            'text' => ['preview_url' => true, 'body' => $text],
        ]);
    }

    /**
     * [AI] Send Official Meta Pre-Approved Template Message
     */
    public function sendTemplateMessage(string $toPhone, string $templateName, string $languageCode = 'en', array $components = []): array
    {
        return $this->dispatchPayload([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => SMSModule::formatNigerianPhone($toPhone),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components,
            ],
        ]);
    }

    /**
     * [AI] Send Interactive Quick Reply Buttons
     */
    public function sendInteractiveButtons(string $toPhone, string $bodyText, array $buttons): array
    {
        $buttonPayload = [];
        foreach ($buttons as $btn) {
            $buttonPayload[] = [
                'type' => 'reply',
                'reply' => [
                    'id' => $btn['id'],
                    'title' => substr($btn['title'], 0, 20),
                ],
            ];
        }

        return $this->dispatchPayload([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => SMSModule::formatNigerianPhone($toPhone),
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $bodyText],
                'action' => ['buttons' => $buttonPayload],
            ],
        ]);
    }

    /**
     * [AI] Send Media Message (Image, Document, Audio)
     */
    public function sendMediaMessage(string $toPhone, string $mediaType, string $mediaUrl, ?string $caption = null): array
    {
        $mediaPayload = ['link' => $mediaUrl];
        if (!empty($caption) && in_array($mediaType, ['image', 'document', 'video'])) {
            $mediaPayload['caption'] = $caption;
        }

        return $this->dispatchPayload([
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => SMSModule::formatNigerianPhone($toPhone),
            'type' => $mediaType,
            $mediaType => $mediaPayload,
        ]);
    }

    /**
     * [AI] Mark Inbound Message as Read (Blue Ticks for Customer)
     */
    public function markMessageAsRead(string $metaMessageId): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(8)
                ->post($this->apiUrl, [
                    'messaging_product' => 'whatsapp',
                    'status' => 'read',
                    'message_id' => $metaMessageId,
                ]);

            return ['success' => $response->successful()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * [AI] Core HTTP Dispatcher to Meta Graph API
     */
    protected function dispatchPayload(array $payload): array
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning('[AI WhatsApp CRM] Missing WhatsApp API Credentials.');
            return ['success' => false, 'error' => 'Missing WhatsApp API configuration'];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(12)
                ->post($this->apiUrl, $payload);

            $resData = $response->json();

            if ($response->successful() && !empty($resData['messages'][0]['id'])) {
                return [
                    'success' => true,
                    'meta_message_id' => $resData['messages'][0]['id'],
                    'data' => $resData,
                ];
            }

            Log::error('[AI WhatsApp CRM Error]', ['status' => $response->status(), 'response' => $resData]);
            return ['success' => false, 'error' => $resData];
        } catch (Exception $e) {
            Log::error('[AI WhatsApp CRM Exception] ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function getSettings(): array
    {
        try {
            $config = DB::table('addon_settings')
                ->where('key_name', 'whatsapp_meta')
                ->where('settings_type', 'sms_config')
                ->first();

            return $config ? json_decode($config->live_values, true) : [];
        } catch (Exception $e) {
            return [];
        }
    }
}
