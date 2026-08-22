<?php

namespace App\Jobs;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppCrmService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        protected string $phone,
        protected string $type = 'text',
        protected array $payload = [],
        protected ?int $conversationId = null,
        protected ?int $agentId = null
    ) {}

    public function handle(WhatsAppCrmService $crmService): void
    {
        $result = ['success' => false];

        if ($this->type === 'text') {
            $result = $crmService->sendTextMessage($this->phone, $this->payload['text'] ?? '');
        } elseif ($this->type === 'template') {
            $result = $crmService->sendTemplateMessage(
                $this->phone,
                $this->payload['template_name'] ?? '',
                $this->payload['language_code'] ?? 'en',
                $this->payload['components'] ?? []
            );
        } elseif ($this->type === 'interactive') {
            $result = $crmService->sendInteractiveButtons(
                $this->phone,
                $this->payload['body'] ?? '',
                $this->payload['buttons'] ?? []
            );
        } elseif (in_array($this->type, ['image', 'document', 'audio', 'video'])) {
            $result = $crmService->sendMediaMessage(
                $this->phone,
                $this->type,
                $this->payload['media_url'] ?? '',
                $this->payload['caption'] ?? null
            );
        }

        // If part of an active conversation thread, log outbound message
        if ($this->conversationId) {
            WhatsAppMessage::create([
                'conversation_id' => $this->conversationId,
                'meta_message_id' => $result['meta_message_id'] ?? null,
                'sender_type' => $this->agentId ? 'agent' : 'system',
                'sender_id' => $this->agentId,
                'message_type' => $this->type === 'interactive' ? 'interactive_button' : $this->type,
                'message_body' => $this->payload['text'] ?? ($this->payload['body'] ?? null),
                'media_url' => $this->payload['media_url'] ?? null,
                'caption' => $this->payload['caption'] ?? null,
                'delivery_status' => $result['success'] ? 'sent' : 'failed',
                'error_reason' => $result['success'] ? null : json_encode($result['error'] ?? null),
            ]);

            WhatsAppConversation::where('id', $this->conversationId)->update([
                'last_message_at' => now(),
            ]);
        }
    }
}
