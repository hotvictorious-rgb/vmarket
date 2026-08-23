<?php

namespace App\Http\Controllers\RestAPI\v1;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppJob;
use App\Jobs\UpdateCustomerAiMemoryJob;
use App\Models\User;
use App\Models\WhatsAppBroadcastLog;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppAiService;
use App\Services\WhatsAppCrmService;
use App\Utils\SMSModule;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * [AI] Meta Webhook Verification Handshake (GET)
     */
    public function verify(Request $request)
    {
        $verifyToken = env('WHATSAPP_VERIFY_TOKEN', 'vmarket_webhook_secret_token');
        
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * [AI] Meta Webhook Event Ingestion (POST)
     */
    public function handle(Request $request, WhatsAppAiService $aiService, WhatsAppCrmService $crmService): JsonResponse
    {
        // 1. HMAC Signature Verification (if App Secret configured)
        $appSecret = env('WHATSAPP_APP_SECRET', '');
        if (!empty($appSecret)) {
            $signature = $request->header('X-Hub-Signature-256');
            if ($signature) {
                $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
                if (!hash_equals($expected, $signature)) {
                    Log::warning('[AI WhatsApp Webhook] Invalid HMAC Signature');
                    return response()->json(['status' => 'invalid_signature'], 403);
                }
            }
        }

        $payload = $request->all();

        // 2. Process Entries
        $entry = $payload['entry'][0]['changes'][0]['value'] ?? null;
        if (!$entry) {
            return response()->json(['status' => 'no_entry'], 200);
        }

        // 3. Handle Delivery Status Receipts (Sent, Delivered, Read, Failed)
        if (!empty($entry['statuses'][0])) {
            $statusData = $entry['statuses'][0];
            $metaMsgId = $statusData['id'] ?? null;
            $newStatus = $statusData['status'] ?? 'pending';

            if ($metaMsgId) {
                WhatsAppMessage::where('meta_message_id', $metaMsgId)->update([
                    'delivery_status' => $newStatus,
                ]);

                WhatsAppBroadcastLog::where('meta_message_id', $metaMsgId)->update([
                    'status' => $newStatus,
                ]);
            }

            return response()->json(['status' => 'status_updated'], 200);
        }

        // 4. Handle Inbound Customer Messages
        if (!empty($entry['messages'][0])) {
            $msgData = $entry['messages'][0];
            $rawPhone = $msgData['from'] ?? '';
            $metaMessageId = $msgData['id'] ?? null;
            $msgType = $msgData['type'] ?? 'text';

            if (empty($rawPhone)) {
                return response()->json(['status' => 'no_phone'], 200);
            }

            $formattedPhone = SMSModule::formatNigerianPhone($rawPhone);

            // [AI] Inbound WhatsApp DDoS Rate Limiter: Max 30 messages/min per phone
            $rateKey = 'wa_inbound_rate_' . $formattedPhone;
            $msgHits = (int)\Illuminate\Support\Facades\Cache::get($rateKey, 0);
            if ($msgHits >= 30) {
                Log::warning("[WhatsApp Rate Limit] Throttling messages from {$formattedPhone}");
                return response()->json(['status' => 'rate_limited'], 200);
            }
            \Illuminate\Support\Facades\Cache::put($rateKey, $msgHits + 1, now()->addMinute());

            // Extract message body / media
            $messageBody = '';
            $mediaUrl = null;

            if ($msgType === 'text') {
                $messageBody = $msgData['text']['body'] ?? '';
            } elseif ($msgType === 'interactive') {
                $messageBody = $msgData['interactive']['button_reply']['title'] ?? ($msgData['interactive']['list_reply']['title'] ?? '');
            } elseif ($msgType === 'image') {
                $messageBody = $msgData['image']['caption'] ?? '[Image]';
                $mediaUrl = $msgData['image']['id'] ?? null;
            } elseif ($msgType === 'audio') {
                $messageBody = '[Voice Note]';
                $mediaUrl = $msgData['audio']['id'] ?? null;
            } elseif ($msgType === 'location') {
                $loc = $msgData['location'] ?? [];
                $lat = $loc['latitude'] ?? '';
                $lng = $loc['longitude'] ?? '';
                $name = $loc['name'] ?? ($loc['address'] ?? 'Live GPS Location');
                $messageBody = "My delivery location is: {$name} (GPS Coordinates: {$lat}, {$lng})";
                \App\Services\EpisodicMemoryService::addMemoryPoint($formattedPhone, "Delivery Landmark: {$name}", 'location');
            } else {
                $messageBody = "[{$msgType} attachment]";
            }

            // Find or associate customer record
            $user = User::where('phone', $formattedPhone)
                ->orWhere('phone', '0' . substr($formattedPhone, 3))
                ->first();

            $customerName = $entry['contacts'][0]['profile']['name'] ?? ($user ? trim($user->f_name . ' ' . $user->l_name) : 'Customer');

            // Find or create conversation thread
            $conversation = WhatsAppConversation::firstOrCreate(
                ['phone' => $formattedPhone],
                [
                    'customer_id' => $user?->id,
                    'customer_name' => $customerName,
                    'status' => 'bot_handling',
                    'priority' => 'medium',
                    'last_message_at' => now(),
                ]
            );

            // Mark message as read on Meta
            if ($metaMessageId) {
                $crmService->markMessageAsRead($metaMessageId);
            }

            // Save inbound message in database
            $inboundMessage = WhatsAppMessage::create([
                'conversation_id' => $conversation->id,
                'meta_message_id' => $metaMessageId,
                'sender_type' => 'customer',
                'message_type' => $msgType === 'interactive' ? 'interactive_button' : $msgType,
                'message_body' => $messageBody,
                'media_url' => $mediaUrl,
                'delivery_status' => 'read',
            ]);

            $conversation->update([
                'last_message_at' => now(),
                'unread_agent_count' => $conversation->unread_agent_count + 1,
            ]);

            // 5. If conversation is in Bot Handling mode, trigger AI Assistant
            if ($conversation->status === 'bot_handling' && !empty($messageBody)) {
                $recentHistory = WhatsAppMessage::where('conversation_id', $conversation->id)
                    ->latest()
                    ->take(6)
                    ->get()
                    ->reverse()
                    ->toArray();

                $aiResult = $aiService->generateResponse($formattedPhone, $messageBody, $recentHistory);

                if (!empty($aiResult['image_url'])) {
                    // Dispatch Media Message with real product photo + caption
                    dispatch(new SendWhatsAppJob(
                        $formattedPhone,
                        'image',
                        [
                            'media_url' => $aiResult['image_url'],
                            'caption' => $aiResult['reply'] ?? '',
                        ],
                        $conversation->id
                    ));
                } elseif (!empty($aiResult['reply'])) {
                    // Dispatch Text response
                    dispatch(new SendWhatsAppJob(
                        $formattedPhone,
                        'text',
                        ['text' => $aiResult['reply']],
                        $conversation->id
                    ));
                }

                // If AI requested escalation, transfer to human queue
                if (!empty($aiResult['escalate']) && $aiResult['escalate'] === true) {
                    $conversation->update([
                        'status' => 'open',
                        'priority' => 'urgent_dispute',
                        'internal_notes' => 'AI Escalation: ' . ($aiResult['escalation_summary'] ?? 'Customer requires human support.'),
                    ]);
                }
            }

            // 6. Trigger Continuous Learning / Memory extraction in background
            dispatch(new UpdateCustomerAiMemoryJob($conversation->id, $formattedPhone))->delay(now()->addSeconds(30));

            return response()->json(['status' => 'message_processed'], 200);
        }

        return response()->json(['status' => 'ignored'], 200);
    }
}
